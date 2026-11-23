<?php
/**
 * Agency Notices & Broadcast Controller
 * Allows Manager to broadcast announcements, urgent notices, or kudos to all, specific teams, or specific members.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Notices_Controller extends FTM_REST_Controller {

    public function register_routes() {
        register_rest_route($this->namespace, '/notices', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_notices'],
                'permission_callback' => [$this, 'check_auth_permission']
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_notice'],
                'permission_callback' => [$this, 'check_manager_permission']
            ]
        ]);

        register_rest_route($this->namespace, '/notices/(?P<id>\d+)/pin', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'toggle_pin'],
            'permission_callback' => [$this, 'check_manager_permission']
        ]);

        register_rest_route($this->namespace, '/notices/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_notice'],
            'permission_callback' => [$this, 'check_manager_permission']
        ]);
    }

    public function check_manager_permission() {
        $uid = get_current_user_id();
        return $uid > 0 && FTM_Permissions::is_super_admin($uid);
    }

    public function get_notices($request) {
        global $wpdb;
        $this->check_and_generate_automated_wishes();

        $uid = get_current_user_id();
        $is_admin = FTM_Permissions::is_super_admin($uid);
        $user_team = FTM_Permissions::get_user_team($uid);
        $custom_id = get_user_meta($uid, 'ftm_custom_id', true) ?: (string)$uid;
        $table = $wpdb->prefix . 'ftm_notices';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return rest_ensure_response(['notices' => []]);
        }

        if ($is_admin || FTM_Permissions::is_executive($uid)) {
            $notices = $wpdb->get_results(
                "SELECT * FROM $table WHERE status = 'active' ORDER BY is_pinned DESC, created_at DESC"
            );
        } else {
            $notices = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table 
                 WHERE status = 'active' 
                   AND (
                     target_type = 'all' 
                     OR (target_type = 'team' AND target_value = %s)
                     OR (target_type = 'member' AND (target_value = %s OR target_value = %s))
                   )
                 ORDER BY is_pinned DESC, created_at DESC",
                $user_team,
                $custom_id,
                (string)$uid
            ));
        }

        $formatted = array_map(function($n) {
            $author = get_userdata((int)$n->created_by);
            $author_name = $author ? $author->display_name : 'Executive Management';
            $custom_avatar = get_user_meta((int)$n->created_by, 'ftm_custom_avatar', true);
            $author_avatar = !empty($custom_avatar) ? $custom_avatar : ('https://ui-avatars.com/api/?name=' . urlencode($author_name) . '&background=0f172a&color=fff&size=128&bold=true');

            // Human friendly target label
            $target_label = 'All Agency Members';
            if ($n->target_type === 'team') {
                $target_label = $n->target_value . ' Team';
            } elseif ($n->target_type === 'member') {
                $target_user = null;
                if (is_numeric($n->target_value)) {
                    // Try looking up by custom_id or user ID
                    global $wpdb;
                    $uid_found = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'ftm_custom_id' AND meta_value = %s LIMIT 1", $n->target_value));
                    if ($uid_found) {
                        $target_user = get_userdata((int)$uid_found);
                    } else {
                        $target_user = get_userdata((int)$n->target_value);
                    }
                }
                $target_label = $target_user ? ('Specialist: ' . $target_user->display_name) : ('Specialist #' . $n->target_value);
            }

            return [
                'id' => (int)$n->id,
                'title' => html_entity_decode(wp_unslash($n->title), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'message' => html_entity_decode(wp_unslash($n->message), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'notice_type' => $n->notice_type ?: 'announcement',
                'target_type' => $n->target_type ?: 'all',
                'target_value' => $n->target_value ?: 'all',
                'target_label' => $target_label,
                'is_pinned' => (bool)$n->is_pinned,
                'created_by' => (int)$n->created_by,
                'author_name' => $author_name,
                'author_avatar' => $author_avatar,
                'created_at' => $n->created_at,
                'created_at_formatted' => date('M j, Y g:i A', strtotime($n->created_at))
            ];
        }, $notices ?: []);

        return rest_ensure_response([
            'notices' => $formatted,
            'can_manage' => $is_admin
        ]);
    }

    public function create_notice($request) {
        global $wpdb;
        $uid = get_current_user_id();
        $data = $request->get_json_params();

        $title = sanitize_text_field(trim($data['title'] ?? ''));
        $message = sanitize_textarea_field(trim($data['message'] ?? ''));
        $notice_type = sanitize_text_field($data['notice_type'] ?? 'announcement');
        $target_type = sanitize_text_field($data['target_type'] ?? 'all');
        $target_value = sanitize_text_field(trim($data['target_value'] ?? 'all'));
        $is_pinned = !empty($data['is_pinned']) ? 1 : 0;

        if (empty($title)) {
            return $this->format_error(__('Notice title is required.', 'fiverr-team-management'), 400);
        }
        if (empty($message)) {
            return $this->format_error(__('Notice message content is required.', 'fiverr-team-management'), 400);
        }

        if (!in_array($notice_type, ['announcement', 'important', 'urgent', 'kudos'], true)) {
            $notice_type = 'announcement';
        }
        if (!in_array($target_type, ['all', 'team', 'member'], true)) {
            $target_type = 'all';
        }
        if ($target_type === 'all') {
            $target_value = 'all';
        }

        $table = $wpdb->prefix . 'ftm_notices';
        $res = $wpdb->insert($table, [
            'title' => $title,
            'message' => $message,
            'notice_type' => $notice_type,
            'target_type' => $target_type,
            'target_value' => $target_value,
            'is_pinned' => $is_pinned,
            'created_by' => $uid,
            'status' => 'active'
        ]);

        if ($res === false) {
            return $this->format_error(__('Failed to publish notice. Database error.', 'fiverr-team-management'), 500);
        }

        $new_id = (int)$wpdb->insert_id;
        if (class_exists('FTM_Mailer')) {
            FTM_Mailer::send_notice_email($new_id, $title, $message, $notice_type, $target_type, $target_value);
        }
        return $this->format_success([
            'id' => $new_id,
            'title' => $title
        ], __('Official Notice broadcasted successfully to targeted members!', 'fiverr-team-management'));
    }

    public function toggle_pin($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $table = $wpdb->prefix . 'ftm_notices';

        $notice = $wpdb->get_row($wpdb->prepare("SELECT id, is_pinned FROM $table WHERE id = %d", $id));
        if (!$notice) {
            return $this->format_error(__('Notice not found.', 'fiverr-team-management'), 404);
        }

        $new_pin = $notice->is_pinned ? 0 : 1;
        $wpdb->update($table, ['is_pinned' => $new_pin], ['id' => $id]);

        return $this->format_success([
            'id' => $id,
            'is_pinned' => (bool)$new_pin
        ], $new_pin ? __('Notice pinned to the top of notice boards!', 'fiverr-team-management') : __('Notice unpinned.', 'fiverr-team-management'));
    }

    public function delete_notice($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $table = $wpdb->prefix . 'ftm_notices';

        $notice = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table WHERE id = %d", $id));
        if (!$notice) {
            return $this->format_error(__('Notice not found.', 'fiverr-team-management'), 404);
        }

        $wpdb->update($table, ['status' => 'archived'], ['id' => $id]);
        return $this->format_success(['id' => $id], __('Notice archived and removed from active boards.', 'fiverr-team-management'));
    }

    private function check_and_generate_automated_wishes() {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_notices';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return;
        }

        $today_md = current_time('m-d');
        $current_year = current_time('Y');
        $users = get_users(['fields' => ['ID', 'display_name']]);

        foreach ($users as $u) {
            $uid = (int)$u->ID;
            $status = get_user_meta($uid, 'ftm_status', true);
            if (!empty($status) && in_array(strtolower($status), ['inactive', 'resigned', 'former', 'left', 'isolated'], true)) {
                continue;
            }

            // 1. Birthday Wish Check
            $bday = get_user_meta($uid, 'ftm_birthday', true);
            if (!empty($bday) && strtotime($bday) !== false) {
                $bday_md = date('m-d', strtotime($bday));
                if ($bday_md === $today_md) {
                    $year_key = "ftm_bday_notice_{$current_year}";
                    $already_notified = get_user_meta($uid, $year_key, true);
                    if (!$already_notified) {
                        $wpdb->insert($table, [
                            'title' => '🎉 Happy Birthday, ' . $u->display_name . '! 🎂',
                            'message' => 'Agency FB International wishes ' . $u->display_name . ' a very Happy Birthday! 🎈 Wishing you excellent health, great happiness, and grand success in all your journeys. Have a wonderful day!',
                            'notice_type' => 'kudos',
                            'target_type' => 'all',
                            'target_value' => 'all',
                            'is_pinned' => 1,
                            'status' => 'active',
                            'created_by' => 1,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql')
                        ]);
                        update_user_meta($uid, $year_key, current_time('mysql'));
                    }
                }
            }

            // 2. Work Anniversary (Duration) Wish Check
            $joined = get_user_meta($uid, 'ftm_joined_date', true);
            if (!empty($joined) && strtotime($joined) !== false) {
                $joined_ts = strtotime($joined);
                $joined_md = date('m-d', $joined_ts);
                $joined_year = (int)date('Y', $joined_ts);
                $years_diff = (int)$current_year - $joined_year;
                if ($joined_md === $today_md && $years_diff >= 1) {
                    $anniv_key = "ftm_anniv_notice_{$current_year}";
                    $already_notified = get_user_meta($uid, $anniv_key, true);
                    if (!$already_notified) {
                        $ord = ($years_diff === 1) ? '1st' : (($years_diff === 2) ? '2nd' : (($years_diff === 3) ? '3rd' : ($years_diff . 'th')));
                        $wpdb->insert($table, [
                            'title' => '🏆 Happy ' . $ord . ' Work Anniversary, ' . $u->display_name . '! 🎉',
                            'message' => 'Congratulations to ' . $u->display_name . ' on completing ' . $years_diff . ' year' . ($years_diff > 1 ? 's' : '') . ' with Agency FB International! Thank you for your dedication, outstanding craftsmanship, and loyalty to our agency mission.',
                            'notice_type' => 'announcement',
                            'target_type' => 'all',
                            'target_value' => 'all',
                            'is_pinned' => 1,
                            'status' => 'active',
                            'created_by' => 1,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql')
                        ]);
                        update_user_meta($uid, $anniv_key, current_time('mysql'));
                    }
                }
            }
        }
    }
}

