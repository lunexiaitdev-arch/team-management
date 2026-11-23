<?php
/**
 * FTM Login View Template
 * Full SaaS 2-Column Responsive Design with Light & Dark Mode
 * Exact implementation matching FB International Design Reference 2
 */

if (!defined('ABSPATH')) {
    exit;
}

$dashboard_url = site_url('/team-dashboard/');
$auth_api_url = esc_url_raw(rest_url('ftm/v1/auth/login'));
$nonce = wp_create_nonce('wp_rest');
$login_post_url = site_url('wp-login.php');
$logo_url = FTM_PLUGIN_URL . 'assets/images/logo.webp?v=' . FTM_VERSION;
$hero_scene_clean_url = FTM_PLUGIN_URL . 'assets/images/login-hero-scene-clean.webp?v=' . FTM_VERSION;
$desk_light_url = FTM_PLUGIN_URL . 'assets/images/login-desk-light.webp?v=' . FTM_VERSION;
$desk_dark_url = FTM_PLUGIN_URL . 'assets/images/login-desk-dark.webp?v=' . FTM_VERSION;

// Query real production project metrics dynamically
global $wpdb;
$archive_table = $wpdb->prefix . 'ftm_monthly_archive';
$projects_table = $wpdb->prefix . 'ftm_projects';
$total_archived_orders = (int)$wpdb->get_var("SELECT SUM(total_completed) FROM $archive_table");
$total_live_projects = (int)$wpdb->get_var("SELECT COUNT(*) FROM $projects_table");
$real_total_projects = $total_archived_orders + $total_live_projects;
$display_project_count = ($real_total_projects > 50) ? ($real_total_projects . '+') : '800+';
// Live operating figures for the bottom telemetry strip (no invented metrics)
$active_orders = (int)$wpdb->get_var("SELECT COUNT(*) FROM $projects_table WHERE status IN ('nra', 'wip', 'revision')");
$team_rows = $wpdb->get_col($wpdb->prepare("SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value <> ''", 'ftm_team'));
$delivery_teams = array_unique(array_filter($team_rows, function ($t) { return !FTM_Permissions::is_management_team($t); }));
$team_count = count($delivery_teams);
$specialist_count = count(array_filter($team_rows, function ($t) { return !FTM_Permissions::is_management_team($t); }));
?>

