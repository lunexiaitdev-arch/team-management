<?php
/**
 * Plugin Name: Fiverr Team Management System
 * Description: Production-grade team, project, financial, and daily progress management system for agencies.
 * Version: 2.14.5
 * Author: FB International Engineering
 * Text Domain: fiverr-team-management
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FTM_VERSION', '2.15.1');
define('FTM_DB_VERSION', '2.12.7');
define('FTM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FTM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload Core Classes
require_once FTM_PLUGIN_DIR . 'includes/class-ftm-activator.php';
require_once FTM_PLUGIN_DIR . 'includes/class-ftm-seeder.php';
require_once FTM_PLUGIN_DIR . 'includes/class-ftm-permissions.php';
require_once FTM_PLUGIN_DIR . 'includes/class-ftm-financial.php';
require_once FTM_PLUGIN_DIR . 'includes/class-ftm-subscriptions.php';
require_once FTM_PLUGIN_DIR . 'includes/class-ftm-manager-scopes.php';
require_once FTM_PLUGIN_DIR . 'includes/class-ftm-api-auth.php';
require_once FTM_PLUGIN_DIR . 'includes/class-ftm-notifications-engine.php';
require_once FTM_PLUGIN_DIR . 'includes/class-ftm-mailer.php';
FTM_Subscriptions::init();
FTM_API_Auth::init();
FTM_Notifications_Engine::init();
require_once FTM_PLUGIN_DIR . 'includes/Controllers/class-ftm-rest-controller.php';
require_once FTM_PLUGIN_DIR . 'includes/Controllers/class-ftm-projects-controller.php';
require_once FTM_PLUGIN_DIR . 'includes/Controllers/class-ftm-daily-report-controller.php';
require_once FTM_PLUGIN_DIR . 'includes/Controllers/class-ftm-monthly-report-controller.php';
require_once FTM_PLUGIN_DIR . 'includes/Controllers/class-ftm-notices-controller.php';
require_once FTM_PLUGIN_DIR . 'includes/Controllers/class-ftm-messages-controller.php';
require_once FTM_PLUGIN_DIR . 'includes/Controllers/class-ftm-tasks-controller.php';
require_once FTM_PLUGIN_DIR . 'includes/Controllers/class-ftm-leaves-controller.php';
require_once FTM_PLUGIN_DIR . 'includes/Controllers/class-ftm-time-tracker-controller.php';
FTM_Time_Tracker_Controller::init_retention_cron();
require_once FTM_PLUGIN_DIR . 'includes/Controllers/class-ftm-isolation-controller.php';
require_once FTM_PLUGIN_DIR . 'includes/Controllers/class-ftm-legacy-controller.php';

// Activation Hook
register_activation_hook(__FILE__, ['FTM_Activator', 'activate']);

// Safe Admin Migration Hook (Avoids Front-End DDL execution)
add_action('admin_init', function() {
    if (is_admin() && current_user_can('manage_options')) {
        if (get_option('ftm_db_version') !== FTM_DB_VERSION) {
            FTM_Activator::migrate_database();
        }
    }
});

// Safe migration trigger on init when admin/manager visits the site
add_action('init', function() {
    if (is_user_logged_in() && current_user_can('manage_options')) {
        if (get_option('ftm_db_version') !== FTM_DB_VERSION) {
            FTM_Activator::migrate_database();
        }
    }
});

// View-only Management / Deputy Manager roles
add_action('init', function() {
    if (!get_role('ftm_deputy')) {
        add_role('ftm_deputy', 'Deputy Manager', ['read' => true]);
    }
    if (!get_role('ftm_management')) {
        add_role('ftm_management', 'Management', ['read' => true]);
    }
});

// Deputy Manager is strictly read-only: refuse every write on the plugin API, whatever the endpoint.
add_filter('rest_request_before_callbacks', function($response, $handler, $request) {
    if (strpos($request->get_route(), '/ftm/v1/') !== 0) {
        return $response;
    }
    if (in_array($request->get_method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
        return $response;
    }
    if (is_user_logged_in() && FTM_Permissions::is_deputy(get_current_user_id())) {
        // The one thing a Deputy Manager may change is their own account: first-login email, password, photo.
        $self_routes = ['/ftm/v1/user/profile', '/ftm/v1/user/avatar', '/ftm/v1/user/password'];
        if (in_array($request->get_route(), $self_routes, true)) {
            $json = $request->get_json_params();
            $target = is_array($json) && !empty($json['target_user_id']) ? (int)$json['target_user_id'] : get_current_user_id();
            if ($target === get_current_user_id()) {
                return $response;
            }
        }
        return new WP_Error('ftm_view_only', __('Management, Executive & Deputy accounts have view-only access across reports and projects.', 'fiverr-team-management'), ['status' => 403]);
    }
    return $response;
}, 10, 3);

// 1. Hide WP Admin Bar on Frontend for Non-Administrators
add_action('after_setup_theme', function() {
    if (!current_user_can('administrator') && !current_user_can('manage_options')) {
        show_admin_bar(false);
    }
});

add_action('init', function() {
    if (!current_user_can('administrator') && !current_user_can('manage_options')) {
        show_admin_bar(false);
    }
});

// 2. Output Professional Brand Favicon
add_action('wp_head', 'ftm_output_brand_favicon', 1);
add_action('login_head', 'ftm_output_brand_favicon', 1);
add_action('admin_head', 'ftm_output_brand_favicon', 1);
function ftm_output_brand_favicon() {
    $logo_url = FTM_PLUGIN_URL . 'assets/images/logo.webp';
    echo '<link rel="icon" type="image/webp" href="' . esc_url($logo_url) . '">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . esc_url($logo_url) . '">' . "\n";
}

// 2b. Private app: keep search engines, AI crawlers and scrapers out
add_filter('wp_robots', function($robots) {
    return ['noindex' => true, 'nofollow' => true, 'noarchive' => true, 'nosnippet' => true, 'noimageindex' => true];
}, 99);
add_action('send_headers', function() {
    header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex', true);
});
add_filter('robots_txt', function($output, $public) {
    $bots = ['GPTBot', 'ChatGPT-User', 'OAI-SearchBot', 'ClaudeBot', 'Claude-Web', 'anthropic-ai', 'CCBot', 'Google-Extended', 'PerplexityBot', 'Bytespider', 'Amazonbot', 'Applebot-Extended', 'FacebookBot', 'cohere-ai', 'Diffbot', 'ImagesiftBot', 'Omgilibot'];
    $txt = "User-agent: *\nDisallow: /\n\n";
    foreach ($bots as $b) {
        $txt .= "User-agent: $b\nDisallow: /\n\n";
    }
    return $txt;
}, 99, 2);
add_filter('wp_sitemaps_enabled', '__return_false');
// Visitors who are not signed in cannot list users (names/usernames) through the REST API or ?author=N
add_filter('rest_endpoints', function($endpoints) {
    if (!is_user_logged_in()) {
        foreach (array_keys($endpoints) as $route) {
            if (strpos($route, '/wp/v2/users') === 0) {
                unset($endpoints[$route]);
            }
        }
    }
    return $endpoints;
});
add_action('template_redirect', function() {
    if (!is_user_logged_in() && (is_author() || isset($_GET['author']))) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
}, 0);
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
add_action('init', function() {
    if (is_admin() || (defined('WP_CLI') && WP_CLI) || (defined('DOING_CRON') && DOING_CRON)) {
        return;
    }
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '';
    if ($ua !== '' && preg_match('/GPTBot|ChatGPT-User|OAI-SearchBot|ClaudeBot|Claude-Web|anthropic-ai|CCBot|PerplexityBot|Bytespider|Amazonbot|Applebot|cohere-ai|Diffbot|ImagesiftBot|Omgilibot|Scrapy|HTTrack|SemrushBot|AhrefsBot|MJ12bot|DotBot|PetalBot|BLEXBot|DataForSeoBot|Googlebot|bingbot|Baiduspider|YandexBot|DuckDuckBot|Slurp|Sogou|Exabot|facebookexternalhit|Twitterbot|LinkedInBot|Slackbot|WhatsApp|Pinterestbot|archive\.org_bot|ia_archiver/i', $ua)) {
        status_header(403);
        header('X-Robots-Tag: noindex, nofollow', true);
        exit('Forbidden');
    }
}, 1);

// Months reported live from project dates (Oct 2026 onwards), newest first. Earlier months come from the verified archive.
function ftm_live_months() {
    $out = [];
    $cursor = strtotime(current_time('Y-m') . '-01');
    $first = strtotime('2026-10-01');
    while ($cursor >= $first) {
        $out[] = ['key' => date('Y-m', $cursor), 'label' => date('F Y', $cursor), 'current' => date('Y-m', $cursor) === current_time('Y-m')];
        $cursor = strtotime('-1 month', $cursor);
    }
    return $out;
}

// Custom Avatar Filter with High-Definition UI-Avatars Fallback
add_filter('pre_get_avatar_data', function($args, $id_or_email) {
    $user_id = 0;
    if (is_numeric($id_or_email)) {
        $user_id = (int)$id_or_email;
    } elseif ($id_or_email instanceof WP_User) {
        $user_id = $id_or_email->ID;
    } elseif ($id_or_email instanceof WP_Post) {
        $user_id = (int)$id_or_email->post_author;
    } elseif (is_string($id_or_email) && ($user = get_user_by('email', $id_or_email))) {
        $user_id = $user->ID;
    }
    if ($user_id > 0) {
        $custom_avatar = get_user_meta($user_id, 'ftm_custom_avatar', true);
        if (!empty($custom_avatar)) {
            $args['url'] = esc_url($custom_avatar);
        } else {
            $u = get_userdata($user_id);
            $name = $u ? $u->display_name : 'Specialist';
            $bg = FTM_Permissions::is_super_admin($user_id) ? '0f172a' : '2563eb';
            $args['url'] = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=' . $bg . '&color=fff&size=128&bold=true';
        }
    }
    return $args;
}, 10, 2);

// Register REST API Routes
add_action('rest_api_init', function() {
    $projects_controller = new FTM_Projects_Controller();
    $projects_controller->register_routes();

    $daily_controller = new FTM_Daily_Report_Controller();
    $daily_controller->register_routes();

    $monthly_controller = new FTM_Monthly_Report_Controller();
    $monthly_controller->register_routes();

    $notices_controller = new FTM_Notices_Controller();
    $notices_controller->register_routes();

    $messages_controller = new FTM_Messages_Controller();
    $messages_controller->register_routes();

    $tasks_controller = new FTM_Tasks_Controller();
    $tasks_controller->register_routes();

    $leaves_controller = new FTM_Leaves_Controller();
    $leaves_controller->register_routes();

    $time_tracker_controller = new FTM_Time_Tracker_Controller();
    $time_tracker_controller->register_routes();

    $isolation_controller = new FTM_Isolation_Controller();
    $isolation_controller->register_routes();

    $legacy_controller = new FTM_Legacy_Controller();
    $legacy_controller->register_routes();
});

// Auto-Redirect Front Page to Dashboard or Login if no static front page is assigned
add_action('template_redirect', function() {
    if (is_front_page() || is_home()) {
        $front_page_id = (int)get_option('page_on_front');
        if ($front_page_id === 0) {
            if (is_user_logged_in()) {
                wp_safe_redirect(site_url('/team-dashboard/'));
            } else {
                wp_safe_redirect(site_url('/team-login/'));
            }
            exit;
        }
    }
});

// Signed-out visitors never get the dashboard shell: send them to the login page
add_action('template_redirect', function() {
    if (!is_user_logged_in() && is_page(['team-dashboard', 'team-members', 'fiverr-profiles', 'profiles', 'my-projects'])) {
        wp_safe_redirect(site_url('/team-login/'));
        exit;
    }
}, 1);

// Enqueue Frontend Assets & Localize State
add_action('wp_enqueue_scripts', function() {
    $has_plugin_shortcode = false;
    $post = get_post();
    if ($post && !empty($post->post_content)) {
        $shortcodes = [
            'ftm_dashboard', 'team_dashboard',
            'ftm_login', 'team_login',
            'team_members', 'team_fiverr_profiles', 'team_my_projects'
        ];
        foreach ($shortcodes as $sc) {
            if (has_shortcode($post->post_content, $sc)) {
                $has_plugin_shortcode = true;
                break;
            }
        }
    }

    $is_plugin_page = is_page([
        'team-dashboard', 'team-login', 'team-members',
        'fiverr-profiles', 'profiles', 'my-projects'
    ]);

    if ($is_plugin_page || $has_plugin_shortcode) {
        $css_ver = FTM_VERSION . '.' . (file_exists(FTM_PLUGIN_DIR . 'assets/css/frontend.css') ? filemtime(FTM_PLUGIN_DIR . 'assets/css/frontend.css') : '1');
        $js_ver  = FTM_VERSION . '.' . (file_exists(FTM_PLUGIN_DIR . 'assets/js/frontend.js') ? filemtime(FTM_PLUGIN_DIR . 'assets/js/frontend.js') : '1');
        wp_enqueue_style('ftm-font-jakarta', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap', [], null);
        wp_enqueue_style('ftm-frontend-css', FTM_PLUGIN_URL . 'assets/css/frontend.css', ['ftm-font-jakarta'], $css_ver);
        wp_enqueue_script('ftm-frontend-js', FTM_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], $js_ver, true);

        $chat_css_ver = FTM_VERSION . '.' . (file_exists(FTM_PLUGIN_DIR . 'assets/css/ftm-messages.css') ? filemtime(FTM_PLUGIN_DIR . 'assets/css/ftm-messages.css') : '1');
        $chat_js_ver  = FTM_VERSION . '.' . (file_exists(FTM_PLUGIN_DIR . 'assets/js/ftm-messages.js') ? filemtime(FTM_PLUGIN_DIR . 'assets/js/ftm-messages.js') : '1');
        wp_enqueue_style('ftm-messages-css', FTM_PLUGIN_URL . 'assets/css/ftm-messages.css', ['ftm-frontend-css'], $chat_css_ver);
        wp_enqueue_script('ftm-messages-js', FTM_PLUGIN_URL . 'assets/js/ftm-messages.js', ['jquery', 'ftm-frontend-js'], $chat_js_ver, true);

        $current_user = wp_get_current_user();
        $is_logged_in = is_user_logged_in();
        $user_id = $is_logged_in ? $current_user->ID : 0;
        
        $role = FTM_Permissions::get_user_role($user_id);
        $team = FTM_Permissions::get_user_team($user_id);
        $is_admin = FTM_Permissions::is_super_admin($user_id);
        $is_leader = FTM_Permissions::is_leader($user_id);
        $is_deputy = FTM_Permissions::is_deputy($user_id);
        $is_member = (!$is_admin && !$is_leader && !$is_deputy);

        $user_email_str = $is_logged_in ? strtolower(trim($current_user->user_email ?? '')) : '';
        $is_placeholder_email = empty($user_email_str) || str_ends_with($user_email_str, '.local') || str_ends_with($user_email_str, '.invalid') || str_ends_with($user_email_str, 'example.com');
        $email_updated = $is_logged_in ? (bool)get_user_meta($user_id, 'ftm_email_updated', true) : false;
        if ($is_logged_in && !$is_placeholder_email && !$email_updated) {
            update_user_meta($user_id, 'ftm_email_updated', 1);
            update_user_meta($user_id, 'ftm_profile_completed', 1);
            $email_updated = true;
        }
        $email_locked = ($is_logged_in && !$is_admin && $email_updated && !$is_placeholder_email);
        $profile_setup_needed = ($is_logged_in && $is_placeholder_email);

        wp_localize_script('ftm-frontend-js', 'ftmData', [
            'root' => esc_url_raw(rest_url('ftm/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'plugin_url' => FTM_PLUGIN_URL,
            'site_url' => site_url(),
            'dashboard_url' => site_url('/team-dashboard/'),
            'login_url' => site_url('/team-login/'),
            'is_user_logged_in' => $is_logged_in ? '1' : '',
            'current_user' => [
                'id' => $user_id,
                'name' => $is_logged_in ? $current_user->display_name : false,
                'email' => $is_logged_in ? $current_user->user_email : false,
                'avatar' => get_avatar_url($user_id, ['size' => 96, 'default' => 'mm']),
                'is_admin' => $is_admin,
                'is_leader' => $is_leader,
                'is_deputy' => $is_deputy,
                'is_member' => $is_member,
                'role' => $role,
                'team' => $team,
                'role_label' => FTM_Permissions::get_role_label($role),
                'email_locked' => $email_locked,
                'profile_setup_needed' => $profile_setup_needed
            ]
        ]);
    }
});

// View Renderers
function ftm_render_dashboard() {
    if (!is_user_logged_in()) {
        return ftm_render_login();
    }
    ob_start();
    include FTM_PLUGIN_DIR . 'templates/dashboard.php';
    return ob_get_clean();
}

function ftm_render_login() {
    if (is_user_logged_in() && empty($_GET['preview_login'])) {
        $dash_url = esc_url(site_url('/team-dashboard/'));
        return '<script>window.location.replace("' . $dash_url . '");</script>' .
               '<div class="ftm-login-body"><div class="ftm-login-container"><div class="ftm-login-card" style="text-align:center;">' .
               '<h3 style="font-size:20px; font-weight:700; margin-bottom:8px;">Already Logged In</h3>' .
               '<p style="margin: 12px 0 20px 0; color: #64748b; font-size:14px;">You are currently logged into the team portal.</p>' .
               '<a href="' . $dash_url . '" class="ftm-btn ftm-btn-primary" style="display:inline-flex; align-items:center; justify-content:center; padding:10px 24px; text-decoration:none;">Go to Dashboard</a>' .
               '</div></div></div>';
    }
    ob_start();
    include FTM_PLUGIN_DIR . 'templates/login.php';
    return ob_get_clean();
}

function ftm_render_dashboard_view($view) {
    if (!is_user_logged_in()) {
        return ftm_render_login();
    }
    $target = esc_url(site_url('/team-dashboard/#' . $view));
    return '<script>window.location.replace("' . $target . '");</script>' .
           '<div style="text-align:center; padding:40px;"><p style="font-size:14px; color:#64748b;">Loading ' . esc_html($view) . '...</p><p><a href="' . $target . '" class="ftm-btn ftm-btn-primary" style="display:inline-block; margin-top:10px; text-decoration:none;">Click here if not redirected</a></p></div>';
}

// Register All Dashboard & Login Shortcodes (Modern & Legacy Compatibility)
add_shortcode('ftm_dashboard', 'ftm_render_dashboard');
add_shortcode('team_dashboard', 'ftm_render_dashboard');
add_shortcode('ftm_login', 'ftm_render_login');
add_shortcode('team_login', 'ftm_render_login');
add_shortcode('team_members', function() {
    return ftm_render_dashboard_view('team-members');
});
add_shortcode('team_fiverr_profiles', function() {
    return ftm_render_dashboard_view('fiverr-profiles');
});
add_shortcode('team_my_projects', function() {
    return ftm_render_dashboard_view('my-projects');
});
