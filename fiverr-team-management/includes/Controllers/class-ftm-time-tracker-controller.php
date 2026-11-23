<?php
/**
 * FTM Time Tracker & Automated Screenshot Review Controller
 * Handles active project time tracking, periodic screenshot captures (every 10 minutes),
 * secure storage in uploads directory, leadership review galleries, and auto-sync with daily standup deliverables.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Time_Tracker_Controller extends FTM_REST_Controller {

    const CLEANUP_CRON_HOOK = 'ftm_purge_old_tracker_snapshots';
    const RETENTION_LAST_RUN_OPTION = 'ftm_snap_retention_last_run';

    public static function init_retention_cron() {
        add_action(self::CLEANUP_CRON_HOOK, [__CLASS__, 'purge_expired_snapshots']);
        add_action('init', function () {
            if (!wp_next_scheduled(self::CLEANUP_CRON_HOOK)) {
                wp_schedule_event(time() + 300, 'daily', self::CLEANUP_CRON_HOOK);
            }
        });
    }

    /**
     * Compute screenshot retention cutoff in site-local MySQL datetime.
     * - On/after the 15th of the current month: delete everything before the 1st of the current month
     *   (e.g. on Nov 15+, all Oct and earlier screenshots are deleted).
     * - Before the 15th: keep the previous month until the 15th arrives, purging anything older than
     *   the 1st of the previous month (e.g. on Nov 12, Oct is kept and Sep or earlier is deleted).
     */
    public static function get_screenshot_retention_cutoff($now_ts = null) {
        if ($now_ts === null) {
            $now_ts = function_exists('current_time') ? (int)current_time('timestamp') : time();
        }
        $day = (int)date('j', $now_ts);
        $current_month_start = date('Y-m-01 00:00:00', $now_ts);
        if ($day >= 15) {
            return $current_month_start;
        }
        return date('Y-m-01 00:00:00', strtotime($current_month_start . ' -1 month'));
    }

    /**
     * Ensure retention cleanup runs at most once per site-local day when cron hasn't fired yet.
     */
    public static function maybe_run_daily_retention() {
        $today = current_time('Y-m-d');
        if (get_option(self::RETENTION_LAST_RUN_OPTION, '') === $today) {
            return;
        }
        $res = self::purge_expired_snapshots();
        if (is_array($res) && !empty($res['cutoff'])) {
            update_option(self::RETENTION_LAST_RUN_OPTION, $today, false);
        }
    }

    /**
     * Delete expired recorded screenshots (DB rows + image files on disk) while preserving session time logs.
     */
    public static function purge_expired_snapshots($now_ts = null) {
        global $wpdb;
        $table_snapshots = $wpdb->prefix . 'ftm_time_tracker_snapshots';
        $table_sessions = $wpdb->prefix . 'ftm_time_tracker_sessions';

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_snapshots)) !== $table_snapshots) {
            return ['deleted' => 0, 'cutoff' => ''];
        }

        $cutoff = self::get_screenshot_retention_cutoff($now_ts);
        $expired = $wpdb->get_results($wpdb->prepare(
            "SELECT id, session_id, file_path, snapshot_url FROM $table_snapshots WHERE created_at < %s",
            $cutoff
        ));

        if (empty($expired)) {
            return ['deleted' => 0, 'cutoff' => $cutoff];
        }

        $upload_dir = wp_upload_dir();
        $tracker_basedir = wp_normalize_path(rtrim($upload_dir['basedir'], '/\\') . '/ftm_time_tracker');
        $affected_session_ids = [];
        $deleted_ids = [];

        foreach ($expired as $snap) {
            $deleted_ids[] = (int)$snap->id;
            if (!empty($snap->session_id)) {
                $affected_session_ids[(int)$snap->session_id] = true;
            }

            $file_deleted = false;
            if (!empty($snap->file_path)) {
                $norm_path = wp_normalize_path($snap->file_path);
                if (strpos($norm_path, $tracker_basedir) === 0 && is_file($norm_path)) {
                    @unlink($norm_path);
                    $file_deleted = true;
                }
            }

            if (!$file_deleted && !empty($snap->snapshot_url)) {
                $marker = '/ftm_time_tracker/';
                $pos = strpos($snap->snapshot_url, $marker);
                if ($pos !== false) {
                    $rel_part = ltrim(substr($snap->snapshot_url, $pos + strlen($marker)), '/\\');
                    $fallback_path = wp_normalize_path($tracker_basedir . '/' . $rel_part);
                    if (strpos($fallback_path, $tracker_basedir . '/') === 0 && is_file($fallback_path)) {
                        @unlink($fallback_path);
                    }
                }
            }
        }

        // Delete DB records in chunks and re-sync snapshots_count on affected sessions
        foreach (array_chunk($deleted_ids, 500) as $id_chunk) {
            $placeholders = implode(',', array_fill(0, count($id_chunk), '%d'));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_snapshots WHERE id IN ($placeholders)",
                $id_chunk
            ));
        }

        $session_ids = array_keys($affected_session_ids);
        foreach (array_chunk($session_ids, 500) as $sess_chunk) {
            $placeholders = implode(',', array_fill(0, count($sess_chunk), '%d'));
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_sessions s
                 SET s.snapshots_count = (
                     SELECT COUNT(*) FROM $table_snapshots sn WHERE sn.session_id = s.id
                 )
                 WHERE s.id IN ($placeholders)",
                $sess_chunk
            ));
        }

        // Prune now-empty YYYY/MM directories strictly older than the cutoff month
        if (is_dir($tracker_basedir)) {
            $cutoff_ym = substr($cutoff, 0, 7);
            foreach (glob($tracker_basedir . '/[0-9][0-9][0-9][0-9]/[0-9][0-9]', GLOB_ONLYDIR) ?: [] as $month_dir) {
                $norm_dir = wp_normalize_path($month_dir);
                $ym = str_replace('/', '-', substr($norm_dir, -7));
                if ($ym < $cutoff_ym) {
                    $entries = @scandir($norm_dir);
                    if (is_array($entries) && count($entries) <= 2) {
                        @rmdir($norm_dir);
                    }
                }
            }
        }

        return ['deleted' => count($deleted_ids), 'cutoff' => $cutoff];
    }

    public function register_routes() {
        self::ensure_tables();

        // Start time tracking session
        register_rest_route($this->namespace, '/time-tracker/start', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'start_session'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Get current user's active tracking session (for page reload restoration)
        register_rest_route($this->namespace, '/time-tracker/active', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_active_session'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Upload snapshot taken every 10 minutes
        register_rest_route($this->namespace, '/time-tracker/snapshot', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'upload_snapshot'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Stop tracking session & optionally log time to daily standup
        register_rest_route($this->namespace, '/time-tracker/stop', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'stop_session'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Submit manual extra time work report (after 6:20 PM)
        register_rest_route($this->namespace, '/time-tracker/extra-time-report', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'submit_extra_time_report'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Heartbeat to update live elapsed time for active session
        register_rest_route($this->namespace, '/time-tracker/heartbeat', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'heartbeat_session'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Get extra time work summary & statistics
        register_rest_route($this->namespace, '/time-tracker/extra-time-summary', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_extra_time_summary'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // List sessions (for leader, manager, and member)
        register_rest_route($this->namespace, '/time-tracker/sessions', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_sessions'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // List snapshots for a session or project gallery (Leaders and Managers only)
        register_rest_route($this->namespace, '/time-tracker/snapshots', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_snapshots'],
            'permission_callback' => [$this, 'check_leader_or_admin_permission']
        ]);

        // Update notes on a captured snapshot (Managers only)
        register_rest_route($this->namespace, '/time-tracker/snapshots/(?P<id>\d+)/update', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_snapshot_notes'],
            'permission_callback' => [$this, 'check_manager_permission']
        ]);

        // Delete a captured snapshot (Managers only)
        register_rest_route($this->namespace, '/time-tracker/snapshots/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_snapshot'],
            'permission_callback' => [$this, 'check_manager_permission']
        ]);

        // Aggregate statistics and live active tracking sessions
        register_rest_route($this->namespace, '/time-tracker/stats', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_tracker_stats'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Date-wise time tracking summary grouped by date and specialist
        register_rest_route($this->namespace, '/time-tracker/daily-summary', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_daily_summary'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);
    }

    public static function ensure_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_sessions = $wpdb->prefix . 'ftm_time_tracker_sessions';
        $sql_sessions = "CREATE TABLE $table_sessions (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            project_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            order_id VARCHAR(100) NOT NULL DEFAULT '',
            client_name VARCHAR(150) NOT NULL DEFAULT '',
            profile_name VARCHAR(100) NOT NULL DEFAULT '',
            team_name VARCHAR(50) NOT NULL DEFAULT '',
            session_type VARCHAR(30) NOT NULL DEFAULT 'regular',
            start_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            end_time DATETIME NULL,
            last_heartbeat_at DATETIME NULL,
            total_seconds INT(11) NOT NULL DEFAULT 0,
            snapshots_count INT(11) NOT NULL DEFAULT 0,
            total_mouse_clicks INT(11) NOT NULL DEFAULT 0,
            total_keyboard_clicks INT(11) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            notes TEXT NULL,
            logged_to_report TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_proj (user_id, project_id),
            KEY session_type (session_type),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_sessions);

        $table_snapshots = $wpdb->prefix . 'ftm_time_tracker_snapshots';
        $sql_snapshots = "CREATE TABLE $table_snapshots (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            project_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            order_id VARCHAR(100) NOT NULL DEFAULT '',
            session_type VARCHAR(30) NOT NULL DEFAULT 'regular',
            snapshot_url VARCHAR(500) NOT NULL DEFAULT '',
            file_path VARCHAR(500) NOT NULL DEFAULT '',
            elapsed_seconds INT(11) NOT NULL DEFAULT 0,
            mouse_clicks INT(11) NOT NULL DEFAULT 0,
            keyboard_clicks INT(11) NOT NULL DEFAULT 0,
            notes VARCHAR(255) NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY session_type (session_type),
            KEY user_proj (user_id, project_id)
        ) $charset_collate;";
        dbDelta($sql_snapshots);

        // Safe column synchronization
        $s_cols = $wpdb->get_col("DESCRIBE $table_sessions");
        if ($s_cols && !in_array('session_type', $s_cols, true)) {
            $wpdb->query("ALTER TABLE $table_sessions ADD COLUMN session_type VARCHAR(30) NOT NULL DEFAULT 'regular' AFTER team_name");
        }
        if ($s_cols && !in_array('last_heartbeat_at', $s_cols, true)) {
            $wpdb->query("ALTER TABLE $table_sessions ADD COLUMN last_heartbeat_at DATETIME NULL AFTER end_time");
        }
        if ($s_cols && !in_array('total_mouse_clicks', $s_cols, true)) {
            $wpdb->query("ALTER TABLE $table_sessions ADD COLUMN total_mouse_clicks INT(11) NOT NULL DEFAULT 0 AFTER snapshots_count");
        }
        if ($s_cols && !in_array('total_keyboard_clicks', $s_cols, true)) {
            $wpdb->query("ALTER TABLE $table_sessions ADD COLUMN total_keyboard_clicks INT(11) NOT NULL DEFAULT 0 AFTER total_mouse_clicks");
        }
        $sn_cols = $wpdb->get_col("DESCRIBE $table_snapshots");
        if ($sn_cols && !in_array('session_type', $sn_cols, true)) {
            $wpdb->query("ALTER TABLE $table_snapshots ADD COLUMN session_type VARCHAR(30) NOT NULL DEFAULT 'regular' AFTER order_id");
        }
        if ($sn_cols && !in_array('mouse_clicks', $sn_cols, true)) {
            $wpdb->query("ALTER TABLE $table_snapshots ADD COLUMN mouse_clicks INT(11) NOT NULL DEFAULT 0 AFTER elapsed_seconds");
        }
        if ($sn_cols && !in_array('keyboard_clicks', $sn_cols, true)) {
            $wpdb->query("ALTER TABLE $table_snapshots ADD COLUMN keyboard_clicks INT(11) NOT NULL DEFAULT 0 AFTER mouse_clicks");
        }
        $daily_items = $wpdb->prefix . 'ftm_daily_report_items';
        $d_cols = $wpdb->get_col("DESCRIBE $daily_items");
        if ($d_cols && !in_array('is_extra_time', $d_cols, true)) {
            $wpdb->query("ALTER TABLE $daily_items ADD COLUMN is_extra_time TINYINT(1) NOT NULL DEFAULT 0 AFTER hours_spent");
        }
        if ($d_cols && !in_array('extra_time_hours', $d_cols, true)) {
            $wpdb->query("ALTER TABLE $daily_items ADD COLUMN extra_time_hours DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER is_extra_time");
        }
        if ($d_cols && !in_array('extra_time_reason', $d_cols, true)) {
            $wpdb->query("ALTER TABLE $daily_items ADD COLUMN extra_time_reason VARCHAR(255) NULL DEFAULT '' AFTER extra_time_hours");
        }
    }

    /**
     * Guarantee last_heartbeat_at column exists once per request lifecycle.
     */
    private function ensure_heartbeat_column() {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        global $wpdb;
        $table_sessions = $wpdb->prefix . 'ftm_time_tracker_sessions';
        $cols = $wpdb->get_col("DESCRIBE $table_sessions");
        if ($cols && !in_array('last_heartbeat_at', $cols, true)) {
            $wpdb->query("ALTER TABLE $table_sessions ADD COLUMN last_heartbeat_at DATETIME NULL AFTER end_time");
        }
    }

    /**
     * Auto-close abandoned active sessions whose last heartbeat/activity exceeded timeout (default 30 min).
     * Prevents abandoned browser tabs from accruing fake multi-day active hours.
     */
    private function expire_stale_active_sessions($timeout_seconds = 1800) {
        global $wpdb;
        $this->ensure_heartbeat_column();
        $table_sessions = $wpdb->prefix . 'ftm_time_tracker_sessions';
        $now_mysql = current_time('mysql');
        $cutoff_mysql = date('Y-m-d H:i:s', current_time('timestamp') - max(300, (int)$timeout_seconds));

        $stale = $wpdb->get_results($wpdb->prepare(
            "SELECT id, start_time, last_heartbeat_at, total_seconds, notes
             FROM $table_sessions
             WHERE status = 'active'
               AND COALESCE(last_heartbeat_at, start_time) < %s",
            $cutoff_mysql
        ));

        foreach ($stale ?: [] as $row) {
            $last_act = !empty($row->last_heartbeat_at) ? $row->last_heartbeat_at : $row->start_time;
            $recorded_sec = max(0, (int)$row->total_seconds);
            // If no heartbeats arrived at all after start, do not invent multi-hour durations
            $close_end = ($recorded_sec > 0 && !empty($row->last_heartbeat_at)) ? $last_act : $row->start_time;
            $note = trim((string)$row->notes);
            if ($note === '') {
                $note = 'Auto-closed after browser inactivity timeout';
            }
            $wpdb->update($table_sessions, [
                'status' => 'completed',
                'end_time' => $close_end ?: $now_mysql,
                'total_seconds' => $recorded_sec,
                'notes' => $note
            ], ['id' => (int)$row->id]);
        }
    }

    /**
     * Compute verified duration in seconds for a single session row without zombie inflation.
     */
    private function compute_session_seconds($row, $current_ts = null) {
        if ($current_ts === null) {
            $current_ts = current_time('timestamp');
        }
        $db_sec = max(0, (int)$row->total_seconds);
        if ($row->status !== 'active') {
            return $db_sec;
        }
        $start_ts = strtotime($row->start_time) ?: $current_ts;
        $last_act_ts = !empty($row->last_heartbeat_at) ? (strtotime($row->last_heartbeat_at) ?: $start_ts) : $start_ts;
        // Only count real-time elapsed while session has fresh activity (within 30m)
        if (($current_ts - $last_act_ts) <= 1800) {
            return max($db_sec, max(0, $current_ts - $start_ts));
        }
        return $db_sec;
    }

    private function format_hms_compact($seconds) {
        $sec = max(0, (int)$seconds);
        if ($sec > 0 && $sec < 60) {
            return sprintf('0m %02ds', $sec);
        }
        $h = floor($sec / 3600);
        $m = floor(($sec % 3600) / 60);
        return sprintf('%dh %02dm', $h, $m);
    }

    /**
     * Start a new tracking session
     * POST /time-tracker/start
     */
    public function start_session($request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $data = $request->get_json_params() ?: $request->get_params();

        $project_id = isset($data['project_id']) ? (int)$data['project_id'] : 0;
        $order_id = sanitize_text_field($data['order_id'] ?? '');
        $client_name = sanitize_text_field($data['client_name'] ?? '');
        $profile_name = sanitize_text_field($data['profile_name'] ?? '');
        $team_name = '';

        if ($project_id > 0) {
            $p_table = $wpdb->prefix . 'ftm_projects';
            $p = $wpdb->get_row($wpdb->prepare("SELECT * FROM $p_table WHERE id = %d", $project_id));
            if ($p) {
                $order_id = $p->order_id ?: $order_id;
                $client_name = $p->client_name ?: $client_name;
                $profile_name = $p->profile_name ?: $profile_name;
            }
        }

        // Attribution contract: session belongs to specialist's own squad for direct leadership oversight
        $member_team = FTM_Permissions::get_user_team($user_id);
        $team_name = !empty($member_team) ? $member_team : (($p && !empty($p->team_name)) ? $p->team_name : 'WpCraft');

        $table_sessions = $wpdb->prefix . 'ftm_time_tracker_sessions';

        $this->ensure_heartbeat_column();

        // Close any previously hanging active sessions for this user safely without multi-day inflation
        $hanging = $wpdb->get_results($wpdb->prepare(
            "SELECT id, start_time, last_heartbeat_at, total_seconds FROM $table_sessions WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
        foreach ($hanging ?: [] as $h_row) {
            $h_sec = max(0, (int)$h_row->total_seconds);
            $h_end = ($h_sec > 0 && !empty($h_row->last_heartbeat_at)) ? $h_row->last_heartbeat_at : current_time('mysql');
            $wpdb->update($table_sessions, [
                'status' => 'completed',
                'end_time' => $h_end,
                'total_seconds' => $h_sec
            ], ['id' => (int)$h_row->id]);
        }

        $session_type = sanitize_text_field($data['session_type'] ?? 'regular');
        if (!in_array($session_type, ['regular', 'extra_time'], true)) {
            $session_type = 'regular';
        }

        $now_mysql = current_time('mysql');
        $inserted = $wpdb->insert($table_sessions, [
            'user_id' => $user_id,
            'project_id' => $project_id,
            'order_id' => $order_id,
            'client_name' => $client_name,
            'profile_name' => $profile_name,
            'team_name' => $team_name,
            'session_type' => $session_type,
            'start_time' => $now_mysql,
            'last_heartbeat_at' => $now_mysql,
            'total_seconds' => 0,
            'snapshots_count' => 0,
            'status' => 'active',
            'created_at' => $now_mysql
        ]);

        if (!$inserted) {
            return $this->format_error(__('Failed to initialize tracking session.', 'fiverr-team-management'), 500);
        }

        $session_id = $wpdb->insert_id;

        return $this->format_success([
            'session_id' => $session_id,
            'user_id' => $user_id,
            'project_id' => $project_id,
            'order_id' => $order_id,
            'client_name' => $client_name,
            'profile_name' => $profile_name,
            'session_type' => $session_type,
            'start_time' => $now_mysql
        ], __('Time tracking session started successfully.', 'fiverr-team-management'));
    }

    /**
     * GET /time-tracker/active
     * Returns current user's active tracking session if running
     */
    public function get_active_session($request) {
        global $wpdb;
        $this->expire_stale_active_sessions();
        $user_id = get_current_user_id();
        $table_sessions = $wpdb->prefix . 'ftm_time_tracker_sessions';

        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_sessions WHERE user_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1",
            $user_id
        ));

        if (!$session) {
            return $this->format_success(null);
        }

        $now = current_time('timestamp');
        $start_ts = strtotime($session->start_time) ?: $now;
        $elapsed = $this->compute_session_seconds($session, $now);

        return $this->format_success([
            'session_id' => (int)$session->id,
            'project_id' => (int)$session->project_id,
            'order_id' => $session->order_id,
            'client_name' => $session->client_name,
            'profile_name' => $session->profile_name,
            'session_type' => $session->session_type ?: 'regular',
            'start_time' => $session->start_time,
            'start_timestamp' => $start_ts * 1000,
            'elapsed_seconds' => $elapsed,
            'snapshots_count' => (int)$session->snapshots_count,
            'total_mouse_clicks' => (int)($session->total_mouse_clicks ?? 0),
            'total_keyboard_clicks' => (int)($session->total_keyboard_clicks ?? 0),
            'status' => $session->status
        ]);
    }

    /**
     * Upload an automated screenshot (every 10 minutes)
     * POST /time-tracker/snapshot
     */
    public function upload_snapshot($request) {
        global $wpdb;
        self::maybe_run_daily_retention();
        $this->ensure_heartbeat_column();
        $user_id = get_current_user_id();
        $data = $request->get_json_params() ?: $request->get_params();

        $session_id = isset($data['session_id']) ? (int)$data['session_id'] : 0;
        $project_id = isset($data['project_id']) ? (int)$data['project_id'] : 0;
        $elapsed_seconds = isset($data['elapsed_seconds']) ? (int)$data['elapsed_seconds'] : (isset($data['total_seconds']) ? (int)$data['total_seconds'] : 0);
        $order_id = sanitize_text_field($data['order_id'] ?? '');
        $image_base64 = !empty($data['image']) ? $data['image'] : (!empty($data['image_base64']) ? $data['image_base64'] : '');
        $session_type = sanitize_text_field($data['session_type'] ?? '');
        $mouse_clicks = isset($data['mouse_clicks']) ? max(0, (int)$data['mouse_clicks']) : 0;
        $keyboard_clicks = isset($data['keyboard_clicks']) ? max(0, (int)$data['keyboard_clicks']) : (isset($data['key_clicks']) ? max(0, (int)$data['key_clicks']) : 0);
        $total_mouse_clicks = isset($data['total_mouse_clicks']) ? max(0, (int)$data['total_mouse_clicks']) : 0;
        $total_keyboard_clicks = isset($data['total_keyboard_clicks']) ? max(0, (int)$data['total_keyboard_clicks']) : 0;

        if (empty($session_id)) {
            return $this->format_error(__('Session ID is required.', 'fiverr-team-management'), 400);
        }

        if (empty($image_base64)) {
            return $this->format_error(__('Screenshot image payload is missing.', 'fiverr-team-management'), 400);
        }

        // Clean base64 string
        if (strpos($image_base64, 'data:image') === 0) {
            $image_base64 = preg_replace('#^data:image/\w+;base64,#i', '', $image_base64);
        }
        $image_binary = base64_decode($image_base64);

        if (!$image_binary) {
            return $this->format_error(__('Invalid image encoding.', 'fiverr-team-management'), 400);
        }

        $upload_dir = wp_upload_dir();
        $tracker_sub = '/ftm_time_tracker/' . date('Y/m');
        $target_dir = $upload_dir['basedir'] . $tracker_sub;
        $target_url_base = $upload_dir['baseurl'] . $tracker_sub;

        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }

        $filename = sprintf('snap_u%d_p%d_s%d_%s_%s.jpg', $user_id, $project_id, $session_id, date('Ymd_His'), wp_generate_password(6, false));
        $file_path = $target_dir . '/' . $filename;
        $file_url = $target_url_base . '/' . $filename;

        $written = file_put_contents($file_path, $image_binary);
        if ($written === false) {
            return $this->format_error(__('Failed to save screenshot file.', 'fiverr-team-management'), 500);
        }

        $table_sessions = $wpdb->prefix . 'ftm_time_tracker_sessions';
        $sess = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_sessions WHERE id = %d", $session_id));
        if (empty($session_type) && $sess) {
            $session_type = $sess->session_type ?: 'regular';
        }

        $now_mysql = current_time('mysql');
        $table_snapshots = $wpdb->prefix . 'ftm_time_tracker_snapshots';
        $wpdb->insert($table_snapshots, [
            'session_id' => $session_id,
            'user_id' => $user_id,
            'project_id' => $project_id,
            'order_id' => $order_id,
            'snapshot_url' => esc_url_raw($file_url),
            'file_path' => sanitize_text_field($file_path),
            'elapsed_seconds' => $elapsed_seconds,
            'mouse_clicks' => $mouse_clicks,
            'keyboard_clicks' => $keyboard_clicks,
            'session_type' => $session_type ?: 'regular',
            'notes' => sanitize_text_field(!empty($data['notes']) ? $data['notes'] : ($session_type === 'extra_time' ? 'Automated overtime activity capture' : 'Automated shift activity capture')),
            'created_at' => $now_mysql
        ]);
        $snap_id = $wpdb->insert_id;

        // Update session without regressing elapsed duration and track cumulative mouse/keyboard clicks
        $final_total_mouse = max((int)($sess->total_mouse_clicks ?? 0), $total_mouse_clicks);
        if ($total_mouse_clicks <= 0 && $mouse_clicks > 0) {
            $final_total_mouse = (int)($sess->total_mouse_clicks ?? 0) + $mouse_clicks;
        }

        $final_total_keyboard = max((int)($sess->total_keyboard_clicks ?? 0), $total_keyboard_clicks);
        if ($total_keyboard_clicks <= 0 && $keyboard_clicks > 0) {
            $final_total_keyboard = (int)($sess->total_keyboard_clicks ?? 0) + $keyboard_clicks;
        }

        $wpdb->query($wpdb->prepare(
            "UPDATE $table_sessions SET 
                total_seconds = GREATEST(total_seconds, %d), 
                last_heartbeat_at = %s, 
                snapshots_count = snapshots_count + 1,
                total_mouse_clicks = %d,
                total_keyboard_clicks = %d
            WHERE id = %d",
            $elapsed_seconds, $now_mysql, $final_total_mouse, $final_total_keyboard, $session_id
        ));

        // Get session and project for notification
        $user_obj = get_userdata($user_id);
        $user_name = $user_obj ? $user_obj->display_name : 'Specialist';
        $client_label = ($sess && !empty($sess->client_name)) ? $sess->client_name : sanitize_text_field($data['client_name'] ?? 'Client');

        // Notify leadership
        if (class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'broadcast_to_leadership')) {
            try {
                $mins_elapsed = round($elapsed_seconds / 60);
                $type_tag = ($session_type === 'extra_time') ? '⚡ [EXTRA TIME] ' : '📸 ';
                FTM_Notifications_Engine::broadcast_to_leadership([
                    'title' => "{$type_tag}Work Screen Activity Captured",
                    'message' => "{$user_name} reached {$mins_elapsed}m of active work on #{$order_id} ({$client_label}) · 🖱️ {$mouse_clicks} clicks · ⌨️ {$keyboard_clicks} keys. Screenshot saved for review.",
                    'type' => 'time_tracker_snapshot',
                    'target_id' => $session_id,
                    'team_name' => $sess ? $sess->team_name : ''
                ]);
            } catch (\Throwable $e) {}
        }

        return $this->format_success([
            'snapshot_id' => $snap_id,
            'session_id' => $session_id,
            'snapshot_url' => $file_url,
            'elapsed_seconds' => $elapsed_seconds,
            'mouse_clicks' => $mouse_clicks,
            'keyboard_clicks' => $keyboard_clicks,
            'session_type' => $session_type,
            'captured_at' => $now_mysql
        ], __('Screenshot captured and stored for leadership review.', 'fiverr-team-management'));
    }

    /**
     * Heartbeat to keep live active session elapsed seconds fresh in DB
     * POST /time-tracker/heartbeat
     */
    public function heartbeat_session($request) {
        global $wpdb;
        $this->ensure_heartbeat_column();
        $data = $request->get_json_params() ?: $request->get_params();

        $session_id = isset($data['session_id']) ? (int)$data['session_id'] : 0;
        $elapsed_seconds = isset($data['elapsed_seconds']) ? (int)$data['elapsed_seconds'] : (isset($data['total_seconds']) ? (int)$data['total_seconds'] : 0);

        if (!$session_id) {
            return $this->format_error(__('Session ID is required.', 'fiverr-team-management'), 400);
        }

        $table_sessions = $wpdb->prefix . 'ftm_time_tracker_sessions';
        $sess = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_sessions WHERE id = %d", $session_id));
        if (!$sess || $sess->status !== 'active') {
            return $this->format_success(['active' => false]);
        }

        $now_ts = current_time('timestamp');
        $start_ts = strtotime($sess->start_time) ?: $now_ts;
        if ($elapsed_seconds <= 0) {
            $elapsed_seconds = max(0, $now_ts - $start_ts);
        }

        // Prevent stale sleep/hibernation wakeups from adding hours to total_seconds
        $last_act_ts = !empty($sess->last_heartbeat_at) ? (strtotime($sess->last_heartbeat_at) ?: $start_ts) : $start_ts;
        $gap = max(0, $now_ts - $last_act_ts);
        $db_sec = (int)$sess->total_seconds;
        $final_seconds = ($gap <= 1800) ? max($elapsed_seconds, $db_sec) : ($db_sec + 30);

        $hb_update = [
            'total_seconds' => $final_seconds,
            'last_heartbeat_at' => current_time('mysql')
        ];
        if (isset($data['total_mouse_clicks'])) {
            $hb_update['total_mouse_clicks'] = max((int)$sess->total_mouse_clicks, (int)$data['total_mouse_clicks']);
        }
        if (isset($data['total_keyboard_clicks'])) {
            $hb_update['total_keyboard_clicks'] = max((int)$sess->total_keyboard_clicks, (int)$data['total_keyboard_clicks']);
        }

        $wpdb->update($table_sessions, $hb_update, ['id' => $session_id]);

        return $this->format_success([
            'session_id' => $session_id,
            'total_seconds' => $final_seconds
        ]);
    }

    /**
     * Stop tracking session & optionally sync to daily report deliverables
     * POST /time-tracker/stop
     */
    public function stop_session($request) {
        global $wpdb;
        $this->ensure_heartbeat_column();
        $user_id = get_current_user_id();
        $data = $request->get_json_params() ?: $request->get_params();

        $session_id = isset($data['session_id']) ? (int)$data['session_id'] : 0;
        $total_seconds = isset($data['total_seconds']) ? (int)$data['total_seconds'] : (isset($data['elapsed_seconds']) ? (int)$data['elapsed_seconds'] : 0);
        $log_to_daily_report = !empty($data['log_to_daily_report']);
        $tasks_note = sanitize_textarea_field($data['tasks_completed'] ?? '');
        $extra_time_reason = sanitize_text_field($data['extra_time_reason'] ?? '');

        if (empty($session_id)) {
            return $this->format_error(__('Session ID is required.', 'fiverr-team-management'), 400);
        }

        $table_sessions = $wpdb->prefix . 'ftm_time_tracker_sessions';
        $sess = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_sessions WHERE id = %d", $session_id));

        if (!$sess) {
            return $this->format_error(__('Tracking session not found.', 'fiverr-team-management'), 404);
        }

        // Compute verified duration without allowing multi-day zombie tab inflation
        $now_ts = current_time('timestamp');
        $start_ts = strtotime($sess->start_time) ?: $now_ts;
        $last_act_ts = !empty($sess->last_heartbeat_at) ? (strtotime($sess->last_heartbeat_at) ?: $start_ts) : $start_ts;
        $db_sec = max(0, (int)$sess->total_seconds);
        $gap_since_hb = max(0, $now_ts - $last_act_ts);

        if ($gap_since_hb <= 1800) {
            $wall_clock_seconds = max(0, $now_ts - $start_ts);
            $final_seconds = max($total_seconds, $db_sec, $wall_clock_seconds);
        } else {
            // Session was abandoned without heartbeats — keep only recorded active seconds
            $final_seconds = max($db_sec, ($db_sec > 0 ? min($total_seconds, $db_sec + 300) : 0));
        }

        $now_mysql = current_time('mysql');
        $stop_update = [
            'status' => 'completed',
            'end_time' => $now_mysql,
            'last_heartbeat_at' => $now_mysql,
            'total_seconds' => $final_seconds,
            'logged_to_report' => $log_to_daily_report ? 1 : 0
        ];
        if (isset($data['total_mouse_clicks'])) {
            $stop_update['total_mouse_clicks'] = max((int)$sess->total_mouse_clicks, (int)$data['total_mouse_clicks']);
        }
        if (isset($data['total_keyboard_clicks'])) {
            $stop_update['total_keyboard_clicks'] = max((int)$sess->total_keyboard_clicks, (int)$data['total_keyboard_clicks']);
        }
        $wpdb->update($table_sessions, $stop_update, ['id' => $session_id]);

        $hours_decimal = round($final_seconds / 3600, 2);
        $h_display = floor($final_seconds / 3600);
        $m_display = floor(($final_seconds % 3600) / 60);
        $time_formatted = sprintf('%dh %02dm', $h_display, $m_display);
        $is_extra_time = ($sess->session_type === 'extra_time');

        // Auto-log to Daily Report deliverables if requested
        if ($log_to_daily_report && $hours_decimal > 0) {
            $this->sync_session_to_daily_report($sess, $hours_decimal, $tasks_note, $is_extra_time, $extra_time_reason);
        }

        // Notify Leadership
        $user_obj = get_userdata($user_id);
        $user_name = $user_obj ? $user_obj->display_name : 'Specialist';
        if (class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'broadcast_to_leadership')) {
            try {
                $type_tag = $is_extra_time ? '⚡ [EXTRA TIME] ' : '⏱ ';
                FTM_Notifications_Engine::broadcast_to_leadership([
                    'title' => "{$type_tag}Tracked Work Session Completed",
                    'message' => "{$user_name} logged {$time_formatted} on #{$sess->order_id} ({$sess->client_name}) with {$sess->snapshots_count} screenshot(s) ready for review." . ($is_extra_time && $extra_time_reason ? " Reason: {$extra_time_reason}" : ""),
                    'type' => 'time_tracker_session',
                    'target_id' => $session_id,
                    'team_name' => $sess->team_name
                ]);
            } catch (\Throwable $e) {}
        }

        return $this->format_success([
            'session_id' => $session_id,
            'session_type' => $sess->session_type ?: 'regular',
            'is_extra_time' => $is_extra_time,
            'total_seconds' => $final_seconds,
            'time_formatted' => $time_formatted,
            'hours_decimal' => $hours_decimal,
            'snapshots_count' => (int)$sess->snapshots_count,
            'logged_to_report' => $log_to_daily_report
        ], __('Work session completed and logged successfully.', 'fiverr-team-management'));
    }

    /**
     * Helper to sync tracked session hours to today's daily report
     */
    private function sync_session_to_daily_report($sess, $hours_decimal, $tasks_note = '', $is_extra_time = false, $extra_time_reason = '') {
        global $wpdb;
        $user_id = (int)$sess->user_id;
        $project_id = (int)$sess->project_id;
        $today = current_time('Y-m-d');

        $reports_table = $wpdb->prefix . 'ftm_daily_reports';
        $items_table = $wpdb->prefix . 'ftm_daily_report_items';

        $report = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status FROM $reports_table WHERE user_id = %d AND report_date = %s",
            $user_id, $today
        ));

        if (!$report) {
            $wpdb->insert($reports_table, [
                'user_id' => $user_id,
                'team_name' => $sess->team_name,
                'report_type' => 'work',
                'report_date' => $today,
                'status' => 'submitted',
                'submission_time' => current_time('H:i:s'),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]);
            $report_id = $wpdb->insert_id;
        } else {
            $report_id = (int)$report->id;
            if ($report->status === 'draft') {
                $wpdb->update($reports_table, [
                    'status' => 'submitted',
                    'submission_time' => current_time('H:i:s')
                ], ['id' => $report_id]);
            }
        }

        // Find existing item for this project
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $items_table WHERE report_id = %d AND (project_id = %d AND project_id > 0 OR order_id = %s)",
            $report_id, $project_id, $sess->order_id
        ));

        $clean_note = !empty($tasks_note) ? $tasks_note : ('Tracked ' . $hours_decimal . 'h on ' . ($sess->client_name ?: $sess->order_id));
        if ($is_extra_time) {
            $clean_note = '[⚡ Extra Time after 6:20 PM]: ' . $clean_note;
        }

        if ($item) {
            $new_hours = round((float)$item->hours_spent + $hours_decimal, 2);
            $note_append = !empty($item->tasks_completed) ? ($item->tasks_completed . "\n" . $clean_note) : $clean_note;
            $update_data = [
                'hours_spent' => $new_hours,
                'tasks_completed' => sanitize_textarea_field($note_append)
            ];
            if ($is_extra_time) {
                $update_data['is_extra_time'] = 1;
                $update_data['extra_time_hours'] = round((float)($item->extra_time_hours ?? 0) + $hours_decimal, 2);
                if (!empty($extra_time_reason)) {
                    $update_data['extra_time_reason'] = sanitize_text_field($extra_time_reason);
                }
            }
            $wpdb->update($items_table, $update_data, ['id' => $item->id]);
        } else {
            $wpdb->insert($items_table, [
                'report_id' => $report_id,
                'project_id' => $project_id,
                'order_id' => $sess->order_id,
                'client_name' => $sess->client_name,
                'tasks_completed' => sanitize_textarea_field($clean_note),
                'work_in_progress' => '',
                'next_day_plan' => '',
                'hours_spent' => $hours_decimal,
                'progress_percent' => 70,
                'is_extra_time' => $is_extra_time ? 1 : 0,
                'extra_time_hours' => $is_extra_time ? $hours_decimal : 0.00,
                'extra_time_reason' => $is_extra_time ? sanitize_text_field($extra_time_reason) : '',
                'created_at' => current_time('mysql')
            ]);
        }
    }

    /**
     * Get sessions list
     * GET /time-tracker/sessions
     */
    /**
     * Build shared role/team/user/date scope WHERE clauses for sessions queries.
     */
    private function append_session_scope_filters($request, &$where, &$params) {
        $current_user_id = get_current_user_id();
        $is_admin = FTM_Permissions::is_super_admin($current_user_id) || FTM_Permissions::is_executive($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id);
        $user_team = FTM_Permissions::get_user_team($current_user_id);

        $user_id = (int)$request->get_param('user_id');
        $team = sanitize_text_field($request->get_param('team') ?: 'all');
        $project_id = (int)$request->get_param('project_id');
        $session_type = sanitize_text_field($request->get_param('session_type') ?: 'all');
        $date = sanitize_text_field($request->get_param('date') ?: '');
        $date_from = sanitize_text_field($request->get_param('date_from') ?: '');
        $date_to = sanitize_text_field($request->get_param('date_to') ?: '');
        $search = sanitize_text_field($request->get_param('search') ?: '');

        if (!$is_admin && !$is_leader) {
            // General specialist / member: strictly their own sessions only
            $where[] = "s.user_id = %d";
            $params[] = $current_user_id;
        } elseif ($is_leader && !$is_admin) {
            // Team Leader / Co-Leader: strictly scoped to their own squad members
            $where[] = "(s.team_name = %s OR s.user_id = %d)";
            $params[] = $user_team;
            $params[] = $current_user_id;

            // Strict privacy hierarchy: prevent leader from querying members of other teams
            if ($user_id > 0) {
                $target_team = FTM_Permissions::get_user_team($user_id);
                if (strcasecmp($target_team, $user_team) !== 0 && $user_id !== $current_user_id) {
                    $where[] = "1=0"; // Block cross-team inspection
                } else {
                    $where[] = "s.user_id = %d";
                    $params[] = $user_id;
                }
            }
        } else {
            // Super Admin / Executive: can filter by any team or user
            if ($team !== 'all' && !empty($team)) {
                $where[] = "s.team_name = %s";
                $params[] = $team;
            }
            if ($user_id > 0) {
                $where[] = "s.user_id = %d";
                $params[] = $user_id;
            }
        }

        if ($project_id > 0) {
            $where[] = "s.project_id = %d";
            $params[] = $project_id;
        }

        if ($session_type !== 'all') {
            $where[] = "s.session_type = %s";
            $params[] = $session_type;
        }

        if (!empty($date)) {
            $where[] = "DATE(s.start_time) = %s";
            $params[] = $date;
        } else {
            if (!empty($date_from)) {
                $where[] = "DATE(s.start_time) >= %s";
                $params[] = $date_from;
            }
            if (!empty($date_to)) {
                $where[] = "DATE(s.start_time) <= %s";
                $params[] = $date_to;
            }
        }

        if (!empty($search)) {
            global $wpdb;
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = "(s.order_id LIKE %s OR s.client_name LIKE %s OR u.display_name LIKE %s)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
    }

    public function get_sessions($request) {
        global $wpdb;
        $this->expire_stale_active_sessions();

        $table_sessions = $wpdb->prefix . 'ftm_time_tracker_sessions';
        $table_snapshots = $wpdb->prefix . 'ftm_time_tracker_snapshots';

        $status = sanitize_text_field($request->get_param('status') ?: 'all');
        $limit = max(1, min(300, (int)($request->get_param('limit') ?: 100)));

        $where = ["1=1"];
        $params = [];
        $this->append_session_scope_filters($request, $where, $params);

        if ($status !== 'all') {
            $where[] = "s.status = %s";
            $params[] = $status;
        }

        $where_sql = implode(' AND ', $where);
        $query = "SELECT s.*, u.display_name as specialist_name 
                  FROM $table_sessions s 
                  LEFT JOIN {$wpdb->users} u ON u.ID = s.user_id 
                  WHERE $where_sql 
                  ORDER BY s.id DESC LIMIT %d";
        $params[] = $limit;

        $sessions = $wpdb->get_results($wpdb->prepare($query, $params));

        $output = [];
        $current_ts = current_time('timestamp');
        foreach ($sessions ?: [] as $s) {
            $snaps = $wpdb->get_results($wpdb->prepare(
                "SELECT id, snapshot_url, elapsed_seconds, mouse_clicks, keyboard_clicks, session_type, notes, created_at FROM $table_snapshots WHERE session_id = %d ORDER BY id ASC LIMIT 10",
                $s->id
            ));

            $sec = $this->compute_session_seconds($s, $current_ts);
            $output[] = [
                'id' => (int)$s->id,
                'user_id' => (int)$s->user_id,
                'specialist_name' => $s->specialist_name ?: 'Specialist',
                'avatar' => get_avatar_url($s->user_id, ['size' => 48]),
                'project_id' => (int)$s->project_id,
                'order_id' => $s->order_id,
                'client_name' => $s->client_name,
                'profile_name' => $s->profile_name,
                'team_name' => $s->team_name,
                'session_type' => $s->session_type ?: 'regular',
                'work_date' => $this->to_bst($s->start_time, 'Y-m-d'),
                'start_time' => $this->to_bst($s->start_time, 'M j, Y g:i A'),
                'end_time' => !empty($s->end_time) ? $this->to_bst($s->end_time, 'M j, Y g:i A') : null,
                'total_seconds' => $sec,
                'time_formatted' => $this->format_hms_compact($sec),
                'snapshots_count' => (int)$s->snapshots_count,
                'total_mouse_clicks' => (int)($s->total_mouse_clicks ?? 0),
                'total_keyboard_clicks' => (int)($s->total_keyboard_clicks ?? 0),
                'mouse_clicks' => (int)($s->total_mouse_clicks ?? 0),
                'keyboard_clicks' => (int)($s->total_keyboard_clicks ?? 0),
                'status' => $s->status,
                'logged_to_report' => (bool)$s->logged_to_report,
                'synced_to_daily_report' => (bool)$s->logged_to_report,
                'created_at' => $s->created_at,
                'snapshots' => array_map(function($sn) {
                    return [
                        'id' => (int)$sn->id,
                        'url' => $sn->snapshot_url,
                        'elapsed_seconds' => (int)$sn->elapsed_seconds,
                        'mouse_clicks' => (int)($sn->mouse_clicks ?? 0),
                        'keyboard_clicks' => (int)($sn->keyboard_clicks ?? 0),
                        'session_type' => $sn->session_type ?: 'regular',
                        'notes' => $sn->notes ?: '',
                        'created_at' => $this->to_bst($sn->created_at, 'M j, Y g:i A')
                    ];
                }, $snaps ?: [])
            ];
        }

        return rest_ensure_response($output);
    }

    /**
     * Convert a stored server/WP MySQL datetime string to Bangladesh Standard Time (Asia/Dhaka, UTC+6)
     * purely for display without altering database storage or timer calculations.
     */
    private function to_bst($mysql_dt, $format = 'M j, Y g:i A') {
        if (empty($mysql_dt)) {
            return '';
        }
        try {
            $src_tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('UTC');
            $dt = new DateTime((string)$mysql_dt, $src_tz);
            $dt->setTimezone(new DateTimeZone('Asia/Dhaka'));
            return $dt->format($format);
        } catch (Exception $e) {
            $ts = strtotime((string)$mysql_dt);
            return $ts ? date($format, $ts) : (string)$mysql_dt;
        }
    }

    /**
     * Get snapshots gallery
     * GET /time-tracker/snapshots
     */
    public function get_snapshots($request) {
        global $wpdb;
        self::maybe_run_daily_retention();
        $current_user_id = get_current_user_id();
        $is_admin = FTM_Permissions::is_super_admin($current_user_id) || FTM_Permissions::is_executive($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id);
        $user_team = FTM_Permissions::get_user_team($current_user_id);

        $table_snapshots = $wpdb->prefix . 'ftm_time_tracker_snapshots';
        $table_sessions = $wpdb->prefix . 'ftm_time_tracker_sessions';

        $session_id = (int)$request->get_param('session_id');
        $project_id = (int)$request->get_param('project_id');
        $user_id = (int)$request->get_param('user_id');
        $team = sanitize_text_field($request->get_param('team') ?: 'all');
        $session_type = sanitize_text_field($request->get_param('session_type') ?: 'all');
        $search = sanitize_text_field($request->get_param('search') ?: '');
        $date = sanitize_text_field($request->get_param('date') ?: '');
        $limit = max(1, min(300, (int)($request->get_param('limit') ?: 100)));

        $where = ["1=1"];
        $params = [];

        if (!$is_admin && !$is_leader) {
            // Screenshots are strictly hidden from general members
            return rest_ensure_response([]);
        } elseif ($is_leader && !$is_admin) {
            // Leader / Co-Leader can ONLY see snapshots from their own team members
            $where[] = "(s.team_name = %s OR sn.user_id = %d)";
            $params[] = $user_team;
            $params[] = $current_user_id;

            if ($user_id > 0) {
                $target_team = FTM_Permissions::get_user_team($user_id);
                if (strcasecmp($target_team, $user_team) !== 0 && $user_id !== $current_user_id) {
                    return rest_ensure_response([]);
                }
                $where[] = "sn.user_id = %d";
                $params[] = $user_id;
            }
        } else {
            // Super Admin / Executive
            if ($team !== 'all' && !empty($team)) {
                $where[] = "s.team_name = %s";
                $params[] = $team;
            }
            if ($user_id > 0) {
                $where[] = "sn.user_id = %d";
                $params[] = $user_id;
            }
        }

        if ($session_id > 0) {
            $where[] = "sn.session_id = %d";
            $params[] = $session_id;
        }

        if ($project_id > 0) {
            $where[] = "sn.project_id = %d";
            $params[] = $project_id;
        }

        if ($session_type !== 'all') {
            $where[] = "sn.session_type = %s";
            $params[] = $session_type;
        }

        if (!empty($date)) {
            $where[] = "DATE(sn.created_at) = %s";
            $params[] = $date;
        }

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = "(sn.order_id LIKE %s OR u.display_name LIKE %s OR s.client_name LIKE %s OR sn.notes LIKE %s)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = implode(' AND ', $where);
        $query = "SELECT sn.*, u.display_name as specialist_name, s.client_name, s.team_name 
                  FROM $table_snapshots sn 
                  LEFT JOIN {$wpdb->users} u ON u.ID = sn.user_id 
                  LEFT JOIN $table_sessions s ON s.id = sn.session_id 
                  WHERE $where_sql 
                  ORDER BY sn.id DESC LIMIT %d";
        $params[] = $limit;

        $snapshots = $wpdb->get_results($wpdb->prepare($query, $params));

        $output = array_map(function($sn) {
            $m = round($sn->elapsed_seconds / 60);
            return [
                'id' => (int)$sn->id,
                'session_id' => (int)$sn->session_id,
                'user_id' => (int)$sn->user_id,
                'specialist_name' => $sn->specialist_name ?: 'Specialist',
                'avatar' => get_avatar_url($sn->user_id, ['size' => 48]),
                'project_id' => (int)$sn->project_id,
                'order_id' => $sn->order_id,
                'client_name' => $sn->client_name ?: '',
                'team_name' => $sn->team_name ?: 'WpCraft',
                'session_type' => $sn->session_type ?: 'regular',
                'notes' => $sn->notes ?: '',
                'url' => $sn->snapshot_url,
                'image_url' => $sn->snapshot_url,
                'elapsed_seconds' => (int)$sn->elapsed_seconds,
                'elapsed_label' => "+{$m}m",
                'mouse_clicks' => (int)($sn->mouse_clicks ?? 0),
                'keyboard_clicks' => (int)($sn->keyboard_clicks ?? 0),
                'keystrokes' => (int)($sn->keyboard_clicks ?? 0),
                'captured_at' => $this->to_bst($sn->created_at, 'Y-m-d H:i:s'),
                'captured_at_formatted' => $this->to_bst($sn->created_at, 'M j, Y g:i A')
            ];
        }, $snapshots ?: []);

        return rest_ensure_response($output);
    }

    /**
     * Get aggregate statistics and active live tracking sessions
     * GET /time-tracker/stats
     */
    public function get_tracker_stats($request) {
        global $wpdb;
        self::maybe_run_daily_retention();
        $this->expire_stale_active_sessions();

        $current_user_id = get_current_user_id();
        $is_admin = FTM_Permissions::is_super_admin($current_user_id) || FTM_Permissions::is_executive($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id);
        $user_team = FTM_Permissions::get_user_team($current_user_id);

        $table_sessions = $wpdb->prefix . 'ftm_time_tracker_sessions';
        $table_snapshots = $wpdb->prefix . 'ftm_time_tracker_snapshots';

        $team_filter = sanitize_text_field($request->get_param('team') ?: 'all');
        $filter_uid = (int)$request->get_param('user_id');
        $date_filter = sanitize_text_field($request->get_param('date') ?: '');
        $date_from = sanitize_text_field($request->get_param('date_from') ?: '');
        $date_to = sanitize_text_field($request->get_param('date_to') ?: '');

        $where = ["1=1"];
        $params = [];
        if (!$is_admin && !$is_leader) {
            // Member: only their own session
            $where[] = "s.user_id = %d";
            $params[] = $current_user_id;
        } elseif ($is_leader && !$is_admin) {
            // Leader: strictly their own squad members!
            $where[] = "(s.team_name = %s OR s.user_id = %d)";
            $params[] = $user_team;
            $params[] = $current_user_id;

            if ($filter_uid > 0) {
                $target_team = FTM_Permissions::get_user_team($filter_uid);
                if (strcasecmp($target_team, $user_team) !== 0 && $filter_uid !== $current_user_id) {
                    $where[] = "1=0";
                } else {
                    $where[] = "s.user_id = %d";
                    $params[] = $filter_uid;
                }
            }
        } else {
            // Admin / Executive: can query all teams or filter
            if ($team_filter !== 'all' && !empty($team_filter)) {
                $where[] = "s.team_name = %s";
                $params[] = $team_filter;
            }
            if ($filter_uid > 0) {
                $where[] = "s.user_id = %d";
                $params[] = $filter_uid;
            }
        }

        $where_sql = implode(' AND ', $where);

        // Completed/non-active sessions total (never overlaps with active sessions loop below)
        $completed_sec_query = "SELECT COALESCE(SUM(s.total_seconds), 0) FROM $table_sessions s WHERE s.status != 'active' AND $where_sql";
        $completed_total_seconds = (int)(!empty($params) ? $wpdb->get_var($wpdb->prepare($completed_sec_query, $params)) : $wpdb->get_var($completed_sec_query));

        // Total snapshots across filtered scope
        $snap_where = $where;
        $snap_params = $params;
        if (!empty($date_filter)) {
            $snap_where[] = "DATE(sn.created_at) = %s";
            $snap_params[] = $date_filter;
        }
        $snap_where_sql = implode(' AND ', $snap_where);
        $total_snaps_query = "SELECT COUNT(sn.id) FROM $table_snapshots sn LEFT JOIN $table_sessions s ON s.id = sn.session_id WHERE $snap_where_sql";
        $total_snapshots = (int)(!empty($snap_params) ? $wpdb->get_var($wpdb->prepare($total_snaps_query, $snap_params)) : $wpdb->get_var($total_snaps_query));

        $today = current_time('Y-m-d');
        $completed_today_query = "SELECT COALESCE(SUM(s.total_seconds), 0) FROM $table_sessions s WHERE s.status != 'active' AND $where_sql AND DATE(s.start_time) = %s";
        $completed_today_seconds = (int)$wpdb->get_var($wpdb->prepare($completed_today_query, array_merge($params, [$today])));

        // Live active sessions (already auto-expired any abandoned ones above)
        $active_query = "SELECT s.*, u.display_name as specialist_name 
                         FROM $table_sessions s 
                         LEFT JOIN {$wpdb->users} u ON u.ID = s.user_id 
                         WHERE s.status = 'active' AND $where_sql 
                         ORDER BY s.id DESC";
        $active_sessions = !empty($params) ? $wpdb->get_results($wpdb->prepare($active_query, $params)) : $wpdb->get_results($active_query);

        $active_formatted = [];
        $active_live_seconds = 0;
        $active_today_seconds = 0;
        $current_ts = current_time('timestamp');

        foreach ($active_sessions ?: [] as $as) {
            $latest_snap = $wpdb->get_var($wpdb->prepare(
                "SELECT snapshot_url FROM $table_snapshots WHERE session_id = %d ORDER BY id DESC LIMIT 1",
                $as->id
            ));

            $display_seconds = $this->compute_session_seconds($as, $current_ts);
            $active_live_seconds += $display_seconds;
            if (substr($as->start_time, 0, 10) === $today) {
                $active_today_seconds += $display_seconds;
            }

            $active_formatted[] = [
                'session_id' => (int)$as->id,
                'user_id' => (int)$as->user_id,
                'specialist_name' => $as->specialist_name ?: 'Specialist',
                'avatar' => get_avatar_url($as->user_id, ['size' => 48]),
                'team_name' => $as->team_name,
                'project_id' => (int)$as->project_id,
                'order_id' => $as->order_id,
                'client_name' => $as->client_name,
                'profile_name' => $as->profile_name,
                'start_time' => $as->start_time,
                'total_seconds' => $display_seconds,
                'time_formatted' => $this->format_hms_compact($display_seconds),
                'snapshots_count' => (int)$as->snapshots_count,
                'latest_snapshot_url' => $latest_snap ?: ''
            ];
        }

        $all_total_seconds = $completed_total_seconds + $active_live_seconds;
        $all_today_seconds = $completed_today_seconds + $active_today_seconds;

        // If a specific date or date range is requested, also compute range totals cleanly
        $selected_seconds = $all_today_seconds;
        $target_date = $date_filter ?: $today;
        if (!empty($date_filter)) {
            $range_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT s.* FROM $table_sessions s WHERE $where_sql AND DATE(s.start_time) = %s",
                array_merge($params, [$date_filter])
            ));
            $selected_seconds = 0;
            foreach ($range_rows ?: [] as $rr) {
                $selected_seconds += $this->compute_session_seconds($rr, $current_ts);
            }
        } elseif (!empty($date_from) || !empty($date_to)) {
            $r_where = [$where_sql];
            $r_params = $params;
            if (!empty($date_from)) {
                $r_where[] = "DATE(s.start_time) >= %s";
                $r_params[] = $date_from;
            }
            if (!empty($date_to)) {
                $r_where[] = "DATE(s.start_time) <= %s";
                $r_params[] = $date_to;
            }
            $range_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT s.* FROM $table_sessions s WHERE " . implode(' AND ', $r_where),
                $r_params
            ));
            $selected_seconds = 0;
            foreach ($range_rows ?: [] as $rr) {
                $selected_seconds += $this->compute_session_seconds($rr, $current_ts);
            }
        }

        return rest_ensure_response([
            'total_seconds' => $all_total_seconds,
            'total_hours_formatted' => $this->format_hms_compact($all_total_seconds),
            'today_seconds' => $all_today_seconds,
            'today_hours_formatted' => $this->format_hms_compact($all_today_seconds),
            'selected_date' => $target_date,
            'selected_seconds' => $selected_seconds,
            'selected_hours_formatted' => $this->format_hms_compact($selected_seconds),
            'total_snapshots' => $total_snapshots,
            'active_sessions_count' => count($active_formatted),
            'active_sessions' => $active_formatted
        ]);
    }

    /**
     * Date-wise time tracking summary grouped by work date and specialist
     * GET /time-tracker/daily-summary
     */
    public function get_daily_summary($request) {
        global $wpdb;
        $this->expire_stale_active_sessions();

        $table_sessions = $wpdb->prefix . 'ftm_time_tracker_sessions';
        $today = current_time('Y-m-d');

        // Default to last 14 days if neither date nor date_from/date_to specified
        $date_param = sanitize_text_field($request->get_param('date') ?: '');
        $date_from = sanitize_text_field($request->get_param('date_from') ?: '');
        $date_to = sanitize_text_field($request->get_param('date_to') ?: '');

        $where = ["1=1"];
        $params = [];
        $this->append_session_scope_filters($request, $where, $params);

        if (empty($date_param) && empty($date_from) && empty($date_to)) {
            $date_from = date('Y-m-d', strtotime($today . ' -30 days'));
            $where[] = "DATE(s.start_time) >= %s";
            $params[] = $date_from;
        }

        $where_sql = implode(' AND ', $where);
        $query = "SELECT s.*, u.display_name as specialist_name
                  FROM $table_sessions s
                  LEFT JOIN {$wpdb->users} u ON u.ID = s.user_id
                  WHERE $where_sql
                  ORDER BY s.start_time DESC, s.id DESC
                  LIMIT 1000";

        $rows = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, $params)) : $wpdb->get_results($query);

        $current_ts = current_time('timestamp');
        $grouped = [];      // key: "YYYY-MM-DD|user_id"
        $daily_totals = []; // key: "YYYY-MM-DD"
        $specialists_map = [];
        $total_regular = 0;
        $total_extra = 0;
        $total_sessions = 0;
        $total_snapshots = 0;

        foreach ($rows ?: [] as $s) {
            $work_date = substr($s->start_time, 0, 10);
            if (!$work_date || $work_date === '0000-00-00') continue;

            $uid = (int)$s->user_id;
            $sec = $this->compute_session_seconds($s, $current_ts);
            $is_extra = ($s->session_type === 'extra_time');
            $snaps_cnt = (int)$s->snapshots_count;
            $spec_name = $s->specialist_name ?: 'Specialist';
            $team_name = $s->team_name ?: 'WpCraft';

            $specialists_map[$uid] = [
                'user_id' => $uid,
                'name' => $spec_name,
                'team' => $team_name
            ];

            $total_sessions++;
            $total_snapshots += $snaps_cnt;
            if ($is_extra) {
                $total_extra += $sec;
            } else {
                $total_regular += $sec;
            }

            $key = $work_date . '|' . $uid;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'date' => $work_date,
                    'date_formatted' => date('D, M j, Y', strtotime($work_date)),
                    'is_today' => ($work_date === $today),
                    'user_id' => $uid,
                    'specialist_name' => $spec_name,
                    'avatar' => get_avatar_url($uid, ['size' => 48]),
                    'team_name' => $team_name,
                    'regular_seconds' => 0,
                    'extra_seconds' => 0,
                    'total_seconds' => 0,
                    'sessions_count' => 0,
                    'active_count' => 0,
                    'snapshots_count' => 0,
                    'total_mouse_clicks' => 0,
                    'total_keyboard_clicks' => 0,
                    'orders' => []
                ];
            }

            if ($is_extra) {
                $grouped[$key]['extra_seconds'] += $sec;
            } else {
                $grouped[$key]['regular_seconds'] += $sec;
            }
            $grouped[$key]['total_seconds'] += $sec;
            $grouped[$key]['sessions_count']++;
            $grouped[$key]['total_mouse_clicks'] += (int)($s->total_mouse_clicks ?? 0);
            $grouped[$key]['total_keyboard_clicks'] += (int)($s->total_keyboard_clicks ?? 0);
            if ($s->status === 'active') {
                $grouped[$key]['active_count']++;
            }
            $grouped[$key]['snapshots_count'] += $snaps_cnt;

            $ord_key = ($s->order_id ?: ('proj-' . $s->project_id));
            if (!isset($grouped[$key]['orders'][$ord_key])) {
                $grouped[$key]['orders'][$ord_key] = [
                    'project_id' => (int)$s->project_id,
                    'order_id' => $s->order_id ?: ('#' . $s->project_id),
                    'client_name' => $s->client_name ?: '',
                    'seconds' => 0
                ];
            }
            $grouped[$key]['orders'][$ord_key]['seconds'] += $sec;

            if (!isset($daily_totals[$work_date])) {
                $daily_totals[$work_date] = [
                    'date' => $work_date,
                    'date_formatted' => date('D, M j, Y', strtotime($work_date)),
                    'is_today' => ($work_date === $today),
                    'regular_seconds' => 0,
                    'extra_seconds' => 0,
                    'total_seconds' => 0,
                    'sessions_count' => 0,
                    'snapshots_count' => 0,
                    'specialists_map' => []
                ];
            }
            if ($is_extra) {
                $daily_totals[$work_date]['extra_seconds'] += $sec;
            } else {
                $daily_totals[$work_date]['regular_seconds'] += $sec;
            }
            $daily_totals[$work_date]['total_seconds'] += $sec;
            $daily_totals[$work_date]['sessions_count']++;
            $daily_totals[$work_date]['snapshots_count'] += $snaps_cnt;
            $daily_totals[$work_date]['specialists_map'][$uid] = true;
        }

        // Format records and daily totals
        $records_out = [];
        foreach ($grouped as $g) {
            $orders_list = array_values($g['orders']);
            foreach ($orders_list as &$ol) {
                $ol['time_formatted'] = $this->format_hms_compact($ol['seconds']);
            }
            $g['orders'] = $orders_list;
            $g['regular_formatted'] = $this->format_hms_compact($g['regular_seconds']);
            $g['extra_formatted'] = $this->format_hms_compact($g['extra_seconds']);
            $g['total_formatted'] = $this->format_hms_compact($g['total_seconds']);
            $g['total_hours_decimal'] = round($g['total_seconds'] / 3600, 2);
            $records_out[] = $g;
        }

        usort($records_out, function($a, $b) {
            if ($a['date'] !== $b['date']) return strcmp($b['date'], $a['date']);
            return $b['total_seconds'] <=> $a['total_seconds'];
        });

        $totals_out = [];
        foreach ($daily_totals as $dt) {
            $totals_out[] = [
                'date' => $dt['date'],
                'date_formatted' => $dt['date_formatted'],
                'is_today' => $dt['is_today'],
                'specialists_count' => count($dt['specialists_map']),
                'sessions_count' => $dt['sessions_count'],
                'snapshots_count' => $dt['snapshots_count'],
                'regular_seconds' => $dt['regular_seconds'],
                'regular_formatted' => $this->format_hms_compact($dt['regular_seconds']),
                'extra_seconds' => $dt['extra_seconds'],
                'extra_formatted' => $this->format_hms_compact($dt['extra_seconds']),
                'total_seconds' => $dt['total_seconds'],
                'total_formatted' => $this->format_hms_compact($dt['total_seconds'])
            ];
        }
        usort($totals_out, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        $specialists_list = array_values($specialists_map);
        usort($specialists_list, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $grand_total = $total_regular + $total_extra;

        return rest_ensure_response([
            'summary' => [
                'total_seconds' => $grand_total,
                'total_formatted' => $this->format_hms_compact($grand_total),
                'regular_seconds' => $total_regular,
                'regular_formatted' => $this->format_hms_compact($total_regular),
                'extra_seconds' => $total_extra,
                'extra_formatted' => $this->format_hms_compact($total_extra),
                'days_count' => count($totals_out),
                'sessions_count' => $total_sessions,
                'snapshots_count' => $total_snapshots
            ],
            'daily_totals' => $totals_out,
            'records' => $records_out,
            'specialists' => $specialists_list
        ]);
    }

    /**
     * Submit manual extra time work report (after 6:20 PM)
     * POST /time-tracker/extra-time-report
     */
    public function submit_extra_time_report($request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $data = $request->get_json_params() ?: $request->get_params();

        $project_id = isset($data['project_id']) ? (int)$data['project_id'] : 0;
        $order_id = sanitize_text_field($data['order_id'] ?? '');
        $client_name = sanitize_text_field($data['client_name'] ?? '');
        $raw_hours = $data['hours'] ?? ($data['work_hours'] ?? 0);
        $tasks_completed = sanitize_textarea_field(wp_unslash($data['tasks_completed'] ?? ''));
        $extra_time_reason = sanitize_text_field(wp_unslash($data['extra_time_reason'] ?? ''));
        $report_date = sanitize_text_field($data['report_date'] ?? current_time('Y-m-d'));

        if (class_exists('FTM_Daily_Report_Controller')) {
            $hours = FTM_Daily_Report_Controller::parse_hours($raw_hours);
        } else {
            $hours = (float)$raw_hours;
        }

        if ($hours <= 0) {
            return $this->format_error(__('Please enter valid extra time work hours (e.g. 1h 30m or 1.5).', 'fiverr-team-management'), 400);
        }

        if (empty($tasks_completed)) {
            return $this->format_error(__('Please describe the tasks completed or deliverables during extra time.', 'fiverr-team-management'), 400);
        }

        $user_team = FTM_Permissions::get_user_team($user_id);
        $reports_table = $wpdb->prefix . 'ftm_daily_reports';
        $items_table = $wpdb->prefix . 'ftm_daily_report_items';

        $report = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status FROM $reports_table WHERE user_id = %d AND report_date = %s",
            $user_id, $report_date
        ));

        if (!$report) {
            $wpdb->insert($reports_table, [
                'user_id' => $user_id,
                'team_name' => $user_team,
                'report_type' => 'work',
                'report_date' => $report_date,
                'status' => 'submitted',
                'submission_time' => current_time('H:i:s'),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]);
            $report_id = $wpdb->insert_id;
        } else {
            $report_id = (int)$report->id;
            if ($report->status === 'draft') {
                $wpdb->update($reports_table, [
                    'status' => 'submitted',
                    'submission_time' => current_time('H:i:s')
                ], ['id' => $report_id]);
            }
        }

        // Insert or update report item
        $existing_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $items_table WHERE report_id = %d AND (project_id = %d AND project_id > 0 OR order_id = %s)",
            $report_id, $project_id, $order_id
        ));

        if ($existing_item) {
            $new_hours = round((float)$existing_item->hours_spent + $hours, 2);
            $new_extra = round((float)($existing_item->extra_time_hours ?? 0) + $hours, 2);
            $combined_tasks = $existing_item->tasks_completed . "\n[⚡ Extra Time after 6:20 PM]: " . $tasks_completed;
            $wpdb->update($items_table, [
                'hours_spent' => $new_hours,
                'tasks_completed' => sanitize_textarea_field($combined_tasks),
                'is_extra_time' => 1,
                'extra_time_hours' => $new_extra,
                'extra_time_reason' => $extra_time_reason ?: ($existing_item->extra_time_reason ?? '')
            ], ['id' => $existing_item->id]);
            $item_id = $existing_item->id;
        } else {
            $wpdb->insert($items_table, [
                'report_id' => $report_id,
                'project_id' => $project_id,
                'order_id' => $order_id,
                'client_name' => $client_name,
                'tasks_completed' => "[⚡ Extra Time after 6:20 PM]: " . $tasks_completed,
                'work_in_progress' => '',
                'next_day_plan' => '',
                'has_blocker' => 0,
                'hours_spent' => $hours,
                'progress_percent' => 100,
                'is_extra_time' => 1,
                'extra_time_hours' => $hours,
                'extra_time_reason' => $extra_time_reason,
                'created_at' => current_time('mysql')
            ]);
            $item_id = $wpdb->insert_id;
        }

        // Notify leadership
        $user_obj = get_userdata($user_id);
        $user_name = $user_obj ? $user_obj->display_name : 'Specialist';
        if (class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'broadcast_to_leadership')) {
            try {
                FTM_Notifications_Engine::broadcast_to_leadership([
                    'title' => '⚡ Extra Time Work Submitted',
                    'message' => "{$user_name} submitted {$hours}h extra time after 6:20 PM on {$order_id} ({$client_name}). Reason: {$extra_time_reason}",
                    'type' => 'extra_time_report',
                    'target_id' => $report_id,
                    'team_name' => $user_team
                ]);
            } catch (\Throwable $e) {}
        }

        return $this->format_success([
            'item_id' => $item_id,
            'report_id' => $report_id,
            'hours' => $hours,
            'extra_time_hours' => $hours,
            'report_date' => $report_date
        ], __('Extra time work report submitted and synced successfully!', 'fiverr-team-management'));
    }

    /**
     * Get extra time summary & history for member
     * GET /time-tracker/extra-time-summary
     */
    public function get_extra_time_summary($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $is_admin = FTM_Permissions::is_super_admin($current_user_id) || FTM_Permissions::is_executive($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id);

        $target_user_id = (int)$request->get_param('user_id');
        if ($target_user_id <= 0 || (!$is_admin && !$is_leader && $target_user_id !== $current_user_id)) {
            $target_user_id = $current_user_id;
        }

        $items_table = $wpdb->prefix . 'ftm_daily_report_items';
        $reports_table = $wpdb->prefix . 'ftm_daily_reports';
        $sessions_table = $wpdb->prefix . 'ftm_time_tracker_sessions';
        $snapshots_table = $wpdb->prefix . 'ftm_time_tracker_snapshots';

        // Overtime items from daily reports
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, r.report_date, r.status as report_status, r.team_name 
             FROM $items_table i 
             INNER JOIN $reports_table r ON r.id = i.report_id 
             WHERE r.user_id = %d AND i.is_extra_time = 1 
             ORDER BY r.report_date DESC, i.id DESC LIMIT 100",
            $target_user_id
        ));

        // Overtime sessions from live tracker
        $sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT s.* FROM $sessions_table s 
             WHERE s.user_id = %d AND s.session_type = 'extra_time' 
             ORDER BY s.id DESC LIMIT 50",
            $target_user_id
        ));

        $total_extra_hours = 0.0;
        foreach ($items ?: [] as $it) {
            $total_extra_hours += (float)$it->extra_time_hours;
        }

        $total_sessions_count = count($sessions ?: []);
        $total_snapshots_count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(id) FROM $snapshots_table WHERE user_id = %d AND session_type = 'extra_time'",
            $target_user_id
        ));

        $formatted_items = array_map(function($it) {
            $h = (float)$it->extra_time_hours;
            $mins = round($h * 60);
            $h_disp = floor($mins / 60);
            $m_disp = $mins % 60;
            return [
                'id' => (int)$it->id,
                'report_id' => (int)$it->report_id,
                'project_id' => (int)$it->project_id,
                'order_id' => $it->order_id,
                'client_name' => $it->client_name,
                'report_date' => $it->report_date,
                'report_date_formatted' => date('M j, Y', strtotime($it->report_date)),
                'extra_time_hours' => $h,
                'extra_time_formatted' => sprintf('%dh %02dm', $h_disp, $m_disp),
                'extra_time_reason' => $it->extra_time_reason ?: 'Urgent Work / Overtime',
                'tasks_completed' => $it->tasks_completed,
                'status' => $it->report_status
            ];
        }, $items ?: []);

        $tot_m = round($total_extra_hours * 60);
        $tot_h_disp = floor($tot_m / 60);
        $tot_m_disp = $tot_m % 60;

        return rest_ensure_response([
            'user_id' => $target_user_id,
            'total_extra_hours' => round($total_extra_hours, 2),
            'total_extra_formatted' => sprintf('%dh %02dm', $tot_h_disp, $tot_m_disp),
            'total_sessions_count' => $total_sessions_count,
            'total_snapshots_count' => $total_snapshots_count,
            'history' => $formatted_items
        ]);
    }

    public function check_manager_permission($request = null) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', __('You must be logged in to access this resource.', 'fiverr-team-management'), ['status' => 401]);
        }
        $uid = get_current_user_id();
        if (!FTM_Permissions::is_super_admin($uid)) {
            return new WP_Error('rest_forbidden', __('Only Operations Managers and Administrators can edit or delete screenshot audit items.', 'fiverr-team-management'), ['status' => 403]);
        }
        return true;
    }

    /**
     * Update notes on a captured snapshot (Manager Only)
     * POST /time-tracker/snapshots/{id}/update
     */
    public function update_snapshot_notes($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $current_user_id = get_current_user_id();
        $is_manager = FTM_Permissions::is_super_admin($current_user_id);
        if (!$is_manager) {
            return $this->format_error(__('Only Operations Managers and Administrators can edit screenshot audit notes.', 'fiverr-team-management'), 403);
        }

        $notes = sanitize_textarea_field(wp_unslash($request->get_param('notes') ?? ''));

        $table = $wpdb->prefix . 'ftm_time_tracker_snapshots';
        $snap = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table WHERE id = %d", $id));

        if (!$snap) {
            return $this->format_error(__('Snapshot not found.', 'fiverr-team-management'), 404);
        }

        $wpdb->update($table, ['notes' => $notes], ['id' => $id]);

        return $this->format_success([
            'id' => $id,
            'notes' => $notes
        ], __('Snapshot notes updated successfully.', 'fiverr-team-management'));
    }

    /**
     * Delete a snapshot from disk and database (Manager Only)
     * DELETE /time-tracker/snapshots/{id}
     */
    public function delete_snapshot($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $current_user_id = get_current_user_id();

        $table = $wpdb->prefix . 'ftm_time_tracker_snapshots';
        $snap = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

        if (!$snap) {
            return $this->format_error(__('Snapshot not found.', 'fiverr-team-management'), 404);
        }

        $is_manager = FTM_Permissions::is_super_admin($current_user_id);
        if (!$is_manager) {
            return $this->format_error(__('Only Operations Managers and Administrators can delete screenshots.', 'fiverr-team-management'), 403);
        }

        // Delete file from disk
        if (!empty($snap->file_path) && file_exists($snap->file_path)) {
            @unlink($snap->file_path);
        }

        // Decrement snapshot count on session
        if (!empty($snap->session_id)) {
            $sess_table = $wpdb->prefix . 'ftm_time_tracker_sessions';
            $wpdb->query($wpdb->prepare(
                "UPDATE $sess_table SET snapshots_count = GREATEST(0, snapshots_count - 1) WHERE id = %d",
                $snap->session_id
            ));
        }

        $wpdb->delete($table, ['id' => $id]);

        return $this->format_success([
            'id' => $id,
            'deleted' => true
        ], __('Screenshot deleted successfully.', 'fiverr-team-management'));
    }
}
