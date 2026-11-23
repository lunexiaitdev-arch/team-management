<?php
/**
 * FTM Mobile & REST API Authentication Engine
 * Provides persistent Bearer Token and Header-based authentication for Flutter mobile clients
 * Zero disruption to web session cookies
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_API_Auth {

    const TOKEN_META_KEY = 'ftm_app_tokens';

    public static function init() {
        // Authenticate incoming REST API requests using Bearer token or X-FTM-Token header
        add_filter('determine_current_user', [__CLASS__, 'determine_current_user'], 20);

        // Ensure CORS headers for mobile/web API consumers
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_action('rest_api_init', [__CLASS__, 'enable_cors'], 15);
    }

    public static function enable_cors() {
        remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
        add_filter('rest_pre_serve_request', function($value) {
            $origin = get_http_origin();
            if ($origin) {
                header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
            } else {
                header('Access-Control-Allow-Origin: *');
            }
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Authorization, X-WP-Nonce, X-FTM-Token, Content-Type, Accept, Origin, User-Agent');
            return $value;
        });
    }

    public static function register_routes() {
        register_rest_route('ftm/v1', '/auth/me', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_current_user_profile'],
            'permission_callback' => function() {
                return is_user_logged_in();
            }
        ]);

        register_rest_route('ftm/v1', '/auth/logout', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'logout'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('ftm/v1', '/app-version', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_app_version'],
            'permission_callback' => '__return_true'
        ]);
    }

    public static function get_app_version() {
        return rest_ensure_response([
            'success' => true,
            'version' => '1.1.9',
            'build' => '20',
            'full_version' => '1.1.9+20',
            'apk_url' => 'https://team.fbinternationalbd.com/downloads/FBIBD_Team.apk',
            'notes' => 'v1.1.9: Exact Website-to-App deadline & BST date parity (fixes CountdownPill overdue/date mismatches), fixes Squad Performance team breakdown display, adds Repeat Client Orders totals & filter, and adds per-member Repeat Orders count & net revenue across Dashboard and Monthly Sheet.'
        ]);
    }

    public static function determine_current_user($user_id) {
        if ($user_id) {
            return $user_id;
        }

        $token = self::get_bearer_token();
        if (empty($token)) {
            return $user_id;
        }

        $found_user_id = self::validate_token($token);
        if ($found_user_id > 0) {
            return $found_user_id;
        }

        return $user_id;
    }

    public static function get_bearer_token() {
        $headers = '';
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        if (!empty($headers)) {
            if (preg_match('/Bearer\s+(\S+)/i', $headers, $matches)) {
                return $matches[1];
            }
        }

        // Check fallback header X-FTM-Token
        if (!empty($_SERVER['HTTP_X_FTM_TOKEN'])) {
            return sanitize_text_field($_SERVER['HTTP_X_FTM_TOKEN']);
        }

        // Check fallback query param ?ftm_token=...
        if (!empty($_REQUEST['ftm_token'])) {
            return sanitize_text_field($_REQUEST['ftm_token']);
        }

        return null;
    }

    public static function generate_token($user_id, $device_name = 'Mobile App') {
        $raw_token = bin2hex(random_bytes(32));
        $hashed = hash('sha256', $raw_token);

        $tokens = get_user_meta($user_id, self::TOKEN_META_KEY, true);
        if (!is_array($tokens)) {
            $tokens = [];
        }

        // Clean expired tokens (older than 60 days)
        $cutoff = time() - (60 * DAY_IN_SECONDS);
        $tokens = array_filter($tokens, function($item) use ($cutoff) {
            return isset($item['created']) && $item['created'] > $cutoff;
        });

        $tokens[] = [
            'hash' => $hashed,
            'created' => time(),
            'device' => sanitize_text_field($device_name)
        ];

        // Limit to max 10 concurrent tokens
        if (count($tokens) > 10) {
            $tokens = array_slice($tokens, -10);
        }

        update_user_meta($user_id, self::TOKEN_META_KEY, $tokens);

        return $raw_token;
    }

    public static function validate_token($raw_token) {
        if (empty($raw_token) || strlen($raw_token) < 32) {
            return 0;
        }

        $hashed = hash('sha256', $raw_token);

        global $wpdb;
        $meta_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
            self::TOKEN_META_KEY
        ));

        if (empty($meta_rows)) {
            return 0;
        }

        foreach ($meta_rows as $row) {
            $tokens = maybe_unserialize($row->meta_value);
            if (is_array($tokens)) {
                foreach ($tokens as $t) {
                    if (isset($t['hash']) && hash_equals($t['hash'], $hashed)) {
                        return (int)$row->user_id;
                    }
                }
            }
        }

        return 0;
    }

    public static function revoke_token($raw_token) {
        if (empty($raw_token)) {
            return false;
        }
        $hashed = hash('sha256', $raw_token);
        $user_id = self::validate_token($raw_token);
        if ($user_id > 0) {
            $tokens = get_user_meta($user_id, self::TOKEN_META_KEY, true);
            if (is_array($tokens)) {
                $tokens = array_filter($tokens, function($item) use ($hashed) {
                    return !isset($item['hash']) || !hash_equals($item['hash'], $hashed);
                });
                update_user_meta($user_id, self::TOKEN_META_KEY, array_values($tokens));
            }
            return true;
        }
        return false;
    }

    public static function get_current_user_profile($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('rest_forbidden', 'User not authenticated', ['status' => 401]);
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('not_found', 'User not found', ['status' => 404]);
        }

        $custom_avatar = get_user_meta($user_id, 'ftm_custom_avatar', true);
        $user_avatar = !empty($custom_avatar) ? $custom_avatar : get_avatar_url($user_id, ['size' => 128, 'default' => 'mm']);

        $role = FTM_Permissions::get_user_role($user_id);
        $team = FTM_Permissions::get_user_team($user_id);
        $custom_id = get_user_meta($user_id, 'ftm_custom_id', true);
        $call_sign = get_user_meta($user_id, 'ftm_call_sign', true);
        $title = get_user_meta($user_id, 'ftm_professional_title', true);

        return rest_ensure_response([
            'success' => true,
            'user' => [
                'id' => $user_id,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'avatar' => $user_avatar,
                'role' => $role,
                'role_label' => FTM_Permissions::get_role_label($role),
                'team' => $team,
                'is_admin' => FTM_Permissions::is_super_admin($user_id),
                'is_leader' => FTM_Permissions::is_leader($user_id),
                'badge_id' => $custom_id ?: ('#' . $user_id),
                'call_sign' => $call_sign ?: $user->display_name,
                'professional_title' => $title ?: 'Specialist'
            ]
        ]);
    }

    public static function logout($request) {
        $token = self::get_bearer_token();
        if ($token) {
            self::revoke_token($token);
        }
        wp_logout();
        return rest_ensure_response([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