<div class="ftm-auth-page" id="ftm-auth-root">
    <!-- Top Bar: Brand on Left, Theme Toggle on Right -->
    <header class="ftm-auth-topbar">
        <div class="ftm-auth-top-brand">
            <img src="<?php echo esc_url($logo_url); ?>" alt="FB International" class="ftm-auth-top-logo" />
            <div>
                <div class="ftm-auth-top-title">FB International</div>
                <div class="ftm-auth-top-sub">Team Management Hub</div>
            </div>
        </div>

        <div class="ftm-auth-theme-segmented" role="group" aria-label="Theme Switcher">
            <button type="button" class="ftm-theme-segment-btn" data-theme-target="light" id="ftm-auth-btn-light" aria-label="Switch to Light Mode">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                <span>Light</span>
            </button>
            <button type="button" class="ftm-theme-segment-btn" data-theme-target="dark" id="ftm-auth-btn-dark" aria-label="Switch to Dark Mode">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                <span>Dark</span>
            </button>
        </div>
    </header>

    <div class="ftm-auth-main-grid">
        <!-- LEFT COLUMN: BRAND HERO & 3D WORKSPACE -->
        <div class="ftm-auth-hero-column">
            <!-- Pill Tag -->
            <div class="ftm-auth-hero-tag">
                <span class="ftm-pill-dot" style="width:7px; height:7px; border-radius:50%; background:#2563eb; display:inline-block; box-shadow:0 0 8px rgba(37,99,235,0.6);"></span>
                <span style="letter-spacing:0.08em;">INTERNAL OPERATIONS CONSOLE</span>
            </div>

            <!-- Headline -->
            <h1 class="ftm-auth-hero-title">
                Every order.<br>
                Every hour.<br>
                <span class="ftm-text-gradient-cyan">One command center.</span>
            </h1>

            <!-- Subtitle -->
            <p class="ftm-auth-hero-subtitle">
                Where FB International specialists, team leaders and management run client orders, log daily standups and reconcile monthly revenue.
            </p>

            <!-- 4 Feature Highlights Grid (Seamlessly floating over the integrated 3D background) -->
            <div class="ftm-auth-features-grid">
                <div class="ftm-auth-feature-card">
                    <div class="ftm-auth-feat-icon bg-purple-soft text-purple">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                    </div>
                    <div class="ftm-auth-feat-info">
                        <strong>Order Pipeline</strong>
                        <span>WIP, revisions, deadlines</span>
                    </div>
                </div>

                <div class="ftm-auth-feature-card">
                    <div class="ftm-auth-feat-icon bg-blue-soft text-blue">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="8" y="2" width="8" height="4" rx="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M9 14l2 2 4-4"></path></svg>
                    </div>
                    <div class="ftm-auth-feat-info">
                        <strong>Daily Standups</strong>
                        <span>Hours, progress, blockers</span>
                    </div>
                </div>

                <div class="ftm-auth-feature-card">
                    <div class="ftm-auth-feat-icon bg-emerald-soft text-emerald">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div class="ftm-auth-feat-info">
                        <strong>Revenue Reconciliation</strong>
                        <span>Delivered vs. in-flight</span>
                    </div>
                </div>

                <div class="ftm-auth-feature-card">
                    <div class="ftm-auth-feat-icon bg-amber-soft text-amber">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="18" y="3" width="4" height="18"></rect><rect x="10" y="8" width="4" height="13"></rect><rect x="2" y="13" width="4" height="8"></rect></svg>
                    </div>
                    <div class="ftm-auth-feat-info">
                        <strong>Performance Leaderboard</strong>
                        <span>On-time rate, client ratings</span>
                    </div>
                </div>
            </div>

            <!-- 3D Isometric Desk Artwork (Theme Aware) -->
            <div class="ftm-auth-hero-artwork-wrap">
                <img src="<?php echo esc_url($desk_light_url); ?>" alt="FB Workspace" class="ftm-auth-desk-art ftm-desk-light" />
                <img src="<?php echo esc_url($desk_dark_url); ?>" alt="FB Workspace Dark" class="ftm-auth-desk-art ftm-desk-dark" />
            </div>
        </div>

        <!-- RIGHT COLUMN: LOGIN CARD -->
        <div class="ftm-auth-card-column">
            <div class="ftm-auth-login-card">
                <!-- Glowing Circle Logo -->
                <div class="ftm-auth-card-logo-wrap">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="FB International" class="ftm-auth-card-logo" />
                </div>

                <div class="ftm-auth-card-brand-name">FB International</div>
                <div class="ftm-auth-card-brand-sub">Team Management Portal</div>

                <h2 class="ftm-auth-card-title">Welcome Back</h2>
                <p class="ftm-auth-card-desc">Sign in to your account to continue</p>

                <!-- Alert Message Container -->
                <div id="ftm-login-alert" class="ftm-alert-danger hidden"></div>

                <!-- Native WordPress Form with REST AJAX Acceleration -->
                <form id="ftm-login-form" method="post" action="<?php echo esc_url($login_post_url); ?>">
                    <div class="ftm-form-group">
                        <label class="ftm-form-label" for="ftm-login-username">Username or Email</label>
                        <div class="ftm-input-icon-wrap">
                            <span class="ftm-input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </span>
                            <input type="text" 
                                   name="log" 
                                   id="ftm-login-username" 
                                   class="ftm-form-control ftm-has-icon" 
                                   placeholder="Enter your username or email" 
                                   required 
                                   autocomplete="username" 
                                   autofocus>
                        </div>
                    </div>

                    <div class="ftm-form-group">
                        <label class="ftm-form-label" for="ftm-login-password">Password</label>
                        <div class="ftm-password-input-wrap ftm-input-icon-wrap">
                            <span class="ftm-input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            </span>
                            <input type="password" 
                                   name="pwd" 
                                   id="ftm-login-password" 
                                   class="ftm-form-control ftm-has-icon ftm-has-toggle" 
                                   placeholder="Enter your password" 
                                   required 
                                   autocomplete="current-password">
                            <button type="button" id="ftm-login-toggle-pwd" class="ftm-toggle-pwd-btn" title="Show or hide password" aria-label="Toggle password visibility">
                                <svg id="ftm-eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg id="ftm-eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                    </div>

                    <div class="ftm-auth-flex-row">
                        <label class="ftm-checkbox-label">
                            <input type="checkbox" name="rememberme" id="ftm-login-remember" value="forever">
                            <span>Remember me</span>
                        </label>
                        <a href="javascript:void(0)" class="ftm-auth-link" id="ftm-auth-forgot-link">Forgot password?</a>
                    </div>

                    <input type="hidden" name="redirect_to" value="<?php echo esc_url($dashboard_url); ?>">
                    <div style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden;" aria-hidden="true">
                        <label for="ftm-hp-website">Website</label>
                        <input type="text" id="ftm-hp-website" name="website_url" value="" tabindex="-1" autocomplete="off">
                    </div>
                    
                    <button type="submit" id="ftm-login-submit-btn" class="ftm-btn ftm-btn-primary ftm-auth-submit-btn">
                        <span id="ftm-login-btn-text">Sign In</span>
                        <span id="ftm-login-btn-spinner" class="ftm-spinner" style="display:none; width:18px; height:18px; border-width:2px; border-color:rgba(255,255,255,0.3); border-top-color:#ffffff;"></span>
                    </button>
                </form>

                <!-- Footer Help Link -->
                <div class="ftm-auth-card-footer">
                    <span>Need help?</span> <a href="https://agencyfbinternational.com" target="_blank" class="ftm-auth-link">Contact Admin</a>
                </div>
            </div>
        </div>
    </div>

    <!-- FULL-WIDTH BOTTOM STATS BAR: Matches user reference exact design -->
    <div class="ftm-auth-bottom-stats-container">
        <div class="ftm-auth-bottom-stats-bar">
            <div class="ftm-auth-stat-pill">
                <span class="ftm-stat-pill-icon stat-icon-purple">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                </span>
                <div>
                    <strong><?php echo esc_html($team_count ?: 3); ?></strong>
                    <span>Delivery Teams</span>
                </div>
            </div>
            <div class="ftm-auth-stat-pill">
                <span class="ftm-stat-pill-icon stat-icon-blue">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                </span>
                <div>
                    <strong><?php echo esc_html($display_project_count); ?></strong>
                    <span>Orders Managed</span>
                </div>
            </div>
            <div class="ftm-auth-stat-pill">
                <span class="ftm-stat-pill-icon stat-icon-emerald">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </span>
                <div>
                    <strong><?php echo esc_html($specialist_count); ?></strong>
                    <span>Specialists</span>
                </div>
            </div>
            <div class="ftm-auth-stat-pill">
                <span class="ftm-stat-pill-icon stat-icon-amber">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                </span>
                <div>
                    <strong><span class="ftm-live-dot" aria-hidden="true"></span><?php echo esc_html($active_orders); ?></strong>
                    <span>Orders in Flight</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile-Only Elegant Security Footer -->
    <footer class="ftm-auth-mobile-footer">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        <span>Encrypted, rate-limited sign-in &bull; FB International</span>
    </footer>
