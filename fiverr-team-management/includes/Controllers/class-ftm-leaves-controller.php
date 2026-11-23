<?php
/**
 * Leaves & Attendance Controller
 * Handles Leave Applications, Day-Off Adjustments, Daily Attendance Roster,
 * Two-stage verification (Leader -> Manager), and Export.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Leaves_Controller extends FTM_Rest_Controller {

    public function register_routes() {
        self::ensure_tables();

        // Leave Requests List & Submit
        register_rest_route($this->namespace, '/leaves', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_leaves'],
                'permission_callback' => [$this, 'check_auth_permission']
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'submit_leave'],
                'permission_callback' => [$this, 'check_auth_permission']
            ]
        ]);

        // Review Leave (Leader or Manager)
        register_rest_route($this->namespace, '/leaves/(?P<id>\d+)/review', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'review_leave'],
            'permission_callback' => [$this, 'check_leader_or_admin_permission']
        ]);

        // Get, Delete, or Update Leave
        register_rest_route($this->namespace, '/leaves/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_single_leave'],
                'permission_callback' => [$this, 'check_auth_permission']
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_leave'],
                'permission_callback' => [$this, 'check_auth_permission']
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_leave'],
                'permission_callback' => [$this, 'check_auth_permission']
            ]
        ]);

        // Daily Attendance Roster
        register_rest_route($this->namespace, '/attendance', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_attendance'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Manual Attendance Override (Leader or Manager)
        register_rest_route($this->namespace, '/attendance/mark', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'mark_attendance'],
            'permission_callback' => [$this, 'check_leader_or_admin_permission']
        ]);

        // Member Weekly Day-Off Config
        register_rest_route($this->namespace, '/members/(?P<id>\d+)/day-off', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'set_member_day_off'],
            'permission_callback' => [$this, 'check_leader_or_admin_permission']
        ]);

        // Super Admin: Per-Member Leave Quota & Balance Adjustment
        register_rest_route($this->namespace, '/members/(?P<id>\d+)/leave-quota', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'set_member_leave_quota'],
            'permission_callback' => [$this, 'check_admin_permission']
        ]);

        // Export Attendance & Daily Progress CSV
        register_rest_route($this->namespace, '/attendance/export', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'export_attendance_csv'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Comprehensive Leave Management Report & Balances
        register_rest_route($this->namespace, '/leaves/report', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_leaves_report'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);
    }

    public static function ensure_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_leaves = $wpdb->prefix . 'ftm_leaves';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_leaves'") !== $table_leaves) {
            $sql = "CREATE TABLE $table_leaves (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                team_name varchar(100) NOT NULL DEFAULT 'WpCraft',
                request_type varchar(50) NOT NULL DEFAULT 'leave',
                leave_type varchar(50) NULL,
                start_date date NOT NULL,
                end_date date NOT NULL,
                days_count decimal(4,1) NOT NULL DEFAULT 1.0,
                reason text NULL,
                switch_from_date date NULL,
                switch_to_date date NULL,
                status varchar(50) NOT NULL DEFAULT 'pending',
                leader_id bigint(20) unsigned NULL,
                leader_approved_at datetime NULL,
                leader_feedback text NULL,
                manager_id bigint(20) unsigned NULL,
                manager_approved_at datetime NULL,
                manager_feedback text NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY status (status),
                KEY start_date (start_date)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        $table_attendance = $wpdb->prefix . 'ftm_attendance';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_attendance'") !== $table_attendance) {
            $sql2 = "CREATE TABLE $table_attendance (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                team_name varchar(100) NOT NULL DEFAULT 'WpCraft',
                attendance_date date NOT NULL,
                status varchar(50) NOT NULL DEFAULT 'present',
                notes varchar(255) NULL,
                marked_by bigint(20) unsigned NULL,
                marked_at datetime NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY user_date (user_id, attendance_date),
                KEY attendance_date (attendance_date)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql2);
        }
    }

    /**
     * GET /leaves
     */
    public function get_leaves($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();

        $is_admin = FTM_Permissions::is_super_admin($current_user_id) || FTM_Permissions::is_executive($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id);
        $is_deputy = FTM_Permissions::is_deputy($current_user_id);
        $user_team = FTM_Permissions::get_user_team($current_user_id);

        $table = $wpdb->prefix . 'ftm_leaves';

        $where = ["1=1"];
        $params = [];

        // Scoping: Members see only their own; Leaders see their squad + own; Super Admin & Deputy see all
        if (!$is_admin && !$is_leader && !$is_deputy) {
            $where[] = "user_id = %d";
            $params[] = $current_user_id;
        } elseif ($is_leader && !$is_admin && !$is_deputy) {
            $where[] = "(team_name = %s OR user_id = %d)";
            $params[] = $user_team;
            $params[] = $current_user_id;
        }

        if (!empty($request['status']) && $request['status'] !== 'all') {
            $where[] = "status = %s";
            $params[] = sanitize_text_field($request['status']);
        }

        if (!empty($request['team']) && $request['team'] !== 'all') {
            $where[] = "team_name = %s";
            $params[] = sanitize_text_field($request['team']);
        }

        if (!empty($request['request_type']) && $request['request_type'] !== 'all') {
            $where[] = "request_type = %s";
            $params[] = sanitize_text_field($request['request_type']);
        }

        $where_sql = implode(' AND ', $where);
        $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY start_date DESC, id DESC";

        $rows = !empty($params) ? $wpdb->get_results($wpdb->prepare($sql, ...$params)) : $wpdb->get_results($sql);

        $formatted = array_map([$this, 'format_leave_item'], $rows ?: []);

        return rest_ensure_response($formatted);
    }

    /**
     * POST /leaves (Submit leave or day-off switch)
     */
    public function submit_leave($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $data = $request->get_json_params() ?: $request->get_params();

        $requested_uid = !empty($data['user_id']) ? (int)$data['user_id'] : 0;
        $is_admin = FTM_Permissions::is_super_admin($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id);

        if ($requested_uid > 0 && $requested_uid !== $current_user_id) {
            if ($is_admin) {
                $target_user_id = $requested_uid;
            } elseif ($is_leader) {
                $my_team = FTM_Permissions::get_user_team($current_user_id);
                $req_team = FTM_Permissions::get_user_team($requested_uid);
                if (strcasecmp($my_team, $req_team) !== 0) {
                    return $this->format_error(__('You can only submit leaves for members in your own squad.', 'fiverr-team-management'), 403);
                }
                $target_user_id = $requested_uid;
            } else {
                $target_user_id = $current_user_id;
            }
        } else {
            $target_user_id = $current_user_id;
        }

        if (!get_userdata($target_user_id)) {
            return $this->format_error(__('Selected team member does not exist.', 'fiverr-team-management'), 404);
        }

        $user_team = FTM_Permissions::get_user_team($target_user_id);
        $request_type = sanitize_text_field($data['request_type'] ?? 'leave'); // 'leave' or 'day_off_switch'
        $leave_type = sanitize_text_field($data['leave_type'] ?? 'casual_leave'); // 'casual_leave', 'sick_leave', 'vacation', 'emergency'
        
        $normalize_date = function($d) {
            if (empty($d) || $d === '0000-00-00') return current_time('Y-m-d');
            $d = trim($d);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                return $d;
            }
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $d, $m)) {
                if ((int)$m[1] > 12) {
                    $ts = strtotime("{$m[3]}-{$m[2]}-{$m[1]}");
                } else {
                    $ts = strtotime("{$m[3]}-{$m[1]}-{$m[2]}");
                }
                if ($ts && $ts > 0) return date('Y-m-d', $ts);
            }
            $ts = strtotime($d);
            return ($ts && $ts > 0) ? date('Y-m-d', $ts) : current_time('Y-m-d');
        };

        $start_date = $normalize_date($data['start_date'] ?? current_time('Y-m-d'));
        $end_date = $normalize_date($data['end_date'] ?? $start_date);
        $reason = sanitize_textarea_field($data['reason'] ?? '');
        $switch_from = !empty($data['switch_from_date']) ? $normalize_date($data['switch_from_date']) : (!empty($data['start_date']) ? $normalize_date($data['start_date']) : null);
        $switch_to = !empty($data['switch_to_date']) ? $normalize_date($data['switch_to_date']) : (!empty($data['end_date']) ? $normalize_date($data['end_date']) : null);

        $is_half_day = in_array($leave_type, ['half_day', 'half_day_morning', 'half_day_evening'], true) || (!empty($data['is_half_day']));

        if ($request_type === 'day_off_switch') {
            if (empty($switch_from) || empty($switch_to)) {
                return $this->format_error(__('Switch from date and switch to date are required for day-off adjustment.', 'fiverr-team-management'), 400);
            }
            $start_date = min($switch_from, $switch_to);
            $end_date = max($switch_from, $switch_to);
            $days_count = 1.0;
        } else {
            if (empty($start_date) || empty($end_date)) {
                return $this->format_error(__('Start date and end date are required for leave notice.', 'fiverr-team-management'), 400);
            }
            if ($is_half_day) {
                $end_date = $start_date;
                $days_count = 0.5;
            } else {
                if ($start_date > $end_date) {
                    return $this->format_error(__('Start date cannot be after end date.', 'fiverr-team-management'), 400);
                }
                try {
                    $start_dt = new DateTime($start_date);
                    $end_dt = new DateTime($end_date);
                    $diff = $start_dt->diff($end_dt);
                    $days_count = (float)($diff->days + 1);
                } catch (Throwable $e) {
                    $days_count = 1.0;
                }
            }
        }

        // Super Admin can pass custom days_count override (e.g., 0.5, 1.5, 2, 0)
        $raw_days = (isset($data['days_count']) && $data['days_count'] !== '') ? $data['days_count'] : ((isset($data['days_override']) && $data['days_override'] !== '') ? $data['days_override'] : null);
        if ($is_admin && $raw_days !== null && is_numeric($raw_days)) {
            $days_count = max(0.0, round((float)$raw_days, 1));
        }

        $table = $wpdb->prefix . 'ftm_leaves';

        // Auto approve by default when Super Admin creates it, or honor explicit status override
        $allowed_statuses = ['pending', 'leader_approved', 'approved', 'rejected'];
        $requested_status = !empty($data['status']) ? sanitize_text_field($data['status']) : (!empty($data['status_override']) ? sanitize_text_field($data['status_override']) : '');
        $initial_status = ($is_admin && in_array($requested_status, $allowed_statuses, true))
            ? $requested_status
            : ($is_admin ? 'approved' : 'pending');

        $leader_feedback = $is_admin && isset($data['leader_feedback']) ? sanitize_textarea_field($data['leader_feedback']) : null;
        $manager_feedback = $is_admin && isset($data['manager_feedback']) ? sanitize_textarea_field($data['manager_feedback']) : null;

        $insert_data = [
            'user_id' => $target_user_id,
            'team_name' => $user_team,
            'request_type' => $request_type,
            'leave_type' => $leave_type,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'days_count' => $days_count,
            'reason' => $reason,
            'switch_from_date' => $switch_from,
            'switch_to_date' => $switch_to,
            'status' => $initial_status,
            'leader_feedback' => $leader_feedback,
            'manager_feedback' => $manager_feedback,
            'created_at' => current_time('mysql')
        ];

        if ($is_admin && in_array($initial_status, ['leader_approved', 'approved'], true)) {
            $insert_data['leader_id'] = $current_user_id;
            $insert_data['leader_approved_at'] = current_time('mysql');
        }
        if ($is_admin && in_array($initial_status, ['approved', 'rejected'], true)) {
            $insert_data['manager_id'] = $current_user_id;
            if ($initial_status === 'approved') {
                $insert_data['manager_approved_at'] = current_time('mysql');
            }
        }

        $wpdb->insert($table, $insert_data);
        $leave_id = $wpdb->insert_id;

        // If approved directly by admin, sync into daily reports & attendance
        if ($initial_status === 'approved') {
            $this->sync_approved_leave_records($leave_id);
        }

        // Notify leader and admin only if pending/awaiting review
        $user_obj = get_userdata($target_user_id);
        $member_name = $user_obj ? $user_obj->display_name : 'Team Member';
        $type_label = ($request_type === 'day_off_switch') ? 'Day-Off Switch Request' : 'Leave Notice (' . ucwords(str_replace('_', ' ', $leave_type)) . ')';

        if (class_exists('FTM_Notifications_Engine') && in_array($initial_status, ['pending', 'leader_approved'], true)) {
            FTM_Notifications_Engine::broadcast_to_leadership([
                'title' => "📋 New {$type_label}",
                'message' => "{$member_name} submitted {$type_label} for {$start_date}" . ($start_date !== $end_date ? " to {$end_date}" : "") . ". Pending review.",
                'type' => 'leave_request',
                'target_id' => $leave_id,
                'team' => $user_team
            ]);
        }

        $success_msg = ($initial_status === 'approved')
            ? __('Leave / Day-Off entry saved and approved immediately.', 'fiverr-team-management')
            : __('Leave / Day-Off application submitted successfully. Forwarded for verification.', 'fiverr-team-management');

        return $this->format_success([
            'id' => $leave_id,
            'user_id' => $target_user_id,
            'status' => $initial_status,
            'days_count' => $days_count
        ], $success_msg);
    }

    /**
     * POST /leaves/{id}/review (Two-stage review: Leader -> Manager)
     */
    public function review_leave($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $current_user_id = get_current_user_id();

        $table = $wpdb->prefix . 'ftm_leaves';
        $leave = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

        if (!$leave) {
            return $this->format_error(__('Leave application not found.', 'fiverr-team-management'), 404);
        }

        $data = $request->get_json_params() ?: $request->get_params();
        $action = sanitize_text_field($data['action'] ?? 'leader_approve'); // 'leader_approve', 'approve', 'reject'
        $feedback = sanitize_textarea_field($data['feedback'] ?? '');

        if (FTM_Permissions::is_deputy($current_user_id)) {
            return $this->format_error(__('Deputy Manager has read-only access.', 'fiverr-team-management'), 403);
        }

        $is_admin = FTM_Permissions::is_super_admin($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id);

        if (!$is_admin && !$is_leader) {
            return $this->format_error(__('Unauthorized to review leaves.', 'fiverr-team-management'), 403);
        }

        if (!$is_admin) {
            $user_team = FTM_Permissions::get_user_team($current_user_id);
            if (strcasecmp($user_team, $leave->team_name) !== 0) {
                return $this->format_error(__('You can only review leaves for your own team members.', 'fiverr-team-management'), 403);
            }
        }

        $update_data = [];

        if ($action === 'leader_approve') {
            $update_data['status'] = 'leader_approved';
            $update_data['leader_id'] = $current_user_id;
            $update_data['leader_approved_at'] = current_time('mysql');
            $update_data['leader_feedback'] = $feedback;
            $msg = __('Leave verified by Team Leader and forwarded to Executive Manager for final approval.', 'fiverr-team-management');
        } elseif ($action === 'approve') {
            $update_data['status'] = 'approved';
            $update_data['manager_id'] = $current_user_id;
            $update_data['manager_approved_at'] = current_time('mysql');
            $update_data['manager_feedback'] = $feedback;
            // Also backfill leader if skipped
            if (empty($leave->leader_id)) {
                $update_data['leader_id'] = $current_user_id;
                $update_data['leader_approved_at'] = current_time('mysql');
            }
            $msg = __('Leave / Day-Off application given final approval! Attendance updated.', 'fiverr-team-management');
        } elseif ($action === 'reject') {
            $update_data['status'] = 'rejected';
            if ($is_admin) {
                $update_data['manager_id'] = $current_user_id;
                $update_data['manager_feedback'] = $feedback;
            } else {
                $update_data['leader_id'] = $current_user_id;
                $update_data['leader_feedback'] = $feedback;
            }
            $msg = __('Leave / Day-Off application rejected.', 'fiverr-team-management');
        } else {
            return $this->format_error(__('Invalid review action.', 'fiverr-team-management'), 400);
        }

        $wpdb->update($table, $update_data, ['id' => $id]);

        $new_status = $update_data['status'] ?? $leave->status;
        if ($leave->status === 'approved' && $new_status !== 'approved') {
            $this->purge_leave_synced_records($leave);
        }

        // When approved, sync into daily reports & attendance
        if ($new_status === 'approved') {
            $this->sync_approved_leave_records($id);
        }

        return $this->format_success([
            'id' => $id,
            'status' => $new_status
        ], $msg);
    }

    /**
     * Sync approved leave dates into daily reports and attendance tables
     */
    private function sync_approved_leave_records($leave_id) {
        global $wpdb;
        $table_leaves = $wpdb->prefix . 'ftm_leaves';
        $l = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_leaves WHERE id = %d", $leave_id));
        if (!$l) return;

        $reports_table = $wpdb->prefix . 'ftm_daily_reports';
        $items_table = $wpdb->prefix . 'ftm_daily_report_items';
        $att_table = $wpdb->prefix . 'ftm_attendance';

        if ($l->request_type === 'day_off_switch') {
            // Day-off switch: the switched date becomes day_off, original date becomes normal working day
            if (!empty($l->switch_to_date)) {
                // Record attendance as day_off for switched date
                $wpdb->replace($att_table, [
                    'user_id' => $l->user_id,
                    'team_name' => $l->team_name,
                    'attendance_date' => $l->switch_to_date,
                    'status' => 'day_off',
                    'notes' => 'Switched Day Off: ' . $l->reason,
                    'marked_by' => $l->manager_id ?: $l->leader_id,
                    'marked_at' => current_time('mysql')
                ]);
            }
            return;
        }

        // Leave dates loop safely guarded against invalid date formats
        $start_clean = $this->clean_valid_date($l->start_date);
        $end_clean = $this->clean_valid_date($l->end_date);
        if ($start_clean > $end_clean) {
            $end_clean = $start_clean;
        }

        try {
            $cur = new DateTime($start_clean);
            $end = new DateTime($end_clean);
            $end->modify('+1 day');
            $interval = new DateInterval('P1D');
            $period = new DatePeriod($cur, $interval, $end);
        } catch (Throwable $e) {
            error_log('FTM Leaves date period error: ' . $e->getMessage());
            $period = [];
        }

        foreach ($period as $dt) {
            $date_str = $dt->format('Y-m-d');

            // 1. Attendance table
            $wpdb->replace($att_table, [
                'user_id' => $l->user_id,
                'team_name' => $l->team_name,
                'attendance_date' => $date_str,
                'status' => 'on_leave',
                'notes' => 'Approved Leave: ' . $l->leave_type . ' (' . $l->reason . ')',
                'marked_by' => $l->manager_id ?: $l->leader_id,
                'marked_at' => current_time('mysql')
            ]);

            // 2. Daily reports table sync
            $existing_rep = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $reports_table WHERE user_id = %d AND report_date = %s",
                $l->user_id, $date_str
            ));

            $rep_payload = [
                'user_id' => $l->user_id,
                'team_name' => $l->team_name,
                'report_type' => 'on_leave',
                'leave_start_date' => $l->start_date,
                'leave_end_date' => $l->end_date,
                'leave_type' => $l->leave_type,
                'leave_reason' => $l->reason,
                'report_date' => $date_str,
                'status' => 'approved',
                'reviewed_by' => $l->manager_id ?: $l->leader_id,
                'reviewed_at' => current_time('mysql'),
                'leader_feedback' => 'Approved leave request #' . $l->id
            ];

            if ($existing_rep) {
                $wpdb->update($reports_table, $rep_payload, ['id' => $existing_rep->id]);
                $r_id = $existing_rep->id;
            } else {
                $wpdb->insert($reports_table, $rep_payload);
                $r_id = $wpdb->insert_id;
            }

            // Sync item
            $wpdb->delete($items_table, ['report_id' => $r_id]);
            $wpdb->insert($items_table, [
                'report_id' => $r_id,
                'project_id' => 0,
                'order_id' => 'LEAVE-' . $l->id,
                'client_name' => 'Leave: ' . ucwords(str_replace('_', ' ', $l->leave_type)),
                'tasks_completed' => 'On Leave: ' . (!empty($l->reason) ? $l->reason : 'Approved leave'),
                'work_in_progress' => '',
                'next_day_plan' => '',
                'hours_spent' => 0.00,
                'progress_percent' => 100,
                'created_at' => current_time('mysql')
            ]);
        }
    }

    private function clean_valid_date($d) {
        if (empty($d) || $d === '0000-00-00') {
            return current_time('Y-m-d');
        }
        $ts = strtotime($d);
        return ($ts && $ts > 0) ? date('Y-m-d', $ts) : current_time('Y-m-d');
    }

    /**
     * GET /leaves/{id}
     */
    public function get_single_leave($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $table = $wpdb->prefix . 'ftm_leaves';
        $leave = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if (!$leave) {
            return $this->format_error(__('Leave application not found.', 'fiverr-team-management'), 404);
        }
        return $this->format_success($this->format_leave_item($leave));
    }

    /**
     * DELETE /leaves/{id}
     */
    public function delete_leave($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $current_user_id = get_current_user_id();

        $table = $wpdb->prefix . 'ftm_leaves';
        $leave = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

        if (!$leave) {
            return $this->format_error(__('Leave application not found.', 'fiverr-team-management'), 404);
        }

        $is_admin = FTM_Permissions::is_super_admin($current_user_id) || FTM_Permissions::is_executive($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id) && FTM_Permissions::can_access_team($leave->team_name, $current_user_id);
        $is_owner = ((int)$leave->user_id === $current_user_id);

        if (!$is_admin && !$is_leader && !$is_owner) {
            return $this->format_error(__('Unauthorized to cancel or delete this leave application.', 'fiverr-team-management'), 403);
        }

        if ($leave->status === 'approved' && !$is_admin && !$is_leader) {
            return $this->format_error(__('Approved leaves can only be deleted or cancelled by Leadership or Executive Management.', 'fiverr-team-management'), 400);
        }

        $this->purge_leave_synced_records($leave);

        // Delete the leave record
        $wpdb->delete($table, ['id' => $id]);

        return $this->format_success([
            'id' => $id,
            'deleted' => true
        ], __('Leave application and all associated records deleted successfully.', 'fiverr-team-management'));
    }

    /**
     * Remove synced daily reports and attendance overrides created for a leave/day-off entry.
     */
    private function purge_leave_synced_records($leave) {
        global $wpdb;
        if (!$leave) return;

        $id = (int)$leave->id;
        $reports_table = $wpdb->prefix . 'ftm_daily_reports';
        $items_table = $wpdb->prefix . 'ftm_daily_report_items';
        $att_table = $wpdb->prefix . 'ftm_attendance';

        // 1. Delete daily reports synced via order_id or report_type=on_leave in this range
        $synced_reps = $wpdb->get_results($wpdb->prepare(
            "SELECT r.id FROM $reports_table r
             LEFT JOIN $items_table i ON i.report_id = r.id
             WHERE r.user_id = %d AND (i.order_id = %s OR (r.report_type = 'on_leave' AND r.report_date BETWEEN %s AND %s))",
            $leave->user_id, 'LEAVE-' . $id, $leave->start_date, $leave->end_date
        ));

        if (!empty($synced_reps)) {
            $rep_ids = array_unique(array_map(function($r) { return (int)$r->id; }, $synced_reps));
            foreach ($rep_ids as $rid) {
                $wpdb->delete($items_table, ['report_id' => $rid]);
                $wpdb->delete($reports_table, ['id' => $rid]);
            }
        }

        // 2. Clean up any remaining on_leave daily reports for non-switch leaves in this range
        if ($leave->request_type !== 'day_off_switch' && !empty($leave->start_date) && !empty($leave->end_date)) {
            $other_reps = $wpdb->get_results($wpdb->prepare(
                "SELECT id FROM $reports_table WHERE user_id = %d AND report_type = 'on_leave' AND report_date BETWEEN %s AND %s",
                $leave->user_id, $leave->start_date, $leave->end_date
            ));
            if (!empty($other_reps)) {
                foreach ($other_reps as $or) {
                    $wpdb->delete($items_table, ['report_id' => (int)$or->id]);
                    $wpdb->delete($reports_table, ['id' => (int)$or->id]);
                }
            }
        }

        // 3. Clean up attendance records marked by this leave or day-off switch
        if ($leave->request_type === 'day_off_switch') {
            if (!empty($leave->switch_to_date)) {
                $wpdb->delete($att_table, [
                    'user_id' => $leave->user_id,
                    'attendance_date' => $leave->switch_to_date
                ]);
            }
        } else {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $att_table WHERE user_id = %d AND attendance_date BETWEEN %s AND %s AND status = 'on_leave'",
                $leave->user_id, $leave->start_date, $leave->end_date
            ));
        }
    }

    /**
     * POST /leaves/{id} or PUT /leaves/{id}
     */
    public function update_leave($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $current_user_id = get_current_user_id();

        $table = $wpdb->prefix . 'ftm_leaves';
        $leave = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

        if (!$leave) {
            return $this->format_error(__('Leave application not found.', 'fiverr-team-management'), 404);
        }

        $is_admin = FTM_Permissions::is_super_admin($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id) && FTM_Permissions::can_access_team($leave->team_name, $current_user_id);
        $is_owner = ((int)$leave->user_id === $current_user_id);

        if (!$is_admin && !$is_leader && !$is_owner) {
            return $this->format_error(__('Unauthorized to edit this leave application.', 'fiverr-team-management'), 403);
        }

        $data = $request->get_json_params() ?: $request->get_params();
        $update_payload = [];

        // Super Admin can reassign the leave/adjustment to any member
        if ($is_admin && !empty($data['user_id']) && (int)$data['user_id'] !== (int)$leave->user_id) {
            $new_uid = (int)$data['user_id'];
            if (!get_userdata($new_uid)) {
                return $this->format_error(__('Selected team member does not exist.', 'fiverr-team-management'), 404);
            }
            $update_payload['user_id'] = $new_uid;
            $update_payload['team_name'] = FTM_Permissions::get_user_team($new_uid);
        }

        if (isset($data['request_type'])) {
            $rt = sanitize_text_field($data['request_type']);
            if (in_array($rt, ['leave', 'day_off_switch'], true)) {
                $update_payload['request_type'] = $rt;
            }
        }
        if (isset($data['leave_type'])) {
            $update_payload['leave_type'] = sanitize_text_field($data['leave_type']);
        }
        if (isset($data['reason'])) {
            $update_payload['reason'] = sanitize_textarea_field($data['reason']);
        }
        if (($is_admin || $is_leader) && isset($data['leader_feedback'])) {
            $update_payload['leader_feedback'] = sanitize_textarea_field($data['leader_feedback']);
        }
        if ($is_admin && isset($data['manager_feedback'])) {
            $update_payload['manager_feedback'] = sanitize_textarea_field($data['manager_feedback']);
        }
        if (isset($data['start_date']) && $data['start_date'] !== '') {
            $update_payload['start_date'] = $this->clean_valid_date($data['start_date']);
        }
        if (isset($data['end_date']) && $data['end_date'] !== '') {
            $update_payload['end_date'] = $this->clean_valid_date($data['end_date']);
        }
        if (isset($data['switch_from_date'])) {
            $update_payload['switch_from_date'] = !empty($data['switch_from_date']) ? $this->clean_valid_date($data['switch_from_date']) : null;
        }
        if (isset($data['switch_to_date'])) {
            $update_payload['switch_to_date'] = !empty($data['switch_to_date']) ? $this->clean_valid_date($data['switch_to_date']) : null;
        }

        // Super Admin can adjust approval status directly
        if ($is_admin && isset($data['status'])) {
            $new_st = sanitize_text_field($data['status']);
            if (in_array($new_st, ['pending', 'leader_approved', 'approved', 'rejected'], true)) {
                $update_payload['status'] = $new_st;
                if ($new_st === 'approved') {
                    $update_payload['manager_id'] = $current_user_id;
                    $update_payload['manager_approved_at'] = current_time('mysql');
                    if (empty($leave->leader_id)) {
                        $update_payload['leader_id'] = $current_user_id;
                        $update_payload['leader_approved_at'] = current_time('mysql');
                    }
                } elseif ($new_st === 'leader_approved') {
                    $update_payload['leader_id'] = $current_user_id;
                    $update_payload['leader_approved_at'] = current_time('mysql');
                }
            }
        }

        $effective_req_type = $update_payload['request_type'] ?? $leave->request_type;
        $effective_leave_type = $update_payload['leave_type'] ?? $leave->leave_type;

        if ($effective_req_type === 'day_off_switch') {
            $sf = $update_payload['switch_from_date'] ?? $leave->switch_from_date;
            $st_d = $update_payload['switch_to_date'] ?? $leave->switch_to_date;
            if (!empty($sf) && !empty($st_d)) {
                $update_payload['start_date'] = min($sf, $st_d);
                $update_payload['end_date'] = max($sf, $st_d);
            }
            $update_payload['days_count'] = 1.0;
        } else {
            $st = $update_payload['start_date'] ?? $leave->start_date;
            $en = $update_payload['end_date'] ?? $leave->end_date;
            $is_half = !empty($data['is_half_day']) || strpos((string)$effective_leave_type, 'half_day') === 0;
            if ($is_half && !empty($st)) {
                $update_payload['end_date'] = $st;
                $en = $st;
            }
            if (!empty($st) && !empty($en)) {
                if ($st > $en) {
                    return $this->format_error(__('Start date cannot be after end date.', 'fiverr-team-management'), 400);
                }
                $ts1 = strtotime($st);
                $ts2 = strtotime($en);
                $diff = max(0, round(($ts2 - $ts1) / 86400)) + 1;
                if ($is_half) {
                    $diff = 0.5;
                }
                $update_payload['days_count'] = (float)$diff;
            }
        }

        // Allow Super Admin explicit custom days_count override
        if ($is_admin && isset($data['days_count']) && $data['days_count'] !== '' && is_numeric($data['days_count'])) {
            $update_payload['days_count'] = max(0.0, round((float)$data['days_count'], 1));
        }

        if (empty($update_payload)) {
            return $this->format_error(__('No update fields provided.', 'fiverr-team-management'), 400);
        }

        $update_payload['updated_at'] = current_time('mysql');

        $wpdb->update($table, $update_payload, ['id' => $id]);

        $new_status = $update_payload['status'] ?? $leave->status;

        // If previously approved, always clean old synced attendance & daily reports first
        if ($leave->status === 'approved') {
            $this->purge_leave_synced_records($leave);
        }

        // Sync fresh records whenever final status is approved
        if ($new_status === 'approved') {
            $this->sync_approved_leave_records($id);
        }

        $updated_leave = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

        return $this->format_success(
            $this->format_leave_item($updated_leave),
            __('Leave application updated successfully.', 'fiverr-team-management')
        );
    }

    /**
     * GET /attendance (Daily Roster for Leaders & Managers)
     */
    public function get_attendance($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $target_date = !empty($request['date']) ? sanitize_text_field($request['date']) : current_time('Y-m-d');
        $team_filter = !empty($request['team']) ? sanitize_text_field($request['team']) : 'all';

        if (!FTM_Permissions::is_super_admin($current_user_id) && !FTM_Permissions::is_deputy($current_user_id)) {
            $team_filter = FTM_Permissions::get_user_team($current_user_id);
        }

        // All working members
        $all_members = FTM_Permissions::get_team_members($team_filter);

        $working_members = array_filter($all_members, function($m) {
            $uid = (int)($m['id'] ?? 0);
            if ($uid === 1 || ($m['custom_id'] ?? '') === '0001' || ($m['custom_id'] ?? '') === '#1') {
                return true;
            }
            $t = $m['team'] ?? '';
            $role = strtolower($m['role_label'] ?? '');
            return !FTM_Permissions::is_management_team($t) && !str_contains($role, 'admin') && ($m['team_role'] ?? '') !== 'super_admin';
        });

        $day_of_week = strtolower(date('l', strtotime($target_date))); // 'friday', 'saturday', etc.
        $now_time = current_time('H:i');
        $is_past_report_cutoff = ($target_date === current_time('Y-m-d')) ? ($now_time > '18:15') : ($target_date < current_time('Y-m-d'));
        $is_past_late_cutoff = ($target_date === current_time('Y-m-d')) ? ($now_time > '09:15') : ($target_date < current_time('Y-m-d'));

        // Load daily reports for date
        $reports_table = $wpdb->prefix . 'ftm_daily_reports';
        $items_table = $wpdb->prefix . 'ftm_daily_report_items';
        $daily_reps = $wpdb->get_results($wpdb->prepare("SELECT * FROM $reports_table WHERE report_date = %s", $target_date));
        $rep_by_user = [];
        foreach ($daily_reps as $dr) {
            $rep_by_user[(int)$dr->user_id] = $dr;
        }

        // Load manual attendance overrides for date
        $att_table = $wpdb->prefix . 'ftm_attendance';
        $att_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $att_table WHERE attendance_date = %s", $target_date));
        $att_by_user = [];
        foreach ($att_rows as $ar) {
            $att_by_user[(int)$ar->user_id] = $ar;
        }

        // Load approved leaves for date
        $leaves_table = $wpdb->prefix . 'ftm_leaves';
        $leaves_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $leaves_table WHERE status = 'approved' AND start_date <= %s AND end_date >= %s",
            $target_date, $target_date
        ));
        $leaves_by_user = [];
        foreach ($leaves_rows as $lr) {
            $leaves_by_user[(int)$lr->user_id] = $lr;
        }

        $roster = [];
        $kpis = [
            'total' => count($working_members),
            'present' => 0,
            'home_office' => 0,
            'working' => 0,
            'on_leave' => 0,
            'day_off' => 0,
            'late' => 0,
            'absent' => 0
        ];

        foreach ($working_members as $m) {
            $uid = (int)$m['id'];
            $weekly_day_off = get_user_meta($uid, 'ftm_day_off', true);
            if (empty($weekly_day_off)) {
                // Default Friday or Sunday based on team
                $weekly_day_off = ($m['team'] === 'WpCraft') ? 'friday' : 'sunday';
            }

            $rep = $rep_by_user[$uid] ?? null;
            $att = $att_by_user[$uid] ?? null;
            $leave = $leaves_by_user[$uid] ?? null;

            // Determine status
            $status = 'working';
            $status_label = 'Working (In Progress)';
            $notes = '';

            if ($att && !empty($att->status)) {
                // Manual override by Leader/Manager takes highest precedence
                $status = $att->status;
                if ($status === 'home_office') {
                    $status_label = 'Home Office';
                } else {
                    $status_label = ucwords(str_replace('_', ' ', $status));
                }
                $notes = $att->notes ?: 'Manually marked';
            } elseif ($leave) {
                $status = ($leave->request_type === 'day_off_switch') ? 'day_off' : 'on_leave';
                $status_label = ($leave->request_type === 'day_off_switch') ? 'Day Off (Switched)' : 'On Leave (' . ucwords(str_replace('_', ' ', $leave->leave_type)) . ')';
                $notes = $leave->reason;
            } elseif (strtolower($weekly_day_off) === $day_of_week) {
                $status = 'day_off';
                $status_label = 'Weekly Day Off';
            } elseif ($rep) {
                if ($rep->report_type === 'on_leave') {
                    $status = 'on_leave';
                    $status_label = 'On Leave';
                    $notes = $rep->leave_reason;
                } elseif ($rep->report_type === 'weekend') {
                    $status = 'day_off';
                    $status_label = 'Weekend / Off Day';
                } elseif ($rep->report_type === 'home_office') {
                    $status = 'home_office';
                    $status_label = ($rep->is_late == 1) ? 'Home Office (Logged after 9:15 AM)' : 'Home Office';
                } else {
                    $status = ($rep->is_late == 1) ? 'late' : 'present';
                    $status_label = ($rep->is_late == 1) ? 'Late (Submitted after 9:15 AM)' : 'Present (Report Submitted)';
                }
            } else {
                // No report, no leave, no day off
                if ($is_past_report_cutoff) {
                    $status = 'absent';
                    $status_label = 'Absent / Unreported (Past 6:15 PM)';
                } else {
                    $status = 'working';
                    $status_label = 'Working (Report Due by 6:15 PM)';
                }
            }

            // Count KPIs
            if ($status === 'present') $kpis['present']++;
            elseif ($status === 'home_office') $kpis['home_office']++;
            elseif ($status === 'working') $kpis['working']++;
            elseif ($status === 'on_leave') $kpis['on_leave']++;
            elseif ($status === 'day_off') $kpis['day_off']++;
            elseif ($status === 'late') $kpis['late']++;
            elseif ($status === 'absent') $kpis['absent']++;

            $roster[] = [
                'user_id' => $uid,
                'custom_id' => get_user_meta($uid, 'ftm_custom_id', true) ?: (get_user_meta($uid, 'ftm_member_id', true) ?: ('#' . $uid)),
                'name' => $m['name'],
                'avatar' => $m['avatar'],
                'team' => $m['team'],
                'role_label' => $m['role_label'],
                'weekly_day_off' => ucfirst($weekly_day_off),
                'status' => $status,
                'status_label' => $status_label,
                'notes' => $notes,
                'has_report' => !empty($rep),
                'report_id' => $rep ? (int)$rep->id : null,
                'report_status' => $rep ? $rep->status : null,
                'submission_time' => $rep ? $rep->submission_time : null,
                'is_late' => $rep ? (int)$rep->is_late : ($status === 'late' || $status === 'absent' ? 1 : 0)
            ];
        }

        return rest_ensure_response([
            'date' => $target_date,
            'day_of_week' => ucfirst($day_of_week),
            'is_past_cutoff' => $is_past_report_cutoff,
            'is_past_late_cutoff' => $is_past_late_cutoff,
            'cutoff_time' => '18:15',
            'late_cutoff_time' => '09:15',
            'kpis' => $kpis,
            'roster' => $roster
        ]);
    }

    /**
     * POST /attendance/mark (Manual Attendance Override)
     */
    public function mark_attendance($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        if (FTM_Permissions::is_deputy($current_user_id)) {
            return $this->format_error(__('Deputy Manager has read-only access.', 'fiverr-team-management'), 403);
        }
        if (!FTM_Permissions::is_super_admin($current_user_id) && !FTM_Permissions::is_leader($current_user_id)) {
            return $this->format_error(__('Only Team Leaders and Management can mark attendance.', 'fiverr-team-management'), 403);
        }

        $data = $request->get_json_params() ?: $request->get_params();

        $user_id = (int)($data['user_id'] ?? 0);
        $date = sanitize_text_field($data['date'] ?? current_time('Y-m-d'));
        $status = sanitize_text_field($data['status'] ?? 'present'); // 'present', 'late', 'absent', 'on_leave', 'day_off'
        $notes = sanitize_text_field($data['notes'] ?? '');

        if (!$user_id) {
            return $this->format_error(__('User ID is required.', 'fiverr-team-management'), 400);
        }

        $user_team = FTM_Permissions::get_user_team($user_id);
        if (!FTM_Permissions::is_super_admin($current_user_id)) {
            $my_team = FTM_Permissions::get_user_team($current_user_id);
            if (strcasecmp($my_team, $user_team) !== 0) {
                return $this->format_error(__('You can only mark attendance for your own team members.', 'fiverr-team-management'), 403);
            }
        }
        $att_table = $wpdb->prefix . 'ftm_attendance';

        if (in_array($status, ['auto', 'reset', 'clear'], true)) {
            $wpdb->delete($att_table, [
                'user_id' => $user_id,
                'attendance_date' => $date
            ]);

            return $this->format_success([
                'user_id' => $user_id,
                'date' => $date,
                'status' => 'auto'
            ], sprintf(__('Manual attendance override cleared for %s.', 'fiverr-team-management'), $date));
        }

        $wpdb->replace($att_table, [
            'user_id' => $user_id,
            'team_name' => $user_team,
            'attendance_date' => $date,
            'status' => $status,
            'notes' => $notes,
            'marked_by' => $current_user_id,
            'marked_at' => current_time('mysql')
        ]);

        return $this->format_success([
            'user_id' => $user_id,
            'date' => $date,
            'status' => $status
        ], sprintf(__('Attendance marked as %s for %s.', 'fiverr-team-management'), ucfirst(str_replace('_', ' ', $status)), $date));
    }

    /**
     * POST /members/{id}/leave-quota (Super Admin: adjust member annual quota, balance adjustment, and weekly day-off)
     */
    public function set_member_leave_quota($request) {
        $current_user_id = get_current_user_id();
        if (FTM_Permissions::is_deputy($current_user_id) || !FTM_Permissions::is_super_admin($current_user_id)) {
            return $this->format_error(__('Only Super Admin can adjust member leave quotas and balances.', 'fiverr-team-management'), 403);
        }

        $user_id = (int)$request['id'];
        if (!get_userdata($user_id)) {
            return $this->format_error(__('Team member not found.', 'fiverr-team-management'), 404);
        }

        $data = $request->get_json_params() ?: $request->get_params();

        if (isset($data['annual_quota']) && is_numeric($data['annual_quota'])) {
            $quota = max(0.0, round((float)$data['annual_quota'], 1));
            update_user_meta($user_id, 'ftm_annual_leave_quota', $quota);
        }

        if (isset($data['adjustment_days']) && is_numeric($data['adjustment_days'])) {
            $adj = round((float)$data['adjustment_days'], 1);
            update_user_meta($user_id, 'ftm_leave_balance_adjustment', $adj);
        }

        if (isset($data['quota_note'])) {
            update_user_meta($user_id, 'ftm_leave_quota_note', sanitize_textarea_field($data['quota_note']));
        }

        if (!empty($data['day_off'])) {
            $day_off = strtolower(sanitize_text_field($data['day_off']));
            $allowed_days = ['friday', 'saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday'];
            if (in_array($day_off, $allowed_days, true)) {
                update_user_meta($user_id, 'ftm_day_off', $day_off);
            }
        }

        $saved_quota = get_user_meta($user_id, 'ftm_annual_leave_quota', true);
        $saved_adj = get_user_meta($user_id, 'ftm_leave_balance_adjustment', true);
        $saved_day = get_user_meta($user_id, 'ftm_day_off', true);

        return $this->format_success([
            'user_id' => $user_id,
            'annual_quota' => $saved_quota !== '' ? (float)$saved_quota : 18.0,
            'adjustment_days' => $saved_adj !== '' ? (float)$saved_adj : 0.0,
            'quota_note' => (string)get_user_meta($user_id, 'ftm_leave_quota_note', true),
            'day_off' => $saved_day ? ucfirst($saved_day) : 'Friday',
        ], __('Member leave quota & balance settings updated successfully.', 'fiverr-team-management'));
    }

    /**
     * POST /members/{id}/day-off
     */
    public function set_member_day_off($request) {
        $current_user_id = get_current_user_id();
        if (FTM_Permissions::is_deputy($current_user_id)) {
            return $this->format_error(__('Deputy Manager has read-only access.', 'fiverr-team-management'), 403);
        }
        if (!FTM_Permissions::is_super_admin($current_user_id) && !FTM_Permissions::is_leader($current_user_id)) {
            return $this->format_error(__('Only Team Leaders and Management can change day offs.', 'fiverr-team-management'), 403);
        }

        $user_id = (int)$request['id'];
        if (!FTM_Permissions::is_super_admin($current_user_id)) {
            $my_team = FTM_Permissions::get_user_team($current_user_id);
            $target_team = FTM_Permissions::get_user_team($user_id);
            if (strcasecmp($my_team, $target_team) !== 0) {
                return $this->format_error(__('You can only change day offs for your own team members.', 'fiverr-team-management'), 403);
            }
        }

        $data = $request->get_json_params() ?: $request->get_params();
        $day_off = strtolower(sanitize_text_field($data['day_off'] ?? 'friday'));

        $allowed_days = ['friday', 'saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday'];
        if (!in_array($day_off, $allowed_days, true)) {
            return $this->format_error(__('Invalid day off specified.', 'fiverr-team-management'), 400);
        }

        update_user_meta($user_id, 'ftm_day_off', $day_off);

        return $this->format_success([
            'user_id' => $user_id,
            'day_off' => ucfirst($day_off)
        ], sprintf(__('Weekly day-off set to %s.', 'fiverr-team-management'), ucfirst($day_off)));
    }

    /**
     * GET /attendance/export (CSV export)
     */
    public function export_attendance_csv($request) {
        $target_date = !empty($request['date']) ? sanitize_text_field($request['date']) : current_time('Y-m-d');
        $team_filter = !empty($request['team']) ? sanitize_text_field($request['team']) : 'all';

        $res = $this->get_attendance($request);
        $data = $res->get_data();
        $roster = $data['roster'] ?? [];

        $filename = "attendance-report-{$target_date}.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        $output = fopen('php://output', 'w');
        // BOM for Excel utf-8
        fputs($output, "\xEF\xBB\xBF");

        fputcsv($output, ['Member ID', 'Specialist Name', 'Team', 'Role', 'Weekly Day-Off', 'Date', 'Attendance Status', 'Late Submission (Past 9:15 AM)', 'Notes / Leave Reason']);

        foreach ($roster as $r) {
            fputcsv($output, [
                $r['custom_id'] ?? ('#' . $r['user_id']),
                $r['name'],
                $r['team'],
                $r['role_label'],
                $r['weekly_day_off'],
                $target_date,
                $r['status_label'],
                ($r['is_late'] ? 'YES' : 'NO'),
                $r['notes']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Format leave item for REST response
     */
    public function format_leave_item($row) {
        $user_obj = get_userdata($row->user_id);
        $leader_obj = !empty($row->leader_id) ? get_userdata($row->leader_id) : null;
        $manager_obj = !empty($row->manager_id) ? get_userdata($row->manager_id) : null;

        $start_ts = (!empty($row->start_date) && $row->start_date !== '0000-00-00') ? strtotime($row->start_date) : false;
        $end_ts = (!empty($row->end_date) && $row->end_date !== '0000-00-00') ? strtotime($row->end_date) : false;

        $leave_label = '';
        if ($row->leave_type === 'half_day_morning') {
            $leave_label = 'Half Day (Morning)';
        } elseif ($row->leave_type === 'half_day_evening') {
            $leave_label = 'Half Day (Evening)';
        } elseif ($row->leave_type === 'half_day') {
            $leave_label = 'Half Day (0.5d)';
        } elseif (!empty($row->leave_type)) {
            $leave_label = ucwords(str_replace('_', ' ', $row->leave_type));
        }

        return [
            'id' => (int)$row->id,
            'user_id' => (int)$row->user_id,
            'user_name' => $user_obj ? $user_obj->display_name : 'Specialist',
            'user_avatar' => get_avatar_url($row->user_id, ['size' => 64]),
            'team_name' => $row->team_name,
            'request_type' => $row->request_type,
            'request_type_label' => ($row->request_type === 'day_off_switch') ? 'Day-Off Switch' : 'Leave Notice',
            'leave_type' => $row->leave_type,
            'leave_type_label' => $leave_label,
            'start_date' => $row->start_date,
            'start_date_formatted' => $start_ts ? date('M j, Y', $start_ts) : '-',
            'end_date' => $row->end_date,
            'end_date_formatted' => $end_ts ? date('M j, Y', $end_ts) : '-',
            'days_count' => (float)$row->days_count,
            'reason' => $row->reason,
            'switch_from_date' => $row->switch_from_date,
            'switch_to_date' => $row->switch_to_date,
            'status' => $row->status,
            'status_label' => $this->get_leave_status_label($row->status),
            'leader_status' => ($row->status === 'leader_approved' || $row->status === 'approved') ? 'Verified' : ($row->status === 'rejected' ? 'Rejected' : 'Pending'),
            'manager_status' => ($row->status === 'approved') ? 'Approved' : ($row->status === 'rejected' ? 'Rejected' : 'Pending'),
            'leader_id' => !empty($row->leader_id) ? (int)$row->leader_id : null,
            'leader_name' => $leader_obj ? $leader_obj->display_name : '',
            'leader_approved_at' => $row->leader_approved_at,
            'leader_feedback' => $row->leader_feedback,
            'manager_id' => !empty($row->manager_id) ? (int)$row->manager_id : null,
            'manager_name' => $manager_obj ? $manager_obj->display_name : '',
            'manager_approved_at' => $row->manager_approved_at,
            'manager_feedback' => $row->manager_feedback,
            'created_at' => $row->created_at,
            'created_at_formatted' => date('M j, Y g:i A', strtotime($row->created_at))
        ];
    }

    /**
     * GET /leaves/report
     * Comprehensive Leave Management Report & Balance Tracking
     */
    public function get_leaves_report($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $is_admin = FTM_Permissions::is_super_admin($current_user_id) || FTM_Permissions::is_executive($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id);
        $is_deputy = FTM_Permissions::is_deputy($current_user_id);
        $user_team = FTM_Permissions::get_user_team($current_user_id);

        $month = sanitize_text_field($request->get_param('month') ?: date('Y-m'));
        $year = sanitize_text_field($request->get_param('year') ?: substr($month, 0, 4));
        $team_filter = sanitize_text_field($request->get_param('team') ?: 'all');
        $user_filter = (int)$request->get_param('user_id');

        if (!$is_admin && !$is_deputy && !$is_leader) {
            $user_filter = $current_user_id;
            $team_filter = $user_team;
        } elseif ($is_leader && !$is_admin && !$is_deputy) {
            $team_filter = $user_team;
        }

        $table = $wpdb->prefix . 'ftm_leaves';

        // 1. Get members for matrix
        $members = FTM_Permissions::get_team_members($team_filter);
        if ($user_filter > 0) {
            $members = array_filter($members, function($m) use ($user_filter) {
                return (int)($m['id'] ?? 0) === $user_filter;
            });
        }

        // 2. Fetch leaves for the whole year to compute year-to-date quotas
        $where_year = ["1=1"];
        $params_year = [];
        if ($team_filter !== 'all') {
            $where_year[] = "team_name = %s";
            $params_year[] = $team_filter;
        }
        if ($user_filter > 0) {
            $where_year[] = "user_id = %d";
            $params_year[] = $user_filter;
        }
        $where_year[] = "YEAR(start_date) = %d";
        $params_year[] = (int)$year;

        $sql_year = "SELECT * FROM $table WHERE " . implode(' AND ', $where_year) . " ORDER BY start_date DESC";
        $year_leaves = !empty($params_year) ? $wpdb->get_results($wpdb->prepare($sql_year, ...$params_year)) : $wpdb->get_results($sql_year);

        $user_leaves_year = [];
        $user_leaves_month = [];
        $summary = [
            'total_leave_days_month' => 0.0,
            'total_leave_days_year' => 0.0,
            'casual_days_month' => 0.0,
            'sick_days_month' => 0.0,
            'switches_count_month' => 0,
            'pending_count' => 0,
            'approved_count' => 0,
            'rejected_count' => 0
        ];

        foreach ($year_leaves ?: [] as $l) {
            $uid = (int)$l->user_id;
            if (!isset($user_leaves_year[$uid])) $user_leaves_year[$uid] = [];
            $user_leaves_year[$uid][] = $l;

            $l_month = date('Y-m', strtotime($l->start_date));
            if ($month === 'all' || $l_month === $month) {
                if (!isset($user_leaves_month[$uid])) $user_leaves_month[$uid] = [];
                $user_leaves_month[$uid][] = $l;

                if ($l->status === 'approved') {
                    if ($l->request_type === 'day_off_switch') {
                        $summary['switches_count_month']++;
                    } else {
                        $summary['total_leave_days_month'] += (float)$l->days_count;
                        $lt_lower = strtolower((string)$l->leave_type);
                        if (str_contains($lt_lower, 'casual')) {
                            $summary['casual_days_month'] += (float)$l->days_count;
                        } elseif (str_contains($lt_lower, 'sick') || str_contains($lt_lower, 'medical')) {
                            $summary['sick_days_month'] += (float)$l->days_count;
                        }
                    }
                }
                if ($l->status === 'pending' || $l->status === 'leader_approved') $summary['pending_count']++;
                elseif ($l->status === 'approved') $summary['approved_count']++;
                elseif ($l->status === 'rejected') $summary['rejected_count']++;
            }

            if ($l->status === 'approved' && $l->request_type !== 'day_off_switch') {
                $summary['total_leave_days_year'] += (float)$l->days_count;
            }
        }

        // 3. Member Matrix
        $matrix = [];
        $default_quota = 18.0; // standard agency entitlement
        foreach ($members as $m) {
            $uid = (int)($m['id'] ?? 0);
            $u_year_leaves = $user_leaves_year[$uid] ?? [];
            $u_month_leaves = $user_leaves_month[$uid] ?? [];

            $custom_quota_raw = get_user_meta($uid, 'ftm_annual_leave_quota', true);
            $annual_quota = ($custom_quota_raw !== '' && is_numeric($custom_quota_raw)) ? (float)$custom_quota_raw : $default_quota;
            $adj_raw = get_user_meta($uid, 'ftm_leave_balance_adjustment', true);
            $adjustment_days = ($adj_raw !== '' && is_numeric($adj_raw)) ? (float)$adj_raw : 0.0;
            $quota_note = (string)get_user_meta($uid, 'ftm_leave_quota_note', true);
            $member_day_off = get_user_meta($uid, 'ftm_day_off', true) ?: (($m['team'] ?? '') === 'WpCraft' ? 'friday' : 'sunday');

            $casual_yr = 0.0;
            $sick_yr = 0.0;
            $casual_mo = 0.0;
            $sick_mo = 0.0;
            $switches_mo = 0;
            $total_taken_yr = 0.0;
            $total_taken_mo = 0.0;

            foreach ($u_year_leaves as $yl) {
                if ($yl->status === 'approved' && $yl->request_type !== 'day_off_switch') {
                    $d = (float)$yl->days_count;
                    $total_taken_yr += $d;
                    $lt_lower = strtolower((string)$yl->leave_type);
                    if (str_contains($lt_lower, 'casual')) {
                        $casual_yr += $d;
                    } elseif (str_contains($lt_lower, 'sick') || str_contains($lt_lower, 'medical')) {
                        $sick_yr += $d;
                    }
                }
            }

            foreach ($u_month_leaves as $ml) {
                if ($ml->status === 'approved') {
                    if ($ml->request_type === 'day_off_switch') {
                        $switches_mo++;
                    } else {
                        $d = (float)$ml->days_count;
                        $total_taken_mo += $d;
                        $lt_lower = strtolower((string)$ml->leave_type);
                        if (str_contains($lt_lower, 'casual')) {
                            $casual_mo += $d;
                        } elseif (str_contains($lt_lower, 'sick') || str_contains($lt_lower, 'medical')) {
                            $sick_mo += $d;
                        }
                    }
                }
            }

            $effective_quota = max(0.0, round($annual_quota + $adjustment_days, 1));
            $rem_balance = max(0.0, round($effective_quota - $total_taken_yr, 1));
            $pct = $effective_quota > 0 ? min(100, round(($total_taken_yr / $effective_quota) * 100)) : ($total_taken_yr > 0 ? 100 : 0);

            $matrix[] = [
                'user_id' => $uid,
                'name' => $m['name'] ?? 'Specialist',
                'team' => $m['team'] ?? 'WpCraft',
                'avatar' => $m['avatar'] ?? get_avatar_url($uid, ['size' => 48]),
                'role_label' => $m['role_label'] ?? 'Specialist',
                'weekly_day_off' => ucfirst($member_day_off),
                'annual_quota' => $annual_quota,
                'adjustment_days' => $adjustment_days,
                'effective_quota' => $effective_quota,
                'quota_note' => $quota_note,
                'total_taken_year' => $total_taken_yr,
                'total_taken_month' => $total_taken_mo,
                'casual_taken_month' => $casual_mo,
                'sick_taken_month' => $sick_mo,
                'switches_month' => $switches_mo,
                'balance_remaining' => $rem_balance,
                'remaining_balance' => $rem_balance,
                'utilization_percent' => $pct,
                'health_status' => ($pct >= 85) ? 'near_limit' : (($pct >= 50) ? 'moderate' : 'healthy')
            ];
        }

        $formatted_records = array_map([$this, 'format_leave_item'], $year_leaves ?: []);
        if ($month !== 'all') {
            $formatted_records = array_values(array_filter($formatted_records, function($r) use ($month) {
                return strpos($r['start_date'], $month) === 0;
            }));
        }

        return rest_ensure_response([
            'month' => $month,
            'year' => $year,
            'team' => $team_filter,
            'summary' => $summary,
            'matrix' => array_values($matrix),
            'records' => $formatted_records
        ]);
    }

    private function get_leave_status_label($status) {
        switch ($status) {
            case 'pending': return 'Pending Leader Review';
            case 'leader_approved': return 'Leader Verified (Awaiting Manager)';
            case 'approved': return 'Final Approved';
            case 'rejected': return 'Rejected';
            default: return ucfirst($status);
        }
    }
}

