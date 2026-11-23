<?php
/**
 * FTM Messages & Real-Time Team Communication Engine
 * Supports Global Chat, Team Squad Chat, Project-wise Discussions, and Private Inboxes.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Messages_Controller extends FTM_REST_Controller {

    public function register_routes() {
        self::ensure_table();

        register_rest_route($this->namespace, '/messages/channels', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_channels'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        register_rest_route($this->namespace, '/messages/thread', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_thread_messages'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        register_rest_route($this->namespace, '/messages/send', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'send_message'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        register_rest_route($this->namespace, '/messages/mark-read', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'mark_read'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);
    }

    public static function ensure_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ftm_messages';
        
        $collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_type VARCHAR(20) NOT NULL DEFAULT 'global',
            conversation_id VARCHAR(100) NOT NULL DEFAULT 'global',
            sender_id BIGINT(20) UNSIGNED NOT NULL,
            recipient_id BIGINT(20) UNSIGNED DEFAULT NULL,
            project_id BIGINT(20) UNSIGNED DEFAULT NULL,
            message TEXT NOT NULL,
            attachment_url VARCHAR(500) DEFAULT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conv_idx (conversation_type, conversation_id, created_at),
            KEY recipient_idx (recipient_id, is_read),
            KEY sender_idx (sender_id),
            KEY project_idx (project_id)
        ) $collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Compute canonical direct conversation ID for 2 users (e.g. "dm_12_45")
     */
    private function get_direct_conv_id($u1, $u2) {
        $u1 = (int)$u1;
        $u2 = (int)$u2;
        $min = min($u1, $u2);
        $max = max($u1, $u2);
        return "dm_{$min}_{$max}";
    }

    /**
     * GET /messages/channels
     * Returns Global, Team, Project, and Direct chat channels
     */
    public function get_channels($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $table = $wpdb->prefix . 'ftm_messages';
        $user_team = FTM_Permissions::get_user_team($current_user_id) ?: 'WpCraft';
        $is_admin = FTM_Permissions::is_super_admin($current_user_id);
        $is_executive = class_exists('FTM_Permissions') && method_exists('FTM_Permissions', 'is_executive') && FTM_Permissions::is_executive($current_user_id);

        // 1. Global Channel
        $last_global = $wpdb->get_row("SELECT * FROM $table WHERE conversation_type = 'global' ORDER BY id DESC LIMIT 1");
        $global_unread = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE conversation_type = 'global' AND sender_id != %d AND id > COALESCE((SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'ftm_last_read_global'), 0)",
            $current_user_id, $current_user_id
        ));

        $global_sender = null;
        if ($last_global) {
            $sender_obj = get_userdata($last_global->sender_id);
            $global_sender = $sender_obj ? $sender_obj->display_name : 'Team Member';
        }

        $channels = [
            'global' => [
                'type' => 'global',
                'id' => 'global',
                'title' => 'FB All-Hands Global',
                'subtitle' => 'Agency-wide broadcast & discussion room',
                'last_message' => $last_global ? $last_global->message : 'Welcome to FB International agency chat!',
                'last_sender' => $global_sender,
                'last_time' => $last_global ? date('M j, g:i A', strtotime($last_global->created_at)) : '',
                'unread_count' => $global_unread
            ]
        ];

        // 2. Team Channels (User's team, or all teams for Admin/Executive)
        $teams_to_show = [];
        if ($is_admin || $is_executive) {
            $teams_to_show = ['WpTitans', 'WpNexus', 'WpCraft', 'WpHorizon'];
        } else {
            $teams_to_show = [$user_team];
        }

        $team_channels = [];
        foreach ($teams_to_show as $t) {
            $t_id = 'team_' . sanitize_key($t);
            $last_team_msg = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE conversation_type = 'team' AND conversation_id = %s ORDER BY id DESC LIMIT 1",
                $t_id
            ));
            $team_unread = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE conversation_type = 'team' AND conversation_id = %s AND sender_id != %d AND id > COALESCE((SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s), 0)",
                $t_id, $current_user_id, $current_user_id, "ftm_last_read_$t_id"
            ));

            $t_sender = null;
            if ($last_team_msg) {
                $u_obj = get_userdata($last_team_msg->sender_id);
                $t_sender = $u_obj ? $u_obj->display_name : 'Specialist';
            }

            $team_channels[] = [
                'type' => 'team',
                'id' => $t_id,
                'team' => $t,
                'title' => "$t Squad Hub",
                'subtitle' => "Official discussion group for $t",
                'last_message' => $last_team_msg ? $last_team_msg->message : "Start discussing deliverables with $t squad",
                'last_sender' => $t_sender,
                'last_time' => $last_team_msg ? date('M j, g:i A', strtotime($last_team_msg->created_at)) : '',
                'unread_count' => $team_unread
            ];
        }
        $channels['teams'] = $team_channels;

        // 3. Project-wise Discussions
        $p_table = $wpdb->prefix . 'ftm_projects';
        
        // Find all project IDs with active messages
        $active_msg_pids = $wpdb->get_col("SELECT DISTINCT CAST(SUBSTRING(conversation_id, 6) AS UNSIGNED) FROM $table WHERE conversation_type = 'project' AND conversation_id LIKE 'proj_%'");
        $pid_in = !empty($active_msg_pids) ? implode(',', array_map('intval', $active_msg_pids)) : '0';

        if ($is_admin || $is_executive) {
            $projects_query = "SELECT id, order_id, profile_name, client_name, team_name, status, delivery_date FROM $p_table WHERE (status NOT IN ('cancelled') OR id IN ($pid_in)) ORDER BY (id IN ($pid_in)) DESC, id DESC LIMIT 100";
            $recent_projects = $wpdb->get_results($projects_query);
        } else {
            $recent_projects = $wpdb->get_results($wpdb->prepare(
                "SELECT id, order_id, profile_name, client_name, team_name, status, delivery_date FROM $p_table 
                 WHERE ((assigned_user_id = %d OR FIND_IN_SET(%d, assigned_user_ids) OR team_name = %s OR id IN ($pid_in)) AND status NOT IN ('cancelled'))
                 ORDER BY (id IN ($pid_in)) DESC, id DESC LIMIT 100",
                $current_user_id, $current_user_id, $user_team
            ));
        }

        $project_channels = [];
        if (!empty($recent_projects)) {
            foreach ($recent_projects as $proj) {
                $pid = (int)$proj->id;
                $conv_id = "proj_$pid";
                $last_p_msg = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table WHERE conversation_type = 'project' AND conversation_id = %s ORDER BY id DESC LIMIT 1",
                    $conv_id
                ));
                $p_unread = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE conversation_type = 'project' AND conversation_id = %s AND sender_id != %d AND id > COALESCE((SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s), 0)",
                    $conv_id, $current_user_id, $current_user_id, "ftm_last_read_$conv_id"
                ));

                $p_sender = null;
                if ($last_p_msg) {
                    $u_obj = get_userdata($last_p_msg->sender_id);
                    $p_sender = $u_obj ? $u_obj->display_name : 'Team';
                }

                $clean_order = '#' . ltrim($proj->order_id, '#');
                $clean_client = !empty($proj->client_name) ? $proj->client_name : 'Client';

                $project_channels[] = [
                    'type' => 'project',
                    'id' => $conv_id,
                    'project_id' => $pid,
                    'order_id' => $clean_order,
                    'title' => "Order {$clean_order} ({$clean_client})",
                    'subtitle' => (!empty($proj->profile_name) ? $proj->profile_name : 'Fiverr Order') . ' • ' . $proj->team_name,
                    'client_name' => $clean_client,
                    'status' => $proj->status,
                    'team' => $proj->team_name,
                    'delivery_date' => $proj->delivery_date,
                    'last_message' => $last_p_msg ? $last_p_msg->message : 'No discussion yet on this order',
                    'last_sender' => $p_sender,
                    'last_time' => $last_p_msg ? date('M j, g:i A', strtotime($last_p_msg->created_at)) : '',
                    'unread_count' => $p_unread
                ];
            }

            usort($project_channels, function($a, $b) {
                if ($a['unread_count'] > 0 && $b['unread_count'] === 0) return -1;
                if ($b['unread_count'] > 0 && $a['unread_count'] === 0) return 1;
                $a_has = ($a['last_message'] !== 'No discussion yet on this order');
                $b_has = ($b['last_message'] !== 'No discussion yet on this order');
                if ($a_has && !$b_has) return -1;
                if (!$a_has && $b_has) return 1;
                return $b['project_id'] <=> $a['project_id'];
            });
        }
        $channels['projects'] = $project_channels;

        // 4. Direct Messages / Inboxes
        // Fetch all active members in agency to chat with
        $all_members = get_users([
            'exclude' => [$current_user_id],
            'meta_key' => 'ftm_team',
            'orderby' => 'display_name'
        ]);

        $direct_channels = [];
        foreach ($all_members as $m) {
            $mid = (int)$m->ID;
            $m_team = get_user_meta($mid, 'ftm_team', true) ?: 'WpCraft';
            $m_role = FTM_Permissions::get_user_role($mid);
            $m_avatar = get_user_meta($mid, 'ftm_custom_avatar', true);
            if (empty($m_avatar)) {
                $m_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($m->display_name) . '&background=2563eb&color=fff&size=128&bold=true';
            }

            $dm_conv_id = $this->get_direct_conv_id($current_user_id, $mid);
            $last_dm = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE conversation_type = 'direct' AND conversation_id = %s ORDER BY id DESC LIMIT 1",
                $dm_conv_id
            ));
            $dm_unread = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE conversation_type = 'direct' AND conversation_id = %s AND sender_id = %d AND is_read = 0",
                $dm_conv_id, $mid
            ));

            $direct_channels[] = [
                'type' => 'direct',
                'id' => $dm_conv_id,
                'recipient_id' => $mid,
                'title' => $m->display_name,
                'subtitle' => FTM_Permissions::get_role_label($m_role) . " • $m_team",
                'avatar' => $m_avatar,
                'team' => $m_team,
                'role' => $m_role,
                'last_message' => $last_dm ? $last_dm->message : 'Tap to start direct private conversation',
                'last_sender' => ($last_dm && $last_dm->sender_id == $current_user_id) ? 'You' : $m->display_name,
                'last_time' => $last_dm ? date('M j, g:i A', strtotime($last_dm->created_at)) : '',
                'unread_count' => $dm_unread,
                'has_history' => $last_dm !== null
            ];
        }

        // Sort direct channels: those with recent conversations first
        usort($direct_channels, function($a, $b) {
            if ($a['has_history'] && !$b['has_history']) return -1;
            if (!$a['has_history'] && $b['has_history']) return 1;
            return $b['unread_count'] <=> $a['unread_count'];
        });

        $channels['direct'] = $direct_channels;

        return rest_ensure_response([
            'success' => true,
            'channels' => $channels
        ]);
    }

    /**
     * GET /messages/thread
     * Returns chronological message list for a specific conversation
     */
    public function get_thread_messages($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $table = $wpdb->prefix . 'ftm_messages';

        $type = sanitize_text_field($request->get_param('type') ?: 'global');
        $id = sanitize_text_field($request->get_param('id') ?: 'global');
        $recipient_id = (int)$request->get_param('recipient_id');
        $project_id = (int)$request->get_param('project_id');

        // Normalize conversation ID
        if ($type === 'direct' && $recipient_id > 0) {
            $id = $this->get_direct_conv_id($current_user_id, $recipient_id);
        } elseif ($type === 'project' && $project_id > 0) {
            $id = "proj_$project_id";
        } elseif ($type === 'team' && strpos($id, 'team_') !== 0) {
            $id = 'team_' . sanitize_key($id);
        }

        $limit = max(10, min(100, (int)($request->get_param('limit') ?: 50)));

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE conversation_type = %s AND conversation_id = %s ORDER BY id ASC LIMIT %d",
            $type, $id, $limit
        ));

        $messages = [];
        $max_id = 0;
        foreach ($rows as $r) {
            $sid = (int)$r->sender_id;
            $s_user = get_userdata($sid);
            $s_name = $s_user ? $s_user->display_name : 'Member';
            $s_role = FTM_Permissions::get_user_role($sid);
            $s_team = FTM_Permissions::get_user_team($sid);
            $s_avatar = get_user_meta($sid, 'ftm_custom_avatar', true);
            if (empty($s_avatar)) {
                $s_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($s_name) . '&background=2563eb&color=fff&size=128&bold=true';
            }

            $msg_id = (int)$r->id;
            if ($msg_id > $max_id) {
                $max_id = $msg_id;
            }

            $messages[] = [
                'id' => $msg_id,
                'conversation_type' => $r->conversation_type,
                'conversation_id' => $r->conversation_id,
                'sender_id' => $sid,
                'sender_name' => $s_name,
                'sender_avatar' => $s_avatar,
                'sender_role' => FTM_Permissions::get_role_label($s_role),
                'sender_team' => $s_team,
                'is_mine' => ($sid === $current_user_id),
                'message' => esc_html($r->message),
                'attachment_url' => $r->attachment_url,
                'is_read' => (bool)$r->is_read,
                'created_at' => date('M j, g:i A', strtotime($r->created_at)),
                'timestamp' => strtotime($r->created_at)
            ];
        }

        // Auto mark as read
        if ($max_id > 0) {
            if ($type === 'direct') {
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table SET is_read = 1 WHERE conversation_type = 'direct' AND conversation_id = %s AND recipient_id = %d",
                    $id, $current_user_id
                ));
            } else {
                update_user_meta($current_user_id, "ftm_last_read_$id", $max_id);
            }
        }

        return rest_ensure_response([
            'success' => true,
            'conversation_type' => $type,
            'conversation_id' => $id,
            'messages' => $messages
        ]);
    }

    /**
     * POST /messages/send
     * Post a new message to a channel or direct inbox
     */
    public function send_message($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            return new WP_Error('rest_forbidden', 'Authentication required', ['status' => 401]);
        }

        $params = $request->get_json_params() ?: $request->get_params();
        $message = trim((string)($params['message'] ?? ''));
        if (empty($message)) {
            return new WP_Error('empty_message', 'Message content is required.', ['status' => 400]);
        }

        $type = sanitize_text_field($params['type'] ?? 'global');
        $conv_id = sanitize_text_field($params['id'] ?? 'global');
        $recipient_id = isset($params['recipient_id']) ? (int)$params['recipient_id'] : null;
        $project_id = isset($params['project_id']) ? (int)$params['project_id'] : null;
        $attachment = isset($params['attachment_url']) ? esc_url_raw($params['attachment_url']) : null;

        if ($type === 'direct' && $recipient_id > 0) {
            $conv_id = $this->get_direct_conv_id($current_user_id, $recipient_id);
        } elseif ($type === 'project' && $project_id > 0) {
            $conv_id = "proj_$project_id";
        } elseif ($type === 'team' && strpos($conv_id, 'team_') !== 0) {
            $conv_id = 'team_' . sanitize_key($conv_id);
        }

        $table = $wpdb->prefix . 'ftm_messages';
        $inserted = $wpdb->insert($table, [
            'conversation_type' => $type,
            'conversation_id' => $conv_id,
            'sender_id' => $current_user_id,
            'recipient_id' => $recipient_id,
            'project_id' => $project_id,
            'message' => $message,
            'attachment_url' => $attachment,
            'is_read' => 0,
            'created_at' => current_time('mysql')
        ]);

        if (!$inserted) {
            return new WP_Error('db_error', 'Could not save message.', ['status' => 500]);
        }

        $msg_id = $wpdb->insert_id;
        $sender = wp_get_current_user();

        // Also if it's a project chat, mirror into project notes so the website can see it too!
        if ($type === 'project' && $project_id > 0) {
            $wpdb->insert($wpdb->prefix . 'ftm_project_notes', [
                'project_id' => $project_id,
                'user_id' => $current_user_id,
                'user_name' => $sender->display_name,
                'note' => $message,
                'created_at' => current_time('mysql')
            ]);
        }

        // Trigger Mobile Push Notifications for Recipients via FCM Engine
        if (class_exists('FTM_Notifications_Engine')) {
            $this->dispatch_chat_push($type, $conv_id, $current_user_id, $recipient_id, $project_id, $message, $sender->display_name);
        }

        return rest_ensure_response([
            'success' => true,
            'message_id' => $msg_id,
            'created_at' => current_time('mysql')
        ]);
    }

    /**
     * Dispatch FCM Push Notification for new messages
     */
    private function dispatch_chat_push($type, $conv_id, $sender_id, $recipient_id, $project_id, $message_text, $sender_name) {
        $recipients = [];

        if ($type === 'direct' && $recipient_id > 0) {
            $recipients[] = $recipient_id;
        } elseif ($type === 'project' && $project_id > 0) {
            global $wpdb;
            $proj = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ftm_projects WHERE id = %d", $project_id));
            if ($proj) {
                if (!empty($proj->assigned_user_ids)) {
                    foreach (explode(',', $proj->assigned_user_ids) as $uid) {
                        $uid = (int)trim($uid);
                        if ($uid > 0 && $uid !== $sender_id) $recipients[] = $uid;
                    }
                }
                if ($proj->assigned_user_id && (int)$proj->assigned_user_id !== $sender_id) {
                    $recipients[] = (int)$proj->assigned_user_id;
                }
            }
        } elseif ($type === 'team') {
            $team_name = str_replace('team_', '', $conv_id);
            $members = get_users(['meta_key' => 'ftm_team', 'meta_value' => $team_name]);
            foreach ($members as $m) {
                if ((int)$m->ID !== $sender_id) $recipients[] = (int)$m->ID;
            }
        }

        $recipients = array_values(array_unique($recipients));
        $preview = mb_substr($message_text, 0, 100);

        foreach ($recipients as $uid) {
            $title = $type === 'direct' ? "Direct message from $sender_name" : "Message in " . ucfirst($type);
            $item = [
                'id' => uniqid('msg_', true),
                'user_id' => $uid,
                'project_id' => $project_id,
                'type' => 'chat_message',
                'title' => $title,
                'message' => "$sender_name: $preview",
                'data' => [
                    'conversation_type' => $type,
                    'conversation_id' => $conv_id,
                    'sender_id' => $sender_id,
                    'sender_name' => $sender_name
                ]
            ];
            FTM_Notifications_Engine::dispatch_fcm_push($uid, $item);
        }
    }

    /**
     * POST /messages/mark-read
     */
    public function mark_read($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $params = $request->get_json_params() ?: $request->get_params();
        $conv_id = sanitize_text_field($params['id'] ?? '');
        $type = sanitize_text_field($params['type'] ?? 'direct');

        if ($type === 'direct') {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}ftm_messages SET is_read = 1 WHERE conversation_type = 'direct' AND conversation_id = %s AND recipient_id = %d",
                $conv_id, $current_user_id
            ));
        } else {
            $max_id = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT MAX(id) FROM {$wpdb->prefix}ftm_messages WHERE conversation_type = %s AND conversation_id = %s",
                $type, $conv_id
            ));
            if ($max_id > 0) {
                update_user_meta($current_user_id, "ftm_last_read_$conv_id", $max_id);
            }
        }

        return rest_ensure_response(['success' => true]);
    }
}
