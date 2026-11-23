<?php
/**
 * FTM Notifications Engine
 * Handles project assignment alerts, 3-day deadline warnings, 2-day progress reminders,
 * and delivery extension advisories directly for mobile notification bar via Firebase Cloud Messaging (FCM)
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Notifications_Engine {

    const TABLE_NAME = 'ftm_notifications';

    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        // Hook project assignee updates to send "New Assignment" notification immediately
        add_action('ftm_project_assigned', [__CLASS__, 'on_project_assigned'], 10, 2);

        // Schedule periodic evaluation for inactive phones
        if (!wp_next_scheduled('ftm_hourly_notifications_check')) {
            wp_schedule_event(time(), 'hourly', 'ftm_hourly_notifications_check');
        }
        add_action('ftm_hourly_notifications_check', [__CLASS__, 'evaluate_all_rules']);
    }

    public static function register_routes() {
        register_rest_route('ftm/v1', '/notifications', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_notifications'],
                'permission_callback' => function() { return is_user_logged_in(); }
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'mark_notifications_read'],
                'permission_callback' => function() { return is_user_logged_in(); }
            ]
        ]);

        register_rest_route('ftm/v1', '/notifications/mark-read', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'mark_notifications_read'],
            'permission_callback' => function() { return is_user_logged_in(); }
        ]);

        register_rest_route('ftm/v1', '/notifications/evaluate', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'evaluate_all_rules'],
            'permission_callback' => function() { return is_user_logged_in(); }
        ]);

        register_rest_route('ftm/v1', '/notifications/register-token', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'register_device_token'],
            'permission_callback' => function() { return is_user_logged_in(); }
        ]);
    }

    /**
     * Evaluate rules:
     * 1. New project assign notification
     * 2. Deadline approaching (< 3 days / 72 hours)
     * 3. 2 days after assign without progress update
     * 4. Extension advisory to leaders and managers
     */
    public static function evaluate_all_rules($request = null) {
        global $wpdb;
        $projects_table = $wpdb->prefix . 'ftm_projects';
        $assignees_table = $wpdb->prefix . 'ftm_project_assignees';

        // Check if tables exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$projects_table'") !== $projects_table) {
            return rest_ensure_response(['success' => false, 'message' => 'Table not found']);
        }

        // Active projects (exclude delivered, accepted, and cancelled orders)
        $projects = $wpdb->get_results("
            SELECT id, order_id, client_name, profile_name, gross_order_value, 
                   status, delivery_date, assigned_user_id, assigned_user_ids, team_name, progress_percent, created_at
            FROM $projects_table 
            WHERE status NOT IN ('completed', 'cancelled', 'delivery_accepted')
        ");

        $now = time();
        $notifications_created = 0;

        foreach ($projects as $p) {
            $pid = (int)$p->id;
            $order_id = $p->order_id ?: ("#" . $pid);
            $delivery_time = !empty($p->delivery_date) ? strtotime($p->delivery_date) : 0;
            $created_time = !empty($p->created_at) ? strtotime($p->created_at) : ($now);

            // Fetch all assigned specialists
            $assignees = [];
            if (!empty($p->assigned_user_id)) {
                $assignees[] = (int)$p->assigned_user_id;
            }
            if ($wpdb->get_var("SHOW TABLES LIKE '$assignees_table'") === $assignees_table) {
                $extra_ids = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM $assignees_table WHERE project_id = %d", $pid));
                if (!empty($extra_ids)) {
                    $assignees = array_unique(array_merge($assignees, array_map('intval', $extra_ids)));
                }
            }

            // RULE 1: Assignment notification check
            foreach ($assignees as $uid) {
                $uid = (int)$uid;
                $key = "ftm_notif_assigned_{$pid}_{$uid}";
                if (!get_user_meta($uid, $key, true)) {
                    self::create_notification(
                        $uid,
                        $pid,
                        'new_assignment',
                        "New Project Assigned: $order_id",
                        "You have been assigned to order $order_id for {$p->client_name} ($" . number_format($p->gross_order_value, 0) . ").",
                        ['order_id' => $order_id, 'project_id' => $pid, 'buyer' => $p->client_name, 'budget' => $p->gross_order_value, 'profile' => $p->profile_name]
                    );
                    update_user_meta($uid, $key, time());
                    $notifications_created++;
                }
            }

            // RULE 2 & 4: Deadline approaching (< 3 days / 72 hours)
            if ($delivery_time > 0) {
                $hours_left = ($delivery_time - $now) / 3600;
                $days_left = ceil($hours_left / 24);

                if ($hours_left > 0 && $hours_left <= 72) {
                    $hours_display = max(1, round($hours_left));

                    // Rule 2: Notify Assigned Specialists
                    foreach ($assignees as $uid) {
                        $uid = (int)$uid;
                        $key = "ftm_notif_deadline_3d_{$pid}_{$uid}";
                        if (!get_user_meta($uid, $key, true)) {
                            self::create_notification(
                                $uid,
                                $pid,
                                'deadline_warning_3d',
                                "Urgent: Deadline < 3 Days Remaining ($order_id)",
                                "Order $order_id delivery deadline is in {$hours_display}h. Please review deliverables and ensure prompt delivery.",
                                ['order_id' => $order_id, 'project_id' => $pid, 'hours_left' => $hours_display, 'delivery_date' => $p->delivery_date]
                            );
                            update_user_meta($uid, $key, time());
                            $notifications_created++;
                        }
                    }

                    // Rule 4: Send Extension Advisory to Team Leaders & Operations Managers
                    $leadership_ids = self::get_leadership_user_ids($p->team_name);
                    foreach ($leadership_ids as $lid) {
                        $key = "ftm_notif_extension_advisory_{$pid}_{$lid}";
                        if (!get_user_meta($lid, $key, true)) {
                            self::create_notification(
                                $lid,
                                $pid,
                                'extension_advisory_3d',
                                "Deadline Extension Advisory: $order_id",
                                "Project $order_id ({$p->team_name}) has only {$hours_display}h left. Review progress with specialist and coordinate deadline extension if needed.",
                                ['order_id' => $order_id, 'project_id' => $pid, 'team' => $p->team_name, 'hours_left' => $hours_display]
                            );
                            update_user_meta($lid, $key, time());
                            $notifications_created++;
                        }
                    }

                    // Email warning if <= 24 hours
                    if ($hours_left <= 24 && class_exists('FTM_Mailer')) {
                        FTM_Mailer::send_deadline_warning_email($pid, $hours_left);
                    }
                }
            }

            // RULE 3: 2 Days after assignment progress update prompt
            $age_hours = ($now - $created_time) / 3600;
            if ($age_hours >= 48 && (int)$p->progress_percent < 100) {
                foreach ($assignees as $uid) {
                    $uid = (int)$uid;
                    $key = "ftm_notif_update_2d_{$pid}_{$uid}";
                    if (!get_user_meta($uid, $key, true)) {
                        self::create_notification(
                            $uid,
                            $pid,
                            'progress_prompt_2d',
                            "Progress Update Reminder: $order_id",
                            "It has been 2 days since $order_id was assigned. Current progress: {$p->progress_percent}%. Please submit your daily update and status.",
                            ['order_id' => $order_id, 'project_id' => $pid]
                        );
                        update_user_meta($uid, $key, time());
                        $notifications_created++;
                    }
                }
            }
        }

        return rest_ensure_response([
            'success' => true,
            'generated' => $notifications_created,
            'timestamp' => time()
        ]);
    }

    public static function notify_user($user_id, $params) {
        if (!$user_id) {
            return false;
        }
        $project_id = $params['target_id'] ?? $params['project_id'] ?? null;
        $type = $params['type'] ?? 'general';
        $title = $params['title'] ?? 'Notification';
        $message = $params['message'] ?? '';
        $data = $params['data'] ?? [];
        return self::create_notification($user_id, $project_id, $type, $title, $message, $data);
    }

    /**
     * Executive / Chairman / Deputy view-only accounts monitor dashboards on-demand
     * and must NOT be spammed with day-to-day operational push/in-app notifications.
     */
    private static function is_muted_executive($user_id) {
        $uid = (int)$user_id;
        if ($uid <= 0) {
            return false;
        }
        if (class_exists('FTM_Permissions')) {
            if (FTM_Permissions::is_super_admin($uid)) {
                return false; // Manager / Super Admin (Pranto) always receives notifications
            }
            if (FTM_Permissions::is_executive($uid) || FTM_Permissions::is_deputy($uid)) {
                return true;
            }
        }
        return false;
    }

    public static function create_notification($user_id, $project_id, $type, $title, $message, $data = []) {
        if (self::is_muted_executive($user_id)) {
            return false;
        }

        $notifications = get_user_meta($user_id, 'ftm_user_notifications', true);
        if (!is_array($notifications)) {
            $notifications = [];
        }

        $item = [
            'id' => time() . rand(100, 999),
            'user_id' => $user_id,
            'project_id' => $project_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
            'created_at' => current_time('mysql'),
            'timestamp' => time()
        ];

        // Prepend to list
        array_unshift($notifications, $item);

        // Keep last 50 notifications
        if (count($notifications) > 50) {
            $notifications = array_slice($notifications, 0, 50);
        }

        update_user_meta($user_id, 'ftm_user_notifications', $notifications);

        // Dispatch remote Firebase Cloud Messaging push to mobile phone (wakes phone even if inactive 1 day)
        self::dispatch_fcm_push($user_id, $item);

        return $item;
    }

    /**
     * Dispatch remote FCM HTTP v1 push notification using Firebase service account
     */
    public static function dispatch_fcm_push($user_id, $item) {
        if (self::is_muted_executive($user_id)) {
            return false;
        }

        $tokens = get_user_meta($user_id, 'ftm_device_push_tokens', true);
        if (empty($tokens) || !is_array($tokens)) {
            return false;
        }

        $access_token = self::get_fcm_oauth_token();
        if (!$access_token) {
            error_log('FTM FCM Error: Could not generate OAuth access token');
            return false;
        }

        $service_account = self::get_service_account_credentials();
        if (!$service_account || empty($service_account['project_id'])) {
            return false;
        }

        $project_id = $service_account['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send";

        $is_urgent = ($item['type'] === 'deadline_warning_3d' || $item['type'] === 'extension_advisory_3d');

        $tokens_to_remove = [];

        foreach ($tokens as $device_token => $meta) {
            $payload = [
                'message' => [
                    'token' => $device_token,
                    'notification' => [
                        'title' => $item['title'],
                        'body' => $item['message']
                    ],
                    'data' => [
                        'id' => (string)$item['id'],
                        'type' => (string)$item['type'],
                        'project_id' => (string)($item['project_id'] ?: '0'),
                        'urgent' => $is_urgent ? '1' : '0'
                    ],
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'fbibd_team_alerts_channel',
                            'color' => '#0689E3',
                            'icon' => 'ic_launcher',
                            'sound' => 'default',
                            'default_vibrate_timings' => true,
                            'notification_priority' => 'PRIORITY_MAX'
                        ]
                    ]
                ]
            ];

            $response = wp_remote_post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json; UTF-8'
                ],
                'body' => wp_json_encode($payload),
                'timeout' => 15
            ]);

            if (is_wp_error($response)) {
                error_log('FTM FCM send error: ' . $response->get_error_message());
            } else {
                $code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                if ($code === 404 || strpos($body, 'UNREGISTERED') !== false) {
                    // Stale token, mark for removal
                    $tokens_to_remove[] = $device_token;
                }
            }
        }

        if (!empty($tokens_to_remove)) {
            foreach ($tokens_to_remove as $stale) {
                unset($tokens[$stale]);
            }
            update_user_meta($user_id, 'ftm_device_push_tokens', $tokens);
        }

        return true;
    }

    /**
     * Get Service Account Credentials
     */
    private static function get_service_account_credentials() {
        $path = dirname(__FILE__) . '/firebase-service-account.json';
        if (!file_exists($path)) {
            return false;
        }
        $raw = file_get_contents($path);
        return json_decode($raw, true);
    }

    /**
     * Generate Google OAuth2 Access Token using RS256 JWT
     */
    private static function get_fcm_oauth_token() {
        $cached = get_transient('ftm_fcm_oauth_token');
        if ($cached) {
            return $cached;
        }

        $creds = self::get_service_account_credentials();
        if (!$creds || empty($creds['private_key']) || empty($creds['client_email'])) {
            return false;
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ];

        $encoded_header = self::base64_url_encode(json_encode($header));
        $encoded_claims = self::base64_url_encode(json_encode($claims));
        $to_sign = $encoded_header . '.' . $encoded_claims;

        $binary_signature = '';
        $pkey = openssl_pkey_get_private($creds['private_key']);
        if (!$pkey) {
            error_log('FTM FCM: Failed to parse private key');
            return false;
        }

        if (!openssl_sign($to_sign, $binary_signature, $pkey, OPENSSL_ALGO_SHA256)) {
            error_log('FTM FCM: Failed to sign JWT');
            return false;
        }

        $jwt = $to_sign . '.' . self::base64_url_encode($binary_signature);

        $res = wp_remote_post('https://oauth2.googleapis.com/token', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ],
            'timeout' => 15
        ]);

        if (is_wp_error($res)) {
            error_log('FTM FCM OAuth Error: ' . $res->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($res), true);
        if (!empty($body['access_token'])) {
            // Cache for 50 minutes (expires in 60)
            set_transient('ftm_fcm_oauth_token', $body['access_token'], 50 * MINUTE_IN_SECONDS);
            return $body['access_token'];
        }

        return false;
    }

    private static function base64_url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function get_notifications($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('rest_forbidden', 'Not logged in', ['status' => 401]);
        }

        // Trigger on-demand rule evaluation (skip for view-only executive/chairman panels)
        if (self::is_muted_executive($user_id)) {
            return rest_ensure_response([
                'success' => true,
                'notifications' => [],
                'unread_count' => 0,
                'total' => 0
            ]);
        }

        self::evaluate_all_rules();

        $notifications = get_user_meta($user_id, 'ftm_user_notifications', true);
        if (!is_array($notifications)) {
            $notifications = [];
        }

        $unread_count = 0;
        foreach ($notifications as $n) {
            if (empty($n['is_read'])) {
                $unread_count++;
            }
        }

        $unread_only = $request->get_param('unread_only') === '1';
        if ($unread_only) {
            $notifications = array_values(array_filter($notifications, function($n) {
                return empty($n['is_read']);
            }));
        }

        return rest_ensure_response([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unread_count,
            'total' => count($notifications)
        ]);
    }

    public static function mark_notifications_read($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('rest_forbidden', 'Not logged in', ['status' => 401]);
        }

        $params = $request->get_json_params() ?: $request->get_params();
        $notif_id = $params['id'] ?? null;
        $mark_all = !empty($params['all']) || $notif_id === 'all' || $notif_id === null || $notif_id === '';
        $target_ids = isset($params['ids']) && is_array($params['ids']) ? array_map('strval', $params['ids']) : [];

        $notifications = get_user_meta($user_id, 'ftm_user_notifications', true);
        $remaining_unread = 0;

        if (is_array($notifications)) {
            foreach ($notifications as &$n) {
                $item_id = (string)($n['id'] ?? '');
                if ($mark_all || (!empty($target_ids) && in_array($item_id, $target_ids, true)) || ($notif_id !== null && $item_id === (string)$notif_id)) {
                    $n['is_read'] = true;
                    $n['read_at'] = current_time('mysql');
                }
                if (empty($n['is_read'])) {
                    $remaining_unread++;
                }
            }
            unset($n);
            update_user_meta($user_id, 'ftm_user_notifications', $notifications);
        }

        return rest_ensure_response([
            'success' => true,
            'unread_count' => $remaining_unread,
            'message' => 'Notifications successfully marked as read'
        ]);
    }

    public static function register_device_token($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('rest_forbidden', 'Not logged in', ['status' => 401]);
        }

        $params = $request->get_json_params() ?: $request->get_params();
        $token = sanitize_text_field($params['token'] ?? '');
        $platform = sanitize_text_field($params['platform'] ?? 'android');

        // Do not register push tokens for muted executive/chairman accounts
        if (self::is_muted_executive($user_id)) {
            delete_user_meta($user_id, 'ftm_device_push_tokens');
            return rest_ensure_response(['success' => true, 'muted' => true]);
        }

        if (!empty($token)) {
            $tokens = get_user_meta($user_id, 'ftm_device_push_tokens', true);
            if (!is_array($tokens)) $tokens = [];
            $tokens[$token] = [
                'platform' => $platform,
                'updated_at' => time()
            ];
            update_user_meta($user_id, 'ftm_device_push_tokens', $tokens);
        }

        return rest_ensure_response(['success' => true]);
    }

    private static function get_leadership_user_ids($team_name = '') {
        $leadership_ids = [];

        // Operations Managers / Admins
        $admins = get_users(['role__in' => ['administrator'], 'fields' => 'ID']);
        $leadership_ids = array_merge($leadership_ids, $admins);

        // Leaders of the squad
        $leaders = get_users([
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'ftm_team_role',
                    'value' => ['leader', 'co_leader'],
                    'compare' => 'IN'
                ],
                [
                    'key' => 'ftm_team',
                    'value' => $team_name,
                    'compare' => '='
                ]
            ],
            'fields' => 'ID'
        ]);
        $leadership_ids = array_merge($leadership_ids, $leaders);

        return array_unique(array_map('intval', $leadership_ids));
    }

    public static function broadcast_to_leadership($params) {
        $title = $params['title'] ?? 'Leadership Alert';
        $message = $params['message'] ?? '';
        $type = $params['type'] ?? 'info';
        $target_id = (int)($params['target_id'] ?? 0);
        $team = $params['team'] ?? '';

        $leadership_ids = self::get_leadership_user_ids($team);
        foreach ($leadership_ids as $leader_id) {
            self::create_notification(
                (int)$leader_id,
                $target_id,
                $type,
                $title,
                $message,
                ['team' => $team]
            );
        }
    }
}