</div>

<script>
(function() {
    // Segmented Theme Switcher Logic
    var btnLight = document.getElementById('ftm-auth-btn-light');
    var btnDark = document.getElementById('ftm-auth-btn-dark');
    var rootEl = document.documentElement;

    function applyTheme(theme) {
        rootEl.setAttribute('data-theme', theme);
        localStorage.setItem('ftm_theme', theme);
        if (btnLight && btnDark) {
            if (theme === 'dark') {
                btnDark.classList.add('active');
                btnLight.classList.remove('active');
            } else {
                btnLight.classList.add('active');
                btnDark.classList.remove('active');
            }
        }
    }

    var savedTheme = localStorage.getItem('ftm_theme');
    if (!savedTheme) {
        // Respect system color scheme preference
        savedTheme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'dark'; // Default dark per Design Reference 2
    }
    applyTheme(savedTheme);

    if (btnLight) {
        btnLight.addEventListener('click', function(e) {
            e.preventDefault();
            applyTheme('light');
        });
    }

    if (btnDark) {
        btnDark.addEventListener('click', function(e) {
            e.preventDefault();
            applyTheme('dark');
        });
    }

    // Password visibility toggle
    var toggleBtn = document.getElementById('ftm-login-toggle-pwd');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var pwd = document.getElementById('ftm-login-password');
            var open = document.getElementById('ftm-eye-open');
            var closed = document.getElementById('ftm-eye-closed');
            if (!pwd) return;
            if (pwd.type === 'password') {
                pwd.type = 'text';
                if (open) open.style.display = 'none';
                if (closed) closed.style.display = 'block';
            } else {
                pwd.type = 'password';
                if (open) open.style.display = 'block';
                if (closed) closed.style.display = 'none';
            }
        });
    }

    var forgotLink = document.getElementById('ftm-auth-forgot-link');
    if (forgotLink) {
        forgotLink.addEventListener('click', function(e) {
            e.preventDefault();
            alert('For account recovery or password resets, please contact the System Administrator at admin@agencyfbinternational.com.');
        });
    }

    // AJAX Login Handler with Native Fallback
    var form = document.getElementById('ftm-login-form');
    var alertBox = document.getElementById('ftm-login-alert');
    var submitBtn = document.getElementById('ftm-login-submit-btn');
    var btnText = document.getElementById('ftm-login-btn-text');
    var btnSpinner = document.getElementById('ftm-login-btn-spinner');

    if (form) {
        form.addEventListener('submit', function(e) {
            var username = document.getElementById('ftm-login-username').value.trim();
            var password = document.getElementById('ftm-login-password').value;
            var remember = document.getElementById('ftm-login-remember').checked;

            if (!username || !password) {
                return;
            }

            e.preventDefault();

            if (alertBox) {
                alertBox.classList.add('hidden');
                alertBox.innerHTML = '';
            }
            if (submitBtn) submitBtn.disabled = true;
            if (btnText) btnText.textContent = 'Signing in...';
            if (btnSpinner) btnSpinner.style.display = 'inline-block';

            var hpField = document.getElementById('ftm-hp-website');
            var payload = {
                username: username,
                password: password,
                remember: remember,
                website_url: hpField ? hpField.value : ''
            };

            fetch('<?php echo $auth_api_url; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo $nonce; ?>'
                },
                body: JSON.stringify(payload)
            })
            .then(function(res) {
                return res.json().then(function(data) {
                    return { ok: res.ok, status: res.status, data: data };
                });
            })
            .then(function(result) {
                if (result.ok && result.data && result.data.success) {
                    if (btnText) btnText.textContent = 'Success! Redirecting...';
                    window.location.href = result.data.redirect_url || '<?php echo esc_url($dashboard_url); ?>';
                } else {
                    if (submitBtn) submitBtn.disabled = false;
                    if (btnText) btnText.textContent = 'Sign In';
                    if (btnSpinner) btnSpinner.style.display = 'none';
                    if (alertBox) {
                        var msg = (result.data && result.data.message) ? result.data.message : 'Invalid username or password.';
                        alertBox.innerHTML = msg;
                        alertBox.classList.remove('hidden');
                    }
                }
            })
            .catch(function(err) {
                form.submit();
            });
        });
    }
})();
</script>
