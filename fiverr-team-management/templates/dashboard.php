<?php
/**
 * FTM Dashboard View Template
 * Fully Restored Production Template with All Legacy and Modern Views & Modals
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}







$current_user_id = get_current_user_id();



$is_admin = FTM_Permissions::is_super_admin($current_user_id);

$is_leader = FTM_Permissions::is_leader($current_user_id);

$is_deputy = FTM_Permissions::is_deputy($current_user_id);

$is_member = (!$is_admin && !$is_leader && !$is_deputy);

$role = FTM_Permissions::get_user_role($current_user_id);

$team = FTM_Permissions::get_user_team($current_user_id);

$can_manage_all_squads = ($is_admin || FTM_Permissions::is_executive($current_user_id));



?>



<link rel="icon" type="image/webp" href="<?php echo esc_url(FTM_PLUGIN_URL . 'assets/images/logo.webp'); ?>">







<style>



/* FTM Dashboard Layout Core Engine */



header.wp-block-template-part,



footer.wp-block-template-part,



h1.wp-block-post-title,



.entry-title,



.wp-block-post-title {



    display: none !important;



}







@media screen {



    html {



        overflow-x: clip !important;



    }







    body:has(#ftm-app),



    body.page-template-default:has(#ftm-app),



    .page-slug-team-dashboard {



        margin: 0 !important;



        padding: 0 !important;



        background-color: var(--ftm-bg) !important;



        overflow-x: clip !important;



    }



}







.ftm-header {



    position: sticky !important;



    top: 0 !important;



    z-index: 100 !important;



}







.ftm-sidebar {



    position: sticky !important;



    top: 68px !important;



    height: calc(100vh - 68px) !important;



    max-height: calc(100vh - 68px) !important;



}







body.admin-bar #wpadminbar,



#wpadminbar {



    display: none !important;



}







html,



body,



body.admin-bar {



    margin-top: 0 !important;



    padding-top: 0 !important;



}







@media (max-width: 782px) {



    .ftm-header {



        top: 0 !important;



    }



    .ftm-sidebar {



        top: 60px !important;



    }



}







main#wp--skip-link--target,



.entry-content,



.wp-block-group,



.wp-site-blocks,



.wp-block-post-content,



.is-layout-flow,



.is-layout-constrained {



    margin: 0 !important;



    padding: 0 !important;



    max-width: 100% !important;



    width: 100% !important;



}







:root :where(.is-layout-constrained > #ftm-app),



.is-layout-constrained > #ftm-app,



#ftm-app.ftm-app-layout,



#ftm-app.alignfull {



    display: flex !important;



    flex-direction: column !important;



    min-height: 100vh !important;



    background-color: var(--ftm-bg) !important;



    color: var(--ftm-text-primary) !important;



    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;



    max-width: 100% !important;



    width: 100% !important;



    margin-left: 0 !important;



    margin-right: 0 !important;



    margin-top: 0 !important;



    box-sizing: border-box !important;



}







@media print {



    html, body, .wp-site-blocks, main, .wp-block-group, .entry-content, .is-layout-constrained, #ftm-app {



        display: block !important;



        position: static !important;



        width: 100% !important;



        max-width: 100% !important;



        min-height: 0 !important;



        height: auto !important;



        margin: 0 !important;



        padding: 0 !important;



        overflow: visible !important;



        background-color: #ffffff !important;



    }



}







.entry-content.is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)) {



    max-width: 100% !important;



}







.ftm-header {



    position: sticky !important;



    top: 0 !important;



    z-index: 100 !important;



    display: flex !important;



    align-items: center !important;



    justify-content: space-between !important;



    padding: 0 28px !important;



    background-color: var(--ftm-header-bg) !important;



    border-bottom: 1px solid var(--ftm-border) !important;



    height: 68px !important;



    box-sizing: border-box !important;



    width: 100% !important;



}







.ftm-header-left {



    display: flex !important;



    align-items: center !important;



    gap: 16px !important;



}







.ftm-header-right {



    display: flex !important;



    align-items: center !important;



    gap: 16px !important;



    margin-left: auto !important;



}







.ftm-brand {



    display: flex !important;



    align-items: center !important;



    gap: 12px !important;



}







.ftm-brand-icon {



    width: 44px !important;



    height: 44px !important;



    max-width: 44px !important;



    max-height: 44px !important;



    border-radius: 10px !important;



    display: flex !important;



    align-items: center !important;



    justify-content: center !important;



    overflow: hidden !important;



    flex-shrink: 0 !important;



}







.ftm-logo-img {



    width: 44px !important;



    height: 44px !important;



    max-width: 44px !important;



    max-height: 44px !important;



    object-fit: contain !important;



    display: block !important;



}







.ftm-brand-text h1 {



    margin: 0 !important;



    font-size: 18px !important;



    font-weight: 700 !important;



    color: var(--ftm-text-primary) !important;



    line-height: 1.2 !important;



}







.ftm-brand-subtitle {



    font-size: 12px !important;



    font-weight: 500 !important;



    color: var(--ftm-text-muted) !important;



}







.ftm-team-pill {



    display: inline-flex !important;



    align-items: center !important;



    gap: 6px !important;



    background-color: var(--ftm-border-light) !important;



    padding: 6px 14px !important;



    border-radius: 9999px !important;



    font-size: 12.5px !important;



    font-weight: 600 !important;



    color: var(--ftm-text-secondary) !important;



    border: 1px solid var(--ftm-border) !important;



}







.ftm-team-indicator {



    width: 8px !important;



    height: 8px !important;



    border-radius: 50% !important;



    background-color: #10b981 !important;



    display: inline-block !important;



}







.ftm-main-container {



    display: grid !important;



    grid-template-columns: 240px minmax(0, 1fr) !important;



    min-height: calc(100vh - 68px) !important;



    width: 100% !important;



    max-width: 100% !important;



    position: relative !important;



    box-sizing: border-box !important;



}







.ftm-sidebar {



    background-color: var(--ftm-sidebar-bg) !important;



    border-right: 1px solid var(--ftm-border) !important;



    padding: 24px 14px 16px 14px !important;



    display: flex !important;



    flex-direction: column !important;



    justify-content: space-between !important;



    width: 240px !important;



    box-sizing: border-box !important;



    position: sticky !important;



    top: 68px !important;



    height: calc(100vh - 68px) !important;



    overflow-y: auto !important;



}







.ftm-nav,



.ftm-nav-menu {



    display: flex !important;



    flex-direction: column !important;



    gap: 4px !important;



}







.ftm-nav-group-title {



    font-size: 11px !important;



    font-weight: 700 !important;



    color: var(--ftm-text-muted) !important;



    text-transform: uppercase !important;



    letter-spacing: 0.05em !important;



    padding: 14px 14px 6px 14px !important;



}







.ftm-nav-item {



    display: flex !important;



    align-items: center !important;



    gap: 12px !important;



    padding: 10px 14px !important;



    color: var(--ftm-text-secondary) !important;



    font-size: 13.5px !important;



    font-weight: 600 !important;



    text-decoration: none !important;



    border-radius: 8px !important;



    transition: all 0.2s ease !important;



}







.ftm-nav-item:hover {



    background-color: var(--ftm-border-light) !important;



    color: var(--ftm-primary) !important;



}







.ftm-nav-item.active {



    background-color: #2563eb !important;



    color: #ffffff !important;



    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2) !important;



}







.ftm-sidebar-footer {



    padding-top: 14px !important;



    border-top: 1px solid var(--ftm-border) !important;



    margin-top: auto !important;



    flex-shrink: 0 !important;



    position: sticky !important;



    bottom: 0 !important;



    background: var(--ftm-sidebar-bg) !important;



    z-index: 5 !important;



}







.ftm-logout-link {



    display: flex !important;



    align-items: center !important;



    gap: 10px !important;



    padding: 10px 14px !important;



    color: #ef4444 !important;



    font-size: 13.5px !important;



    font-weight: 600 !important;



    text-decoration: none !important;



    border-radius: 8px !important;



    transition: all 0.2s ease !important;



}







.ftm-logout-link:hover {



    background-color: rgba(239, 68, 68, 0.12) !important;



}







.ftm-main-workspace {



    flex: 1 !important;



    padding: 28px 32px !important;



    background-color: var(--ftm-bg) !important;



    overflow-x: hidden !important;



    box-sizing: border-box !important;



    min-width: 0 !important;



}







/* Hide Desktop-only and Mobile-only controls */



@media (min-width: 1025px) {



    .ftm-hide-desktop,



    #ftm-mobile-toggle,



    .ftm-sidebar-overlay,



    .ftm-sidebar-header,



    .ftm-sidebar-user-card {



        display: none !important;



    }



}







@media (max-width: 1024px) {



    .ftm-main-container {



        grid-template-columns: 1fr !important;



    }



    .ftm-hide-desktop {



        display: inline-flex !important;



    }



    .ftm-hide-mobile,



    #ftm-btn-new-daily-standup,



    #ftm-btn-add-project,



    .ftm-user-meta,



    .ftm-team-pill,



    .ftm-brand-subtitle {



        display: none !important;



    }







    /* Clean Mobile Header */



    .ftm-header {



        padding: 0 14px !important;



        height: 60px !important;



        display: flex !important;



        align-items: center !important;



        justify-content: space-between !important;



        gap: 8px !important;



        position: sticky !important;



        top: 0 !important;



        z-index: 50 !important;



    }



    .ftm-header-left {



        gap: 10px !important;



        min-width: 0 !important;



        flex: 0 1 auto !important;



        display: flex !important;



        align-items: center !important;



    }



    .ftm-brand {



        gap: 8px !important;



        min-width: 0 !important;



        display: flex !important;



        align-items: center !important;



        flex-shrink: 0 !important;



    }



    .ftm-brand-icon {



        width: 36px !important;



        height: 36px !important;



        max-width: 36px !important;



        max-height: 36px !important;



        font-size: 15px !important;



        border-radius: 9px !important;



        flex-shrink: 0 !important;



    }



    .ftm-logo-img {



        width: 36px !important;



        height: 36px !important;



        max-width: 36px !important;



        max-height: 36px !important;



        flex-shrink: 0 !important;



    }



    .ftm-brand-text {



        min-width: 0 !important;



        overflow: hidden !important;



        display: flex !important;



        flex-direction: column !important;



    }



    .ftm-brand-text h1 {



        font-size: 14.5px !important;



        font-weight: 700 !important;



        white-space: nowrap !important;



        overflow: hidden !important;



        text-overflow: ellipsis !important;



        max-width: 140px !important;



        line-height: 1.2 !important;



        margin: 0 !important;



    }



    .ftm-header-right {



        gap: 8px !important;



        margin-left: auto !important;



        flex-shrink: 0 !important;



        display: flex !important;



        align-items: center !important;



    }



    .ftm-theme-toggle-btn,



    .ftm-icon-btn {



        width: 38px !important;



        height: 38px !important;



        min-width: 38px !important;



        border-radius: 9px !important;



        flex-shrink: 0 !important;



        display: flex !important;



        align-items: center !important;



        justify-content: center !important;



    }



    .ftm-user-menu-wrap {



        flex-shrink: 0 !important;



    }



    .ftm-user-profile-btn {



        padding: 2px !important;



        border-radius: 50% !important;



        display: flex !important;



        align-items: center !important;



    }



    .ftm-avatar.ftm-avatar-sm {



        width: 34px !important;



        height: 34px !important;



        border-radius: 50% !important;



        flex-shrink: 0 !important;



    }







    /* Lock background scroll when drawer is open */



    body.ftm-drawer-open {



        overflow: hidden !important;



    }







    /* Mobile Sidebar & Overlay: Full-height native drawer with dedicated header */



    .ftm-sidebar-overlay {



        display: block !important;



        position: fixed !important;



        inset: 0 !important;



        background-color: rgba(15, 23, 42, 0.65) !important;



        backdrop-filter: blur(4px) !important;



        -webkit-backdrop-filter: blur(4px) !important;



        z-index: 9998 !important;



        opacity: 0 !important;



        pointer-events: none !important;



        transition: opacity 0.25s ease !important;



    }



    .ftm-sidebar-overlay.open {



        opacity: 1 !important;



        pointer-events: auto !important;



    }



    .ftm-sidebar {



        position: fixed !important;



        top: 0 !important;



        bottom: 0 !important;



        left: -320px !important;



        width: 285px !important;



        max-width: 86vw !important;



        z-index: 9999 !important;



        height: 100vh !important;



        height: 100dvh !important;



        max-height: 100vh !important;



        max-height: 100dvh !important;



        display: flex !important;



        flex-direction: column !important;



        justify-content: flex-start !important;



        padding: 0 !important;



        overflow: hidden !important;



        transition: left 0.28s cubic-bezier(0.16, 1, 0.3, 1) !important;



        box-shadow: 10px 0 32px rgba(0, 0, 0, 0.25) !important;



        background-color: var(--ftm-card-bg, #ffffff) !important;



    }



    .ftm-sidebar.open {



        left: 0 !important;



    }



    .ftm-sidebar-header {



        display: flex !important;



        align-items: center !important;



        justify-content: space-between !important;



        padding: 16px 14px !important;



        border-bottom: 1px solid var(--ftm-border) !important;



        background: var(--ftm-card-bg, #ffffff) !important;



        flex-shrink: 0 !important;



    }



    .ftm-sidebar-brand {



        display: flex !important;



        align-items: center !important;



        gap: 10px !important;



        min-width: 0 !important;



    }



    .ftm-sidebar-logo {



        width: 38px !important;



        height: 38px !important;



        border-radius: 8px !important;



        object-fit: contain !important;



        flex-shrink: 0 !important;



        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2) !important;



    }



    .ftm-sidebar-brand-text {



        display: flex !important;



        flex-direction: column !important;



        min-width: 0 !important;



    }



    .ftm-sidebar-title {



        font-size: 14px !important;



        font-weight: 700 !important;



        color: var(--ftm-text, #0f172a) !important;



        line-height: 1.2 !important;



        white-space: nowrap !important;



        overflow: hidden !important;



        text-overflow: ellipsis !important;



    }



    .ftm-sidebar-sub {



        font-size: 11px !important;



        color: var(--ftm-text-muted, #64748b) !important;



        line-height: 1.2 !important;



        margin-top: 1px !important;



    }



    .ftm-sidebar-close-btn {



        width: 32px !important;



        height: 32px !important;



        display: flex !important;



        align-items: center !important;



        justify-content: center !important;



        background: var(--ftm-bg, #f1f5f9) !important;



        border: 1px solid var(--ftm-border, #e2e8f0) !important;



        border-radius: 8px !important;



        color: var(--ftm-text-muted, #64748b) !important;



        cursor: pointer !important;



        padding: 0 !important;



        transition: all 0.15s ease !important;



        flex-shrink: 0 !important;



    }



    .ftm-sidebar-close-btn:hover {



        background: var(--ftm-border, #e2e8f0) !important;



        color: var(--ftm-text, #0f172a) !important;



    }



    .ftm-sidebar-user-card {



        display: flex !important;



        align-items: center !important;



        gap: 10px !important;



        padding: 10px 12px !important;



        margin: 12px 14px 4px 14px !important;



        background: var(--ftm-bg, #f8fafc) !important;



        border: 1px solid var(--ftm-border, #e2e8f0) !important;



        border-radius: 10px !important;



        flex-shrink: 0 !important;



    }



    .ftm-sidebar-user-avatar-wrap {



        position: relative !important;



        flex-shrink: 0 !important;



    }



    .ftm-sidebar-user-avatar {



        width: 36px !important;



        height: 36px !important;



        border-radius: 50% !important;



        object-fit: cover !important;



        border: 2px solid var(--ftm-border) !important;



        display: block !important;



    }



    .ftm-sidebar-user-status {



        position: absolute !important;



        bottom: 0 !important;



        right: 0 !important;



        width: 8px !important;



        height: 8px !important;



        background: #10b981 !important;



        border: 1.5px solid var(--ftm-bg, #f8fafc) !important;



        border-radius: 50% !important;



    }



    .ftm-sidebar-user-info {



        display: flex !important;



        flex-direction: column !important;



        min-width: 0 !important;



        flex: 1 1 auto !important;



    }



    .ftm-sidebar-user-name {



        font-size: 13px !important;



        font-weight: 700 !important;



        color: var(--ftm-text, #0f172a) !important;



        line-height: 1.3 !important;



        white-space: nowrap !important;



        overflow: hidden !important;



        text-overflow: ellipsis !important;



    }



    .ftm-sidebar-user-meta {



        display: flex !important;



        align-items: center !important;



        gap: 6px !important;



        margin-top: 2px !important;



    }



    .ftm-sidebar .ftm-nav-menu {



        flex: 1 1 auto !important;



        overflow-y: auto !important;



        -webkit-overflow-scrolling: touch !important;



        padding: 10px 14px 14px 14px !important;



        gap: 4px !important;



    }



    .ftm-sidebar-footer {



        padding: 12px 14px !important;



        border-top: 1px solid var(--ftm-border) !important;



        background: var(--ftm-card-bg, #ffffff) !important;



        flex-shrink: 0 !important;



        margin-top: auto !important;



    }



    .ftm-logout-link {



        display: flex !important;



        align-items: center !important;



        gap: 10px !important;



        padding: 10px 14px !important;



        font-size: 14px !important;



        font-weight: 600 !important;



        border-radius: 8px !important;



        cursor: pointer !important;



    }







    .ftm-main-workspace {



        padding: 14px 12px 100px 12px !important;



    }







    /* Table & Footer Responsiveness */



    .ftm-table-card {



        max-width: 100% !important;



        overflow: hidden !important;



        border-radius: 14px !important;



    }



    .ftm-table-responsive {



        width: 100% !important;



        overflow-x: auto !important;



        -webkit-overflow-scrolling: touch !important;



    }



    .ftm-table-header {



        flex-direction: column !important;



        align-items: stretch !important;



        gap: 12px !important;



        padding: 14px 16px !important;



    }



    .ftm-table-title {



        font-size: 16px !important;



    }



    .ftm-table-actions {



        display: flex !important;



        flex-direction: column !important;



        gap: 8px !important;



        width: 100% !important;



    }



    .ftm-table-actions input,



    .ftm-table-actions select {



        width: 100% !important;



        min-width: 0 !important;



    }



    .ftm-table-footer {



        flex-direction: column !important;



        gap: 12px !important;



        align-items: center !important;



        text-align: center !important;



        padding: 14px 16px !important;



    }



    .ftm-pagination {



        display: flex !important;



        flex-wrap: wrap !important;



        justify-content: center !important;



        gap: 6px !important;



    }







    /* Bottom Nav Sizing & Alignment */



    .ftm-mobile-bottom-nav {



        display: flex !important;



        align-items: center !important;



        justify-content: space-around !important;



        position: fixed !important;



        bottom: 0 !important;



        left: 0 !important;



        right: 0 !important;



        height: 64px !important;



        z-index: 120 !important;



        padding: 0 4px !important;



        padding-bottom: env(safe-area-inset-bottom, 0px) !important;



        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.4) !important;



        backdrop-filter: blur(12px) !important;



        -webkit-backdrop-filter: blur(12px) !important;



    }



    .ftm-mob-nav-item {



        flex: 1 1 0 !important;



        min-width: 0 !important;



        gap: 2px !important;



        padding: 4px 0 !important;



    }



    .ftm-mob-nav-label {



        font-size: 10px !important;



    }



    .ftm-mob-action-center {



        flex: 0 0 56px !important;



        width: 56px !important;



    }



    .ftm-mob-action-btn {



        width: 48px !important;



        height: 48px !important;



        top: -22px !important;



    }







    /* Modals & Isolation on Mobile */



    .ftm-modal-backdrop {



        z-index: 300 !important;



        padding: 12px !important;



    }



    .ftm-modal-dialog {



        width: 100% !important;



        max-width: 520px !important;



        max-height: calc(100vh - 24px) !important;



        margin: auto !important;



        display: flex !important;



        flex-direction: column !important;



        overflow: hidden !important;



        border-radius: 18px !important;



        z-index: 301 !important;



    }



    .ftm-modal-body {



        max-height: calc(100vh - 160px) !important;



        overflow-y: auto !important;



        -webkit-overflow-scrolling: touch !important;



        flex: 1 1 auto !important;



    }



    .ftm-modal-footer {



        flex-shrink: 0 !important;



        padding: 14px 20px !important;



    }



}







/* Modal Active State: Hide Bottom Navigation Bar */



body:has(.ftm-modal-backdrop:not(.hidden)) #ftm-mobile-bottom-nav,



body.ftm-modal-open #ftm-mobile-bottom-nav {



    display: none !important;



}







/* COMPREHENSIVE FULL DARK MODE OVERRIDES */



:root[data-theme="dark"],



[data-theme="dark"] {



    --ftm-bg: #080b11 !important;



    --ftm-card-bg: #0f1523 !important;



    --ftm-sidebar-bg: #0c101b !important;



    --ftm-header-bg: #0c101b !important;



    --ftm-text-primary: #ffffff !important;



    --ftm-text-secondary: #e2e8f0 !important;



    --ftm-text-muted: #a9b8cc !important;



    --ftm-border: #1e293b !important;



    --ftm-border-light: #151d2e !important;



}







[data-theme="dark"] body,



[data-theme="dark"] .page-slug-team-dashboard,



[data-theme="dark"] #ftm-app.ftm-app-layout,



[data-theme="dark"] .ftm-main-workspace {



    background-color: #080b11 !important;



    color: #ffffff !important;



}







[data-theme="dark"] .ftm-header {



    background-color: #0c101b !important;



    border-bottom: 1px solid #1e293b !important;



}







[data-theme="dark"] .ftm-sidebar {



    background-color: #0c101b !important;



    border-right: 1px solid #1e293b !important;



}







[data-theme="dark"] .ftm-sidebar-header {



    background: #0f172a !important;



    border-color: #1e293b !important;



}







[data-theme="dark"] .ftm-sidebar-title {



    color: #f8fafc !important;



}







[data-theme="dark"] .ftm-sidebar-close-btn {



    background: #1e293b !important;



    border-color: #334155 !important;



    color: #94a3b8 !important;



}







[data-theme="dark"] .ftm-sidebar-close-btn:hover {



    background: #334155 !important;



    color: #ffffff !important;



}







[data-theme="dark"] .ftm-sidebar-user-card {



    background: #111827 !important;



    border-color: #1f2937 !important;



}







[data-theme="dark"] .ftm-sidebar-user-name {



    color: #f9fafb !important;



}







[data-theme="dark"] .ftm-sidebar-user-status {



    border-color: #111827 !important;



}







[data-theme="dark"] .ftm-brand-text h1 {



    color: #ffffff !important;



}







[data-theme="dark"] .ftm-brand-subtitle {



    color: #94a3b8 !important;



}







[data-theme="dark"] .ftm-team-pill {



    background-color: #151d2e !important;



    border: 1px solid #334155 !important;



    color: #e2e8f0 !important;



}







[data-theme="dark"] .ftm-team-pill .ftm-text-muted {



    color: #94a3b8 !important;



}







[data-theme="dark"] .ftm-user-role {



    color: #94a3b8 !important;



}







[data-theme="dark"] .ftm-dropdown-card {



    background-color: #0c101b !important;



    border: 1px solid #334155 !important;



    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6) !important;



}







[data-theme="dark"] .ftm-dropdown-user-header {



    background-color: #0c101b !important;



    border-bottom: 1px solid #1e293b !important;



}







[data-theme="dark"] .ftm-dropdown-user-header .ftm-font-semibold {



    color: #ffffff !important;



    font-size: 14px !important;



}







[data-theme="dark"] .ftm-dropdown-user-header .ftm-text-muted {



    color: #94a3b8 !important;



    font-size: 12px !important;



    font-weight: 500 !important;



}







[data-theme="dark"] .ftm-dropdown-item {



    color: #e2e8f0 !important;



    font-size: 13.5px !important;



    font-weight: 500 !important;



}







[data-theme="dark"] .ftm-dropdown-item svg {



    stroke: #94a3b8 !important;



}







[data-theme="dark"] .ftm-dropdown-item:hover {



    background-color: #1e293b !important;



    color: #ffffff !important;



}







[data-theme="dark"] .ftm-dropdown-item:hover svg {



    stroke: #ffffff !important;



}







[data-theme="dark"] .ftm-nav-group-title {



    color: #94a3b8 !important;



    font-weight: 700 !important;



    letter-spacing: 0.06em !important;



}







[data-theme="dark"] .ftm-nav-item {



    color: #cbd5e1 !important;



    background: transparent !important;



    font-weight: 500 !important;



}







[data-theme="dark"] .ftm-nav-item svg {



    stroke: #94a3b8 !important;



}







[data-theme="dark"] .ftm-nav-item:hover {



    background-color: #162032 !important;



    color: #ffffff !important;



}







[data-theme="dark"] .ftm-nav-item:hover svg {



    stroke: #ffffff !important;



}







[data-theme="dark"] .ftm-nav-item.active {



    background-color: #2563eb !important;



    color: #ffffff !important;



    box-shadow: 0 4px 14px rgba(37,99,235,0.4) !important;



}







[data-theme="dark"] .ftm-sidebar-footer {



    border-top: 1px solid #1e293b !important;



}







[data-theme="dark"] .ftm-logout-link {



    color: #f87171 !important;



}







[data-theme="dark"] .ftm-logout-link:hover {



    background-color: rgba(239, 68, 68, 0.12) !important;



}







[data-theme="dark"] .ftm-welcome-title {



    color: #ffffff !important;



}







[data-theme="dark"] .ftm-welcome-sub {



    color: #cbd5e1 !important;



    font-weight: 400 !important;



}







[data-theme="dark"] .ftm-date-pill {



    background-color: #151d2e !important;



    border: 1px solid #334155 !important;



    color: #e2e8f0 !important;



}







[data-theme="dark"] .ftm-date-pill svg {



    stroke: #94a3b8 !important;



}







[data-theme="dark"] .ftm-card,



[data-theme="dark"] .ftm-table-card,



[data-theme="dark"] .ftm-kpi-card {



    background-color: #0f1523 !important;



    border: 1px solid #1e293b !important;



    box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.45) !important;



}







[data-theme="dark"] .ftm-kpi-label,



[data-theme="dark"] .ftm-stat-label {



    color: #cbd5e1 !important;



    font-weight: 600 !important;



    letter-spacing: 0.03em !important;



}







[data-theme="dark"] .ftm-kpi-sub,



[data-theme="dark"] .ftm-stat-sub,



[data-theme="dark"] .ftm-kpi-footnote,



[data-theme="dark"] .ftm-card p {



    color: #94a3b8 !important;



}







[data-theme="dark"] .ftm-table-title {



    color: #ffffff !important;



    font-weight: 700 !important;



}







[data-theme="dark"] .ftm-table th {



    background-color: #131b2e !important;



    color: #cbd5e1 !important;



    font-weight: 600 !important;



    border-bottom: 1px solid #334155 !important;



}







[data-theme="dark"] .ftm-table td {



    border-bottom: 1px solid #1e293b !important;



    color: #f1f5f9 !important;



}







[data-theme="dark"] .ftm-table tr:hover td {



    background-color: #151d2e !important;



}







[data-theme="dark"] .ftm-assignee-primary-name {



    color: #ffffff !important;



    font-weight: 600 !important;



}







[data-theme="dark"] .ftm-assignee-role-tag {



    color: #94a3b8 !important;



}







[data-theme="dark"] .ftm-assignee-avatar-stack img {



    border-color: #1e293b !important;



    background-color: #0f172a !important;



}







[data-theme="dark"] .ftm-badge-outline {



    background-color: #151d2e !important;



    border-color: #334155 !important;



    color: #cbd5e1 !important;



}







[data-theme="dark"] .ftm-text-muted {



    color: #a9b8cc !important;



}







[data-theme="dark"] #ftm-pagination-info,



[data-theme="dark"] .ftm-table-footer div {



    color: #cbd5e1 !important;



    font-weight: 500 !important;



}







[data-theme="dark"] .ftm-input::placeholder,



[data-theme="dark"] .ftm-search-input::placeholder,



[data-theme="dark"] textarea::placeholder {



    color: #94a3b8 !important;



    opacity: 1 !important;



}







[data-theme="dark"] .ftm-input,



[data-theme="dark"] .ftm-select,



[data-theme="dark"] .ftm-form-control,



[data-theme="dark"] .ftm-textarea {



    background-color: #151d2e !important;



    border: 1px solid #334155 !important;



    color: #f8fafc !important;



}







[data-theme="dark"] .ftm-label,



[data-theme="dark"] .ftm-form-label {



    color: #e2e8f0 !important;



    font-weight: 600 !important;



}







[data-theme="dark"] .ftm-help-text,



[data-theme="dark"] .ftm-form-text {



    color: #94a3b8 !important;



}







[data-theme="dark"] .ftm-badge-neutral {



    background-color: #151d2e !important;



    color: #cbd5e1 !important;



    border: 1px solid #334155 !important;



}







[data-theme="dark"] .ftm-mobile-bottom-nav {



    background-color: #0c101b !important;



    border-top: 1px solid #1e293b !important;



}







[data-theme="dark"] .ftm-sheet-dialog {



    background-color: #0f1523 !important;



    border-top: 1px solid #1e293b !important;



}







[data-theme="dark"] .ftm-sheet-action-item {



    background-color: #131b2e !important;



    border: 1px solid #1e293b !important;



}







[data-theme="dark"] .ftm-modal-dialog {



    background-color: #0f1523 !important;



    border: 1px solid #1e293b !important;



}







[data-theme="dark"] .ftm-modal-header,



[data-theme="dark"] .ftm-modal-footer {



    background-color: #0c101b !important;



    border-color: #1e293b !important;



}







[data-theme="dark"] .ftm-table-footer {



    border-top: 1px solid #1e293b !important;



}







[data-theme="dark"] .ftm-standup-item-box,



[data-theme="dark"] .ftm-standup-total-row,



[data-theme="dark"] .ftm-activity-table-section,



[data-theme="dark"] .ftm-info-stat-box,



[data-theme="dark"] .ftm-details-assignee-card,



[data-theme="dark"] .ftm-assignee-edit-btn,



[data-theme="dark"] .ftm-member-activity-btn {



    background-color: #131b2e !important;



    border-color: #1e293b !important;



    color: #e2e8f0 !important;



}







[data-theme="dark"] .ftm-standup-item-header {



    border-bottom: 1px solid #1e293b !important;



    color: #94a3b8 !important;



}







[data-theme="dark"] .ftm-blocker-row {



    background-color: rgba(239, 68, 68, 0.12) !important;



    border: 1px solid rgba(239, 68, 68, 0.3) !important;



    color: #fca5a5 !important;



}







[data-theme="dark"] .ftm-details-kpi-card,



[data-theme="dark"] .ftm-spec-card,



[data-theme="dark"] .ftm-activity-stat-card,



[data-theme="dark"] .ftm-settings-info-card,



[data-theme="dark"] .ftm-member-profile-card,



[data-theme="dark"] .ftm-login-card {



    background-color: #0f1523 !important;



    border-color: #1e293b !important;



    color: #f8fafc !important;



}







[data-theme="dark"] .ftm-period-btn.active {



    background-color: #2563eb !important;



    color: #ffffff !important;



}







[data-theme="dark"] .ftm-details-meta-pill {



    background-color: #151d2e !important;



    color: #94a3b8 !important;



    border-color: #1e293b !important;



}



</style>











<div id="ftm-app" class="ftm-app-layout alignfull" 



     data-user-id="<?php echo esc_attr($current_user_id); ?>"



     data-user-role="<?php echo esc_attr($role); ?>"



     data-user-team="<?php echo esc_attr($team); ?>"



     data-is-admin="<?php echo $is_admin ? '1' : '0'; ?>"
     data-view-only="<?php echo FTM_Permissions::is_deputy($current_user_id) ? '1' : '0'; ?>"



     data-is-leader="<?php echo $is_leader ? '1' : '0'; ?>">







    <!-- TOP HEADER -->



    <header class="ftm-header">



        <div class="ftm-header-left">



            <button type="button" id="ftm-mobile-toggle" class="ftm-icon-btn ftm-hide-desktop" aria-label="Toggle Navigation">



                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>



            </button>



            <div class="ftm-brand">



                <img src="<?php echo esc_url(FTM_PLUGIN_URL . 'assets/images/logo.webp'); ?>" alt="FB International" class="ftm-brand-icon ftm-logo-img" style="width:44px; height:44px; border-radius:10px; object-fit:contain; flex-shrink:0; box-shadow:0 4px 12px rgba(37,99,235,0.25);" />



                <div class="ftm-brand-text">



                    <h1>FB International</h1>



                    <div class="ftm-brand-subtitle">Team Management Hub</div>



                </div>



            </div>



            <div class="ftm-team-pill">



                <span class="ftm-team-indicator"></span>



                <?php if (!FTM_Permissions::is_management_team($team)) : ?>



                <span class="ftm-font-semibold"><?php echo esc_html($team); ?></span>



                <span class="ftm-text-muted">·</span>



                <?php endif; ?>



                <span class="ftm-text-muted"><?php echo esc_html(FTM_Permissions::get_role_label($role)); ?></span>



            </div>



        </div>







        <div class="ftm-header-right">

            <!-- Live Bangladesh Standard Time (Asia/Dhaka) Date, Day, Month & Clock -->
            <div id="ftm-live-bst-clock" class="ftm-live-clock-pill" title="Bangladesh Standard Time (Asia/Dhaka · UTC+6)">
                <span class="ftm-live-clock-dot" aria-hidden="true"></span>
                <span id="ftm-live-clock-date" class="ftm-live-clock-date"></span>
                <span class="ftm-live-clock-sep" aria-hidden="true"></span>
                <span id="ftm-live-clock-time" class="ftm-live-clock-time"></span>
                <span class="ftm-live-clock-tz">BST</span>
            </div>
            <script>
            (function() {
                function ftmTickBstClock() {
                    var dateEl = document.getElementById('ftm-live-clock-date');
                    var timeEl = document.getElementById('ftm-live-clock-time');
                    if (!dateEl || !timeEl) return;
                    var now = new Date();
                    try {
                        var parts = new Intl.DateTimeFormat('en-GB', {
                            timeZone: 'Asia/Dhaka',
                            weekday: 'long',
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric'
                        }).formatToParts(now);
                        var map = {};
                        parts.forEach(function(p) { map[p.type] = p.value; });
                        dateEl.textContent = (map.weekday || '') + ', ' + (map.day || '') + ' ' + (map.month || '') + ' ' + (map.year || '');
                        timeEl.textContent = new Intl.DateTimeFormat('en-US', {
                            timeZone: 'Asia/Dhaka',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                            hour12: true
                        }).format(now);
                    } catch (e) {
                        var utcMs = now.getTime() + (now.getTimezoneOffset() * 60000);
                        var bst = new Date(utcMs + (6 * 3600000));
                        dateEl.textContent = bst.toDateString();
                        timeEl.textContent = bst.toLocaleTimeString();
                    }
                }
                ftmTickBstClock();
                if (!window._ftmBstClockInterval) {
                    window._ftmBstClockInterval = setInterval(ftmTickBstClock, 1000);
                }
            })();
            </script>

            <!-- Theme Toggle (Light / Dark Mode) -->



            <button type="button" class="ftm-icon-btn ftm-theme-toggle-btn" id="ftm-header-theme-toggle" aria-label="Toggle Dark/Light Mode" title="Toggle Theme (Light / Dark / System)">



                <svg class="ftm-sun-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>



                <svg class="ftm-moon-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>



            </button>







            <!-- Team Messages & Communication Hub Trigger -->
            <div class="ftm-header-action-wrap" style="position:relative;">
                <button type="button" class="ftm-icon-btn ftm-nav-trigger" id="ftm-header-messages-btn" data-target="messages" aria-label="Team Messages" title="Team Messages & Chat Hub" style="position:relative;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <span class="ftm-badge-count hidden" id="ftm-header-messages-badge" style="background:#2563eb;">0</span>
                </button>
            </div>

            <!-- Urgent Alerts Dropdown Trigger -->



            <div class="ftm-header-action-wrap" style="position:relative;">



                <button type="button" class="ftm-icon-btn" id="ftm-bell-btn" aria-label="Alerts" style="position:relative;">



                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>



                    <span class="ftm-badge-count" id="ftm-header-alert-badge">0</span>



                </button>



                <div id="ftm-alerts-dropdown" class="ftm-dropdown-card ftm-alerts-dropdown-panel hidden">
                    <div class="ftm-dropdown-header" style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; border-bottom:1px solid var(--ftm-border, #e2e8f0);">
                        <span class="ftm-font-semibold" style="font-size:13px;">Urgent Alerts</span>
                        <button type="button" class="ftm-btn-link" id="ftm-btn-mark-all-alerts-read" style="cursor:pointer; color:var(--ftm-primary, #2563eb); font-weight:700; text-decoration:none; background:none; border:none; padding:2px 6px; font-size:11px;">
                            Mark all read
                        </button>
                    </div>



                    <div id="ftm-dropdown-alerts-list" class="ftm-dropdown-body">



                        <!-- Populated dynamically via JS -->



                    </div>



                </div>



            </div>







            <?php if (!$is_admin && !$is_deputy): ?>



            <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm ftm-hide-mobile" id="ftm-btn-new-daily-standup">



                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>



                <span>Log Daily Progress</span>



            </button>



            <?php else: ?>



            <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm ftm-hide-mobile" id="ftm-btn-broadcast-notice">



                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>



                <span>Broadcast Notice</span>



            </button>



            <?php endif; ?>

            <?php if (($is_admin || $is_leader) && !$is_deputy): ?>
            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-open-modal-add-project ftm-hide-mobile" id="ftm-btn-add-project">

                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>

                <span>New Project</span>

            </button>
            <?php endif; ?>







            <!-- User Profile & Elevated Dropdown Panel -->



            <div class="ftm-user-menu-wrap" style="position:relative;">



                <?php 



                $hdr_custom_avatar = get_user_meta($current_user_id, 'ftm_custom_avatar', true);



                $hdr_avatar_url = !empty($hdr_custom_avatar) ? $hdr_custom_avatar : get_avatar_url($current_user_id, ['size' => 128, 'default' => 'mm']);



                ?>



                <div class="ftm-user-profile-btn" id="ftm-user-btn" style="cursor:pointer; display:flex; align-items:center; gap:10px; padding:4px 8px; border-radius:8px;">



                    <div style="position:relative;">



                        <img src="<?php echo esc_url($hdr_avatar_url); ?>" 



                             alt="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" 



                             class="ftm-header-avatar">



                        <span style="position:absolute; bottom:0; right:0; width:9px; height:9px; background:#10b981; border:2px solid var(--ftm-header-bg); border-radius:50%;"></span>



                    </div>



                    <div class="ftm-user-meta ftm-hide-mobile" style="display:flex; flex-direction:column; text-align:left;">



                        <span class="ftm-user-name" style="font-size:13px; font-weight:600; line-height:1.2;"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>



                        <span class="ftm-user-role" style="font-size:11px;"><?php echo esc_html(FTM_Permissions::get_role_label($role)); ?></span>



                    </div>



                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="ftm-hide-mobile" style="opacity:0.6; margin-left:2px;"><polyline points="6 9 12 15 18 9"></polyline></svg>



                </div>



                



                <div id="ftm-user-dropdown" class="ftm-dropdown-card ftm-user-dropdown-panel hidden" style="width:280px; border-radius:12px; box-shadow:0 12px 32px rgba(0,0,0,0.18); border:1px solid var(--ftm-border); overflow:hidden; z-index:1100;">



                    <div class="ftm-dropdown-user-header" style="display:flex; align-items:center; gap:12px; padding:14px 16px; background:var(--ftm-surface-card);">



                        <div style="position:relative; flex-shrink:0;">



                            <img src="<?php echo esc_url($hdr_avatar_url); ?>" class="ftm-header-avatar" style="width:46px; height:46px;" alt="">



                            <span style="position:absolute; bottom:0; right:0; width:10px; height:10px; background:#10b981; border:2px solid var(--ftm-surface-card); border-radius:50%;"></span>



                        </div>



                        <div style="overflow:hidden; flex:1;">



                            <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">



                                <div class="ftm-font-semibold" id="ftm-dropdown-user-name" style="font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html(wp_get_current_user()->display_name); ?></div>



                                <span class="ftm-badge ftm-badge-outline ftm-badge-sm" style="font-size:10px; padding:1px 5px;">#<?php echo esc_html(get_user_meta($current_user_id, 'ftm_custom_id', true) ?: ($is_admin ? '0001' : $current_user_id)); ?></span>



                            </div>



                            <div class="ftm-text-muted ftm-text-xs" style="font-size:11px; margin-bottom:2px; display:flex; align-items:center; gap:4px;">



                                <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" style="font-size:10px;"><?php echo esc_html(FTM_Permissions::get_role_label($role)); ?></span>



                                <span style="font-size:10px; opacity:0.8;">• <?php echo esc_html($team); ?></span>



                            </div>



                            <div class="ftm-text-muted" id="ftm-dropdown-user-email" style="font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html(wp_get_current_user()->user_email); ?></div>



                        </div>



                    </div>



                    <div class="ftm-dropdown-divider" style="border-top:1px solid var(--ftm-border); margin:0;"></div>



                    <div style="padding:6px;">



                        <?php if ($is_admin || $is_deputy): ?>



                        <a href="#admin-profile" class="ftm-dropdown-item ftm-nav-trigger" data-target="admin-profile" style="display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px; text-decoration:none; color:inherit; font-size:13px;">



                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>



                            <span style="font-weight:600;">Executive Manager Profile</span>



                        </a>



                        <?php else: ?>



                        <a href="#member-profile" class="ftm-dropdown-item ftm-nav-trigger" data-target="member-profile" data-member-id="<?php echo esc_attr($current_user_id); ?>" style="display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px; text-decoration:none; color:inherit; font-size:13px;">



                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>



                            <span style="font-weight:600;">My Profile & Portfolio</span>



                        </a>



                        <?php endif; ?>



                        <a href="#my-projects" class="ftm-dropdown-item ftm-nav-trigger" data-target="my-projects" style="display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px; text-decoration:none; color:inherit; font-size:13px;">



                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>



                            <span>My Assigned Projects</span>



                        </a>



                        <?php if (!$is_admin && !$is_deputy): ?>



                        <a href="#daily-reports" class="ftm-dropdown-item ftm-nav-trigger" data-target="daily-reports" style="display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px; text-decoration:none; color:inherit; font-size:13px;">



                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>



                            <span>Daily Standup</span>



                        </a>



                        <?php else: ?>



                        <a href="#agency-notices" class="ftm-dropdown-item ftm-nav-trigger" data-target="agency-notices" style="display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px; text-decoration:none; color:inherit; font-size:13px;">



                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>



                            <span>Agency Notice Board</span>



                        </a>



                        <?php endif; ?>



                        <a href="#settings" class="ftm-dropdown-item ftm-nav-trigger" data-target="settings" style="display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px; text-decoration:none; color:inherit; font-size:13px;">



                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>



                            <span>Account Settings</span>



                        </a>



                    </div>



                    <div class="ftm-dropdown-divider" style="border-top:1px solid var(--ftm-border); margin:0;"></div>



                    <div style="padding:6px;">



                        <a href="<?php echo esc_url(wp_logout_url(site_url('/team-login/'))); ?>" class="ftm-dropdown-item ftm-text-danger" style="display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px; text-decoration:none; color:var(--ftm-danger); font-size:13px; font-weight:600;">



                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>



                            <span>Log Out</span>



                        </a>



                    </div>



                </div>



            </div>



        </div>



    </header>







    <!-- MAIN 2-COLUMN CONTAINER -->



    <div class="ftm-main-container">



        <!-- SIDEBAR BACKDROP OVERLAY FOR MOBILE -->



        <div id="ftm-sidebar-overlay" class="ftm-sidebar-overlay"></div>







        <!-- SIDEBAR -->



        <aside id="ftm-sidebar" class="ftm-sidebar">



            <!-- Mobile Drawer Brand Header (Hidden on Desktop) -->



            <div class="ftm-sidebar-header ftm-hide-desktop">



                <div class="ftm-sidebar-brand">



                    <img src="<?php echo esc_url(FTM_PLUGIN_URL . 'assets/images/logo.webp'); ?>" alt="FB International" class="ftm-sidebar-logo" />



                    <div class="ftm-sidebar-brand-text">



                        <span class="ftm-sidebar-title">FB International</span>



                        <span class="ftm-sidebar-sub">Team Management Hub</span>



                    </div>



                </div>



                <button type="button" id="ftm-sidebar-close-btn" class="ftm-sidebar-close-btn" aria-label="Close Navigation Drawer">



                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>



                </button>



            </div>







            <!-- Mobile Drawer User Identity Card (Hidden on Desktop) -->



            <div class="ftm-sidebar-user-card ftm-hide-desktop">



                <div class="ftm-sidebar-user-avatar-wrap">



                    <img src="<?php echo esc_url($hdr_avatar_url); ?>" alt="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" class="ftm-sidebar-user-avatar" />



                    <span class="ftm-sidebar-user-status"></span>



                </div>



                <div class="ftm-sidebar-user-info">



                    <div class="ftm-sidebar-user-name"><?php echo esc_html(wp_get_current_user()->display_name); ?></div>



                    <div class="ftm-sidebar-user-meta">



                        <span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs"><?php echo esc_html($team); ?></span>



                        <span class="ftm-text-muted" style="font-size:10px;">·</span>



                        <span class="ftm-text-muted" style="font-size:11px; font-weight:500;"><?php echo esc_html(FTM_Permissions::get_role_label($role)); ?></span>



                    </div>



                </div>



            </div>







            <nav class="ftm-nav ftm-nav-menu">



                <div class="ftm-nav-group-title">WORKSPACE</div>

                <a href="#overview" class="ftm-nav-item" data-view="overview">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                    <span>Overview</span>
                    <span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs" style="margin-left:auto; font-weight:700; font-size:10px; padding:2px 6px;">Live</span>
                </a>

                <a href="#dashboard" class="ftm-nav-item active" data-view="dashboard">



                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>



                    <span>All Projects</span>



                </a>



                <a href="#my-projects" class="ftm-nav-item" data-view="my-projects">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7.5" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                    <span>My Assigned</span>
                </a>

                <a href="#my-tasks" class="ftm-nav-item" data-view="my-tasks">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    <span>My Tasks</span>
                    <span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs hidden" id="ftm-nav-tasks-badge" style="margin-left:auto; font-weight:700;">0</span>
                </a>

                <?php if ($is_admin || $is_leader || $is_deputy): ?>
                <a href="#time-tracker" class="ftm-nav-item" data-view="time-tracker">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    <span>Work Tracker & Screens</span>
                    <span class="ftm-pulse-dot hidden" id="ftm-nav-tracker-pulse" style="width:8px; height:8px; border-radius:50%; background:#10b981; margin-left:auto;"></span>
                </a>
                <?php endif; ?>

                <a href="#messages" class="ftm-nav-item" data-view="messages">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <span>Team Messages</span>
                    <span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs hidden" id="ftm-nav-messages-badge">0</span>
                </a>



                <?php if ($is_admin || $is_deputy): ?>



                <a href="#admin-profile" class="ftm-nav-item" data-view="admin-profile">



                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>



                    <span>Executive Profile</span>



                </a>



                <?php else: ?>



                <a href="#member-profile" class="ftm-nav-item" data-view="member-profile" data-member-id="<?php echo esc_attr($current_user_id); ?>">



                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>



                    <span>My Portfolio</span>



                </a>



                <?php endif; ?>







                <div class="ftm-nav-group-title">DAILY REPORTING & NOTICES</div>



                <?php if (!$is_admin && !$is_deputy): ?>



                <a href="#daily-reports" class="ftm-nav-item" data-view="daily-reports">



                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>



                    <span>My Daily Standup</span>



                </a>



                <?php endif; ?>



                <a href="#agency-notices" class="ftm-nav-item" data-view="agency-notices">



                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>



                    <span>Agency Notices</span>



                    <span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs hidden" id="ftm-nav-notices-badge" style="margin-left:auto; font-size:10px;">0</span>



                </a>



                <?php if ($is_leader || $is_deputy): ?>



                <a href="#team-daily-report" class="ftm-nav-item" data-view="team-daily-report">



                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>



                    <span>Team Daily Report</span>



                </a>



                <?php endif; ?>

                <a href="#attendance" class="ftm-nav-item" data-view="attendance">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><path d="m9 16 2 2 4-4"></path></svg>
                    <span>Attendance & Leaves</span>
                </a>

                <div class="ftm-nav-group-title">ANALYTICS & AGENCY</div>

                <?php if ($is_admin || $is_leader || $is_deputy): ?>
                <a href="#monthly-reports" class="ftm-nav-item" data-view="monthly-reports">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    <span>Monthly Sheet & Revenue</span>
                </a>
                <?php endif; ?>

                <a href="#leaderboard" class="ftm-nav-item" data-view="leaderboard">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    <span>Performance Leaderboard</span>
                </a>

                <a href="#team-members" class="ftm-nav-item" data-view="team-members">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span>Team Members</span>
                </a>

                <?php if ($is_admin || $is_leader || $is_deputy): ?>
                <a href="#profiles" class="ftm-nav-item" data-view="fiverr-profiles">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    <span>Profiles</span>
                </a>
                <?php endif; ?>

                <?php if ($is_admin): ?>
                <a href="#settings" class="ftm-nav-item" data-view="settings">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span>Settings</span>
                </a>
                <?php endif; ?>



            </nav>



            <div class="ftm-sidebar-footer">



                <a href="<?php echo esc_url(wp_logout_url(site_url('/team-login/'))); ?>" class="ftm-logout-link">



                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>



                    <span>Log Out</span>



                </a>



            </div>



        </aside>







        <!-- MAIN WORKSPACE -->



        <main class="ftm-main-workspace">



            <div class="ftm-content-container">







            <!-- ONBOARDING / PROFILE SETUP ALERT BANNER (1-TIME EMAIL UPDATE & PROFILE COMPLETION) -->



            <?php



            $current_user_obj = wp_get_current_user();



            $user_email_val = strtolower(trim($current_user_obj->user_email ?? ''));



            $is_placeholder_email = empty($user_email_val) || str_ends_with($user_email_val, '.local') || str_ends_with($user_email_val, '.invalid') || str_ends_with($user_email_val, 'example.com');



            // If the user already has a valid official email address, NEVER show the setup banner



            $show_setup_banner = $is_placeholder_email;



            if (!$is_placeholder_email && !get_user_meta($current_user_id, 'ftm_email_updated', true)) {



                update_user_meta($current_user_id, 'ftm_email_updated', 1);



                update_user_meta($current_user_id, 'ftm_profile_completed', 1);



            }



            if ($show_setup_banner):



            ?>



            <div id="ftm-banner-profile-setup" class="ftm-onboarding-banner">



                <div class="ftm-onboarding-banner-icon">



                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>



                </div>



                <div class="ftm-onboarding-banner-body">



                    <div class="ftm-onboarding-banner-title">Action Required: Complete Profile & Set Official Work Email</div>



                    <div class="ftm-onboarding-banner-desc">Welcome to FB International Hub! Your account currently has initial credentials. Please set your official work email address and complete your profile. <em>Note: Your email address can only be self-updated once.</em></div>



                </div>



                <div class="ftm-onboarding-banner-actions">



                    <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-btn-open-onboarding-profile">



                        <span>Set Email & Complete Profile &rarr;</span>



                    </button>



                </div>



            </div>



            <?php endif; ?>







            <!-- ACTIVE NOTICES DASHBOARD BANNER STRIP -->



            <div id="ftm-active-notices-banner" class="ftm-notices-banner-container hidden"></div>







            <!-- 0. OVERVIEW VIEW (Role-Scoped Current Month Operational & Financial Overview) -->
            <section id="ftm-view-overview" class="ftm-view-panel hidden">
                <style>
                    .ftm-overview-kpi-grid {
                        display: grid;
                        grid-template-columns: repeat(4, 1fr);
                        gap: 16px;
                        margin-bottom: 24px;
                    }
                    @media (max-width: 1100px) {
                        .ftm-overview-kpi-grid {
                            grid-template-columns: repeat(2, 1fr);
                        }
                    }
                    @media (max-width: 640px) {
                        .ftm-overview-kpi-grid {
                            grid-template-columns: 1fr;
                        }
                    }
                    .ftm-ov-card {
                        background: var(--ftm-card-bg, #0f1523);
                        border: 1px solid var(--ftm-border, #1e293b);
                        border-radius: 12px;
                        padding: 16px 18px;
                        padding: 18px 20px;
                        display: flex;
                        flex-direction: column;
                        justify-content: space-between;
                        position: relative;
                        overflow: hidden;
                        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
                        min-height: 145px;
                    }
                    .ftm-ov-card:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.45);
                    }
                    .ftm-ov-accent-top {
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        height: 3px;
                    }
                    .ftm-ov-head {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 10px;
                    }
                    .ftm-ov-title {
                        font-size: 11px;
                        font-weight: 700;
                        text-transform: uppercase;
                        letter-spacing: 0.6px;
                        color: var(--ftm-text-muted, #94a3b8);
                    }
                    .ftm-ov-icon-wrap {
                        width: 32px;
                        height: 32px;
                        border-radius: 8px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .ftm-ov-val {
                        font-size: 22px;
                        font-weight: 800;
                        letter-spacing: -0.5px;
                        color: var(--ftm-text-primary, #f8fafc);
                        line-height: 1.2;
                        margin-bottom: 4px;
                    }
                    .ftm-ov-sub {
                        font-size: 12px;
                        color: var(--ftm-text-muted, #94a3b8);
                        margin-bottom: 8px;
                    }
                    .ftm-ov-pill {
                        font-size: 11px;
                        font-weight: 600;
                        padding: 3px 8px;
                        border-radius: 6px;
                        display: inline-flex;
                        align-items: center;
                        gap: 4px;
                        width: fit-content;
                    }
                    .ftm-ov-squads-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                        gap: 16px;
                    }
                    .ftm-squad-ov-card {
                        background: var(--ftm-card-bg, #0f1523);
                        border: 1px solid var(--ftm-border, #1e293b);
                        border-radius: 12px;
                        padding: 18px 20px;
                        transition: all 0.2s ease;
                    }
                    .ftm-squad-ov-card:hover {
                        border-color: rgba(99, 102, 241, 0.4);
                        box-shadow: 0 8px 24px -4px rgba(0,0,0,0.35);
                    }
                    .ftm-ov-roster-row:hover {
                        background-color: rgba(255, 255, 255, 0.03) !important;
                    }
                    .ftm-ov-team-pill {
                        cursor: pointer;
                        padding: 4px 12px;
                        border-radius: 20px;
                        font-size: 12px;
                        font-weight: 600;
                        border: 1px solid var(--ftm-border, #1e293b);
                        background: transparent;
                        color: var(--ftm-text-muted, #94a3b8);
                        transition: all 0.2s ease;
                    }
                    .ftm-ov-team-pill.active {
                        background: var(--ftm-primary, #2563eb);
                        color: #ffffff;
                        border-color: var(--ftm-primary, #2563eb);
                    }
                </style>

                <div class="ftm-overview-container">
                    <!-- Overview Header & Control Bar -->
                    <div class="ftm-refb-page-header ftm-overview-header-bar" style="margin-bottom:20px;">
                        <div class="ftm-refb-breadcrumb">
                            <span class="ftm-refb-crumb-root">Workspace</span>
                            <span class="ftm-refb-crumb-sep">/</span>
                            <span class="ftm-refb-crumb-current">Overview</span>
                            <span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs" style="font-weight:700; letter-spacing:0.5px;">LIVE RECONCILIATION</span>
                        </div>

                        <div class="ftm-refb-header-row" style="margin-top:8px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
                            <div>
                                <h1 class="ftm-refb-page-title" style="display:flex; align-items:center; gap:10px; margin:0; font-size:24px; font-weight:800;">
                                    <span>Current Month Overview</span>
                                    <span id="ftm-overview-month-pill" class="ftm-badge bg-blue-soft text-blue ftm-badge-sm">November 2026</span>
                                </h1>
                                <p class="ftm-refb-page-sub" id="ftm-overview-subtitle" style="margin:4px 0 0; font-size:13px; color:var(--ftm-text-muted);">Real-time operational drivers, financial reconciliation, and specialist performance metrics.</p>
                            </div>

                            <div class="ftm-refb-header-actions" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                <div class="ftm-overview-role-badge-wrap">
                                    <span class="ftm-badge bg-indigo-soft text-indigo ftm-badge-sm" id="ftm-overview-role-badge" style="font-weight:700; padding:6px 12px; font-size:12px; border-radius:8px;">
                                        Loading Scope...
                                    </span>
                                </div>

                                <div class="ftm-select-wrap">
                                    <select id="ftm-overview-month-select" class="ftm-select ftm-select-sm" style="font-weight:600; min-width:160px; height:36px;">
                                        <?php
                                        if (class_exists('FTM_Monthly_Report_Controller')) {
                                            $dyn_months = FTM_Monthly_Report_Controller::get_dynamic_reporting_months();
                                            foreach ($dyn_months as $dm) {
                                                $sel = $dm['is_current'] ? 'selected="selected"' : '';
                                                echo '<option value="' . esc_attr($dm['key']) . '" ' . $sel . '>' . esc_html($dm['label']) . '</option>';
                                            }
                                        } else {
                                            echo '<option value="' . esc_attr(current_time('Y-m')) . '" selected="selected">Current Month</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-overview-refresh-btn" style="height:36px; gap:6px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                                    <span>Sync Live</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 7 Core KPI Cards Container -->
                    <div class="ftm-overview-kpi-grid" id="ftm-overview-kpi-grid">
                        <div class="ftm-table-loading" style="grid-column: 1 / -1; text-align:center; padding:30px;">
                            <div class="ftm-spinner"></div> Loading current month operational KPIs...
                        </div>
                    </div>

                    <!-- Squad Operational Breakdown (Role-Sensitive) -->
                    <div class="ftm-overview-squads-wrap" id="ftm-overview-squads-wrap" style="margin-top:24px;">
                        <!-- Squad cards populated dynamically via JS -->
                    </div>

                    <!-- Specialists Performance Roster -->
                    <div class="ftm-table-card ftm-overview-roster-card" style="margin-top:24px;">
                        <div class="ftm-table-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; padding:16px 20px;">
                            <div>
                                <div class="ftm-table-title" style="display:flex; align-items:center; gap:8px;">
                                    <span id="ftm-overview-roster-title">Specialist Performance &amp; Reconciliation Roster</span>
                                    <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" id="ftm-overview-roster-count">13 Specialists</span>
                                </div>
                                <div class="ftm-text-muted ftm-text-sm" style="margin-top:2px;">Real-time individual deliveries, tips, WIP, repeat client metrics, 5★ reviews, and on-time adherence. Click any specialist to inspect full monthly breakdown.</div>
                            </div>
                            <div class="ftm-overview-roster-controls" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                <div class="ftm-team-filter-pills" id="ftm-overview-team-pills" style="display:flex; gap:6px;">
                                    <!-- Pills: All, WpCraft, WpNexus, WpTitans -->
                                </div>
                                <div class="ftm-search-wrap" style="position:relative;">
                                    <input type="text" id="ftm-overview-search-input" class="ftm-input ftm-input-sm" placeholder="Search specialist..." style="width:190px; padding-left:28px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute; left:9px; top:50%; transform:translateY(-50%); color:var(--ftm-text-muted, #94a3b8); pointer-events:none;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                </div>
                            </div>
                        </div>

                        <div class="ftm-table-responsive">
                            <table class="ftm-table" id="ftm-overview-specialists-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px; text-align:center;">#</th>
                                        <th>Specialist</th>
                                        <th>Squad</th>
                                        <th style="text-align:right;">Delivered ($)</th>
                                        <th style="text-align:right;">Submitted ($)</th>
                                        <th style="text-align:center;">Orders</th>
                                        <th style="text-align:right;">WIP Pipeline</th>
                                        <th style="text-align:right;">Repeat Orders</th>
                                        <th style="text-align:center;">Reviews</th>
                                        <th style="text-align:center;">On-Time %</th>
                                        <th style="text-align:right;">Total Assigned</th>
                                        <th style="text-align:center; width:110px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="ftm-overview-specialists-tbody">
                                    <tr>
                                        <td colspan="12" class="ftm-table-loading" style="text-align:center; padding:40px;">
                                            <div class="ftm-spinner"></div> Loading current month specialists overview...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Current Month Active Deliverables Pipeline -->
                    <div class="ftm-table-card ftm-overview-deliverables-card" style="margin-top:24px;">
                        <div class="ftm-table-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; padding:16px 20px;">
                            <div>
                                <div class="ftm-table-title" style="display:flex; align-items:center; gap:8px;">
                                    <span id="ftm-overview-deliverables-title">Active Month Deliverables Pipeline</span>
                                    <span class="ftm-badge bg-amber-soft text-amber ftm-badge-sm" id="ftm-overview-deliverables-count">0 Open</span>
                                </div>
                                <div class="ftm-text-muted ftm-text-sm" style="margin-top:2px;">Real-time tracking of active orders, live deadlines, assigned specialists, and completion states.</div>
                            </div>
                            <div class="ftm-table-actions">
                                <a href="#dashboard" class="ftm-btn ftm-btn-secondary ftm-btn-sm" style="text-decoration:none;">
                                    <span>View All in Projects</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </a>
                            </div>
                        </div>

                        <div class="ftm-table-responsive">
                            <table class="ftm-table" id="ftm-overview-deliverables-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Profile</th>
                                        <th>Client</th>
                                        <th>Squad</th>
                                        <th>Assigned Specialist(s)</th>
                                        <th style="text-align:right;">Net Value</th>
                                        <th>Deadline &amp; Countdown</th>
                                        <th style="text-align:center;">Status</th>
                                        <th style="text-align:center; width:90px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="ftm-overview-deliverables-tbody">
                                    <tr>
                                        <td colspan="9" class="ftm-table-loading" style="text-align:center; padding:30px;">
                                            <div class="ftm-spinner"></div> Loading active deliverables...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 1. ALL PROJECTS VIEW (Reference B Design Direction) -->



            <section id="ftm-view-dashboard" class="ftm-view-panel active">



                <!-- Reference B Breadcrumb & Header Row -->



                <div class="ftm-refb-page-header">



                    <div class="ftm-refb-breadcrumb">



                        <span class="ftm-text-muted">Projects</span>



                        <span class="ftm-refb-bc-sep">/</span>



                        <span class="ftm-refb-bc-active">All Projects</span>



                    </div>



                    <div class="ftm-refb-header-row">



                        <div>



                            <h1 class="ftm-refb-page-title">All Projects</h1>



                            <p class="ftm-refb-page-sub">Manage client orders, delivery timelines, and team assignments.</p>



                        </div>



                        <div class="ftm-refb-header-actions">



                            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-export-projects">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>



                                <span>Export</span>



                            </button>

                            <?php if (($is_admin || $is_leader) && !$is_deputy): ?>
                            <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm ftm-open-modal-add-project" id="ftm-btn-refb-new-project">

                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>

                                <span>New Project</span>

                            </button>
                            <?php endif; ?>



                        </div>



                    </div>



                </div>







                <!-- Reference B: 6 Metric Cards -->



                <div class="ftm-refb-kpi-grid">

                    <div class="ftm-refb-kpi-card ftm-kpi-clickable" id="ftm-kpi-card-total" data-kpi-filter="all" title="Click to show all projects">
                        <div class="ftm-refb-kpi-header">
                            <span class="ftm-refb-kpi-title">Total</span>
                            <span class="ftm-refb-kpi-icon-pill bg-blue-soft text-blue">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                            </span>
                        </div>
                        <div class="ftm-refb-kpi-val" id="refb-stat-total">0</div>
                        <div class="ftm-refb-kpi-sub" id="refb-stat-total-sub"><span style="color:#f97316; font-weight:700;" id="refb-stat-new-assigned-badge">61 New</span> &bull; <span style="color:#eab308; font-weight:700;" id="refb-stat-carry-badge">27 Carry</span></div>
                    </div>

                    <div class="ftm-refb-kpi-card ftm-kpi-clickable" id="ftm-kpi-card-active" data-kpi-filter="active" title="Click to filter Active (WIP + NRA) projects">
                        <div class="ftm-refb-kpi-header">
                            <span class="ftm-refb-kpi-title">Active</span>
                            <span class="ftm-refb-kpi-icon-pill bg-indigo-soft text-indigo">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                            </span>
                        </div>
                        <div class="ftm-refb-kpi-val" id="refb-stat-active">0</div>
                        <div class="ftm-refb-kpi-sub">Projects in progress</div>
                    </div>

                    <div class="ftm-refb-kpi-card ftm-kpi-clickable" id="ftm-kpi-card-wip" data-kpi-filter="wip" title="Click to filter WIP orders">
                        <div class="ftm-refb-kpi-header">
                            <span class="ftm-refb-kpi-title">WIP</span>
                            <span class="ftm-refb-kpi-icon-pill bg-amber-soft text-amber">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </span>
                        </div>
                        <div class="ftm-refb-kpi-val-row"><div class="ftm-refb-kpi-val" id="refb-stat-wip">0</div><span class="ftm-kpi-value-chip" id="refb-stat-wip-value" title="Pipeline value of orders in progress">$0.00</span></div>
                        <div class="ftm-refb-kpi-sub">Orders in progress &middot; pipeline value</div>
                    </div>

                    <div class="ftm-refb-kpi-card ftm-kpi-clickable" id="ftm-kpi-card-revision" data-kpi-filter="revision" title="Click to filter Revision orders">
                        <div class="ftm-refb-kpi-header">
                            <span class="ftm-refb-kpi-title">Revision</span>
                            <span class="ftm-refb-kpi-icon-pill bg-purple-soft text-purple">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                            </span>
                        </div>
                        <div class="ftm-refb-kpi-val-row"><div class="ftm-refb-kpi-val text-purple" id="refb-stat-revision">0</div><span class="ftm-kpi-value-chip ftm-chip-purple" id="refb-stat-revision-value" title="Pipeline value of orders in revision">$0.00</span></div>
                        <div class="ftm-refb-kpi-sub">Needs revisions &middot; pipeline value</div>
                    </div>

                    <div class="ftm-refb-kpi-card ftm-kpi-clickable" id="ftm-kpi-card-completed" data-kpi-filter="delivered_all" title="Click to filter Completed (Delivered & Submitted) orders">
                        <div class="ftm-refb-kpi-header">
                            <span class="ftm-refb-kpi-title">Completed</span>
                            <span class="ftm-refb-kpi-icon-pill bg-emerald-soft text-emerald">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                        </div>
                        <div class="ftm-refb-kpi-val text-emerald" id="refb-stat-completed">0</div>
                        <div class="ftm-refb-kpi-sub">Successfully delivered</div>
                    </div>

                    <div class="ftm-refb-kpi-card ftm-kpi-clickable" id="ftm-kpi-card-cancelled" data-kpi-filter="cancelled" title="Click to filter Cancelled orders">
                        <div class="ftm-refb-kpi-header">
                            <span class="ftm-refb-kpi-title">Cancelled</span>
                            <span class="ftm-refb-kpi-icon-pill bg-rose-soft text-rose">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                            </span>
                        </div>
                        <div class="ftm-refb-kpi-val-row"><div class="ftm-refb-kpi-val text-rose" id="refb-stat-cancelled">0</div><span class="ftm-kpi-value-chip ftm-chip-rose" id="refb-stat-cancelled-value" title="Net value of cancelled orders">$0.00</span></div>
                        <div class="ftm-refb-kpi-sub">Cancelled orders &middot; lost value</div>
                    </div>

                    <div class="ftm-refb-kpi-card ftm-kpi-clickable" id="ftm-kpi-card-repeat" data-kpi-filter="repeat" title="Click to filter Repeat Client orders">
                        <div class="ftm-refb-kpi-header">
                            <span class="ftm-refb-kpi-title">Repeat Orders</span>
                            <span class="ftm-refb-kpi-icon-pill bg-cyan-soft text-cyan">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                            </span>
                        </div>
                        <div class="ftm-refb-kpi-val-row"><div class="ftm-refb-kpi-val" style="color:#06b6d4;" id="refb-stat-repeat">0</div><span class="ftm-kpi-value-chip ftm-chip-cyan" id="refb-stat-repeat-value" title="Net value of repeat client orders">$0.00</span></div>
                        <div class="ftm-refb-kpi-sub">Repeat clients &middot; net value</div>
                    </div>

                    <div class="ftm-refb-kpi-card ftm-kpi-clickable" id="ftm-kpi-card-net-delivery" data-kpi-filter="delivery_accepted" title="Click to filter Accepted & Paid delivered orders">
                        <div class="ftm-refb-kpi-header">
                            <span class="ftm-refb-kpi-title">Net Delivery</span>
                            <span class="ftm-refb-kpi-icon-pill bg-emerald-soft text-emerald">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            </span>
                        </div>
                        <div class="ftm-refb-kpi-val text-emerald" id="refb-stat-net-delivery">$0.00</div>
                        <div class="ftm-refb-kpi-sub">Delivered revenue</div>
                    </div>

                </div>







                <!-- Projects Table Card with Reference B Toolbar -->



                <div class="ftm-table-card">



                    <div class="ftm-refb-toolbar">



                        <div class="ftm-refb-search-wrap">



                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>



                            <input type="text" id="ftm-filter-search" class="ftm-refb-search-input" placeholder="Search projects...">



                        </div>



                        <div class="ftm-refb-filters-group">



                            <select id="ftm-filter-member" class="ftm-refb-filter-select" style="min-width:170px;">

                                <option value="0">All Specialists</option>

                            </select>



                            <select id="ftm-filter-team" class="ftm-refb-filter-select">



                                <option value="all">All Teams</option>



                                <option value="WpCraft">WpCraft</option>



                                <option value="WpTitans">WpTitans</option>



                                <option value="WpNexus">WpNexus</option>



                            </select>



                            <select id="ftm-filter-status" class="ftm-refb-filter-select">
                                <option value="all">All Statuses</option>
                                <option value="active">Active (WIP + NRA)</option>
                                <option value="nra">NRA</option>
                                <option value="wip">WIP</option>
                                <option value="delivery_submitted">Submitted</option>
                                <option value="revision">Revision</option>
                                <option value="delivered_all">Completed (All Delivered)</option>
                                <option value="delivery_accepted">Accepted (Net Delivery)</option>
                                <option value="repeat">Repeat Client</option>
                                <option value="cancelled">Cancelled</option>
                            </select>



                            <select id="ftm-filter-client" class="ftm-refb-filter-select">



                                <option value="all">All Clients</option>



                            </select>

                            <select id="ftm-filter-month" class="ftm-refb-filter-select">
                                <option value="all">All Months</option>
                                <?php
                                if (class_exists('FTM_Monthly_Report_Controller')) {
                                    $dyn_months = FTM_Monthly_Report_Controller::get_dynamic_reporting_months();
                                    foreach ($dyn_months as $dm) {
                                        $sel = $dm['is_current'] ? 'selected="selected"' : '';
                                        echo '<option value="' . esc_attr($dm['key']) . '" ' . $sel . '>' . esc_html($dm['label']) . '</option>';
                                    }
                                }
                                ?>
                                <option value="carry_forward">Carry Forward</option>
                            </select>

                            <select id="ftm-filter-per-page" class="ftm-refb-filter-select">
                                <option value="20">20 / page</option>
                                <option value="50" selected>50 / page</option>
                                <option value="100">100 / page</option>
                                <option value="500">500 / page</option>
                            </select>



                            <button type="button" class="ftm-refb-date-picker-btn" id="ftm-btn-date-range-toggle" style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; background:var(--ftm-card-bg); border:1px solid var(--ftm-border); border-radius:8px; padding:6px 12px; font-size:13px; font-weight:500; color:var(--ftm-text-primary);">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>



                                <span id="ftm-date-range-label">Date Range</span>



                            </button>







                        </div>



                    </div>







                    <div class="ftm-table-responsive ftm-projects-desktop-table">



                        <table class="ftm-table" id="ftm-projects-table">



                            <thead>



                                <tr>



                                    <th>#</th>



                                    <th>Team</th>



                                    <th>Profile</th>



                                    <th>Client & Order</th>



                                    <th>Net Value</th>



                                    <th>Assign Date</th>



                                    <th>Delivery Date</th>



                                    <th>Deadline</th>



                                    <th>Assignees</th>



                                    <th>Status</th>



                                    <th class="ftm-text-right">Action</th>



                                </tr>



                            </thead>



                            <tbody id="ftm-projects-tbody">



                                <tr><td colspan="11" class="ftm-table-loading"><div class="ftm-spinner"></div> Loading projects...</td></tr>



                            </tbody>



                        </table>



                    </div>



                    <!-- MOBILE PROJECTS CARDS LIST (Mobile-First Card Layout) -->



                    <div id="ftm-mobile-projects-list" class="ftm-mobile-projects-cards"></div>



                    <div class="ftm-table-footer">



                        <div id="ftm-pagination-info" class="ftm-text-muted ftm-text-sm"></div>



                        <div id="ftm-pagination-controls" class="ftm-pagination ftm-pagination-controls"></div>



                    </div>



                </div>



            </section>







            <!-- 2. MY ASSIGNED PROJECTS VIEW -->



            <section id="ftm-view-my-projects" class="ftm-view-panel hidden">



                <div class="ftm-table-card">



                    <div class="ftm-table-header" style="flex-wrap:wrap; gap:12px; justify-content:space-between; align-items:center;">



                        <div>



                            <div class="ftm-table-title">My Assigned Orders & Deliveries</div>



                            <div class="ftm-text-muted ftm-text-sm">Track your assigned projects, deadlines, and delivery status.</div>



                        </div>



                        <div class="ftm-my-work-filter-pills" id="ftm-my-work-filter-pills">



                            <button type="button" class="ftm-pill-btn active" data-filter="active">Active (<span id="ftm-my-count-active">0</span>)</button>



                            <button type="button" class="ftm-pill-btn" data-filter="completed">Completed (<span id="ftm-my-count-completed">0</span>)</button>



                            <button type="button" class="ftm-pill-btn" data-filter="all">All (<span id="ftm-my-count-all">0</span>)</button>



                        </div>



                    </div>

                    <!-- ACTIVE TIME TRACKER & SCREENSHOT BANNER -->
                    <div id="ftm-active-tracker-bar" class="ftm-tracker-bar hidden" style="background:linear-gradient(135deg, rgba(16,185,129,0.08), rgba(37,99,235,0.08)); border:1px solid rgba(16,185,129,0.35); border-radius:12px; padding:14px 20px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="ftm-pulse-dot" style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,0.25);"></span>
                            <div>
                                <div style="font-size:10.5px; text-transform:uppercase; font-weight:800; color:#059669; letter-spacing:0.5px;">Live Work Tracker & 10m Screenshot Active</div>
                                <div style="font-size:14px; font-weight:800; color:var(--ftm-text-primary);" id="ftm-tracker-project-title">Tracking Project</div>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:18px; flex-wrap:wrap;">
                            <div style="text-align:right;">
                                <div style="font-size:10.5px; color:var(--ftm-text-muted);">Elapsed Time</div>
                                <div style="font-size:22px; font-weight:900; font-family:'Consolas',monospace; color:#2563eb;" id="ftm-tracker-clock">00:00:00</div>
                            </div>
                            <div style="text-align:center; background:var(--ftm-card-bg); border:1px solid var(--ftm-border); border-radius:8px; padding:4px 10px;">
                                <div style="font-size:10px; text-transform:uppercase; color:var(--ftm-text-muted); font-weight:600;">Next Screenshot</div>
                                <div style="font-size:12px; font-weight:800; color:#10b981;" id="ftm-tracker-next-snap">10:00</div>
                            </div>
                            <div style="text-align:center; background:var(--ftm-card-bg); border:1px solid var(--ftm-border); border-radius:8px; padding:4px 10px;">
                                <div style="font-size:10px; text-transform:uppercase; color:var(--ftm-text-muted); font-weight:600;">Snapshots</div>
                                <div style="font-size:12px; font-weight:800; color:var(--ftm-text-primary);" id="ftm-tracker-snaps-count">0</div>
                            </div>
                            <div style="text-align:center; background:var(--ftm-card-bg); border:1px solid var(--ftm-border); border-radius:8px; padding:4px 10px;">
                                <div style="font-size:10px; text-transform:uppercase; color:var(--ftm-text-muted); font-weight:600;">Activity</div>
                                <div style="font-size:12px; font-weight:800; color:#3b82f6;"><span title="Mouse clicks">🖱️ <span id="ftm-bar-mouse-count">0</span></span> · <span title="Keyboard keystrokes">⌨️ <span id="ftm-bar-key-count">0</span></span></div>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-tracker-snap-now" title="Capture immediate manual screenshot" style="font-size:12px;">📸 Snap Now</button>
                                <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-sm text-rose" id="ftm-btn-tracker-stop" style="border-color:rgba(244,63,94,0.4); font-weight:700; font-size:12px;">⏹ Stop & Log</button>
                            </div>
                        </div>
                    </div>



                    <div class="ftm-table-responsive ftm-my-projects-desktop-table">



                        <table class="ftm-table">



                            <thead>



                                <tr>



                                    <th>#</th>



                                    <th>Profile</th>



                                    <th>Client & Order</th>



                                    <th>Type</th>



                                    <th>Assign Date</th>



                                    <th>Delivery Date</th>



                                    <th>Delivery Status & Deadline</th>



                                    <th>Status</th>



                                    <th class="ftm-text-right">Action</th>



                                </tr>



                            </thead>



                            <tbody id="ftm-my-projects-tbody">



                                <tr><td colspan="9" class="ftm-table-loading"><div class="ftm-spinner"></div> Loading your projects...</td></tr>



                            </tbody>



                        </table>



                    </div>



                    <!-- MOBILE MY PROJECTS CARDS LIST -->



                    <div id="ftm-mobile-my-projects-list" class="ftm-mobile-projects-cards"></div>



                </div>



            </section>

            <!-- 2A. MY TASKS & DELIVERABLES WORKSPACE -->
            <section id="ftm-view-my-tasks" class="ftm-view-panel hidden">
                <div class="ftm-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; margin-bottom:20px;">
                    <div>
                        <h2 class="ftm-page-title" style="margin:0; font-size:22px; font-weight:800; color:var(--ftm-text-primary);">My Tasks &amp; Milestones</h2>
                        <p class="ftm-page-subtitle" style="margin:4px 0 0; color:var(--ftm-text-muted); font-size:13px;">Track assigned order tasks, priorities, and deadlines. Completing a task automatically logs it to that order's Daily Progress Report.</p>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-my-tasks-refresh-btn">
                            <span>↻</span> Refresh
                        </button>
                        <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-my-tasks-add-btn">
                            <span>+</span> Add Task
                        </button>
                    </div>
                </div>

                <!-- 4 Clickable Task KPI Overview Cards -->
                <div class="ftm-refb-kpi-grid" style="grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); margin-bottom:20px;">
                    <div class="ftm-refb-kpi-card ftm-kpi-clickable ftm-task-kpi-card ftm-kpi-active" data-task-status="all" title="Show all tasks">
                        <div class="ftm-refb-kpi-header">
                            <span class="ftm-refb-kpi-title">Total Tasks</span>
                            <span class="ftm-refb-kpi-icon-pill bg-blue-soft text-blue">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                            </span>
                        </div>
                        <div class="ftm-refb-kpi-val" id="ftm-tasks-kpi-total">0</div>
                        <div class="ftm-refb-kpi-sub">All active order tasks</div>
                    </div>

                    <div class="ftm-refb-kpi-card ftm-kpi-clickable ftm-task-kpi-card" data-task-status="pending" title="Filter pending tasks">
                        <div class="ftm-refb-kpi-header">
                            <span class="ftm-refb-kpi-title">Pending</span>
                            <span class="ftm-refb-kpi-icon-pill bg-amber-soft text-amber">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </span>
                        </div>
                        <div class="ftm-refb-kpi-val text-amber" id="ftm-tasks-kpi-pending">0</div>
                        <div class="ftm-refb-kpi-sub">Open deliverables to finish</div>
                    </div>

                    <div class="ftm-refb-kpi-card ftm-kpi-clickable ftm-task-kpi-card" data-task-status="overdue" title="Filter overdue tasks">
                        <div class="ftm-refb-kpi-header">
                            <span class="ftm-refb-kpi-title">Overdue</span>
                            <span class="ftm-refb-kpi-icon-pill bg-rose-soft text-rose">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            </span>
                        </div>
                        <div class="ftm-refb-kpi-val text-rose" id="ftm-tasks-kpi-overdue">0</div>
                        <div class="ftm-refb-kpi-sub">Past deadline &middot; needs action</div>
                    </div>

                    <div class="ftm-refb-kpi-card ftm-kpi-clickable ftm-task-kpi-card" data-task-status="done" title="Filter completed tasks">
                        <div class="ftm-refb-kpi-header">
                            <span class="ftm-refb-kpi-title">Completed</span>
                            <span class="ftm-refb-kpi-icon-pill bg-emerald-soft text-emerald">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                        </div>
                        <div class="ftm-refb-kpi-val text-emerald" id="ftm-tasks-kpi-completed">0</div>
                        <div class="ftm-refb-kpi-sub">Done &amp; synced to order report</div>
                    </div>
                </div>

                <!-- Dedicated Team / Squad Organization Tabs (Visible for Managers & Team Leaders) -->
                <?php if ($is_admin || $is_leader || $is_deputy): ?>
                <div id="ftm-my-tasks-team-tabs" class="ftm-team-tabs-bar" style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; align-items:center;">
                    <!-- Populated dynamically per role & team counts -->
                </div>
                <?php endif; ?>

                <!-- Tasks Filter Toolbar & Table -->
                <div class="ftm-table-card">
                    <div class="ftm-refb-toolbar" style="padding:14px 16px; border-bottom:1px solid var(--ftm-border); display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between;">
                        <div class="ftm-refb-search-wrap" style="flex:1; min-width:220px; max-width:340px;">
                            <input type="text" id="ftm-my-tasks-search" class="ftm-input ftm-input-sm" placeholder="Search task, order ID, or client..." style="width:100%;">
                        </div>
                        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                            <?php if ($is_admin || $is_leader || $is_deputy): ?>
                            <select id="ftm-my-tasks-member" class="ftm-refb-filter-select">
                                <option value="0">All Specialists</option>
                            </select>
                            <?php endif; ?>
                            <select id="ftm-my-tasks-status" class="ftm-refb-filter-select">
                                <option value="all">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="overdue">Overdue</option>
                                <option value="done">Completed</option>
                            </select>
                            <select id="ftm-my-tasks-priority" class="ftm-refb-filter-select">
                                <option value="all">All Priorities</option>
                                <option value="urgent">Urgent</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                            <select id="ftm-my-tasks-group" class="ftm-refb-filter-select">
                                <option value="auto" selected>Organized View (By Team / Project)</option>
                                <option value="project">Group by Project</option>
                                <option value="flat">Flat List</option>
                            </select>
                        </div>
                    </div>

                    <div class="ftm-table-responsive">
                        <table class="ftm-table" id="ftm-my-tasks-table">
                            <thead>
                                <tr>
                                    <th style="width:44px; text-align:center;">Done</th>
                                    <th>Task &amp; Milestone</th>
                                    <th>Dedicated Project</th>
                                    <th>Assigned To</th>
                                    <th>Priority</th>
                                    <th>Deadline</th>
                                    <th>Status &amp; Report</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ftm-my-tasks-tbody">
                                <tr><td colspan="8" class="ftm-table-loading"><div class="ftm-spinner"></div> Loading tasks...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 2B. WORK TRACKER & SCREENSHOTS HUB (TIMERS, 10-MIN SCREENSHOTS, SESSIONS LOG) -->
            <section id="ftm-view-time-tracker" class="ftm-view-panel hidden">
                <div class="ftm-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
                    <div>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <h2 class="ftm-page-title" style="margin:0; font-size:22px; font-weight:800; color:var(--ftm-text-main);">Live Work Tracker & Screenshot Activity Hub</h2>
                            <span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-sm" id="ftm-hub-active-badge">⚡ Real-time</span>
                            <span class="ftm-badge ftm-badge-outline ftm-badge-xs" style="font-weight:600; color:var(--ftm-text-muted);" title="Recorded screenshots from the previous month are automatically deleted on the 15th of each month.">🗑️ Auto-cleanup: Prev month deleted on 15th</span>
                        </div>
                        <p class="ftm-page-subtitle" style="margin:4px 0 0; color:var(--ftm-text-muted); font-size:13px;">Live tracking monitor, automated 10-minute work screenshots, specialist time logs, and leadership audit gallery.</p>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-hub-btn-refresh" style="display:inline-flex; align-items:center; gap:6px;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                            <span>Refresh Feed</span>
                        </button>
                    </div>
                </div>

                <!-- Active Live Sessions Banner -->
                <div id="ftm-hub-live-active-container" class="hidden" style="margin-bottom:20px;">
                    <div style="background:linear-gradient(135deg, rgba(16,185,129,0.1), rgba(37,99,235,0.08)); border:1px solid rgba(16,185,129,0.35); border-radius:12px; padding:16px 20px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                            <span class="ftm-pulse-dot" style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,0.25);"></span>
                            <span style="font-size:12px; font-weight:800; color:#059669; text-transform:uppercase; letter-spacing:0.5px;">Live Specialists Working Now</span>
                        </div>
                        <div id="ftm-hub-live-active-list" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:14px;">
                            <!-- Active tracking cards -->
                        </div>
                    </div>
                </div>

                <!-- KPI Statistics Overview -->
                <div class="ftm-kpi-grid ftm-hub-kpi-grid">
                    <div class="ftm-card ftm-kpi-card ftm-hub-kpi-card ftm-hub-kpi-blue">
                        <div class="ftm-hub-kpi-top">
                            <span class="ftm-hub-kpi-label">Total Hours Tracked</span>
                            <span class="ftm-hub-kpi-icon bg-blue-soft text-blue" aria-hidden="true">⏱️</span>
                        </div>
                        <div class="ftm-kpi-value text-blue" id="ftm-hub-kpi-total-hours">0h 00m</div>
                        <div class="ftm-hub-kpi-meta">All logged sessions</div>
                    </div>
                    <div class="ftm-card ftm-kpi-card ftm-hub-kpi-card ftm-hub-kpi-emerald">
                        <div class="ftm-hub-kpi-top">
                            <span class="ftm-hub-kpi-label">Today's Tracked Work</span>
                            <span class="ftm-hub-kpi-icon bg-emerald-soft text-emerald" aria-hidden="true">📅</span>
                        </div>
                        <div class="ftm-kpi-value text-emerald" id="ftm-hub-kpi-today-hours">0h 00m</div>
                        <div class="ftm-hub-kpi-meta">Regular &amp; extra shifts today</div>
                    </div>
                    <div class="ftm-card ftm-kpi-card ftm-hub-kpi-card ftm-hub-kpi-indigo">
                        <div class="ftm-hub-kpi-top">
                            <span class="ftm-hub-kpi-label">Screenshots Captured</span>
                            <span class="ftm-hub-kpi-icon bg-purple-soft text-purple" aria-hidden="true">📸</span>
                        </div>
                        <div class="ftm-kpi-value text-indigo" id="ftm-hub-kpi-total-snaps">0</div>
                        <div class="ftm-hub-kpi-meta">10-minute verification snaps</div>
                    </div>
                    <div class="ftm-card ftm-kpi-card ftm-hub-kpi-card ftm-hub-kpi-amber">
                        <div class="ftm-hub-kpi-top">
                            <span class="ftm-hub-kpi-label">Active Live Sessions</span>
                            <span class="ftm-hub-kpi-icon bg-amber-soft text-amber" aria-hidden="true">⚡</span>
                        </div>
                        <div class="ftm-kpi-value text-amber" id="ftm-hub-kpi-active-sessions">0</div>
                        <div class="ftm-hub-kpi-meta">Timers currently running</div>
                    </div>
                </div>

                <!-- Subtab Navigation -->
                <div class="ftm-hub-tab-nav" role="tablist" aria-label="Work Tracker Sections">
                    <button type="button" class="ftm-tab-btn ftm-hub-tab-btn active" id="ftm-hub-subtab-gallery" role="tab" aria-selected="true">
                        <span>📸</span>
                        <span>Screenshot Gallery &amp; Audit</span>
                        <span class="ftm-hub-tab-count" id="ftm-hub-tab-snaps-count">0</span>
                    </button>
                    <button type="button" class="ftm-tab-btn ftm-hub-tab-btn" id="ftm-hub-subtab-daily" role="tab" aria-selected="false">
                        <span>📅</span>
                        <span>Date-Wise Time Records</span>
                        <span class="ftm-hub-tab-count" id="ftm-hub-tab-daily-count">0</span>
                    </button>
                    <button type="button" class="ftm-tab-btn ftm-hub-tab-btn" id="ftm-hub-subtab-sessions" role="tab" aria-selected="false">
                        <span>⏱</span>
                        <span>Tracking Sessions Log</span>
                        <span class="ftm-hub-tab-count" id="ftm-hub-tab-sessions-count">0</span>
                    </button>
                </div>

                <!-- SUBTAB 1: SCREENSHOTS GALLERY -->
                <div id="ftm-hub-view-gallery">
                    <!-- Filters Toolbar -->
                    <div class="ftm-card ftm-hub-toolbar">
                        <div class="ftm-hub-toolbar-filters">
                            <div class="ftm-hub-filter-group">
                                <label for="ftm-hub-filter-team">SQUAD</label>
                                <select id="ftm-hub-filter-team" class="ftm-select ftm-hub-control">
                                    <?php if ($can_manage_all_squads): ?>
                                    <option value="all">All Squads</option>
                                    <option value="WpTitans">WpTitans</option>
                                    <option value="WpNexus">WpNexus</option>
                                    <option value="WpCraft">WpCraft</option>
                                    <?php else: ?>
                                    <option value="<?php echo esc_attr($team); ?>"><?php echo esc_html($team); ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="ftm-hub-filter-group">
                                <label for="ftm-hub-filter-date">DATE</label>
                                <input type="date" id="ftm-hub-filter-date" class="ftm-input ftm-hub-control">
                            </div>
                            <div class="ftm-hub-filter-group">
                                <label for="ftm-hub-filter-session-type">TYPE</label>
                                <select id="ftm-hub-filter-session-type" class="ftm-select ftm-hub-control">
                                    <option value="all">All Shifts &amp; Overtime</option>
                                    <option value="regular">⏱️ Regular Shift</option>
                                    <option value="extra_time">⚡ Extra Time (After 6:20 PM)</option>
                                </select>
                            </div>
                            <div class="ftm-hub-filter-search-wrap">
                                <input type="text" id="ftm-hub-filter-search" class="ftm-input ftm-hub-control ftm-hub-search-input" placeholder="Search order ID, client, or specialist...">
                            </div>
                        </div>
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-hub-reset-btn" id="ftm-hub-btn-reset-filters">
                            Reset Filters
                        </button>
                    </div>

                    <!-- Screenshot Cards Grid -->
                    <div id="ftm-hub-gallery-grid" class="ftm-hub-gallery-grid">
                        <!-- Screenshot cards injected by JS -->
                    </div>
                </div>

                <!-- SUBTAB 2: DATE-WISE TIME RECORDS -->
                <div id="ftm-hub-view-daily" class="hidden">
                    <!-- Date-Wise Filter Bar -->
                    <div class="ftm-card ftm-hub-toolbar">
                        <div class="ftm-hub-toolbar-filters">
                            <div class="ftm-hub-preset-group">
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-hub-daily-preset" data-preset="today">Today</button>
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-hub-daily-preset" data-preset="yesterday">Yesterday</button>
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-hub-daily-preset" data-preset="7d">Last 7 Days</button>
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-hub-daily-preset active" data-preset="30d">Last 30 Days</button>
                            </div>
                            <div class="ftm-hub-filter-group">
                                <label for="ftm-hub-daily-from">FROM</label>
                                <input type="date" id="ftm-hub-daily-from" class="ftm-input ftm-hub-control">
                            </div>
                            <div class="ftm-hub-filter-group">
                                <label for="ftm-hub-daily-to">TO</label>
                                <input type="date" id="ftm-hub-daily-to" class="ftm-input ftm-hub-control">
                            </div>
                            <div class="ftm-hub-filter-group">
                                <label for="ftm-hub-daily-team">SQUAD</label>
                                <select id="ftm-hub-daily-team" class="ftm-select ftm-hub-control">
                                    <?php if ($can_manage_all_squads): ?>
                                    <option value="all">All Squads</option>
                                    <option value="WpTitans">WpTitans</option>
                                    <option value="WpNexus">WpNexus</option>
                                    <option value="WpCraft">WpCraft</option>
                                    <?php else: ?>
                                    <option value="<?php echo esc_attr($team); ?>"><?php echo esc_html($team); ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="ftm-hub-filter-group">
                                <label for="ftm-hub-daily-specialist">MEMBER</label>
                                <select id="ftm-hub-daily-specialist" class="ftm-select ftm-hub-control" style="max-width:190px;">
                                    <option value="0">All Specialists</option>
                                </select>
                            </div>
                        </div>
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-hub-reset-btn" id="ftm-hub-daily-reset">Reset</button>
                    </div>

                    <!-- Period Summary KPI Strip -->
                    <div class="ftm-kpi-grid ftm-hub-kpi-grid ftm-hub-daily-kpi-grid">
                        <div class="ftm-card ftm-hub-kpi-card ftm-hub-kpi-blue">
                            <div class="ftm-hub-kpi-top">
                                <span class="ftm-hub-kpi-label">Period Total Tracked</span>
                            </div>
                            <div class="ftm-kpi-value text-blue" id="ftm-hub-daily-kpi-total">0h 00m</div>
                        </div>
                        <div class="ftm-card ftm-hub-kpi-card ftm-hub-kpi-emerald">
                            <div class="ftm-hub-kpi-top">
                                <span class="ftm-hub-kpi-label">Regular Shift Time</span>
                            </div>
                            <div class="ftm-kpi-value text-emerald" id="ftm-hub-daily-kpi-regular">0h 00m</div>
                        </div>
                        <div class="ftm-card ftm-hub-kpi-card ftm-hub-kpi-amber">
                            <div class="ftm-hub-kpi-top">
                                <span class="ftm-hub-kpi-label">Extra Time (After 6:20 PM)</span>
                            </div>
                            <div class="ftm-kpi-value text-amber" id="ftm-hub-daily-kpi-extra">0h 00m</div>
                        </div>
                        <div class="ftm-card ftm-hub-kpi-card ftm-hub-kpi-indigo">
                            <div class="ftm-hub-kpi-top">
                                <span class="ftm-hub-kpi-label">Sessions &amp; Snapshots</span>
                            </div>
                            <div class="ftm-kpi-value" id="ftm-hub-daily-kpi-counts" style="color:var(--ftm-text-main);">0 / 0</div>
                        </div>
                    </div>

                    <!-- Daily Totals Chips -->
                    <div id="ftm-hub-daily-totals-strip" class="ftm-hub-daily-strip"></div>

                    <!-- Date-Wise Specialist Breakdown Table -->
                    <div class="ftm-card ftm-hub-table-card">
                        <div class="ftm-hub-table-head">
                            <h4>Date-Wise Time Record (Grouped by Date &amp; Specialist)</h4>
                            <span class="ftm-text-xs ftm-text-muted">Click "Sessions" or "Screens" on any row to inspect that day's logs and screenshots.</span>
                        </div>
                        <div class="ftm-table-responsive">
                            <table class="ftm-table" style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">DATE</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">SPECIALIST</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">SQUAD</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">REGULAR SHIFT</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">EXTRA TIME</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">TOTAL TIME</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">PROJECTS WORKED</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">SESSIONS &amp; SNAPS</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:right;">INSPECT</th>
                                    </tr>
                                </thead>
                                <tbody id="ftm-hub-daily-table-body">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SUBTAB 3: TRACKING SESSIONS LOG -->
                <div id="ftm-hub-view-sessions" class="hidden">
                    <!-- Sessions Filter Toolbar -->
                    <div class="ftm-card ftm-hub-toolbar">
                        <div class="ftm-hub-toolbar-filters">
                            <div class="ftm-hub-filter-group">
                                <label for="ftm-hub-sessions-date">DATE</label>
                                <input type="date" id="ftm-hub-sessions-date" class="ftm-input ftm-hub-control">
                            </div>
                            <div class="ftm-hub-filter-group">
                                <label for="ftm-hub-sessions-team">SQUAD</label>
                                <select id="ftm-hub-sessions-team" class="ftm-select ftm-hub-control">
                                    <?php if ($can_manage_all_squads): ?>
                                    <option value="all">All Squads</option>
                                    <option value="WpTitans">WpTitans</option>
                                    <option value="WpNexus">WpNexus</option>
                                    <option value="WpCraft">WpCraft</option>
                                    <?php else: ?>
                                    <option value="<?php echo esc_attr($team); ?>"><?php echo esc_html($team); ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="ftm-hub-filter-group">
                                <label for="ftm-hub-sessions-type">TYPE</label>
                                <select id="ftm-hub-sessions-type" class="ftm-select ftm-hub-control">
                                    <option value="all">All Shifts &amp; Overtime</option>
                                    <option value="regular">⏱️ Regular Shift</option>
                                    <option value="extra_time">⚡ Extra Time (After 6:20 PM)</option>
                                </select>
                            </div>
                            <div class="ftm-hub-filter-search-wrap">
                                <input type="text" id="ftm-hub-sessions-search" class="ftm-input ftm-hub-control ftm-hub-search-input" placeholder="Search specialist, order ID, client...">
                            </div>
                        </div>
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-hub-reset-btn" id="ftm-hub-sessions-reset">
                            Reset Filters
                        </button>
                    </div>

                    <div class="ftm-card ftm-hub-table-card">
                        <div class="ftm-hub-table-head">
                            <h4>Specialist Work Tracking Sessions</h4>
                            <span class="ftm-text-xs ftm-text-muted">Detailed log of verified active work durations and screenshots.</span>
                        </div>
                        <div class="ftm-table-responsive">
                            <table class="ftm-table" style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">#</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">SPECIALIST</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">SQUAD</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">ORDER / PROJECT</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">START & END TIME</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">DURATION</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">ACTIVITY</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">SCREENSHOTS</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">STANDUP SYNC</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:right;">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="ftm-hub-sessions-table-body">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. DAILY REPORTS VIEW (MEMBER SUBMISSION & HISTORY - REFERENCE B) -->



            <section id="ftm-view-daily-reports" class="ftm-view-panel hidden">



                <!-- Standup View Top Header & Date Navigation -->



                <div class="ftm-view-header-bar">



                    <div>



                        <div class="ftm-breadcrumb-nav">



                            <a href="#dashboard" class="ftm-breadcrumb-link">&larr; Dashboard</a>



                            <span class="ftm-breadcrumb-sep">&gt;</span>



                            <span class="ftm-breadcrumb-current">My Daily Standup</span>



                        </div>



                        <h2 class="ftm-page-title">My Daily Standup</h2>



                        <p class="ftm-page-subtitle">Log your daily progress, keep your team updated, and track your work history.</p>



                    </div>



                    <div class="ftm-header-actions-group">



                        <div class="ftm-date-navigator">



                            <button type="button" class="ftm-date-nav-btn" id="ftm-standup-date-prev" title="Previous Day">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>



                            </button>



                            <div class="ftm-date-display" id="ftm-standup-date-display-wrap">



                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>



                                <span id="ftm-standup-current-date-label"><?php echo esc_html(current_time('l, F j, Y')); ?></span>



                                <input type="date" id="ftm-standup-date-input" value="<?php echo esc_attr(current_time('Y-m-d')); ?>" max="<?php echo esc_attr(current_time('Y-m-d')); ?>" style="position:absolute; opacity:0; pointer-events:none; width:0; height:0;">



                            </div>



                            <button type="button" class="ftm-date-nav-btn" id="ftm-standup-date-next" title="Next Day" disabled style="opacity:0.35; cursor:not-allowed;">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>



                            </button>



                        </div>



                    </div>



                </div>







                <!-- 4 KPI Summary Cards (Reference B / Image 2) -->



                <div class="ftm-standup-kpi-grid">



                    <div class="ftm-standup-kpi-card ftm-kpi-blue">



                        <div class="ftm-standup-kpi-icon-wrap bg-blue-soft text-blue">



                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>



                        </div>



                        <div class="ftm-standup-kpi-content">



                            <div class="ftm-standup-kpi-val" id="ftm-standup-kpi-assigned">0</div>



                            <div class="ftm-standup-kpi-lbl">Assigned Projects</div>



                        </div>



                    </div>



                    <div class="ftm-standup-kpi-card ftm-kpi-emerald">



                        <div class="ftm-standup-kpi-icon-wrap bg-emerald-soft text-emerald">



                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>



                        </div>



                        <div class="ftm-standup-kpi-content">



                            <div class="ftm-standup-kpi-val" id="ftm-standup-kpi-updated">0</div>



                            <div class="ftm-standup-kpi-lbl">Work Updated</div>



                        </div>



                    </div>



                    <div class="ftm-standup-kpi-card ftm-kpi-purple">



                        <div class="ftm-standup-kpi-icon-wrap bg-purple-soft text-purple">



                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>



                        </div>



                        <div class="ftm-standup-kpi-content">



                            <div class="ftm-standup-kpi-val" id="ftm-standup-kpi-hours">0h 00m</div>



                            <div class="ftm-standup-kpi-lbl">Total Work Hours</div>



                        </div>



                    </div>



                    <div class="ftm-standup-kpi-card ftm-kpi-amber">



                        <div class="ftm-standup-kpi-icon-wrap bg-amber-soft text-amber">



                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>



                        </div>



                        <div class="ftm-standup-kpi-content">



                            <div class="ftm-standup-kpi-val" id="ftm-standup-kpi-pending">0</div>



                            <div class="ftm-standup-kpi-lbl">Pending Tasks</div>



                        </div>



                    </div>



                </div>







                <!-- 2-Column Main Workspace (Reference B / Image 2) -->



                <div class="ftm-standup-workspace-grid">



                    <!-- LEFT COLUMN: Standup Form -->



                    <div class="ftm-standup-form-col">



                        <div class="ftm-standup-card">



                            <div class="ftm-standup-card-header">



                                <div>



                                    <h3 class="ftm-card-title">Log Your Daily Progress</h3>



                                    <p class="ftm-card-subtitle">Record what you worked on today, update current status and plan your next steps.</p>



                                </div>



                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs" id="ftm-btn-jump-history">



                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>



                                    <span>View Previous Reports</span>



                                </button>



                            </div>







                            <form id="ftm-standup-workspace-form">



                                <input type="hidden" id="ftm-ws-report-id" value="">



                                <input type="hidden" id="ftm-ws-selected-type" value="work">



                                



                                <!-- Report Type Segmented Switcher (Work, Weekend, Leave) -->



                                <div class="ftm-report-type-segmented">



                                    <button type="button" class="ftm-report-type-toggle active" data-type="work">



                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>



                                        <span>Regular Work</span>



                                    </button>



                                    <button type="button" class="ftm-report-type-toggle" data-type="weekend">
                                        <span style="display:inline-flex; align-items:center; gap:6px;">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>
                                            Weekend / Off
                                        </span>
                                    </button>
                                    <button type="button" class="ftm-report-type-toggle" data-type="on_leave">
                                        <span style="display:inline-flex; align-items:center; gap:6px;">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"></path></svg>
                                            On Leave / Vacation
                                        </span>
                                    </button>



                                </div>







                                <!-- SECTION 1: REGULAR WORK FORM -->



                                <div id="ftm-ws-work-section">



                                    <div class="ftm-form-row-2">



                                        <div class="ftm-form-group">



                                            <label class="ftm-form-label" for="ftm-ws-select-project">Select Project <span class="text-rose">*</span></label>

                                            <div class="ftm-searchable-select-wrap" id="ftm-ws-proj-picker-wrap">
                                                <select id="ftm-ws-select-project" class="ftm-form-control ftm-native-hidden-select" required tabindex="-1" aria-hidden="true">

                                                    <option value="">-- Choose Assigned Project --</option>

                                                    <!-- Populated dynamically -->

                                                    <option value="custom">+ Other / Custom Project (Old Client / Internal Task)</option>

                                                </select>
                                                <button type="button" id="ftm-ws-proj-picker-btn" class="ftm-form-control ftm-search-select-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                    <span id="ftm-ws-proj-picker-label">-- Choose Assigned Project --</span>
                                                    <span class="ftm-search-select-caret" aria-hidden="true">&#9662;</span>
                                                </button>
                                                <div id="ftm-ws-proj-dropdown" class="ftm-search-select-dropdown hidden" role="listbox">
                                                    <div class="ftm-search-select-input-wrap">
                                                        <input type="text" id="ftm-ws-proj-search" class="ftm-form-control" placeholder="Search Order ID, client, profile, or status..." autocomplete="off">
                                                    </div>
                                                    <div id="ftm-ws-proj-options" class="ftm-search-select-options"></div>
                                                </div>
                                            </div>

                                        </div>



                                        <div class="ftm-form-group">



                                            <label class="ftm-form-label" for="ftm-ws-work-hours">Work Hours <span class="text-rose">*</span> <span id="ftm-ws-hours-live-preview" class="ftm-badge bg-blue-soft text-blue ftm-badge-xs hidden" style="margin-left:8px; font-weight:700;"></span></label>



                                            <div class="ftm-input-icon-wrap">



                                                <span class="ftm-input-icon">



                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>



                                                </span>



                                                <input type="text" id="ftm-ws-work-hours" class="ftm-form-control ftm-has-icon" placeholder="e.g. 2h 30m or 2.5" required>



                                            </div>



                                        </div>



                                    </div>







                                    <!-- Custom Project Info (if user selected custom/old client) -->



                                    <div id="ftm-ws-custom-project-wrap" class="ftm-form-row-2 hidden" style="margin-top:-8px; margin-bottom:14px; background:var(--ftm-bg, #f8fafc); padding:12px; border-radius:8px; border:1px dashed var(--ftm-border);">



                                        <div class="ftm-form-group" style="margin:0;">



                                            <label class="ftm-form-label" for="ftm-ws-custom-title">Client / Project Title</label>



                                            <input type="text" id="ftm-ws-custom-title" class="ftm-form-control" placeholder="e.g. Old Client Website Update">



                                        </div>



                                        <div class="ftm-form-group" style="margin:0;">



                                            <label class="ftm-form-label" for="ftm-ws-custom-order">Order ID / Ref (Optional)</label>



                                            <input type="text" id="ftm-ws-custom-order" class="ftm-form-control" placeholder="e.g. #FO123456789">



                                        </div>



                                    </div>















                                    <!-- 4 Quadrants Grid (Reference B / Image 2) -->



                                    <div class="ftm-standup-quadrants-grid">



                                        <!-- Quadrant 1: Completed -->



                                        <div class="ftm-quadrant-card ftm-quadrant-completed">



                                            <div class="ftm-quadrant-header">



                                                <span class="ftm-quadrant-icon bg-emerald-soft text-emerald">



                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>



                                                </span>



                                                <span class="ftm-quadrant-title">What is completed? <span class="text-rose">*</span></span>



                                            </div>



                                            <textarea id="ftm-ws-completed" class="ftm-quadrant-input" rows="3" placeholder="&bull; Header section development completed&#10;&bull; Mobile responsive issues fixed" required></textarea>



                                        </div>







                                        <!-- Quadrant 2: WIP -->



                                        <div class="ftm-quadrant-card ftm-quadrant-wip">



                                            <div class="ftm-quadrant-header">



                                                <span class="ftm-quadrant-icon bg-amber-soft text-amber">



                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 10"></polyline></svg>



                                                </span>



                                                <span class="ftm-quadrant-title">What are you working on now?</span>



                                            </div>



                                            <textarea id="ftm-ws-wip" class="ftm-quadrant-input" rows="3" placeholder="&bull; Client customization section&#10;&bull; Image optimization and testing"></textarea>



                                        </div>







                                        <!-- Quadrant 3: Blockers -->



                                        <div class="ftm-quadrant-card ftm-quadrant-blockers">



                                            <div class="ftm-quadrant-header">



                                                <span class="ftm-quadrant-icon bg-rose-soft text-rose">



                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>



                                                </span>



                                                <span class="ftm-quadrant-title">Any blockers or issues?</span>



                                            </div>



                                            <textarea id="ftm-ws-blockers" class="ftm-quadrant-input" rows="3" placeholder="Waiting for client feedback, access credentials, or blockers..."></textarea>



                                        </div>







                                        <!-- Quadrant 4: Next Plan -->



                                        <div class="ftm-quadrant-card ftm-quadrant-next">



                                            <div class="ftm-quadrant-header">



                                                <span class="ftm-quadrant-icon bg-blue-soft text-blue">



                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>



                                                </span>



                                                <span class="ftm-quadrant-title">Next Plan / Tomorrow's Plan</span>



                                            </div>



                                            <textarea id="ftm-ws-next-plan" class="ftm-quadrant-input" rows="3" placeholder="&bull; Complete client customization section&#10;&bull; Test on multiple browsers&#10;&bull; Prepare for client review"></textarea>



                                        </div>



                                    </div>







                                    <!-- Progress Percentage Slider/Input -->



                                    <div class="ftm-form-row-2" style="margin-top:14px;">



                                        <div class="ftm-form-group" style="margin:0;">



                                            <label class="ftm-form-label" for="ftm-ws-progress-percent">Est. Project Progress: <strong id="ftm-ws-progress-val">70%</strong></label>



                                            <input type="range" id="ftm-ws-progress-percent" min="0" max="100" value="70" class="ftm-range-slider" style="width:100%;">



                                        </div>



                                    </div>







                                    <!-- Form Footer Actions -->



                                    <div class="ftm-standup-form-footer">



                                        <div class="ftm-attachment-action">



                                            <label class="ftm-btn ftm-btn-secondary ftm-btn-sm" style="cursor:pointer; margin:0;">



                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>



                                                <span>Add Attachment</span>



                                                <input type="file" id="ftm-ws-attachment" style="display:none;" multiple>



                                            </label>



                                            <span class="ftm-attachment-hint">Upload screenshots, files or references (Max 10MB)</span>



                                            <span id="ftm-ws-attachment-name" class="ftm-text-xs text-blue" style="margin-left:6px;"></span>



                                        </div>







                                        <div class="ftm-submit-group">



                                            <button type="button" class="ftm-btn ftm-btn-secondary" id="ftm-ws-btn-draft">Save as Draft</button>



                                            <button type="submit" class="ftm-btn ftm-btn-primary" id="ftm-ws-btn-submit">



                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>



                                                <span>Submit Daily Report</span>



                                            </button>



                                        </div>



                                    </div>



                                </div>







                                <!-- SECTION 2: WEEKEND / OFF DAY -->



                                <div id="ftm-ws-weekend-section" class="hidden" style="padding:28px 20px; text-align:center; background:var(--ftm-bg, #f8fafc); border-radius:12px; border:1px dashed var(--ftm-border); margin-bottom:16px;">



                                    <div style="font-size:36px; margin-bottom:8px;"><span class="dashicons dashicons-palmtree"></span></div>



                                    <h4 style="font-size:16px; font-weight:700; color:var(--ftm-text-primary); margin-bottom:6px;">Mark as Weekend / Weekly Off Day</h4>



                                    <p style="font-size:13px; color:var(--ftm-text-muted); max-width:460px; margin:0 auto 20px auto; line-height:1.5;">



                                        Marking today as your weekly off day records your status cleanly across the agency. Your team leader and manager will see you are off, keeping reporting parity at 100%.



                                    </p>



<button type="button" class="ftm-btn ftm-btn-primary" id="ftm-ws-btn-confirm-weekend" style="padding:10px 24px; font-weight:600; gap:8px;">



                                        <span>Confirm Weekend for <span id="ftm-ws-weekend-date-label"><?php echo esc_html(current_time('l, M j, Y')); ?></span></span>



                                    </button>



                                </div>







                                <!-- SECTION 3: ON LEAVE / VACATION -->



                                <div id="ftm-ws-leave-section" class="hidden" style="padding:22px; background:var(--ftm-bg, #f8fafc); border-radius:12px; border:1px solid var(--ftm-border); margin-bottom:16px;">



                                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">



                                        <span style="font-size:26px;"><span class="dashicons dashicons-calendar-alt"></span></span>



                                        <div>



                                            <h4 style="font-size:15px; font-weight:700; color:var(--ftm-text-primary); margin:0;">Leave Notice & Vacation Request</h4>



                                            <p style="font-size:12px; color:var(--ftm-text-muted); margin:2px 0 0 0;">Notify management of your planned absence with date range, leave category, and reason.</p>



                                        </div>



                                    </div>







                                    <div class="ftm-form-row-2">



                                        <div class="ftm-form-group">



                                            <label class="ftm-form-label" for="ftm-ws-leave-start">Leave Start Date <span class="text-rose">*</span></label>



                                            <input type="date" id="ftm-ws-leave-start" class="ftm-form-control" value="<?php echo esc_attr(current_time('Y-m-d')); ?>">



                                        </div>



                                        <div class="ftm-form-group">



                                            <label class="ftm-form-label" for="ftm-ws-leave-end">Leave End Date <span class="text-rose">*</span></label>



                                            <input type="date" id="ftm-ws-leave-end" class="ftm-form-control" value="<?php echo esc_attr(current_time('Y-m-d')); ?>">



                                        </div>



                                    </div>







                                    <div class="ftm-form-row-2">



                                        <div class="ftm-form-group">



                                            <label class="ftm-form-label" for="ftm-ws-leave-type">Leave Category <span class="text-rose">*</span></label>



                                            <select id="ftm-ws-leave-type" class="ftm-form-control">
                                                <option value="casual_leave">Casual Leave (Full Day)</option>
                                                <option value="half_day_morning">Half Day Leave (Morning / 1st Half - 0.5 Day)</option>
                                                <option value="half_day_evening">Half Day Leave (Evening / 2nd Half - 0.5 Day)</option>
                                                <option value="sick_leave">Sick / Medical Leave</option>
                                                <option value="vacation">Vacation / Annual Leave</option>
                                                <option value="emergency">Emergency / Urgent Leave</option>
                                            </select>



                                        </div>



                                        <div class="ftm-form-group">



                                            <label class="ftm-form-label">Duration</label>



                                            <div id="ftm-ws-leave-duration-badge" style="padding:8px 14px; background:var(--ftm-surface); border:1px solid var(--ftm-border); border-radius:8px; font-size:13px; font-weight:600; color:var(--ftm-text-primary); display:flex; align-items:center; height:42px;">



                                                1 day



                                            </div>



                                        </div>



                                    </div>







                                    <div class="ftm-form-group">



                                        <label class="ftm-form-label" for="ftm-ws-leave-reason">Reason / Details for Manager <span class="text-rose">*</span></label>



                                        <textarea id="ftm-ws-leave-reason" class="ftm-form-control" rows="3" placeholder="Please write the reason for your leave (e.g. Attending family function, medical recovery, personal rest)..."></textarea>



                                    </div>







                                    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px;">



                                        <button type="button" class="ftm-btn ftm-btn-primary" id="ftm-ws-btn-submit-leave" style="padding:10px 22px; font-weight:600; gap:8px;">



                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>



                                            <span>Submit Leave Notice to Manager</span>



                                        </button>



                                    </div>



                                </div>



                            </form>



                        </div>



                    </div>







                    <!-- RIGHT COLUMN: Sidebar Widgets -->



                    <div class="ftm-standup-sidebar-col">



                        <!-- Today's Projects Widget -->



                        <div class="ftm-standup-widget-card">



                            <div class="ftm-widget-header">



                                <h4 class="ftm-widget-title">Today's Projects</h4>



                                <a href="#my-assigned" class="ftm-widget-link">View All</a>



                            </div>



                            <div id="ftm-standup-todays-projects-list" class="ftm-widget-items-list">



                                <!-- Loaded dynamically via JS -->



                            </div>



                        </div>







                        <!-- Recent Reports Widget -->



                        <div class="ftm-standup-widget-card" style="margin-top:16px;">



                            <div class="ftm-widget-header">



                                <h4 class="ftm-widget-title">Recent Reports</h4>



                                <a href="javascript:void(0)" id="ftm-link-recent-view-all" class="ftm-widget-link">View All</a>



                            </div>



                            <div id="ftm-standup-recent-reports-list" class="ftm-widget-items-list">



                                <!-- Loaded dynamically via JS -->



                            </div>



                        </div>



                    </div>



                </div>







                <!-- BOTTOM CARD: My Progress History (Reference B / Image 2) -->



                <div class="ftm-standup-history-card" id="ftm-standup-history-section" style="margin-top:24px;">



                    <div class="ftm-history-header">



                        <h3 class="ftm-card-title">My Progress History</h3>



                        <div class="ftm-history-filters">



                            <select id="ftm-history-filter-project" class="ftm-select ftm-select-sm">



                                <option value="all">All Projects</option>



                            </select>



                            <select id="ftm-history-filter-range" class="ftm-select ftm-select-sm">



                                <option value="7">Last 7 Days</option>



                                <option value="30">Last 30 Days</option>



                                <option value="all">All Time</option>



                            </select>



                        </div>



                    </div>



                    <div class="ftm-history-table-wrap">



                        <table class="ftm-table ftm-table-hover" id="ftm-standup-history-table">



                            <thead>



                                <tr>



                                    <th>Date</th>



                                    <th>Project</th>



                                    <th>Work Summary</th>



                                    <th>Hours</th>



                                    <th>Status</th>



                                    <th style="text-align:right;">Action</th>



                                </tr>



                            </thead>



                            <tbody id="ftm-standup-history-tbody">



                                <!-- Populated dynamically via JS -->



                            </tbody>



                        </table>



                    </div>







                    <!-- Mobile Progress History Cards (<= 768px) -->



                    <div class="ftm-standup-history-mobile-cards" id="ftm-standup-history-mobile-cards">



                        <!-- Populated dynamically on mobile via JS -->



                    </div>



                </div>



            </section>







            <!-- 4. TEAM DAILY REPORT VIEW (LEADER & MANAGER VIEW - REFERENCE C) -->



            <?php if ($is_leader || $is_deputy): ?>



            <section id="ftm-view-team-daily-report" class="ftm-view-panel hidden">



                <!-- Header Bar -->



                <div class="ftm-view-header-bar">



                    <div>



                        <div class="ftm-breadcrumb-nav">



                            <a href="#dashboard" class="ftm-breadcrumb-link">&larr; Dashboard</a>



                            <span class="ftm-breadcrumb-sep">&gt;</span>



                            <span class="ftm-breadcrumb-current">Team Daily Report</span>



                        </div>



                        <h2 class="ftm-page-title">Team Daily Report</h2>



                        <p class="ftm-page-subtitle"><?php echo ($is_admin || $is_deputy) ? 'Monitor your agency\'s daily progress, workload, and project updates across all teams.' : 'Monitor your team\'s daily progress, workload, and project updates.'; ?></p>



                    </div>



                    <div class="ftm-header-actions-group">



                        <div class="ftm-date-navigator">



                            <button type="button" class="ftm-date-nav-btn" id="ftm-team-date-prev" title="Previous Day">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>



                            </button>



                            <div class="ftm-date-display" id="ftm-team-date-display-wrap">



                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>



                                <span id="ftm-team-current-date-label"><?php echo esc_html(current_time('l, F j, Y')); ?></span>



                                <input type="date" id="ftm-team-report-date" value="<?php echo esc_attr(current_time('Y-m-d')); ?>" max="<?php echo esc_attr(current_time('Y-m-d')); ?>" style="position:absolute; opacity:0; pointer-events:none; width:0; height:0;">



                            </div>



                            <button type="button" class="ftm-date-nav-btn" id="ftm-team-date-next" title="Next Day" disabled style="opacity:0.35; cursor:not-allowed;">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>



                            </button>



                        </div>



                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-export-team-report">



                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>



                            <span>Export CSV</span>



                        </button>



                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-open-executive-doc" style="border-color:#2563eb; color:#2563eb; font-weight:600;">



                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>



                            <span>Executive Briefing Doc</span>



                        </button>



                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-print-team-daily" title="Print Daily Operations Report">



                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>



                            <span>Print Report</span>



                        </button>

                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-manage-leaves" style="border-color:#8b5cf6; color:#8b5cf6; font-weight:600;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            <span>Leaves & Day-Offs</span>
                        </button>

                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-apply-leave-header" style="font-weight:600;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            <span>Apply Leave</span>
                        </button>



                        <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-btn-view-weekly-report">



                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"></path><path d="M12 20V4"></path><path d="M6 20v-6"></path></svg>



                            <span>View Weekly Report</span>



                        </button>



                        <?php if ($is_leader && !$is_admin && !$is_deputy): ?>



                        <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-btn-forward-report" style="white-space:nowrap;">



                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>



                            <span>Forward to Manager</span>



                        </button>



                        <?php endif; ?>



                    </div>



                </div>







                <!-- 5 KPI Summary Cards (Reference C / Image 3) -->



                <div class="ftm-team-kpi-grid">



                    <div class="ftm-team-kpi-card ftm-kpi-purple">



                        <div class="ftm-team-kpi-icon-wrap bg-purple-soft text-purple">



                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>



                        </div>



                        <div class="ftm-team-kpi-content">



                            <div class="ftm-team-kpi-label">Team Members</div>



                            <div class="ftm-team-kpi-val" id="ftm-tkpi-members-count">0</div>



                            <div class="ftm-team-kpi-subtext" id="ftm-tkpi-members-sub">0 submitted (0%)</div>



                        </div>



                    </div>







                    <div class="ftm-team-kpi-card ftm-kpi-emerald">



                        <div class="ftm-team-kpi-icon-wrap bg-emerald-soft text-emerald">



                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>



                        </div>



                        <div class="ftm-team-kpi-content">



                            <div class="ftm-team-kpi-label">Total Work Hours</div>



                            <div class="ftm-team-kpi-val" id="ftm-tkpi-hours-count">0h 00m</div>



                            <div class="ftm-team-kpi-subtext ftm-text-muted" id="ftm-tkpi-hours-sub">Logged production time</div>



                        </div>



                    </div>







                    <div class="ftm-team-kpi-card ftm-kpi-blue">



                        <div class="ftm-team-kpi-icon-wrap bg-blue-soft text-blue">



                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>



                        </div>



                        <div class="ftm-team-kpi-content">



                            <div class="ftm-team-kpi-label">Active Projects</div>



                            <div class="ftm-team-kpi-val" id="ftm-tkpi-projects-count">0</div>



                            <div class="ftm-team-kpi-subtext" id="ftm-tkpi-projects-sub">Across all members</div>



                        </div>



                    </div>







                    <div class="ftm-team-kpi-card ftm-kpi-cyan">



                        <div class="ftm-team-kpi-icon-wrap bg-cyan-soft text-cyan">



                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>



                        </div>



                        <div class="ftm-team-kpi-content">



                            <div class="ftm-team-kpi-label">Completed Tasks</div>



                            <div class="ftm-team-kpi-val" id="ftm-tkpi-tasks-count">0</div>



                            <div class="ftm-team-kpi-subtext ftm-text-muted" id="ftm-tkpi-tasks-sub">Delivered today</div>



                        </div>



                    </div>







                    <div class="ftm-team-kpi-card ftm-kpi-rose">



                        <div class="ftm-team-kpi-icon-wrap bg-rose-soft text-rose">



                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>



                        </div>



                        <div class="ftm-team-kpi-content">



                            <div class="ftm-team-kpi-label">Blocked / Issues</div>



                            <div class="ftm-team-kpi-val text-rose" id="ftm-tkpi-issues-count">0</div>



                            <div class="ftm-team-kpi-subtext text-emerald" id="ftm-tkpi-issues-sub">All clear / No blockers</div>



                        </div>



                    </div>



                </div>







                <!-- Filter Toolbar

                <!-- Daily Attendance, Day-Off & 9:15 AM Cutoff Roster Bar -->
                <div id="ftm-daily-attendance-section" style="margin-bottom:20px; background:var(--ftm-card-bg, #fff); border:1px solid var(--ftm-border); border-radius:12px; padding:16px 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid var(--ftm-border);">
                        <div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <h3 style="margin:0; font-size:15px; font-weight:700; color:var(--ftm-text-main);">Daily Team Attendance & Roster</h3>
                                <span id="ftm-cutoff-badge" class="ftm-badge bg-blue-soft text-blue ftm-badge-sm">⏰ Daily Cutoff: 6:15 PM</span>
                            </div>
                            <div style="font-size:12px; color:var(--ftm-text-muted); margin-top:2px;">Live attendance status, daily report submission, day-offs, and manual leader/manager marking.</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-sm" id="ftm-att-kpi-present">Present: 0</span>
                            <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" id="ftm-att-kpi-working">Working: 0</span>
                            <span class="ftm-badge bg-indigo-soft text-indigo ftm-badge-sm" id="ftm-att-kpi-home-office">Home Office: 0</span>
                            <span class="ftm-badge bg-amber-soft text-amber ftm-badge-sm" id="ftm-att-kpi-leave">On Leave: 0</span>
                            <span class="ftm-badge ftm-badge-outline ftm-badge-sm" id="ftm-att-kpi-dayoff">Day-Off: 0</span>
                            <span class="ftm-badge bg-rose-soft text-rose ftm-badge-sm" id="ftm-att-kpi-late">Late / Absent: 0</span>
                        </div>
                    </div>
                    <div id="ftm-attendance-roster-list" style="display:flex; flex-direction:column; gap:8px; max-height:280px; overflow-y:auto;">
                        <!-- Roster items populated dynamically via JS -->
                    </div>
                </div>

                <!-- Filter Toolbar (Reference C / Image 3) -->



                <div class="ftm-team-filter-card">



                    <div class="ftm-team-filter-search">



                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>



                        <input type="text" id="ftm-team-filter-search" class="ftm-filter-input" placeholder="Search members or projects...">



                    </div>



                    <div class="ftm-team-filter-selects">



                        <?php if ($is_admin || $is_deputy): ?>



                        <select id="ftm-team-report-team-filter" class="ftm-select ftm-select-sm">



                            <option value="all">All Teams</option>



                            <option value="WpCraft">WpCraft</option>



                            <option value="WpTitans">WpTitans</option>



                            <option value="WpNexus">WpNexus</option>



                        </select>



                        <?php endif; ?>



                        <select id="ftm-team-filter-project" class="ftm-select ftm-select-sm">



                            <option value="all">All Projects</option>



                        </select>



                        <select id="ftm-team-filter-status" class="ftm-select ftm-select-sm">



                            <option value="all">All Statuses</option>



                            <option value="submitted">Submitted</option>



                            <option value="approved">Approved</option>



                            <option value="revision_requested">Revision Requested</option>



                            <option value="not_submitted">Not Submitted</option>



                        </select>



                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs" id="ftm-team-filter-reset">



                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>



                            <span>Reset</span>



                        </button>



                    </div>



                </div>







                <!-- 2-Column Team Workspace (Reference C / Image 3) -->



                <div class="ftm-team-workspace-grid">



                    <!-- LEFT COLUMN: Progress Table -->



                    <div class="ftm-team-table-col">



                        <div class="ftm-team-table-card">



                            <div class="ftm-table-header-flex">



                                <h3 class="ftm-card-title">



                                    <span>Team Daily Progress (<span id="ftm-team-table-date-label"><?php echo esc_html(current_time('M j, Y')); ?></span>)</span>



                                </h3>



                                <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" id="ftm-team-submission-ratio-badge">0 of 0 submitted</span>



                            </div>



                            <div class="ftm-table-responsive ftm-team-desktop-table">



                                <table class="ftm-table ftm-table-hover" id="ftm-team-progress-table">



                                    <thead>



                                        <tr>



                                            <th style="width:36px;">#</th>



                                            <th>Member</th>



                                            <th>Team</th>



                                            <th>Projects Worked</th>



                                            <th style="white-space:nowrap; text-align:center;">Work Hours</th>



                                            <th>Completed</th>



                                            <th>In Progress</th>



                                            <th>Issues</th>



                                            <th>Status</th>



                                            <th style="text-align:right;">Action</th>



                                        </tr>



                                    </thead>



                                    <tbody id="ftm-team-progress-tbody">



                                        <!-- Populated dynamically via JS -->



                                    </tbody>



                                </table>



                            </div>







                            <!-- Mobile Card System for Team Daily Progress -->



                            <div class="ftm-team-mobile-cards" id="ftm-team-mobile-cards">



                                <!-- Populated dynamically on mobile via JS -->



                            </div>



                        </div>



                    </div>







                    <!-- RIGHT COLUMN: Analytics Widgets (Donut, Status Overview, Top Contributors) -->



                    <div class="ftm-team-sidebar-col">



                        <!-- Widget 1: Work Hours Distribution Donut Chart -->



                        <div class="ftm-team-widget-card">



                            <div class="ftm-widget-header">



                                <h4 class="ftm-widget-title">Work Hours Distribution</h4>



                            </div>



                            <div class="ftm-donut-chart-container">



                                <div class="ftm-donut-svg-wrap">



                                    <svg viewBox="0 0 120 120" class="ftm-donut-svg" id="ftm-team-donut-svg">



                                        <circle cx="60" cy="60" r="48" class="ftm-donut-bg-ring"></circle>



                                        <circle cx="60" cy="60" r="48" class="ftm-donut-seg ftm-donut-seg-1" stroke="#3b82f6" stroke-dasharray="120 301" stroke-dashoffset="0"></circle>



                                        <circle cx="60" cy="60" r="48" class="ftm-donut-seg ftm-donut-seg-2" stroke="#8b5cf6" stroke-dasharray="100 301" stroke-dashoffset="-120"></circle>



                                        <circle cx="60" cy="60" r="48" class="ftm-donut-seg ftm-donut-seg-3" stroke="#f59e0b" stroke-dasharray="81 301" stroke-dashoffset="-220"></circle>



                                    </svg>



                                    <div class="ftm-donut-center-label">



                                        <strong id="ftm-donut-total-hours">0h 00m</strong>



                                        <span>Total Hours</span>



                                    </div>



                                </div>



                                <div class="ftm-donut-legend" id="ftm-team-donut-legend">



                                    <!-- Populated dynamically with teams, hours, % -->



                                </div>



                            </div>



                        </div>







                        <!-- Widget 2: Project Status Overview Horizontal Bars -->



                        <div class="ftm-team-widget-card" style="margin-top:16px;">



                            <div class="ftm-widget-header">



                                <h4 class="ftm-widget-title">Project Status Overview</h4>



                            </div>



                            <div class="ftm-status-bars-container" id="ftm-team-status-bars">



                                <div class="ftm-status-bar-row">



                                    <div class="ftm-status-bar-meta">



                                        <span class="ftm-status-bar-label">Active</span>



                                        <span class="ftm-status-bar-count" id="ftm-sbar-active-count">0</span>



                                    </div>



                                    <div class="ftm-status-progress-track">



                                        <div class="ftm-status-progress-fill bg-blue" id="ftm-sbar-active-fill" style="width:0%;"></div>



                                    </div>



                                </div>



                                <div class="ftm-status-bar-row">



                                    <div class="ftm-status-bar-meta">



                                        <span class="ftm-status-bar-label">In Progress</span>



                                        <span class="ftm-status-bar-count" id="ftm-sbar-wip-count">0</span>



                                    </div>



                                    <div class="ftm-status-progress-track">



                                        <div class="ftm-status-progress-fill bg-cyan" id="ftm-sbar-wip-fill" style="width:0%;"></div>



                                    </div>



                                </div>



                                <div class="ftm-status-bar-row">



                                    <div class="ftm-status-bar-meta">



                                        <span class="ftm-status-bar-label">Revision</span>



                                        <span class="ftm-status-bar-count" id="ftm-sbar-rev-count">0</span>



                                    </div>



                                    <div class="ftm-status-progress-track">



                                        <div class="ftm-status-progress-fill bg-purple" id="ftm-sbar-rev-fill" style="width:0%;"></div>



                                    </div>



                                </div>



                                <div class="ftm-status-bar-row">



                                    <div class="ftm-status-bar-meta">



                                        <span class="ftm-status-bar-label">Completed</span>



                                        <span class="ftm-status-bar-count" id="ftm-sbar-comp-count">0</span>



                                    </div>



                                    <div class="ftm-status-progress-track">



                                        <div class="ftm-status-progress-fill bg-emerald" id="ftm-sbar-comp-fill" style="width:0%;"></div>



                                    </div>



                                </div>



                            </div>



                        </div>







                        <!-- Widget 3: Top Contributors -->



                        <div class="ftm-team-widget-card" style="margin-top:16px;">



                            <div class="ftm-widget-header">



                                <h4 class="ftm-widget-title">Top Contributors</h4>



                                <a href="javascript:void(0)" id="ftm-link-top-contributors-all" class="ftm-widget-link">View All</a>



                            </div>



                            <div class="ftm-contributors-list" id="ftm-team-top-contributors-list">



                                <!-- Populated dynamically -->



                            </div>



                        </div>



                    </div>



                </div>



            </section>



            <?php endif; ?>

            <!-- 8B. DEDICATED ATTENDANCE, ROSTER & LEAVE MANAGEMENT TAB -->
            <section id="ftm-view-attendance" class="ftm-view-panel hidden">
                <div class="ftm-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
                    <div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <h2 class="ftm-page-title" style="margin:0; font-size:22px; font-weight:800; color:var(--ftm-text-main);">Daily Team Attendance & Leaves</h2>
                            <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm">⏰ Daily Cutoff: 9:15 AM</span>
                        </div>
                        <p class="ftm-page-subtitle" style="margin:4px 0 0; color:var(--ftm-text-muted); font-size:13px;">Daily attendance roster, automated report tracking, scheduled day-offs, and complete leave management reports.</p>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-att-btn-apply-leave" style="display:inline-flex; align-items:center; gap:6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            <span>Apply for Leave / Adjust Day-Off</span>
                        </button>
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-att-btn-export-csv" style="display:inline-flex; align-items:center; gap:6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            <span>Export CSV</span>
                        </button>
                        <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-att-btn-print" style="display:inline-flex; align-items:center; gap:6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                            <span>Print Report</span>
                        </button>
                    </div>
                </div>

                <!-- Attendance Subtab Navigation -->
                <div class="ftm-tab-nav" style="display:flex; gap:8px; border-bottom:1px solid var(--ftm-border); margin-bottom:20px; padding-bottom:10px;">
                    <button type="button" class="ftm-tab-btn active" id="ftm-att-subtab-btn-roster" style="background:none; border:none; padding:8px 16px; font-size:13.5px; font-weight:700; color:var(--ftm-text-primary); cursor:pointer; border-radius:8px;">
                        📋 Daily Attendance Roster
                    </button>
                    <button type="button" class="ftm-tab-btn" id="ftm-att-subtab-btn-leaves" style="background:none; border:none; padding:8px 16px; font-size:13.5px; font-weight:700; color:var(--ftm-text-muted); cursor:pointer; border-radius:8px;">
                        📅 Leave Applications (<span id="ftm-att-tab-leaves-count">0</span>)
                    </button>
                    <button type="button" class="ftm-tab-btn" id="ftm-att-subtab-btn-report" style="background:none; border:none; padding:8px 16px; font-size:13.5px; font-weight:700; color:var(--ftm-text-muted); cursor:pointer; border-radius:8px;">
                        📊 Leave Management Report
                    </button>
                </div>

                <!-- SUBTAB 1: DAILY ATTENDANCE ROSTER -->
                <div id="ftm-att-panel-roster">
                    <!-- KPI Overview Bar -->
                    <div class="ftm-kpi-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:14px; margin-bottom:20px;">
                        <div class="ftm-card ftm-kpi-card" style="padding:14px 16px; border-radius:12px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border);">
                            <div class="ftm-text-muted ftm-text-xs" style="font-weight:700; text-transform:uppercase;">Present (Submitted)</div>
                            <div class="ftm-kpi-value text-emerald" id="ftm-view-att-kpi-present" style="font-size:22px; font-weight:800; margin-top:4px;">0</div>
                        </div>
                        <div class="ftm-card ftm-kpi-card" style="padding:14px 16px; border-radius:12px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border);">
                            <div class="ftm-text-muted ftm-text-xs" style="font-weight:700; text-transform:uppercase;">Working (Due 6:15 PM)</div>
                            <div class="ftm-kpi-value text-blue" id="ftm-view-att-kpi-working" style="font-size:22px; font-weight:800; margin-top:4px;">0</div>
                        </div>
                        <div class="ftm-card ftm-kpi-card" style="padding:14px 16px; border-radius:12px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border);">
                            <div class="ftm-text-muted ftm-text-xs" style="font-weight:700; text-transform:uppercase;">Home Office</div>
                            <div class="ftm-kpi-value text-indigo" id="ftm-view-att-kpi-home-office" style="font-size:22px; font-weight:800; margin-top:4px;">0</div>
                        </div>
                        <div class="ftm-card ftm-kpi-card" style="padding:14px 16px; border-radius:12px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border);">
                            <div class="ftm-text-muted ftm-text-xs" style="font-weight:700; text-transform:uppercase;">On Leave</div>
                            <div class="ftm-kpi-value text-amber" id="ftm-view-att-kpi-leave" style="font-size:22px; font-weight:800; margin-top:4px;">0</div>
                        </div>
                        <div class="ftm-card ftm-kpi-card" style="padding:14px 16px; border-radius:12px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border);">
                            <div class="ftm-text-muted ftm-text-xs" style="font-weight:700; text-transform:uppercase;">Scheduled Day Off</div>
                            <div class="ftm-kpi-value" id="ftm-view-att-kpi-dayoff" style="font-size:22px; font-weight:800; margin-top:4px; color:var(--ftm-text-muted);">0</div>
                        </div>
                        <div class="ftm-card ftm-kpi-card" style="padding:14px 16px; border-radius:12px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border);">
                            <div class="ftm-text-muted ftm-text-xs" style="font-weight:700; text-transform:uppercase;">Late / Absent</div>
                            <div class="ftm-kpi-value text-rose" id="ftm-view-att-kpi-late" style="font-size:22px; font-weight:800; margin-top:4px;">0</div>
                        </div>
                    </div>

                    <!-- Date & Team Controls Toolbar -->
                    <div class="ftm-card" style="padding:12px 18px; margin-bottom:20px; border-radius:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <label for="ftm-view-att-date" style="font-size:12px; font-weight:700; color:var(--ftm-text-muted);">DATE:</label>
                                <input type="date" id="ftm-view-att-date" class="ftm-input" style="padding:6px 10px; font-size:13px; border-radius:8px;">
                            </div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <label for="ftm-view-att-team" style="font-size:12px; font-weight:700; color:var(--ftm-text-muted);">SQUAD:</label>
                                <select id="ftm-view-att-team" class="ftm-select" style="padding:6px 12px; font-size:13px; border-radius:8px;">
                                    <option value="all">All Squads</option>
                                    <option value="WpTitans">WpTitans</option>
                                    <option value="WpNexus">WpNexus</option>
                                    <option value="WpCraft">WpCraft</option>
                                </select>
                            </div>
                        </div>
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs" id="ftm-view-att-btn-refresh">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                            <span>Refresh Roster</span>
                        </button>
                    </div>

                    <!-- Live Roster Table -->
                    <div class="ftm-card" style="padding:0; border-radius:12px; overflow:hidden; margin-bottom:24px;">
                        <div style="padding:14px 18px; border-bottom:1px solid var(--ftm-border); display:flex; justify-content:space-between; align-items:center;">
                            <h4 style="margin:0; font-size:14px; font-weight:700; color:var(--ftm-text-main);">Daily Specialist Attendance Roster</h4>
                            <span class="ftm-text-xs ftm-text-muted">Leaders and Managers can adjust off-days and mark attendance overrides.</span>
                        </div>
                        <div class="ftm-table-responsive">
                            <table class="ftm-table" style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">SPECIALIST</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">SQUAD</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">WEEKLY DAY OFF</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">ATTENDANCE STATUS</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">REPORT SUBMISSION</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:right;">OVERRIDE ACTION</th>
                                    </tr>
                                </thead>
                                <tbody id="ftm-view-attendance-table-body">
                                    <!-- Populated dynamically by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SUBTAB 2: LEAVE APPLICATIONS & REVIEWS -->
                <div id="ftm-att-panel-leaves" class="hidden">
                    <div class="ftm-card" style="padding:0; border-radius:12px; overflow:hidden;">
                        <div style="padding:14px 18px; border-bottom:1px solid var(--ftm-border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                            <div>
                                <h4 style="margin:0; font-size:14px; font-weight:700; color:var(--ftm-text-main);">Leave Applications & Day-Off Adjustments</h4>
                                <span class="ftm-text-xs ftm-text-muted">Members can submit requests; Squad leaders verify, Super Admin gives final approval.</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <select id="ftm-leaves-filter-status" class="ftm-select" style="padding:5px 10px; font-size:12px; border-radius:6px;">
                                    <option value="all">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="leader_approved">Leader Verified</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                                <span class="ftm-badge ftm-badge-outline ftm-badge-xs" id="ftm-pending-leaves-count-badge">0 Pending</span>
                            </div>
                        </div>
                        <div class="ftm-table-responsive">
                            <table class="ftm-table" style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">MEMBER</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">TYPE</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">DATES / DETAILS</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">REASON</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">LEADER STATUS</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">MANAGER STATUS</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:right;">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="ftm-view-leaves-queue-body">
                                    <!-- Populated dynamically by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SUBTAB 3: LEAVE MANAGEMENT REPORT -->
                <div id="ftm-att-panel-report" class="hidden">
                    <!-- Report Filter Toolbar -->
                    <div class="ftm-card" style="padding:14px 18px; margin-bottom:20px; border-radius:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <label for="ftm-lr-filter-month" style="font-size:12px; font-weight:700; color:var(--ftm-text-muted);">MONTH:</label>
                                <input type="month" id="ftm-lr-filter-month" class="ftm-input" value="<?php echo esc_attr(date('Y-m')); ?>" style="padding:6px 10px; font-size:12.5px; border-radius:8px;">
                            </div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <label for="ftm-lr-filter-team" style="font-size:12px; font-weight:700; color:var(--ftm-text-muted);">SQUAD:</label>
                                <select id="ftm-lr-filter-team" class="ftm-select" style="padding:6px 12px; font-size:12.5px; border-radius:8px;">
                                    <option value="all">All Squads</option>
                                    <option value="WpTitans">WpTitans</option>
                                    <option value="WpNexus">WpNexus</option>
                                    <option value="WpCraft">WpCraft</option>
                                </select>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs" id="ftm-lr-btn-refresh">
                                🔄 Refresh Report
                            </button>
                        </div>
                    </div>

                    <!-- Leave KPI Summary -->
                    <div class="ftm-kpi-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:14px; margin-bottom:20px;">
                        <div class="ftm-card ftm-kpi-card" style="padding:14px 16px; border-radius:12px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border);">
                            <div class="ftm-text-muted ftm-text-xs" style="font-weight:700; text-transform:uppercase;">Leave Days (Month)</div>
                            <div class="ftm-kpi-value text-blue" id="ftm-lr-kpi-month-days" style="font-size:22px; font-weight:800; margin-top:4px;">0.0d</div>
                        </div>
                        <div class="ftm-card ftm-kpi-card" style="padding:14px 16px; border-radius:12px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border);">
                            <div class="ftm-text-muted ftm-text-xs" style="font-weight:700; text-transform:uppercase;">Leave Days (Year)</div>
                            <div class="ftm-kpi-value text-indigo" id="ftm-lr-kpi-year-days" style="font-size:22px; font-weight:800; margin-top:4px;">0.0d</div>
                        </div>
                        <div class="ftm-card ftm-kpi-card" style="padding:14px 16px; border-radius:12px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border);">
                            <div class="ftm-text-muted ftm-text-xs" style="font-weight:700; text-transform:uppercase;">Casual Leaves</div>
                            <div class="ftm-kpi-value text-emerald" id="ftm-lr-kpi-casual-days" style="font-size:22px; font-weight:800; margin-top:4px;">0.0d</div>
                        </div>
                        <div class="ftm-card ftm-kpi-card" style="padding:14px 16px; border-radius:12px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border);">
                            <div class="ftm-text-muted ftm-text-xs" style="font-weight:700; text-transform:uppercase;">Medical / Sick</div>
                            <div class="ftm-kpi-value text-amber" id="ftm-lr-kpi-sick-days" style="font-size:22px; font-weight:800; margin-top:4px;">0.0d</div>
                        </div>
                        <div class="ftm-card ftm-kpi-card" style="padding:14px 16px; border-radius:12px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border);">
                            <div class="ftm-text-muted ftm-text-xs" style="font-weight:700; text-transform:uppercase;">Pending Approvals</div>
                            <div class="ftm-kpi-value text-rose" id="ftm-lr-kpi-pending" style="font-size:22px; font-weight:800; margin-top:4px;">0</div>
                        </div>
                    </div>

                    <!-- Member Leave Balance Matrix Table -->
                    <div class="ftm-card" style="padding:0; border-radius:12px; overflow:hidden; margin-bottom:24px;">
                        <div style="padding:14px 18px; border-bottom:1px solid var(--ftm-border); display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h4 style="margin:0; font-size:14px; font-weight:700; color:var(--ftm-text-main);">Specialist Leave Quota & Remaining Balance Matrix</h4>
                                <span class="ftm-text-xs ftm-text-muted">Standard entitlement: 18.0 days / year. Tracks used casual, medical, and remaining balance.</span>
                            </div>
                        </div>
                        <div class="ftm-table-responsive">
                            <table class="ftm-table" style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">SPECIALIST</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">SQUAD</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:center;">YEAR QUOTA</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:center;">CASUAL USED</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:center;">SICK USED</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:center;">DAY-OFF SWITCHES</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:center;">TOTAL TAKEN</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:center;">REMAINING BALANCE</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:center;">UTILIZATION</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:right;">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody id="ftm-lr-matrix-table-body">
                                    <!-- Populated dynamically by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Filtered Leaves Detailed Audit History -->
                    <div class="ftm-card" style="padding:0; border-radius:12px; overflow:hidden;">
                        <div style="padding:14px 18px; border-bottom:1px solid var(--ftm-border); display:flex; justify-content:space-between; align-items:center;">
                            <h4 style="margin:0; font-size:14px; font-weight:700; color:var(--ftm-text-main);">Leave Applications & Decisions Log</h4>
                            <span class="ftm-text-xs ftm-text-muted" id="ftm-lr-records-count">0 Records</span>
                        </div>
                        <div class="ftm-table-responsive">
                            <table class="ftm-table" style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">MEMBER</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">TYPE</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">DATE RANGE & DURATION</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">REASON</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">LEADER DECISION</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:left;">MANAGER DECISION</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:center;">STATUS</th>
                                        <th style="padding:10px 16px; font-size:11.5px; text-align:right;">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="ftm-lr-records-table-body">
                                    <!-- Populated dynamically by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>







            <!-- 5. MONTHLY REPORTS (GOOGLE SHEET REPLACEMENT & VISUAL ANALYTICS) -->



            <section id="ftm-view-monthly-reports" class="ftm-view-panel hidden">



                <div class="ftm-table-card">



                    <div class="ftm-table-header ftm-monthly-header">



                        <div>



                            <div class="ftm-table-title" style="display:flex; align-items:center; gap:8px;">



                                <span>Monthly Performance & Financial Reconciliation</span>



                                <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" id="ftm-monthly-badge-mode">Live Sync</span>



                            </div>



                            <div class="ftm-text-muted ftm-text-sm">Automated live calculation replacing manual Google Sheets with executive visual KPI drivers</div>



                        </div>



                        <div class="ftm-monthly-header-actions">



                            <select id="ftm-monthly-select-month" class="ftm-select ftm-select-sm">
                                <?php
                                if (class_exists('FTM_Monthly_Report_Controller')) {
                                    $dyn_months = FTM_Monthly_Report_Controller::get_dynamic_reporting_months();
                                    foreach ($dyn_months as $dm) {
                                        $sel = $dm['is_current'] ? 'selected="selected"' : '';
                                        echo '<option value="' . esc_attr($dm['key']) . '" ' . $sel . '>' . esc_html($dm['label']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="' . esc_attr(current_time('Y-m')) . '" selected="selected">Current Month</option>';
                                }
                                ?>
                            </select>



                            <?php if ($is_admin && !$is_deputy): ?>



                            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-open-import-csv">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>



                                <span>Import Past Sheet</span>



                            </button>



                            <?php endif; ?>



                            <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-export-monthly-csv">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>



                                <span>Export CSV</span>



                            </button>



                            <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-btn-print-monthly" title="Print Executive Monthly Financial & Performance Report">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>



                                <span>Print Report</span>



                            </button>



                        </div>



                    </div>







                    <!-- Top Performer Spotlight Podium -->



                    <div id="ftm-monthly-spotlight-wrap" class="ftm-monthly-spotlight-container"></div>







                    <!-- Visual KPI Driver Graphs & Executive Analytics Suite -->



                    <div id="ftm-monthly-visual-drivers" class="ftm-monthly-drivers-grid"></div>







                    <!-- Team Performance Highlights (Compact Executive Summary) -->



                    <div id="ftm-monthly-highlights-section" class="ftm-monthly-highlights-card">



                        <div class="ftm-highlights-header">



                            <div class="ftm-highlights-title-flex">



                                <span class="ftm-highlights-badge-icon">



                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="#f59e0b" stroke="#d97706" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>



                                </span>



                                <div>



                                    <h3 class="ftm-highlights-title">Team Performance Highlights</h3>



                                    <span class="ftm-highlights-subtitle">Key operational benchmarks for the selected reporting period</span>



                                </div>



                            </div>



                            <button type="button" class="ftm-btn ftm-btn-sm ftm-btn-outline ftm-btn-goto-leaderboard" id="ftm-btn-view-full-leaderboard">



                                <span>View Full Leaderboard</span>



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>



                            </button>



                        </div>



                        <div class="ftm-highlights-grid" id="ftm-monthly-highlights-grid">



                            <!-- Populated dynamically via JS -->



                        </div>



                    </div>







                    <!-- Executive Summary KPI Cards -->



                    <div class="ftm-monthly-summary-bar" id="ftm-monthly-summary-kpis">



                        <!-- Summary KPIs calculated dynamically -->



                    </div>







                    <!-- Interactive Team Filter Tabs & Member Instant Search -->



                    <div class="ftm-monthly-filter-toolbar">



                        <div class="ftm-team-filter-tabs" id="ftm-monthly-team-tabs">



                            <!-- Populated dynamically: All Teams, WpCraft, WpTitans, WpNexus, WpHorizon -->



                        </div>



                        <div class="ftm-search-input-wrap ftm-monthly-table-search">



                            <svg class="ftm-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>



                            <input type="text" id="ftm-monthly-search-input" class="ftm-input ftm-input-sm" placeholder="Search by name, ID, or team...">



                        </div>



                    </div>







                    <!-- Desktop Spreadsheet Table -->



                    <div class="ftm-table-responsive ftm-monthly-desktop-table">



                        <table class="ftm-table ftm-table-condensed" id="ftm-monthly-table">



                            <thead>



                                <tr>



                                    <th># ID</th>
                                    <th>Specialist</th>
                                    <th>Squad</th>
                                    <th>Delivery (Net)</th>
                                    <th>Active WIP</th>
                                    <th>Tips</th>
                                    <th>Cancelled</th>
                                    <th>Partial</th>
                                    <th>Completed</th>
                                    <th>Repeat Orders</th>
                                    <th>5★ Reviews</th>
                                    <th>4★ Reviews</th>
                                    <th>Cancellations</th>
                                    <th>On-Time</th>



                                </tr>



                            </thead>



                            <tbody id="ftm-monthly-tbody">



                                <tr><td colspan="14" class="ftm-table-loading"><div class="ftm-spinner"></div> Compiling monthly sheet...</td></tr>



                            </tbody>



                            <tfoot id="ftm-monthly-tfoot"></tfoot>



                        </table>



                    </div>







                    <!-- Mobile Financial Member Cards (Responsive Native App Cards) -->



                    <div class="ftm-monthly-mobile-cards" id="ftm-monthly-mobile-cards">



                        <!-- Populated dynamically on mobile -->



                    </div>







                    <!-- Certified Executive Audit Banner (High Visibility Official Statement) -->



                    <div class="ftm-certified-audit-strip">



                        <div class="ftm-audit-strip-left">



                            <div class="ftm-audit-badge-pill">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>



                                <span>OFFICIAL AUDIT</span>



                            </div>



                            <div class="ftm-audit-strip-text">



                                <div class="ftm-audit-main-title"><strong>Confidential</strong> &bull; Agency FB International Management & Leadership Review.</div>



                                <div class="ftm-audit-sub-title">Certified Financial Reconciliation &bull; Page 1 of 1 &bull; Authorized by Agency Operations & Finance Board</div>



                            </div>



                        </div>



                        <div class="ftm-audit-strip-right">



                            <span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-sm" style="font-weight:700; letter-spacing:0.04em;">



                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline-block; margin-right:3px; vertical-align:-1px;"><polyline points="20 6 9 17 4 12"></polyline></svg>



                                RECONCILIATION VERIFIED



                            </span>



                        </div>



                    </div>



                </div>



            </section>







            <!-- 5B. DEDICATED PERFORMANCE LEADERBOARD VIEW -->



            <section id="ftm-view-leaderboard" class="ftm-view-panel hidden">



                <div class="ftm-leaderboard-container">



                    <div id="ftm-performance-leaderboard-section" class="ftm-leaderboard-card">



                        <!-- Section Header -->



                        <div class="ftm-lb-header">



                            <div class="ftm-lb-title-wrap">



                                <div class="ftm-lb-icon-title">



                                    <span class="ftm-lb-crown-icon" aria-hidden="true">



                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="#f59e0b" stroke="#d97706" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>



                                    </span>



                                    <h2 class="ftm-lb-title">Performance Leaderboard</h2>



                                </div>



                                <p class="ftm-lb-subtitle">Recognizing consistent operational excellence across the team with transparent, data-driven rankings.</p>



                            </div>



                            <div class="ftm-lb-header-actions">



                                <button type="button" class="ftm-lb-methodology-btn" id="ftm-lb-btn-methodology" title="View Scoring Formula & Rules">



                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>



                                    <span>View Methodology</span>



                                </button>



                            </div>



                        </div>







                        <!-- Period Switcher Toolbar & Context Badge -->



                        <div class="ftm-lb-period-toolbar">



                            <div class="ftm-lb-period-tabs" role="tablist">



                                <button type="button" class="ftm-lb-tab active" data-period="monthly" role="tab" id="ftm-lb-tab-monthly">



                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>



                                    <span class="ftm-tab-label-desktop">Monthly Leaders</span>



                                    <span class="ftm-tab-label-mobile">Monthly</span>



                                </button>



                                <button type="button" class="ftm-lb-tab" data-period="quarterly" role="tab" id="ftm-lb-tab-quarterly">



                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>



                                    <span class="ftm-tab-label-desktop">Quarterly Excellence</span>



                                    <span class="ftm-tab-label-mobile">Quarterly</span>



                                </button>



                                <button type="button" class="ftm-lb-tab" data-period="all_time" role="tab" id="ftm-lb-tab-alltime">



                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2"></path><path d="M18 9h2a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-2"></path><path d="M6 3h12v7a6 6 0 0 1-12 0V3z"></path><path d="M12 17v4"></path><path d="M8 21h8"></path></svg>



                                    <span class="ftm-tab-label-desktop">All-Time Rankings</span>



                                    <span class="ftm-tab-label-mobile">All-Time</span>



                                </button>



                            </div>







                            <div class="ftm-lb-period-context">



                                <span class="ftm-lb-period-pill hidden" id="ftm-lb-period-badge">



                                    <span id="ftm-lb-period-text">October 2026</span>



                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>



                                </span>



                                <select id="ftm-lb-month-select" class="ftm-select ftm-select-sm ftm-lb-dropdown">



                                    <?php foreach (ftm_live_months() as $lm) : ?>
                                    <option value="<?php echo esc_attr($lm['key']); ?>"><?php echo esc_html($lm['label'] . ($lm['current'] ? ' (Live)' : '')); ?></option>
                                    <?php endforeach; ?>



                                    <option value="September_2026">September 2026</option>



                                    <option value="August_2026">August 2026</option>



                                    <option value="July_2026">July 2026</option>



                                    <option value="June_2026">June 2026</option>



                                    <option value="May_2026">May 2026</option>



                                    <option value="April_2026">April 2026</option>



                                    <option value="March_2026">March 2026</option>



                                    <option value="February_2026">February 2026</option>



                                    <option value="January_2026">January 2026</option>



                                </select>



                                <select id="ftm-lb-quarter-select" class="ftm-select ftm-select-sm ftm-lb-dropdown hidden">



                                    <option value="Q4_2026">Q4 2026 (Oct - Dec)</option>



                                    <option value="Q3_2026" selected>Q3 2026 (Jul - Sep)</option>



                                    <option value="Q2_2026">Q2 2026 (Apr - Jun)</option>



                                    <option value="Q1_2026">Q1 2026 (Jan - Mar)</option>



                                </select>



                            </div>



                        </div>







                        <!-- Top 3 Performers Podium Grid / Mobile Auto-Slider -->



                        <div class="ftm-lb-podium-container">



                            <div id="ftm-leaderboard-podium" class="ftm-lb-podium-grid">



                                <!-- Populated dynamically via JS -->



                            </div>



                            <div id="ftm-lb-slider-dots" class="ftm-lb-slider-dots hidden" role="tablist" aria-label="Top Performers Slider"></div>



                        </div>







                        <!-- Filter Toolbar: Team Pills, Search Input, Sort Dropdown -->



                        <div class="ftm-lb-filter-toolbar">



                            <div class="ftm-lb-team-pills" id="ftm-leaderboard-team-pills">



                                <button type="button" class="ftm-lb-pill active" data-team="all">All Teams <span class="ftm-pill-count" id="ftm-lb-count-all">13</span></button>



                                <button type="button" class="ftm-lb-pill" data-team="WpTitans">WpTitans <span class="ftm-pill-count" id="ftm-lb-count-wptitans">4</span></button>



                                <button type="button" class="ftm-lb-pill" data-team="WpNexus">WpNexus <span class="ftm-pill-count" id="ftm-lb-count-wpnexus">5</span></button>



                                <button type="button" class="ftm-lb-pill" data-team="WpCraft">WpCraft <span class="ftm-pill-count" id="ftm-lb-count-wpcraft">4</span></button>



                            </div>







                            <div class="ftm-lb-filter-right">



                                <div class="ftm-search-input-wrap ftm-lb-search-wrap">



                                    <svg class="ftm-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>



                                    <input type="text" id="ftm-leaderboard-search" class="ftm-input ftm-input-sm" placeholder="Search by name, ID, or team...">



                                </div>







                                <div class="ftm-lb-sort-wrap">



                                    <select id="ftm-leaderboard-sort" class="ftm-select ftm-select-sm">



                                        <option value="score" selected>Sort: Performance Score</option>



                                        <option value="revenue">Sort: Delivered Revenue</option>



                                        <option value="completed">Sort: Completed Orders</option>



                                        <option value="ontime">Sort: On-Time Delivery</option>



                                        <option value="rating">Sort: Client Rating</option>



                                        <option value="cancel">Sort: Cancellation Rate</option>



                                    </select>



                                </div>



                            </div>



                        </div>







                        <!-- Full Leaderboard Table (Desktop) -->



                        <div class="ftm-table-responsive ftm-lb-table-wrap">



                            <table class="ftm-table ftm-table-hover ftm-lb-table" id="ftm-leaderboard-table">



                                <thead>



                                    <tr>



                                        <th style="width: 48px; text-align: center;">#</th>



                                        <th>Member</th>



                                        <th>Team</th>



                                        <th style="text-align: right;">Delivered Revenue</th>



                                        <th style="text-align: center;">Completed Orders</th>



                                        <th style="text-align: center;">On-Time Delivery</th>



                                        <th style="text-align: center;">Client Rating</th>



                                        <th style="text-align: center;">Cancellation</th>



                                        <th style="min-width: 170px;">Performance Score</th>



                                        <th style="width: 100px; text-align: center;">Actions</th>



                                    </tr>



                                </thead>



                                <tbody id="ftm-leaderboard-tbody">



                                    <!-- Populated dynamically via JS -->



                                </tbody>



                            </table>



                        </div>







                        <!-- Full Leaderboard Mobile Cards Container (Visible on mobile <= 768px) -->



                        <div id="ftm-leaderboard-mobile-cards" class="ftm-lb-mobile-cards">



                            <!-- Populated dynamically via JS -->



                        </div>



                    </div>



                </div>



            </section>







            <!-- 6. TEAM MEMBERS DIRECTORY VIEW -->



            <section id="ftm-view-team-members" class="ftm-view-panel hidden">



                <div class="ftm-table-card">



                    <div class="ftm-table-header">



                        <div>



                            <div class="ftm-table-title">Agency Team Members</div>



                            <div class="ftm-text-muted ftm-text-sm">Team directory, workload distribution, and individual performance</div>



                        </div>



                        <div class="ftm-flex-gap" style="align-items:center; flex-wrap:wrap;">



                            <?php if ($is_admin || $is_deputy): ?>



                            <select id="ftm-members-team-filter" class="ftm-select ftm-select-sm">



                                <option value="all">All Teams</option>



                                <option value="WpCraft">WpCraft</option>



                                <option value="WpNexus">WpNexus</option>



                                <option value="WpTitans">WpTitans</option>
                                <option value="Management">Management</option>






                            </select>

                            <?php if (!$is_deputy): ?>
                            <label style="display:inline-flex; align-items:center; gap:6px; font-size:12px; color:var(--ftm-text-muted); cursor:pointer;">
                                <input type="checkbox" id="ftm-members-show-inactive">
                                <span>Include Inactive</span>
                            </label>
                            <button type="button" id="ftm-btn-add-member" class="ftm-btn ftm-btn-primary ftm-btn-sm">
                                + Add Member
                            </button>
                            <button type="button" id="ftm-btn-open-isolation-vault" class="ftm-btn ftm-btn-secondary ftm-btn-sm" style="display:inline-flex; align-items:center; gap:6px; border-color:rgba(245,158,11,0.4); color:#f59e0b;">
                                <span>🗄️ Isolation Vault</span>
                                <span class="ftm-badge ftm-badge-amber ftm-badge-xs" id="ftm-isolation-vault-badge" style="background:rgba(245,158,11,0.2); color:#fbbf24; border:1px solid rgba(245,158,11,0.3); border-radius:10px; padding:1px 6px; font-size:11px;">24</span>
                            </button>
                            <?php endif; ?>



                            <?php endif; ?>



                        </div>



                    </div>



                    <div id="ftm-team-members-container" class="ftm-members-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:20px; padding:20px;">



                        <!-- Populated dynamically via JS -->



                    </div>



                </div>



            </section>







            <!-- 7. FIVERR PROFILES VIEW -->



            <section id="ftm-view-fiverr-profiles" class="ftm-view-panel hidden">



                <div class="ftm-table-card">



                    <div class="ftm-table-header ftm-profiles-toolbar">



                        <div class="ftm-profiles-header-info">



                            <div class="ftm-table-title" style="display:flex; align-items:center; gap:8px;">



                                <span>Agency Profiles</span>



                                <span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-sm" id="ftm-profiles-count-badge">0 Active</span>



                            </div>



                            <div class="ftm-text-muted ftm-text-sm">Connected seller profiles, order distribution, and performance tracking</div>



                        </div>



                        <div class="ftm-profiles-header-actions">



                            <div class="ftm-search-input-wrap ftm-profiles-search-wrap">



                                <svg class="ftm-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>



                                <input type="text" id="ftm-profiles-search-input" class="ftm-input ftm-input-sm" placeholder="Filter profiles...">



                            </div>



                            <?php if ($is_leader && !$is_deputy): ?>



                            <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm ftm-open-modal-add-profile">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>



                                <span>Add Profile</span>



                            </button>



                            <?php endif; ?>



                        </div>



                    </div>



                    <div id="ftm-profiles-container" class="ftm-profiles-grid">



                        <!-- Populated dynamically via JS -->



                    </div>



                </div>



            </section>







            <!-- 8. SETTINGS VIEW -->



            <section id="ftm-view-settings" class="ftm-view-panel hidden">



                <div class="ftm-settings-container-grid">



                    <!-- Profile Card -->



                    <div class="ftm-settings-box">



                        <div class="ftm-settings-box-header">



                            <div class="ftm-box-icon bg-blue-soft text-blue">



                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>



                            </div>



                            <div>



                                <h3 class="ftm-settings-box-title">Profile Settings</h3>



                                <p class="ftm-settings-box-sub">Update your display name and choose a custom avatar</p>



                            </div>



                        </div>







                        <form id="ftm-user-profile-settings-form" class="ftm-settings-form">



                            <div class="ftm-avatar-upload-section">



                                <div class="ftm-avatar-preview-wrap">



                                    <img id="ftm-settings-avatar-preview" src="<?php echo esc_url(get_avatar_url($current_user_id, ['size' => 96])); ?>" class="ftm-settings-avatar-img" alt="">



                                    <label for="ftm-avatar-file-input" class="ftm-avatar-edit-badge" title="Upload new photo">



                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>



                                    </label>



                                </div>



                                <div class="ftm-avatar-upload-info">



                                    <span class="ftm-font-semibold ftm-text-sm">Profile Avatar</span>



                                    <div class="ftm-avatar-btn-row">



                                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs" id="ftm-btn-trigger-upload">



                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>



                                            <span>Upload File</span>



                                        </button>



                                        <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-xs" id="ftm-btn-remove-avatar">Reset</button>



                                    </div>



                                    <span class="ftm-avatar-hint">JPG, PNG, or WebP. Max 5MB.</span>



                                    <input type="file" id="ftm-avatar-file-input" accept="image/*" class="hidden">



                                    <input type="hidden" id="ftm-avatar-url-input" value="">



                                </div>



                            </div>







                            <!-- Avatar Presets -->



                            <div class="ftm-avatar-presets-wrap">



                                <span class="ftm-label ftm-text-xs" style="margin-bottom:8px; display:block;">Or choose a fun preset avatar:</span>



                                <div class="ftm-avatar-presets-list">



                                    <?php



                                    $presets = [



                                        'https://api.dicebear.com/7.x/bottts/svg?seed=Alpha',



                                        'https://api.dicebear.com/7.x/bottts/svg?seed=Beta',



                                        'https://api.dicebear.com/7.x/bottts/svg?seed=Gamma',



                                        'https://api.dicebear.com/7.x/bottts/svg?seed=Delta',



                                        'https://api.dicebear.com/7.x/bottts/svg?seed=Omega',



                                        'https://api.dicebear.com/7.x/avataaars/svg?seed=Felix',



                                        'https://api.dicebear.com/7.x/avataaars/svg?seed=Aneka',



                                        'https://api.dicebear.com/7.x/avataaars/svg?seed=Zack'



                                    ];



                                    foreach ($presets as $p_url):



                                    ?>



                                    <button type="button" class="ftm-preset-avatar-btn" data-avatar="<?php echo esc_url($p_url); ?>">



                                        <img src="<?php echo esc_url($p_url); ?>" alt="">



                                    </button>



                                    <?php endforeach; ?>



                                </div>



                            </div>







                            <div class="ftm-form-group">



                                <label class="ftm-label" for="ftm-setting-display-name">Display Name *</label>



                                <input type="text" id="ftm-setting-display-name" class="ftm-input" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" required>



                            </div>







                            <div class="ftm-form-group">



                                <label class="ftm-label" for="ftm-setting-email">Official Email Address *</label>



                                <input type="email" id="ftm-setting-email" class="ftm-input" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" required>



                                <span class="ftm-text-muted ftm-text-xs" style="margin-top:4px; display:block;">Primary email for system alerts, operational reviews, and agency access.</span>



                            </div>







                            <div class="ftm-form-group">



                                <label class="ftm-label">Assigned Team & Role</label>



                                <input type="text" class="ftm-input ftm-readonly-input" value="<?php echo esc_attr($team); ?> (<?php echo esc_attr(FTM_Permissions::get_role_label($role)); ?>)" readonly>



                            </div>







                            <div id="ftm-profile-feedback" class="ftm-form-feedback hidden"></div>







                            <div class="ftm-settings-action-row">



                                <button type="submit" class="ftm-btn ftm-btn-primary" id="ftm-btn-save-profile">



                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>



                                    <span>Save Profile Changes</span>



                                </button>



                            </div>



                        </form>



                    </div>







                    <!-- Password Security Card -->



                    <div class="ftm-settings-box">



                        <div class="ftm-settings-box-header">



                            <div class="ftm-box-icon bg-emerald-soft text-emerald">



                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>



                            </div>



                            <div>



                                <h3 class="ftm-settings-box-title">Security & Password</h3>



                                <p class="ftm-settings-box-sub">Change your account password securely</p>



                            </div>



                        </div>







                        <form id="ftm-password-form" class="ftm-settings-form">



                            <div class="ftm-form-group">



                                <label class="ftm-label" for="ftm-old-password">Current Password</label>



                                <div style="position:relative; width:100%;">



                                    <input type="password" id="ftm-old-password" class="ftm-input" style="padding-right:42px; width:100%; box-sizing:border-box;" placeholder="••••••••" required>



                                    <button type="button" class="ftm-toggle-pwd-btn ftm-pwd-eye-toggle" data-target="ftm-old-password" aria-label="Toggle password visibility" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--ftm-text-muted); cursor:pointer; padding:6px; display:flex; align-items:center;">



                                        <svg class="ftm-eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>



                                        <svg class="ftm-eye-closed hidden" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>



                                    </button>



                                </div>



                            </div>







                            <div class="ftm-form-group">



                                <label class="ftm-label" for="ftm-new-password">New Password</label>



                                <div style="position:relative; width:100%;">



                                    <input type="password" id="ftm-new-password" class="ftm-input" style="padding-right:42px; width:100%; box-sizing:border-box;" placeholder="••••••••" minlength="6" required>



                                    <button type="button" class="ftm-toggle-pwd-btn ftm-pwd-eye-toggle" data-target="ftm-new-password" aria-label="Toggle password visibility" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--ftm-text-muted); cursor:pointer; padding:6px; display:flex; align-items:center;">



                                        <svg class="ftm-eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>



                                        <svg class="ftm-eye-closed hidden" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>



                                    </button>



                                </div>



                            </div>







                            <div class="ftm-form-group">



                                <label class="ftm-label" for="ftm-confirm-password">Confirm New Password</label>



                                <div style="position:relative; width:100%;">



                                    <input type="password" id="ftm-confirm-password" class="ftm-input" style="padding-right:42px; width:100%; box-sizing:border-box;" placeholder="••••••••" minlength="6" required>



                                    <button type="button" class="ftm-toggle-pwd-btn ftm-pwd-eye-toggle" data-target="ftm-confirm-password" aria-label="Toggle password visibility" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--ftm-text-muted); cursor:pointer; padding:6px; display:flex; align-items:center;">



                                        <svg class="ftm-eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>



                                        <svg class="ftm-eye-closed hidden" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>



                                    </button>



                                </div>



                            </div>







                            <div id="ftm-password-feedback" class="ftm-form-feedback hidden"></div>







                            <div class="ftm-settings-action-row">



                                <button type="submit" class="ftm-btn ftm-btn-primary" id="ftm-btn-update-password">



                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>



                                    <span>Update Password</span>



                                </button>



                            </div>



                        </form>



                    </div>



                </div>



            </section>







            <!-- 9. MEMBER PROFILE & PORTFOLIO VIEW (Reference A Design Direction) -->



            <section id="ftm-view-member-profile" class="ftm-view-panel hidden">



                <div class="ftm-refa-breadcrumb">



                    <a href="#team-members" class="ftm-refa-bc-link ftm-nav-trigger" data-target="team-members">Team Members</a>



                    <span class="ftm-refa-bc-sep">/</span>



                    <span class="ftm-refa-bc-active" id="ftm-profile-bc-name">Member Profile</span>



                </div>







                <!-- Hero Profile Card -->
                <div class="ftm-refa-hero-card">
                    <div class="ftm-refa-hero-main">
                        <div class="ftm-refa-avatar-wrap">
                            <img src="" id="ftm-profile-hero-avatar" class="ftm-refa-hero-avatar" alt="">
                            <div class="ftm-refa-online-badge">
                                <span class="ftm-refa-online-dot"></span>
                                <span>Online</span>
                            </div>
                        </div>
                        <div class="ftm-refa-hero-info">
                            <div class="ftm-refa-name-row">
                                <h1 class="ftm-refa-name" id="ftm-profile-hero-name">Loading Profile...</h1>
                                <span class="ftm-refa-id-badge" id="ftm-profile-hero-id">#1086</span>
                            </div>
                            <!-- Subline: Professional Role and Team -->
                            <div class="ftm-refa-hero-subline" id="ftm-profile-hero-subline">
                                <span class="ftm-refa-hero-sub-role">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    <span id="ftm-profile-hero-role-text">WordPress Developer</span>
                                </span>
                                <span id="ftm-profile-hero-leader-badge"></span>
                                <span class="ftm-refa-hero-sep">|</span>
                                <span class="ftm-refa-hero-sub-team">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    <span id="ftm-profile-hero-team-text">WpCraft Team</span>
                                </span>
                            </div>
                            <!-- Tech tags with icons -->
                            <div class="ftm-refa-tech-badges" id="ftm-profile-hero-tags">
                                <span class="ftm-badge ftm-badge-outline ftm-badge-sm">WordPress</span>
                                <span class="ftm-badge ftm-badge-outline ftm-badge-sm">WooCommerce</span>
                                <span class="ftm-badge ftm-badge-outline ftm-badge-sm">Elementor</span>
                                <span class="ftm-badge ftm-badge-outline ftm-badge-sm">PHP</span>
                                <span class="ftm-badge ftm-badge-outline ftm-badge-sm">+2</span>
                            </div>
                            <p class="ftm-refa-bio" id="ftm-profile-hero-bio">
                                WordPress developer with strong experience in custom theme development, plugin customization and eCommerce projects. Focused on clean code, performance and client satisfaction.
                            </p>
                        </div>
                    </div>
                    <div class="ftm-refa-hero-aside">
                        <div class="ftm-refa-hero-actions">
                            <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-profile-btn-edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                <span>Edit Profile</span>
                            </button>
                            <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-sm" id="ftm-profile-btn-view-projects">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                <span>View Assigned Projects</span>
                            </button>
                        </div>
                        <div class="ftm-refa-meta-box">
                            <div class="ftm-refa-meta-box-row">
                                <span class="ftm-refa-meta-label">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    Joined
                                </span>
                                <span class="ftm-refa-meta-val" id="ftm-profile-meta-joined">12 Jan 2024</span>
                            </div>
                            <div class="ftm-refa-meta-box-row" id="ftm-profile-meta-birthday-row">
                                <span class="ftm-refa-meta-label">
                                    <span style="font-size:12px;">🎂</span>
                                    Birthday
                                </span>
                                <span class="ftm-refa-meta-val" id="ftm-profile-meta-birthday">&mdash;</span>
                            </div>
                            <div class="ftm-refa-meta-box-row">
                                <span class="ftm-refa-meta-label">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    Role
                                </span>
                                <span class="ftm-refa-meta-val" id="ftm-profile-meta-role">WordPress Developer</span>
                            </div>
                            <div class="ftm-refa-meta-box-row">
                                <span class="ftm-refa-meta-label">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    Team
                                </span>
                                <span class="ftm-refa-meta-val" id="ftm-profile-meta-team">WpCraft</span>
                            </div>
                            <div class="ftm-refa-meta-box-row">
                                <span class="ftm-refa-meta-label">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                                    Status
                                </span>
                                <span class="ftm-refa-meta-val text-emerald" id="ftm-profile-meta-status"><span class="ftm-refa-online-dot"></span> Active</span>
                            </div>
                            <div class="ftm-refa-meta-box-row">
                                <span class="ftm-refa-meta-label">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                    Email
                                </span>
                                <span class="ftm-refa-meta-val ftm-refa-meta-email-text" id="ftm-profile-meta-email">saidur@agencyfbinternational.com</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 7 Navigation Tabs -->
                <div class="ftm-refa-tabs-bar">
                    <button type="button" class="ftm-refa-tab active" data-tab="overview">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        Overview
                    </button>
                    <button type="button" class="ftm-refa-tab" data-tab="projects">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                        Projects
                    </button>
                    <button type="button" class="ftm-refa-tab" data-tab="calendar">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        Calendar
                    </button>
                    <button type="button" class="ftm-refa-tab" data-tab="journey">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
                        Journey
                    </button>
                    <button type="button" class="ftm-refa-tab" data-tab="activity">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        Activity
                    </button>
                    <button type="button" class="ftm-refa-tab" data-tab="skills">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                        Skills
                    </button>
                    <button type="button" class="ftm-refa-tab" data-tab="achievements">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                        Achievements
                    </button>
                    <button type="button" class="ftm-refa-tab" data-tab="extra-time">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        ⚡ Extra Time (Overtime)
                    </button>
                    <button type="button" class="ftm-refa-tab" data-tab="team-insights">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        Team Insights
                    </button>
                </div>

                <!-- 5 Metric Cards -->
                <div class="ftm-refa-kpi-row">
                    <div class="ftm-refa-kpi-card">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-blue">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">Total Projects</div>
                            <div class="ftm-refa-kpi-value" id="ftm-profile-kpi-total">0</div>
                            <div class="ftm-refa-kpi-sub text-emerald" id="ftm-profile-kpi-total-sub">All-time portfolio</div>
                        </div>
                    </div>
                    <div class="ftm-refa-kpi-card">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-emerald">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">Completed</div>
                            <div class="ftm-refa-kpi-value text-emerald" id="ftm-profile-kpi-completed">0</div>
                            <div class="ftm-refa-kpi-sub" id="ftm-profile-kpi-completed-sub">100% completion rate</div>
                        </div>
                    </div>
                    <div class="ftm-refa-kpi-card">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-amber">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">In Progress</div>
                            <div class="ftm-refa-kpi-value text-blue" id="ftm-profile-kpi-inprogress">0</div>
                            <div class="ftm-refa-kpi-sub" id="ftm-profile-kpi-inprogress-sub">No active projects</div>
                        </div>
                    </div>
                    <div class="ftm-refa-kpi-card">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-indigo">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">Total Net Value</div>
                            <div class="ftm-refa-kpi-value text-emerald" id="ftm-profile-kpi-value">$0</div>
                            <div class="ftm-refa-kpi-sub">Lifetime earnings</div>
                        </div>
                    </div>
                    <div class="ftm-refa-kpi-card ftm-refa-kpi-card-rating">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-gold">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">Client Rating</div>
                            <div class="ftm-refa-kpi-value text-gold" id="ftm-profile-kpi-rating">⭐ 5.0</div>
                            <div class="ftm-refa-kpi-sub" id="ftm-profile-kpi-reviews">0 reviews</div>
                        </div>
                    </div>
                </div>

                <div id="ftm-profile-managed-scope" class="ftm-managed-scope hidden"></div>
                <!-- Overview Tab Two-Column Body -->
                <div class="ftm-refa-two-column-layout">
                    <!-- Left Column (Main Area) -->
                    <div class="ftm-refa-col-left">
                        <!-- Charts Row: Projects Breakdown & Monthly Activity side-by-side -->
                        <div class="ftm-refa-charts-row">
                            <!-- 1. Projects Breakdown (Donut Chart) -->
                            <div class="ftm-refa-card ftm-refa-chart-card">
                                <div class="ftm-refa-card-title">Project Status Distribution</div>
                                <div class="ftm-refa-donut-layout">
                                    <div class="ftm-refa-donut-chart-wrap" id="ftm-profile-donut-wrap">
                                        <!-- Dynamic SVG Donut Chart -->
                                    </div>
                                    <div class="ftm-refa-donut-legend" id="ftm-profile-donut-legend">
                                        <!-- Dynamic Donut Legend -->
                                    </div>
                                </div>
                            </div>
                            <!-- 2. Monthly Activity (Bar Chart) -->
                            <div class="ftm-refa-card ftm-refa-chart-card">
                                <div class="ftm-flex-between" style="margin-bottom:12px;">
                                    <div class="ftm-refa-card-title" style="margin:0;">Monthly Project Activity</div>
                                    <span class="ftm-text-xs ftm-text-muted">Last 6 months</span>
                                </div>
                                <div class="ftm-refa-barchart" id="ftm-profile-activity-chart">
                                    <!-- Dynamic 6-Month Bar Chart -->
                                </div>
                            </div>
                        </div>

                        <!-- Professional Journey Timeline -->
                        <div class="ftm-refa-card" style="margin-top:20px;">
                            <div class="ftm-flex-between" style="margin-bottom:14px;">
                                <div class="ftm-refa-card-title" style="margin:0;">Professional Journey</div>
                                <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-xs" id="ftm-btn-add-milestone">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    <span>Add Milestone</span>
                                </button>
                            </div>
                            <div class="ftm-refa-timeline" id="ftm-profile-journey-timeline">
                                <!-- Dynamic Milestone Timeline -->
                            </div>
                        </div>

                        <!-- Recent Work Activity Feed -->
                        <div class="ftm-refa-card" style="margin-top:20px;">
                            <div class="ftm-flex-between" style="margin-bottom:14px;">
                                <div class="ftm-refa-card-title" style="margin:0;">Recent Work Activity</div>
                                <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-xs" id="ftm-profile-btn-view-all-activity">
                                    <span>View All</span>
                                </button>
                            </div>
                            <div class="ftm-refa-activity-feed" id="ftm-profile-recent-feed">
                                <!-- Dynamic Recent Feed -->
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (Sidebar) -->
                    <div class="ftm-refa-col-right">
                        <!-- Skills & Expertise -->
                        <div class="ftm-refa-card">
                            <div class="ftm-flex-between" style="margin-bottom:14px;">
                                <div class="ftm-refa-card-title" style="margin:0;">Skills & Expertise</div>
                                <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-xs" id="ftm-profile-btn-edit-skills">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    <span>Edit</span>
                                </button>
                            </div>
                            <div class="ftm-refa-skills-cloud" id="ftm-profile-skills-cloud">
                                <!-- Dynamic Skill Badges -->
                            </div>
                        </div>

                        <!-- Recent Achievements -->
                        <div class="ftm-refa-card" style="margin-top:20px;">
                            <div class="ftm-flex-between" style="margin-bottom:14px;">
                                <div class="ftm-refa-card-title" style="margin:0;">Recent Achievements</div>
                                <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-xs" id="ftm-profile-btn-add-achievement">
                                    <span>+ Add</span>
                                </button>
                            </div>
                            <div class="ftm-refa-achievements-list" id="ftm-profile-achievements-list">
                                <!-- Dynamic Achievements -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Member Projects Section (Tab 2: Projects) -->



                <div id="ftm-profile-projects-section" class="hidden" style="margin-top:20px;">



                    <div class="ftm-refa-card">



                        <div class="ftm-flex-between" style="margin-bottom:16px;">



                            <div>



                                <h3 style="margin:0; font-size:18px; font-weight:700;" id="ftm-profile-projects-title">Assigned Projects</h3>



                                <p class="ftm-text-muted ftm-text-sm" style="margin:4px 0 0;" id="ftm-profile-projects-sub">Active client orders, deliverables, and production records for this specialist.</p>



                            </div>



                            <div id="ftm-profile-projects-badge"></div>



                        </div>



                        <div id="ftm-profile-projects-container">



                            <!-- Dynamic Member Projects or Empty State -->



                        </div>



                    </div>



                </div>







                <!-- Team Insights Section (Tab 7) -->



                <div id="ftm-profile-team-insights-section" class="hidden" style="margin-top:20px;">



                    <div class="ftm-refa-card ftm-insights-header-card" style="margin-bottom:16px;">
                        <div class="ftm-flex-between ftm-insights-header-flex">
                            <div class="ftm-insights-header-main">
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <h3 style="margin:0; font-size:18px; font-weight:700;" id="ftm-insights-team-title">Team Insights</h3>
                                    <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" id="ftm-insights-team-badge">Production Unit</span>
                                </div>
                                <p class="ftm-text-muted ftm-text-sm" style="margin:4px 0 0;" id="ftm-insights-team-desc">Collaborative capacity, deliverable realization, and peer performance metrics.</p>
                            </div>
                            <div class="ftm-insights-lead-box" style="text-align:right;">
                                <div class="ftm-text-xs ftm-text-muted ftm-insights-lead-lbl">Team Leadership</div>
                                <div class="ftm-font-semibold ftm-text-sm ftm-insights-lead-val" id="ftm-insights-team-leader">—</div>
                            </div>
                        </div>
                    </div>







                    <!-- Team KPIs Row -->
                    <div class="ftm-refa-kpi-row ftm-refa-kpi-row-4" style="margin-bottom:20px;">
                        <div class="ftm-refa-kpi-card">
                            <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-emerald">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            </div>
                            <div class="ftm-refa-kpi-content">
                                <div class="ftm-refa-kpi-label">Team Delivery Volume</div>
                                <div class="ftm-refa-kpi-value text-emerald" id="ftm-insights-kpi-volume">$81,627.10</div>
                                <div class="ftm-refa-kpi-sub">Total team earnings</div>
                            </div>
                        </div>
                        <div class="ftm-refa-kpi-card">
                            <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-blue">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                            </div>
                            <div class="ftm-refa-kpi-content">
                                <div class="ftm-refa-kpi-label">Active Team Pipeline</div>
                                <div class="ftm-refa-kpi-value text-blue" id="ftm-insights-kpi-active">2 Projects</div>
                                <div class="ftm-refa-kpi-sub">In progress deliverables</div>
                            </div>
                        </div>
                        <div class="ftm-refa-kpi-card">
                            <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-indigo">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </div>
                            <div class="ftm-refa-kpi-content">
                                <div class="ftm-refa-kpi-label">Active Team Members</div>
                                <div class="ftm-refa-kpi-value" id="ftm-insights-kpi-members">4 Members</div>
                                <div class="ftm-refa-kpi-sub">Production specialists</div>
                            </div>
                        </div>
                        <div class="ftm-refa-kpi-card">
                            <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-purple" style="background:#f5f3ff; color:#7c3aed;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                            </div>
                            <div class="ftm-refa-kpi-content">
                                <div class="ftm-refa-kpi-label">Member Contribution</div>
                                <div class="ftm-refa-kpi-value text-purple" id="ftm-insights-kpi-contrib">Primary Contributor</div>
                                <div class="ftm-refa-kpi-sub" id="ftm-insights-kpi-contrib-sub">Delivery share in team</div>
                            </div>
                        </div>
                    </div>







                    <!-- Team Teammates Roster Card -->



                    <div class="ftm-refa-card">



                        <div class="ftm-refa-card-title">Team Roster & Specialists</div>



                        <div id="ftm-insights-teammates-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:12px; margin-top:12px;">



                            <!-- Teammates dynamic cards -->



                        </div>



                    </div>



                </div>

                <!-- Calendar Section (Tab 8) -->
                <div id="ftm-profile-calendar-section" class="hidden" style="margin-top:20px;">
                    <div class="ftm-refa-card" style="padding:20px; border-radius:14px; background:var(--ftm-card-bg); border:1px solid var(--ftm-border);">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                            <div>
                                <h3 style="margin:0; font-size:17px; font-weight:800; color:var(--ftm-text-main);">Specialist Schedule & Deliverables Calendar</h3>
                                <p class="ftm-text-muted ftm-text-xs" style="margin:2px 0 0;">Interactive monthly view showing project deadlines, daily reports, and weekly off-days.</p>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs" id="ftm-cal-prev-month">&larr; Prev</button>
                                <span style="font-weight:700; font-size:14px; min-width:130px; text-align:center;" id="ftm-cal-current-month-label">November 2026</span>
                                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs" id="ftm-cal-next-month">Next &rarr;</button>
                            </div>
                        </div>

                        <!-- 7 Column Day-of-Week Header -->
                        <div style="display:grid; grid-template-columns:repeat(7, 1fr); gap:6px; margin-bottom:8px; text-align:center; font-weight:700; font-size:12px; color:var(--ftm-text-muted);">
                            <div>SUN</div>
                            <div>MON</div>
                            <div>TUE</div>
                            <div>WED</div>
                            <div>THU</div>
                            <div>FRI</div>
                            <div>SAT</div>
                        </div>

                        <!-- Dynamic Calendar Days Grid -->
                        <div id="ftm-profile-calendar-grid" style="display:grid; grid-template-columns:repeat(7, 1fr); gap:6px;">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>

                <!-- Extra Time Work (Overtime) Section -->
                <div id="ftm-profile-extra-time-section" class="hidden" style="margin-top:20px;">
                    <!-- Shift Hours Notice Banner -->
                    <div class="ftm-refa-card" style="background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.25); border-radius:12px; padding:18px 22px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                        <div style="display:flex; align-items:center; gap:14px;">
                            <div style="width:44px; height:44px; border-radius:10px; background:rgba(245,158,11,0.18); display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">⚡</div>
                            <div>
                                <div style="font-weight:800; font-size:15px; color:#d97706;">Shift Ends at 6:20 PM &bull; Extra Time Work Tracking</div>
                                <div class="ftm-text-muted ftm-text-xs" style="margin-top:3px; max-width:620px;">
                                    Official office shift ends at <strong>6:20 PM</strong>. Any work carried out after 6:20 PM is counted as <strong>Extra Time (Overtime)</strong>. Use the live <strong>Extra Time Tracker</strong> (takes random screenshots every ~3 minutes to verify work) or submit a manual Extra Time Work Report with deliverables below.
                                </div>
                            </div>
                        </div>
                        <div style="display:flex; gap:10px; flex-shrink:0;">
                            <button type="button" class="ftm-btn ftm-btn-primary" id="ftm-btn-start-extra-tracker-prof" style="background:#d97706; border-color:#d97706; font-weight:700;">
                                <span>⚡</span> Start Extra Time Tracker
                            </button>
                            <button type="button" class="ftm-btn ftm-btn-secondary" id="ftm-btn-open-extra-report-modal" style="font-weight:600;">
                                <span>📝</span> Submit Extra Time Report
                            </button>
                        </div>
                    </div>

                    <!-- Overtime KPI Cards -->
                    <div class="ftm-refa-kpi-row ftm-refa-kpi-row-3" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:24px;">
                        <div class="ftm-refa-kpi-card" style="padding:18px 20px;">
                            <div class="ftm-refa-kpi-label">Total Extra Time Logged</div>
                            <div class="ftm-refa-kpi-value text-amber" id="ftm-profile-kpi-extra-hours" style="font-size:26px; font-weight:900; margin-top:4px;">0h 00m</div>
                            <div class="ftm-refa-kpi-sub" style="color:#d97706; margin-top:2px;">Work completed after 6:20 PM</div>
                        </div>
                        <div class="ftm-refa-kpi-card" style="padding:18px 20px;">
                            <div class="ftm-refa-kpi-label">Extra Time Sessions</div>
                            <div class="ftm-refa-kpi-value text-blue" id="ftm-profile-kpi-extra-sessions" style="font-size:26px; font-weight:900; margin-top:4px;">0</div>
                            <div class="ftm-refa-kpi-sub" style="margin-top:2px;">Live tracked sessions</div>
                        </div>
                        <div class="ftm-refa-kpi-card" style="padding:18px 20px;">
                            <div class="ftm-refa-kpi-label">Verified Snapshots</div>
                            <div class="ftm-refa-kpi-value text-emerald" id="ftm-profile-kpi-extra-snapshots" style="font-size:26px; font-weight:900; margin-top:4px;">0</div>
                            <div class="ftm-refa-kpi-sub text-emerald" style="margin-top:2px;">Captured during overtime</div>
                        </div>
                    </div>

                    <!-- Overtime History Table -->
                    <div class="ftm-refa-card" style="padding:22px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                            <div>
                                <h3 style="margin:0; font-size:16px; font-weight:800; display:flex; align-items:center; gap:8px;">
                                    <span>📋</span> Extra Time Work History & Deliverables
                                </h3>
                                <div class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">All recorded extra time after 6:20 PM with project assignments and reasons.</div>
                            </div>
                            <span class="ftm-badge bg-amber-soft text-amber ftm-badge-sm" id="ftm-profile-extra-count-badge">0 Entries</span>
                        </div>
                        <div class="ftm-table-responsive">
                            <table class="ftm-table ftm-table-sm" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Project / Order</th>
                                        <th>Extra Hours</th>
                                        <th>Reason / Context</th>
                                        <th>Deliverables Completed</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="ftm-profile-extra-history-tbody">
                                    <tr><td colspan="6" style="text-align:center; padding:24px; color:var(--ftm-text-muted);">Loading extra time records...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>



            </section>







            <!-- 10. EXECUTIVE MANAGER PROFILE (Standalone Admin / Manager View) -->



            <section id="ftm-view-admin-profile" class="ftm-view-panel hidden">
                <?php
                // The manager hero belongs to whoever is signed in (Operations Manager or Deputy Manager), not always user #1
                $hero_uid = get_current_user_id();
                $hero_deputy = FTM_Permissions::is_deputy($hero_uid);
                $hero_avatar = get_user_meta($hero_uid, 'ftm_custom_avatar', true) ?: get_avatar_url($hero_uid, ['size' => 128]);
                $hero_cid = ($hero_uid === 1) ? '#0001' : ('#' . (get_user_meta($hero_uid, 'ftm_custom_id', true) ?: str_pad((string)$hero_uid, 4, '0', STR_PAD_LEFT)));
                $hero_role = FTM_Permissions::get_role_label(FTM_Permissions::get_user_role($hero_uid));
                $hero_skills = $hero_deputy ? json_decode((string)get_user_meta($hero_uid, 'ftm_skills', true), true) : null;
                $hero_bio = $hero_deputy ? (string)get_user_meta($hero_uid, 'ftm_bio', true) : '';
                ?>



                <div class="ftm-refa-breadcrumb">



                    <span class="ftm-text-muted">Operations</span>



                    <span class="ftm-refa-bc-sep">/</span>



                    <span class="ftm-refa-bc-active">Executive Management Profile</span>



                </div>







                <!-- Hero Executive Manager Card -->
                <div class="ftm-refa-hero-card">
                    <div class="ftm-refa-hero-main">
                        <div class="ftm-refa-avatar-wrap">
                            <img src="<?php echo esc_url($hero_avatar); ?>" class="ftm-refa-hero-avatar" alt="Manager">
                            <div class="ftm-refa-online-badge">
                                <span class="ftm-refa-online-dot"></span>
                                <span>Active</span>
                            </div>
                        </div>
                        <div class="ftm-refa-hero-info">
                            <div class="ftm-refa-name-row">
                                <h1 class="ftm-refa-name"><?php echo esc_html(wp_get_current_user()->display_name); ?></h1>
                                <span class="ftm-refa-id-badge"><?php echo esc_html($hero_cid); ?></span>
                            </div>
                            <div class="ftm-refa-hero-subline">
                                <span class="ftm-refa-hero-sub-role">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    <span><?php echo esc_html($hero_role); ?></span>
                                </span>
                                <span class="ftm-badge bg-amber-soft text-amber ftm-badge-sm" style="font-size:11px;"><?php echo $hero_role === 'Management' ? 'View-Only Oversight' : ($hero_deputy ? 'Second to the Head of Management' : ' Operations Lead'); ?></span>
                                <span class="ftm-refa-hero-sep">|</span>
                                <span class="ftm-refa-hero-sub-team">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    <span>Executive Team</span>
                                </span>
                            </div>
                            <div class="ftm-refa-tech-badges">
                                <?php if ($hero_deputy && is_array($hero_skills) && $hero_skills) : foreach ($hero_skills as $sk) : ?>
                                <span class="ftm-refa-skill-pill"><span><?php echo esc_html($sk); ?></span></span>
                                <?php endforeach; else : ?>
                                <span class="ftm-refa-skill-pill" style="background:rgba(37, 99, 235, 0.1); color:#2563eb; border-color:rgba(37, 99, 235, 0.25);"><strong style="margin-right:4px; font-size:10px;">★</strong><span>Executive Operations</span></span>
                                <span class="ftm-refa-skill-pill" style="background:rgba(16, 185, 129, 0.1); color:#059669; border-color:rgba(16, 185, 129, 0.25);"><strong style="margin-right:4px; font-size:10px;">✓</strong><span>Full Governance</span></span>
                                <span class="ftm-refa-skill-pill"><span>Financial Planning</span></span>
                                <span class="ftm-refa-skill-pill"><span>Quality Control</span></span>
                            <?php endif; ?>
                            </div>
                            <p class="ftm-refa-bio">
                                <?php echo $hero_bio !== '' ? esc_html($hero_bio) : 'Executive manager directing end-to-end agency operations, multi-team deliverable pipelines, financial volume realization, cross-team capacity allocations, and quality control.'; ?>
                            </p>
                        </div>
                    </div>
                    <div class="ftm-refa-hero-aside">
                        <div class="ftm-refa-hero-actions">
                            <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-btn-edit-admin-profile">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                <span>Edit Profile</span>
                            </button>
                            <a href="#monthly-reports" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-nav-trigger" data-target="monthly-reports">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                                <span>Monthly Sheet</span>
                            </a>
                        </div>
                        <div class="ftm-refa-meta-box">
                            <div class="ftm-refa-meta-box-row">
                                <span class="ftm-refa-meta-label">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    Managed Teams
                                </span>
                                <span class="ftm-refa-meta-val" id="ftm-manager-meta-teams">3 Production Units</span>
                            </div>
                            <div class="ftm-refa-meta-box-row">
                                <span class="ftm-refa-meta-label">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    Active Members
                                </span>
                                <span class="ftm-refa-meta-val" id="ftm-manager-meta-members">14 Specialists</span>
                            </div>
                            <div class="ftm-refa-meta-box-row">
                                <span class="ftm-refa-meta-label">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                                    Agency
                                </span>
                                <span class="ftm-refa-meta-val">FB International</span>
                            </div>
                            <div class="ftm-refa-meta-box-row">
                                <span class="ftm-refa-meta-label">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                                    Role
                                </span>
                                <span class="ftm-refa-meta-val text-emerald"><?php echo $hero_deputy ? esc_html($hero_role) : 'Super Admin'; ?></span>
                            </div>
                            <div class="ftm-refa-meta-box-row">
                                <span class="ftm-refa-meta-label">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                    Email
                                </span>
                                <span class="ftm-refa-meta-val ftm-refa-meta-email-text" id="ftm-admin-profile-email"><?php echo esc_html(wp_get_current_user()->user_email); ?></span>
                            </div>
                        </div>
                    </div>
                </div>







                <!-- 4 Executive KPI Cards -->
                <div class="ftm-refa-kpi-row ftm-refa-kpi-row-4">
                    <div class="ftm-refa-kpi-card">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-emerald">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">Total 2026 Volume</div>
                            <div class="ftm-refa-kpi-value text-emerald" id="ftm-manager-kpi-volume">$151,663.24</div>
                            <div class="ftm-refa-kpi-sub">Across 9 realized months</div>
                        </div>
                    </div>
                    <div class="ftm-refa-kpi-card">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-blue">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">Production Teams</div>
                            <div class="ftm-refa-kpi-value text-blue" id="ftm-manager-kpi-teams">3 Teams</div>
                            <div class="ftm-refa-kpi-sub" id="ftm-manager-kpi-teams-sub">WpCraft, WpTitans, WpNexus</div>
                        </div>
                    </div>
                    <div class="ftm-refa-kpi-card">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-purple" style="background:#f5f3ff; color:#7c3aed;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">Live Active Projects</div>
                            <div class="ftm-refa-kpi-value text-purple" id="ftm-manager-kpi-projects">24 Deliverables</div>
                            <div class="ftm-refa-kpi-sub">$1,268.00 current net</div>
                        </div>
                    </div>
                    <div class="ftm-refa-kpi-card">
                        <div class="ftm-refa-kpi-icon-box ftm-kpi-icon-amber">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div class="ftm-refa-kpi-content">
                            <div class="ftm-refa-kpi-label">On-Time Compliance</div>
                            <div class="ftm-refa-kpi-value text-emerald" id="ftm-manager-kpi-compliance">96.4%</div>
                            <div class="ftm-refa-kpi-sub">Agency milestone delivery</div>
                        </div>
                    </div>
                </div>







                <!-- Multi-Team Matrix -->
                <div class="ftm-table-card ftm-manager-matrix-card" style="margin-top:20px;">
                    <div class="ftm-table-header ftm-matrix-header">
                        <div class="ftm-table-title">Production Teams Realization Matrix</div>
                        <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm ftm-matrix-eyebrow">Live Workload & Financials</span>
                    </div>



                    <div class="ftm-table-responsive">



                        <table class="ftm-table" id="ftm-manager-teams-table">



                            <thead>



                                <tr>



                                    <th>Team Name</th>



                                    <th>Team Leader</th>



                                    <th>Members</th>



                                    <th>2026 Delivery Volume</th>



                                    <th>Active Pipeline</th>



                                    <th>On-Time Rate</th>



                                    <th>Health</th>



                                </tr>



                            </thead>



                            <tbody id="ftm-manager-teams-tbody">
                                <!-- Rendered dynamically via JS -->
                            </tbody>
                        </table>
                    </div>
                    <!-- Mobile View: Premium Cards for Production Teams Realization Matrix -->
                    <div id="ftm-manager-teams-mobile-cards" class="ftm-mobile-only ftm-manager-mobile-cards">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>



            </section>







            <!-- 11. AGENCY NOTICES & BROADCASTS VIEW -->



            <section id="ftm-view-agency-notices" class="ftm-view-panel hidden">



                <div class="ftm-refb-page-header">



                    <div class="ftm-refb-breadcrumb">



                        <span class="ftm-text-muted">Agency</span>



                        <span class="ftm-refb-bc-sep">/</span>



                        <span class="ftm-refb-bc-active">Notice Board & Broadcasts</span>



                    </div>



                    <div class="ftm-refb-header-row">



                        <div>



                            <h1 class="ftm-refb-page-title">Agency Notice Board</h1>



                            <p class="ftm-refb-page-sub">Official leadership broadcasts, priority directives, operational updates, and team recognitions.</p>



                        </div>



                        <div class="ftm-refb-header-actions">



                            <?php if ($is_admin && !$is_deputy): ?>



                            <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-btn-open-notice-modal">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>



                                <span>Post Notice</span>



                            </button>



                            <?php endif; ?>



                        </div>



                    </div>



                </div>







                <!-- Filter Pills -->



                <div class="ftm-monthly-filter-toolbar" style="margin-top:16px;">



                    <div class="ftm-pill-tabs" id="ftm-notice-type-filters">



                        <button type="button" class="ftm-pill-tab active" data-type="all">All Notices</button>



                        <button type="button" class="ftm-pill-tab" data-type="announcement">Announcements</button>



                        <button type="button" class="ftm-pill-tab" data-type="important">Important</button>



                        <button type="button" class="ftm-pill-tab" data-type="urgent">Urgent</button>



                        <button type="button" class="ftm-pill-tab" data-type="kudos">Kudos</button>



                    </div>



                </div>







                <!-- Notices Feed Grid -->



                <div id="ftm-notices-feed" class="ftm-notices-feed-grid">



                    <!-- Populated dynamically via JS -->



                </div>



            </section>

            <!-- 12. REAL-TIME TEAM MESSAGES & CHAT HUB -->
            <section id="ftm-view-messages" class="ftm-view-panel hidden">
                <div class="ftm-messages-page-wrapper">
                    <div class="ftm-chat-workspace">
                        <!-- Left Channels Sidebar -->
                        <aside class="ftm-chat-sidebar">
                            <div class="ftm-chat-sidebar-header">
                                <div class="ftm-chat-title-row">
                                    <h2 class="ftm-chat-title">Team Messages</h2>
                                    <button type="button" class="ftm-icon-btn ftm-chat-refresh-btn" id="ftm-chat-channels-refresh" title="Refresh Inboxes">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                                    </button>
                                </div>
                                
                                <!-- Search Input -->
                                <div class="ftm-chat-search-wrap">
                                    <svg class="ftm-chat-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <input type="text" id="ftm-chat-search-input" class="ftm-chat-search-input" placeholder="Search members, orders..." />
                                    <button type="button" class="ftm-chat-search-clear hidden" id="ftm-chat-search-clear">&times;</button>
                                </div>

                                <!-- Filter Tabs (All-Hands, Squads, Direct DMs, Order Chats) -->
                                <div class="ftm-chat-tabs" id="ftm-chat-category-tabs">
                                    <button type="button" class="ftm-chat-tab active" data-tab="global">
                                        <span>All-Hands</span>
                                        <span class="ftm-chat-tab-badge hidden" id="ftm-chat-badge-global">0</span>
                                    </button>
                                    <button type="button" class="ftm-chat-tab" data-tab="team">
                                        <span>Squads</span>
                                        <span class="ftm-chat-tab-badge hidden" id="ftm-chat-badge-team">0</span>
                                    </button>
                                    <button type="button" class="ftm-chat-tab" data-tab="direct">
                                        <span>Direct DMs</span>
                                        <span class="ftm-chat-tab-badge hidden" id="ftm-chat-badge-direct">0</span>
                                    </button>
                                    <button type="button" class="ftm-chat-tab" data-tab="project">
                                        <span>Order Chats</span>
                                        <span class="ftm-chat-tab-badge hidden" id="ftm-chat-badge-project">0</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Scrollable Channel List -->
                            <div class="ftm-chat-channel-list" id="ftm-chat-channel-list">
                                <!-- Populated dynamically by ftm-messages.js -->
                            </div>
                        </aside>

                        <!-- Right Chat Main Pane -->
                        <div class="ftm-chat-main" id="ftm-chat-main-pane">
                            <!-- Chat Active Header -->
                            <div class="ftm-chat-header" id="ftm-chat-header">
                                <button type="button" class="ftm-chat-back-btn" id="ftm-chat-back-btn" title="Back to Channels">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                                    <span>Inboxes</span>
                                </button>
                                <div class="ftm-chat-header-avatar-wrap" id="ftm-chat-header-avatar">
                                    <div class="ftm-chat-avatar-placeholder">G</div>
                                </div>
                                <div class="ftm-chat-header-info">
                                    <div class="ftm-chat-header-title-row">
                                        <h3 class="ftm-chat-header-title" id="ftm-chat-header-title">FB All-Hands Global</h3>
                                        <span class="ftm-chat-header-badge" id="ftm-chat-header-badge">LIVE</span>
                                    </div>
                                    <p class="ftm-chat-header-sub" id="ftm-chat-header-sub">Agency-wide broadcast &amp; discussion room</p>
                                </div>
                                <div class="ftm-chat-header-actions">
                                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs hidden" id="ftm-chat-btn-view-order">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                        <span>Order Details</span>
                                    </button>
                                    <button type="button" class="ftm-icon-btn ftm-chat-refresh-thread-btn" id="ftm-chat-refresh-thread-btn" title="Refresh messages">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Messages Container -->
                            <div class="ftm-chat-messages-container" id="ftm-chat-messages-container">
                                <!-- Messages dynamically rendered -->
                            </div>

                            <!-- Composer Input Bar -->
                            <form class="ftm-chat-composer" id="ftm-chat-composer-form">
                                <div class="ftm-chat-input-wrapper">
                                    <textarea id="ftm-chat-input" class="ftm-chat-input" rows="1" placeholder="Type a message... (Enter to send, Shift+Enter for new line)"></textarea>
                                </div>
                                <button type="submit" class="ftm-chat-send-btn" id="ftm-chat-send-btn" title="Send message" disabled>
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>



        </div>



    </main>



</div> <!-- end .ftm-main-container -->







    <!-- MODAL: ADD / EDIT PROJECT (WITH FINANCIALS & EXTENSION TRACKING) -->



    <div id="ftm-modal-project" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog ftm-modal-lg">



            <div class="ftm-modal-header">



                <div>



                    <h3 id="ftm-modal-project-title">Add New Project</h3>



                    <p class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">Enter project specifications, pricing breakdown, and assignments.</p>



                </div>



                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>



            </div>



            <form id="ftm-project-form" class="ftm-modal-body">



                <input type="hidden" id="ftm-form-project-id" value="">



                



                <!-- Team & Profile Row -->



                <div class="ftm-form-row ftm-grid-2">



                    <div class="ftm-form-group">



                        <label class="ftm-label" for="ftm-form-team-name">Assigned Team *</label>
                        <select id="ftm-form-team-name" class="ftm-select" required>
                            <option value="WpCraft">WpCraft</option>
                            <option value="WpNexus">WpNexus</option>
                            <option value="WpTitans">WpTitans</option>
                        </select>



                    </div>



                    <div class="ftm-form-group">



                        <label class="ftm-label" for="ftm-form-profile">Seller Profile *</label>



                        <select id="ftm-form-profile" class="ftm-select" required>



                            <option value="">Select Profile...</option>



                        </select>



                    </div>



                </div>







                <!-- Order ID & Client Username (with Recurring Client Suggestion) -->



                <div class="ftm-form-row ftm-grid-2">



                    <div class="ftm-form-group">



                        <label class="ftm-label" for="ftm-form-order-id">Order ID *</label>



                        <input type="text" id="ftm-form-order-id" class="ftm-input" placeholder="#FO12345678" required style="font-family:monospace; font-weight:700; text-transform:uppercase;">



                    </div>



                    <div class="ftm-form-group">



                        <label class="ftm-label" for="ftm-form-client">Client Username * (Repeat Client Auto-Detection)</label>



                        <input type="text" id="ftm-form-client" class="ftm-input" placeholder="e.g. arielreg, atiba15" list="ftm-client-datalist" autocomplete="off" required>



                        <datalist id="ftm-client-datalist">



                            <!-- Populated dynamically via JS -->



                        </datalist>



                        <div id="ftm-client-repeat-badge" class="hidden" style="margin-top:6px; padding:6px 12px; background:rgba(37,99,235,0.08); border:1px solid rgba(37,99,235,0.25); border-radius:8px; font-size:12px; color:#2563eb; display:flex; align-items:center; justify-content:space-between;"></div>
                    </div>
                </div>

                <!-- Google Sheet Link & Order Source Row -->
                <div class="ftm-form-row ftm-grid-2">
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-form-sheet-link">
                            <span style="display:inline-flex; align-items:center; gap:5px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                Google Sheet Link / Attachment
                            </span>
                        </label>
                        <input type="text" id="ftm-form-sheet-link" class="ftm-input" placeholder="https://docs.google.com/spreadsheets/d/... or sheet details">
                        <small class="ftm-text-muted" style="font-size:11px;">Link to Google Sheet requirements or client tracker.</small>
                    </div>

                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-form-order-source">
                            <span style="display:inline-flex; align-items:center; gap:5px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                Order Source (Repeat Customer) *
                            </span>
                        </label>
                        <select id="ftm-form-order-source" class="ftm-select">
                            <option value="Query">Query (New Client)</option>
                            <option value="Repeat Client">Repeat Client (Existing Customer)</option>
                            <option value="Custom Order Request">Custom Order Request</option>
                            <option value="Follow Up">Follow Up</option>
                            <option value="Brief">Brief</option>
                            <option value="Add On">Add On</option>
                            <option value="TIP">TIP</option>
                            <option value="Direct Purchase">Direct Purchase</option>
                        </select>
                        <small class="ftm-text-muted" style="font-size:11px;">Track repeat orders approached and closed by developer.</small>
                    </div>
                </div>







                <!-- Financial Breakdown & Addons Card -->



                <div class="ftm-pricing-card" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-bottom:16px;">



                    <div style="font-weight:700; font-size:12.5px; color:var(--ftm-text-secondary); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">



                        <span>Project Pricing & Addon Calculation</span>



                        <span class="ftm-text-xs ftm-text-muted" id="ftm-pricing-formula-hint">Net = Total Gross - 20% Fee</span>



                    </div>



                    <div class="ftm-form-row ftm-grid-3" style="margin-bottom:10px;">



                        <div class="ftm-form-group" style="margin:0;">



                            <label class="ftm-label" for="ftm-form-base-value">Initial Order Gross ($)</label>



                            <input type="number" step="0.01" id="ftm-form-base-value" class="ftm-input" placeholder="e.g. 250.00">



                        </div>



                        <div class="ftm-form-group" style="margin:0;">



                            <label class="ftm-label" for="ftm-form-addon-value">Addon / Extra Gig ($)</label>



                            <input type="number" step="0.01" id="ftm-form-addon-value" class="ftm-input" placeholder="e.g. 150.00">



                        </div>



                        <div class="ftm-form-group" style="margin:0;">



                            <label class="ftm-label" for="ftm-form-gross-value">Total Gross Order ($) *</label>



                            <input type="number" step="0.01" id="ftm-form-gross-value" class="ftm-input" placeholder="400.00" required style="font-weight:700;">



                        </div>



                    </div>



                    <div class="ftm-form-row ftm-grid-2">



                        <div class="ftm-form-group" style="margin:0;">



                            <label class="ftm-label" for="ftm-form-fee-percent">Marketplace Fee (%)</label>



                            <input type="number" step="0.1" min="0" max="100" id="ftm-form-fee-percent" class="ftm-input" value="20.0" required>



                        </div>



                        <div class="ftm-form-group" style="margin:0;">



                            <label class="ftm-label" for="ftm-form-net-value">Net Delivery Payout ($)</label>



                            <input type="number" step="0.01" id="ftm-form-net-value" class="ftm-input" readonly style="background:#ecfdf5; border-color:#a7f3d0; font-weight:800; color:#059669; font-size:15px;">



                        </div>



                    </div>



                    <!-- Live Financial Visual Bar -->



                    <div id="ftm-pricing-visual-bar" style="margin-top:12px; background:#e2e8f0; height:8px; border-radius:4px; overflow:hidden; display:flex;">



                        <div id="ftm-visual-net-bar" style="width:80%; background:#10b981; transition:width 0.3s ease;"></div>



                        <div id="ftm-visual-fee-bar" style="width:20%; background:#f59e0b; transition:width 0.3s ease;"></div>



                    </div>



                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px; font-size:11px; font-weight:600;">



                        <span id="ftm-visual-net-text" style="color:#059669;">● Net Payout: $0.00 (80%)</span>



                        <span id="ftm-visual-fee-text" style="color:#d97706;">● Marketplace Fee: $0.00 (20%)</span>



                    </div>



                    <div class="ftm-form-group" style="margin-top:10px; margin-bottom:0;">



                        <label class="ftm-label" for="ftm-form-addon-note">Pricing / Addon Reason / Milestone Notes</label>



                        <input type="text" id="ftm-form-addon-note" class="ftm-input" placeholder="e.g. Initial website $250 + WooCommerce addon $150">



                    </div>
                    <div class="ftm-form-group" style="margin-top:10px; margin-bottom:0;">
                        <label class="ftm-label" for="ftm-form-tip">Client Tip ($)</label>
                        <input type="number" step="0.01" min="0" id="ftm-form-tip" class="ftm-input" placeholder="0.00">
                        <small class="ftm-text-muted">Enter the tip exactly as the client paid it. The marketplace keeps 20% of tips too, so reports count 80%.</small>
                    </div>
                    <div class="ftm-form-group" style="margin-top:10px; margin-bottom:0;">
                        <label class="ftm-label" for="ftm-form-order-type">Order Type</label>
                            <select id="ftm-form-order-type" class="ftm-select">
                                <option value="standard">Standard order</option>
                                <option value="subscription">Monthly subscription (no review)</option>
                                <option value="outside">Outside marketplace (no review)</option>
                            </select>
                    </div>
                    <div id="ftm-subscription-fields" class="ftm-subscription-fields hidden">
                            <label class="ftm-sub-toggle"><input type="checkbox" id="ftm-form-sub-active" checked> <span><strong>Auto-renew every month</strong> - a new order is created on the renewal date</span></label>
                            <div class="ftm-sub-row">
                                <div><label class="ftm-label" for="ftm-form-sub-next">Next renewal date</label><input type="date" id="ftm-form-sub-next" class="ftm-input"></div>
                                <div><label class="ftm-label" for="ftm-form-sub-term">Delivery window (days)</label><input type="number" id="ftm-form-sub-term" class="ftm-input" min="1" max="60" value="7"></div>
                            </div>
                            <small class="ftm-text-muted">Untick to stop the subscription (e.g. the client cancelled it). Already-created orders stay.</small>
                        </div>
                    <!-- Combined order: this value is only my share, the rest goes to other teams / skills -->
                    <div class="ftm-combined-box" id="ftm-combined-box">
                        <label class="ftm-combined-toggle">
                            <input type="checkbox" id="ftm-form-combined-toggle">
                            <span><strong>Combined order</strong> - this order is shared with other teams or skills (SEO, Full Stack, Horizon...)</span>
                        </label>
                        <div id="ftm-combined-fields" class="hidden">
                            <div class="ftm-form-group" style="margin:10px 0 0;">
                                <label class="ftm-label" for="ftm-form-order-total">Full order net value, all parties ($)</label>
                                <input type="number" step="0.01" min="0" id="ftm-form-order-total" class="ftm-input" placeholder="e.g. 1440.00">
                                <small class="ftm-text-muted">The amount above is only <em>your</em> share. Enter the whole order's net value here and list who gets the rest.</small>
                            </div>
                            <div class="ftm-label" style="margin:12px 0 6px;">Other parties' shares</div>
                            <div id="ftm-form-splits"></div>
                            <button type="button" class="ftm-btn ftm-btn-sm ftm-btn-secondary" id="ftm-form-split-add">+ Add party</button>
                            <div id="ftm-combined-summary" class="ftm-combined-summary"></div>
                        </div>
                    </div>



                </div>







                <!-- Deadline & Status -->



                <div class="ftm-form-row ftm-grid-3">

                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-form-assign-date">Assign Date</label>
                        <input type="date" id="ftm-form-assign-date" class="ftm-input">
                    </div>

                    <div class="ftm-form-group">



                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">



                            <label class="ftm-label" for="ftm-form-delivery-date" style="margin:0;">Delivery Deadline *</label>



                            <span id="ftm-form-deadline-countdown" class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" style="font-weight:600;"> In 3 Days</span>



                        </div>



                        <input type="date" id="ftm-form-delivery-date" class="ftm-input" required>



                    </div>



                    <div class="ftm-form-group">



                        <label class="ftm-label" for="ftm-form-status">Project Status</label>



                        <select id="ftm-form-status" class="ftm-select">



                            <option value="nra">NRA (Not Ready / Assigned)</option>



                            <option value="wip" selected>WIP (Work In Progress)</option>



                            <option value="delivery_submitted">Delivery Submitted</option>



                            <option value="revision">Revision</option>



                            <option value="delivery_accepted">Delivery Accepted / Completed</option>



                            <option value="cancelled">Cancelled</option>



                        </select>



                    </div>



                </div>

                <!-- Delivery & Settlement Attribution Row -->
                <div class="ftm-form-group" id="ftm-form-delivery-settle-row" style="background:rgba(16, 185, 129, 0.05); border:1px solid rgba(16, 185, 129, 0.25); border-radius:12px; padding:16px; margin-bottom:16px;">
                    <div style="font-weight:700; font-size:13px; color:var(--ftm-text-primary, #f1f5f9); margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                        <span style="display:inline-flex; align-items:center; gap:8px;">
                            <span>📦</span> <span style="color:#10b981; font-weight:700;">Delivery Date & Monthly Settlement</span>
                        </span>
                        <span style="font-size:11px; font-weight:500; text-transform:none; color:var(--ftm-text-muted, #94a3b8);">Credits revenue & tips to selected monthly report</span>
                    </div>
                    <div class="ftm-form-row ftm-grid-2" style="margin-bottom:0; gap:14px;">
                        <div class="ftm-form-group" style="margin-bottom:0;">
                            <label class="ftm-label" for="ftm-form-delivered-date" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; color:var(--ftm-text-primary, #f1f5f9); font-weight:600; font-size:12px;">
                                <span style="display:inline-flex; align-items:center; gap:6px;">
                                    <span>📦</span> Delivered Date / Month
                                </span>
                                <span class="ftm-badge ftm-badge-sm" style="font-size:10px; font-weight:700; padding:2px 8px; background:rgba(16,185,129,0.18); color:#10b981; border:1px solid rgba(16,185,129,0.35); border-radius:4px;">Report Settle</span>
                            </label>
                            <input type="date" id="ftm-form-delivered-date" class="ftm-input" style="width:100%; border-radius:8px; border:1px solid var(--ftm-border); background:var(--ftm-card-bg); color:var(--ftm-text-primary);">
                            <small class="ftm-text-muted" style="font-size:11px; display:block; margin-top:5px; line-height:1.4; color:var(--ftm-text-muted, #94a3b8);">The month of this date determines which monthly delivery report this project is credited to (e.g. Oct 2026).</small>
                        </div>
                        <div class="ftm-form-group" style="margin-bottom:0;">
                            <label class="ftm-label" for="ftm-form-tip-date" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; color:var(--ftm-text-primary, #f1f5f9); font-weight:600; font-size:12px;">
                                <span style="display:inline-flex; align-items:center; gap:6px;">
                                    <span>💰</span> Tip Received Date
                                </span>
                                <span class="ftm-badge ftm-badge-sm" style="font-size:10px; font-weight:700; padding:2px 8px; background:rgba(245,158,11,0.18); color:#f59e0b; border:1px solid rgba(245,158,11,0.35); border-radius:4px;">Cross-Month Tip</span>
                            </label>
                            <input type="date" id="ftm-form-tip-date" class="ftm-input" style="width:100%; border-radius:8px; border:1px solid var(--ftm-border); background:var(--ftm-card-bg); color:var(--ftm-text-primary);">
                            <small class="ftm-text-muted" style="font-size:11px; display:block; margin-top:5px; line-height:1.4; color:var(--ftm-text-muted, #94a3b8);">If client sends a tip in a later month, set tip date here to credit that month's earnings.</small>
                        </div>
                    </div>
                </div>

                <!-- Extension Tracking & On-Time Delivery -->
                <div class="ftm-form-group" style="background:var(--ftm-bg, #f8fafc); border:1px solid var(--ftm-border); border-radius:10px; padding:14px; margin-bottom:16px;">
                    <div style="font-weight:700; font-size:12px; color:var(--ftm-text-secondary); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                        <span>⏱️ Extension Tracking & On-Time Delivery</span>
                        <div style="display:inline-flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <button type="button" class="ftm-btn ftm-btn-xs ftm-btn-secondary" id="ftm-form-btn-reset-ext" style="font-size:11px; padding:2px 8px; color:#ef4444; border-color:rgba(239, 68, 68, 0.4);" title="Reset extensions if entered by mistake">Reset Extension (0)</button>
                            <span id="ftm-form-ontime-auto-badge" class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" style="font-size:11px; font-weight:700;">⏱️ Auto-Tracked on Completion (0 Ext)</span>
                            <input type="hidden" id="ftm-form-delivered-without-ext" value="0">
                        </div>
                    </div>
                    <small class="ftm-text-muted" style="font-size:11px; display:block; margin-bottom:10px; color:var(--ftm-text-muted, #94a3b8);">Automatically calculated from Extension Count &amp; completion: completed with 0 extensions = On-Time; any extension (&gt;0) = Not On-Time.</small>
                    <div class="ftm-form-row ftm-grid-2">
                        <div class="ftm-form-group" style="margin-bottom:0;">
                            <label class="ftm-label" for="ftm-form-extension-count">Extension Count</label>
                            <input type="number" id="ftm-form-extension-count" class="ftm-input" min="0" value="0">
                        </div>
                        <div class="ftm-form-group" style="margin-bottom:0;">
                            <label class="ftm-label" for="ftm-form-extension-days">Extra Days Extended</label>
                            <input type="number" id="ftm-form-extension-days" class="ftm-input" min="0" value="0">
                        </div>
                    </div>
                    <div class="ftm-form-group" style="margin-top:10px; margin-bottom:0;">
                        <label class="ftm-label" for="ftm-form-extension-reason">Extension Reason</label>
                        <input type="text" id="ftm-form-extension-reason" class="ftm-input" placeholder="e.g. Client requested complex additions, waiting for buyer assets...">
                    </div>
                </div>







                <!-- Review Section (visible on delivery_accepted) -->



                <div id="ftm-form-review-section" class="ftm-review-box hidden" style="background:var(--ftm-bg, #f8fafc); border:1px solid var(--ftm-border); border-radius:10px; padding:14px; margin-bottom:16px;">



                    <div style="font-weight:700; font-size:12px; color:var(--ftm-text-secondary); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:10px; display:flex; align-items:center; gap:6px;">



                        <span>⭐</span> Client Review & Rating



                    </div>



                    <div class="ftm-form-row ftm-grid-2">



                        <div class="ftm-form-group" style="margin:0;">



                            <label class="ftm-label" for="ftm-form-client-reviewed">Review Left by Client?</label>



                            <select id="ftm-form-client-reviewed" class="ftm-select">
                                <option value="no" selected>No Review Left</option>
                                <option value="yes">Yes (Review & Rating Received)</option>
                            </select>
                        </div>

                        <div id="ftm-form-star-rating-wrap" class="ftm-form-group hidden" style="margin:0;">



                            <label class="ftm-label" for="ftm-form-star-rating">Star Rating</label>



                            <div class="ftm-rate" data-ftm-rate>
                                <div class="ftm-rate-top">
                                    <div class="ftm-rate-stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                                    <div class="ftm-rate-readout"><input type="number" id="ftm-form-star-rating" class="ftm-rate-num" min="1" max="5" step="0.1" value="5.0" inputmode="decimal" aria-label="Star rating"><span>/ 5.0</span></div>
                                </div>
                                <input type="range" class="ftm-rate-range" min="1" max="5" step="0.1" value="5" aria-label="Star rating slider">
                                <div class="ftm-rate-chips">
                                    <button type="button" data-v="5">5.0</button><button type="button" data-v="4.5">4.5</button><button type="button" data-v="4">4.0</button><button type="button" data-v="3.5">3.5</button><button type="button" data-v="3">3.0</button><button type="button" data-v="2">2.0</button><button type="button" data-v="1">1.0</button>
                                </div>
                            </div>



                        </div>



                    </div>



                    <div id="ftm-form-review-feedback-wrap" class="ftm-form-group" style="margin-top:10px; margin-bottom:0;">



                        <label class="ftm-label" for="ftm-form-review-feedback">Client Feedback Comment</label>



                        <textarea id="ftm-form-review-feedback" class="ftm-textarea" rows="2" placeholder="Paste client review text here..."></textarea>



                    </div>



                </div>







                <!-- Multi-Assignee Checkboxes with Interactive Filter & Toolbar -->



                <div class="ftm-form-group">



                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">



                        <label class="ftm-label" style="margin:0;">Assign Team Members (Multiple Supported)</label>



                        <span id="ftm-form-assignees-count-badge" class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" style="font-weight:700;">0 Assigned</span>



                    </div>



                    <div style="display:flex; gap:8px; margin-bottom:8px; align-items:center;">
                        <div style="position:relative; flex:1;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); opacity:0.6; pointer-events:none;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="ftm-assignee-search" class="ftm-input" placeholder="Search specialist by name or role..." style="font-size:12px; padding:6px 12px 6px 32px; height:34px; width:100%;">
                        </div>
                        <button type="button" id="ftm-btn-assign-select-all" class="ftm-btn ftm-btn-secondary ftm-btn-xs" style="white-space:nowrap; padding:0 12px; height:34px;">Select All</button>
                        <button type="button" id="ftm-btn-assign-clear" class="ftm-btn ftm-btn-secondary ftm-btn-xs" style="white-space:nowrap; padding:0 12px; height:34px;">Clear</button>
                    </div>



                    <div id="ftm-form-assignees-list" class="ftm-assignee-picker-box">



                        <!-- Populated via JS -->



                    </div>



                </div>
                    <div id="ftm-split-panel" class="ftm-split-panel hidden"></div>







                <div class="ftm-form-group">



                    <label class="ftm-label" for="ftm-form-notes">Project Instructions / Requirements Note</label>



                    <textarea id="ftm-form-notes" class="ftm-textarea" rows="2" placeholder="Key deliverables, client scope, drive links..."></textarea>



                </div>







                <div class="ftm-modal-footer">



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Cancel</button>



                    <button type="submit" class="ftm-btn ftm-btn-primary" id="ftm-btn-save-project">Save Project</button>



                </div>



            </form>



        </div>



    </div>







    <!-- MODAL: PROJECT DETAILS (TIMELINE, NOTES, REVIEW, STATUS CHANGER) -->



    <div id="ftm-modal-details" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog ftm-modal-lg">



            <div class="ftm-modal-header" style="align-items:flex-start;">



                <div>



                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">



                        <span class="ftm-badge ftm-badge-outline" id="ftm-details-order-id" style="font-family:monospace; font-weight:700;">#FO000000</span>



                        <span class="ftm-badge bg-blue-soft text-blue" id="ftm-details-team-badge">WpCraft</span>



                        <span id="ftm-details-current-status" class="ftm-badge">-</span>



                    </div>



                    <h3 id="ftm-details-client-title" style="margin:0; font-size:18px; font-weight:700;">Project Details</h3>



                </div>



                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>



            </div>



            <div class="ftm-modal-body">



                <!-- 1. Top 4 Financial & Timeline KPI Cards -->



                <div class="ftm-details-kpi-grid">



                    <div class="ftm-details-kpi-card">



                        <div class="ftm-details-kpi-label">Gross Order Value</div>



                        <div class="ftm-details-kpi-val text-blue" id="ftm-details-gross">$0.00</div>



                        <div class="ftm-details-kpi-sub" id="ftm-details-fee-calc">Marketplace Fee (20%): -$0.00</div>



                    </div>



                    <div class="ftm-details-kpi-card ftm-details-kpi-payout">



                        <div class="ftm-details-kpi-label">Net Delivery Payout</div>



                        <div class="ftm-details-kpi-val text-emerald" id="ftm-details-net">$0.00</div>



                        <div class="ftm-details-kpi-sub text-emerald">Agency Net Earnings</div>



                    </div>



                    <div class="ftm-details-kpi-card">



                        <div class="ftm-details-kpi-label">Delivery Deadline</div>



                        <div class="ftm-details-kpi-val" id="ftm-details-delivery" style="font-size:16px;">-</div>



                        <div class="ftm-details-kpi-sub" id="ftm-details-remaining">-</div>



                    </div>



                    <div class="ftm-details-kpi-card">



                        <div class="ftm-details-kpi-label">Client Review</div>



                        <div class="ftm-details-kpi-val" id="ftm-details-rating-display">-</div>



                        <div class="ftm-details-kpi-sub">Post-Delivery Rating</div>



                    </div>



                </div>







                <!-- 1b. Combined order breakdown (shown only for shared orders) -->
                <div id="ftm-details-combined" class="ftm-details-combined hidden"></div>

                <!-- Google Sheet & Resources Banner -->
                <div id="ftm-details-resources-banner" style="margin-bottom:14px; padding:10px 14px; border-radius:10px; background:#f0fdfa; border:1px solid #99f6e4; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:18px;"></span>
                        <div>
                            <span style="font-weight:700; font-size:12.5px; color:#0f766e;">Google Sheet & Requirements:</span>
                            <span id="ftm-details-sheet-name" style="font-size:12px; color:#115e59; margin-left:6px; font-weight:500;">No sheet attached</span>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span id="ftm-details-repeat-badge" class="ftm-badge bg-blue-soft text-blue hidden" style="font-weight:700;"> Repeat Client</span>
                        <a id="ftm-details-sheet-btn" href="#" target="_blank" rel="noopener noreferrer" class="ftm-btn ftm-btn-primary ftm-btn-xs hidden" style="display:inline-flex; align-items:center; gap:5px; font-weight:600; background:#0d9488; border-color:#0d9488;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                            <span>Open Project Sheet</span>
                        </a>
                        <a id="ftm-details-order-link-btn" href="#" target="_blank" rel="noopener noreferrer" class="ftm-btn ftm-btn-secondary ftm-btn-xs hidden" style="display:inline-flex; align-items:center; gap:5px; font-weight:600;">
                            <span>Fiverr Order</span>
                        </a>
                        <?php if ($is_admin || $is_leader || $is_deputy): ?>
                        <button type="button" id="ftm-details-view-tracker-snaps-btn" class="ftm-btn ftm-btn-secondary ftm-btn-xs" style="display:inline-flex; align-items:center; gap:5px; font-weight:600;" title="View Screen Captures & Time Tracking">
                            📸 <span>Work Screenshots</span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Delivery & Extension Tracking Banner -->
                <div id="ftm-details-extension-banner" class="ftm-details-combined" style="margin-bottom:14px; padding:12px 16px; border-radius:10px; background:var(--ftm-card-bg, #ffffff); border:1px solid var(--ftm-border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span style="font-size:22px;">⏱️</span>
                        <div>
                            <div style="font-size:11px; font-weight:700; color:var(--ftm-text-secondary); text-transform:uppercase; letter-spacing:0.04em;">Delivery & Extension Tracking</div>
                            <div id="ftm-details-extension-text" style="font-size:13px; font-weight:600; color:var(--ftm-text-primary); margin-top:2px;">On-Time Delivery (No extensions recorded)</div>
                            <div id="ftm-details-extension-reason-wrap" class="hidden" style="font-size:12px; color:var(--ftm-text-muted); margin-top:3px;"><strong>Reason:</strong> <span id="ftm-details-extension-reason-text">-</span></div>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span id="ftm-details-delivery-badge" class="ftm-badge bg-emerald-soft text-emerald" style="font-weight:700;">On-Time Delivery</span>
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs" id="ftm-details-btn-extend-delivery">+ Extend Delivery</button>
                        <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-xs text-rose" id="ftm-details-btn-reset-extension" style="border-color:rgba(244,63,94,0.4);" title="Clear / Reset accidental extension">↺ Reset Extension</button>
                    </div>
                </div>

                <!-- Team Leader Quality Assurance & Delivery Approval Gates -->
                <div id="ftm-details-approvals-card" class="ftm-details-approvals-section" style="margin-bottom:14px; padding:14px 16px; border-radius:12px; background:var(--ftm-card-bg, #ffffff); border:1px solid var(--ftm-border); box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:20px;">🛡️</span>
                            <div>
                                <h4 style="margin:0; font-size:13px; font-weight:700; color:var(--ftm-text-primary); text-transform:uppercase; letter-spacing:0.04em;">Team Leader Quality Gates & Delivery Approvals</h4>
                                <div style="font-size:11.5px; color:var(--ftm-text-muted);">Dedicated 4-step quality assurance and authorization pipeline required for squad leadership</div>
                            </div>
                        </div>
                        <div id="ftm-details-approvals-badge-wrap">
                            <span id="ftm-details-approvals-status-badge" class="ftm-badge bg-amber-soft text-amber" style="font-weight:700; font-size:11.5px;">0 of 4 Approved</span>
                        </div>
                    </div>
                    <div id="ftm-details-gates-grid" class="ftm-gates-grid">
                        <!-- Dynamically populated by renderLeaderQualityGates() -->
                    </div>
                </div>

                <!-- 2. Dual Panel: Specifications (Left) & Assigned Team Members (Right) -->



                <div class="ftm-details-spec-team-grid">



                    <!-- Left: Specifications -->



                    <div class="ftm-spec-card">



                        <div class="ftm-spec-card-title">



                            <div style="display:flex; align-items:center; gap:6px;">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ftm-primary);"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>



                                <span>Order Specifications</span>



                            </div>



                        </div>



                        <div class="ftm-spec-rows">



                            <div class="ftm-spec-row">



                                <span class="ftm-spec-label">Profile</span>



                                <span class="ftm-spec-value" id="ftm-details-profile">-</span>



                            </div>



                            <div class="ftm-spec-row">



                                <span class="ftm-spec-label">Client Username</span>



                                <span class="ftm-spec-value ftm-font-semibold" id="ftm-details-client-username">-</span>



                            </div>



                            <div class="ftm-spec-row">



                                <span class="ftm-spec-label">Order Number</span>



                                <span class="ftm-spec-value"><code id="ftm-details-spec-order-id" class="ftm-code-badge">-</code></span>



                            </div>



                        </div>



                    </div>







                    <!-- Right: Assigned Team Members -->



                    <div class="ftm-spec-card">



                        <div class="ftm-spec-card-title ftm-flex-between">



                            <div style="display:flex; align-items:center; gap:6px;">



                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ftm-primary);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>



                                <span>Assigned Team Members</span>



                            </div>



                            <span id="ftm-details-edit-assignees-container"></span>



                        </div>



                        <div id="ftm-details-member" class="ftm-assignees-list">



                            <!-- Populated dynamically via JS with spacious cards -->



                        </div>



                    </div>



                </div>







                <!-- 3. Status Changer Strip -->



                <div class="ftm-status-changer-strip">



                    <div class="ftm-quick-status-form">



                        <span class="ftm-font-semibold ftm-text-sm">Change Status:</span>



                        <select id="ftm-details-new-status-select" class="ftm-select ftm-select-sm" style="min-width:180px;">



                            <option value="nra">NRA (Not Ready / Assigned)</option>



                            <option value="wip">WIP (Work In Progress)</option>



                            <option value="delivery_submitted">Delivery Submitted</option>



                            <option value="revision">Revision</option>



                            <option value="delivery_accepted">Delivery Accepted</option>



                            <option value="cancelled">Cancelled</option>



                        </select>



                        <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-details-update-status-btn">Update Status</button>



                    </div>



                    <div style="display:flex; gap:8px;">



                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm hidden" id="ftm-details-edit-btn">Edit Project</button>



                        <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-sm ftm-text-danger hidden" id="ftm-details-delete-btn">Delete</button>



                    </div>



                </div>

                <!-- Monthly Settlement & Delivery Attribution in Details Modal -->
                <div id="ftm-details-settle-card" class="ftm-form-group hidden" style="background:var(--ftm-bg, #f8fafc); border:1px solid var(--ftm-border); border-radius:10px; padding:14px; margin-bottom:16px;">
                    <div style="font-weight:700; font-size:12px; color:var(--ftm-text-secondary); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                        <span style="display:inline-flex; align-items:center; gap:6px;">
                            <span>📦</span> Delivery Date & Monthly Report Settlement
                        </span>
                        <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-xs" id="ftm-details-save-settle-btn" style="padding:4px 12px; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                            <span>💾</span> Save Dates & Tips
                        </button>
                    </div>
                    <div id="ftm-details-cross-month-callout" class="hidden" style="margin-bottom:12px; padding:10px 14px; background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); border-radius:8px; font-size:12px; line-height:1.45; color:var(--ftm-text-primary);">
                    </div>
                    <div class="ftm-form-row ftm-grid-3" style="margin-bottom:0; gap:12px;">
                        <div class="ftm-form-group" style="margin-bottom:0;">
                            <label class="ftm-label" for="ftm-details-delivered-date" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <span style="display:inline-flex; align-items:center; gap:6px;">
                                    <span>📦</span> Delivered Date
                                </span>
                                <span class="ftm-badge ftm-badge-sm" style="font-size:10px; font-weight:600; padding:1px 6px; background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.25);">Report Settle</span>
                            </label>
                            <input type="date" id="ftm-details-delivered-date" class="ftm-input">
                            <small class="ftm-text-muted" style="font-size:11px; display:block; margin-top:5px; line-height:1.4;">Sets which monthly report credits this delivery (e.g. Oct 2026).</small>
                        </div>
                        <div class="ftm-form-group" style="margin-bottom:0;">
                            <label class="ftm-label" for="ftm-details-tip-amount" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <span style="display:inline-flex; align-items:center; gap:6px;">
                                    <span>💵</span> Client Tip ($)
                                </span>
                                <span class="ftm-badge ftm-badge-sm" style="font-size:10px; font-weight:600; padding:1px 6px; background:rgba(59,130,246,0.12); color:#3b82f6; border:1px solid rgba(59,130,246,0.25);">Tip Amount</span>
                            </label>
                            <input type="number" step="0.01" min="0" id="ftm-details-tip-amount" class="ftm-input" placeholder="0.00">
                            <small class="ftm-text-muted" style="font-size:11px; display:block; margin-top:5px; line-height:1.4;">Gross tip paid by client (80% net credited).</small>
                        </div>
                        <div class="ftm-form-group" style="margin-bottom:0;">
                            <label class="ftm-label" for="ftm-details-tip-date" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <span style="display:inline-flex; align-items:center; gap:6px;">
                                    <span>💰</span> Tip Received Date
                                </span>
                                <span class="ftm-badge ftm-badge-sm" style="font-size:10px; font-weight:600; padding:1px 6px; background:rgba(245,158,11,0.12); color:#f59e0b; border:1px solid rgba(245,158,11,0.25);">Credit Month</span>
                            </label>
                            <input type="date" id="ftm-details-tip-date" class="ftm-input">
                            <small class="ftm-text-muted" style="font-size:11px; display:block; margin-top:5px; line-height:1.4;">For tips received in a new month (e.g. Nov for an Oct order).</small>
                        </div>
                    </div>
                </div>

                <div id="ftm-details-tip-box" class="ftm-details-tip-box hidden">
                    <label class="ftm-label" for="ftm-details-tip-input">Client Tip ($)</label>
                    <div class="ftm-details-tip-row">
                        <input type="number" step="0.01" min="0" id="ftm-details-tip-input" class="ftm-input" placeholder="0.00">
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-details-tip-save">Save tip</button>
                    </div>
                    <small class="ftm-text-muted">Enter the full tip the client paid; reports count 80% after the marketplace fee.</small>
                </div>

                <!-- On-Time Delivery Toggle Card for Delivered/Completed Projects -->
                <div id="ftm-details-ontime-card" class="ftm-card hidden" style="margin-bottom:14px; padding:12px 14px; background:rgba(16,185,129,0.04); border:1px solid rgba(16,185,129,0.2); border-radius:10px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <div>
                        <div style="font-weight:700; font-size:12.5px; color:var(--ftm-text-main); display:flex; align-items:center; gap:6px;">
                            <span>⏱️</span> On-Time Delivery Status
                        </div>
                        <div class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">Check whether this completed project was delivered on-time (Leaders &amp; Admin editable).</div>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; margin:0;" class="ftm-ontime-label">
                            <input type="checkbox" id="ftm-details-ontime-checkbox" style="width:16px; height:16px; accent-color:#10b981; cursor:pointer;">
                            <span id="ftm-details-ontime-badge" class="ftm-badge bg-emerald-soft text-emerald ftm-badge-sm" style="font-weight:700;">✓ On-Time</span>
                        </label>
                    </div>
                </div>

                <!-- 4. Review Feedback Box (Visible on delivery_accepted) -->
                <div id="ftm-details-review-section" class="ftm-details-review-card hidden" style="margin-bottom:18px; padding:14px; background:var(--ftm-bg, #f8fafc); border:1px solid var(--ftm-border); border-radius:10px;">
                    <div style="font-weight:700; font-size:12px; color:var(--ftm-text-secondary); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                        <span style="display:inline-flex; align-items:center; gap:6px;">
                            <span>⭐</span> Client Review & Rating Details
                        </span>
                        <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-xs" id="ftm-details-save-review-btn" style="padding:4px 12px; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                            <span>💾</span> Save Review & Rating
                        </button>
                    </div>

                    <div style="display:flex; gap:14px; align-items:center; flex-wrap:wrap;">
                        <div class="ftm-form-group" style="margin:0;">
                            <label class="ftm-label" for="ftm-details-client-reviewed">Review Left by Client?</label>
                            <select id="ftm-details-client-reviewed" class="ftm-select ftm-select-sm">
                                <option value="no" selected>No (Order completed without review)</option>
                                <option value="yes">Yes (Review & Rating Provided)</option>
                            </select>
                        </div>

                        <div id="ftm-details-star-rating-wrap" class="ftm-form-group hidden" style="margin:0;">



                            <label class="ftm-label" for="ftm-details-star-rating">Star Rating</label>



                            <div class="ftm-rate" data-ftm-rate>
                                <div class="ftm-rate-top">
                                    <div class="ftm-rate-stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                                    <div class="ftm-rate-readout"><input type="number" id="ftm-details-star-rating" class="ftm-rate-num" min="1" max="5" step="0.1" value="5.0" inputmode="decimal" aria-label="Star rating"><span>/ 5.0</span></div>
                                </div>
                                <input type="range" class="ftm-rate-range" min="1" max="5" step="0.1" value="5" aria-label="Star rating slider">
                                <div class="ftm-rate-chips">
                                    <button type="button" data-v="5">5.0</button><button type="button" data-v="4.5">4.5</button><button type="button" data-v="4">4.0</button><button type="button" data-v="3.5">3.5</button><button type="button" data-v="3">3.0</button><button type="button" data-v="2">2.0</button><button type="button" data-v="1">1.0</button>
                                </div>
                            </div>



                        </div>



                    </div>



                    <div id="ftm-details-review-feedback-wrap" class="hidden" style="margin-top:10px;">



                        <label class="ftm-label" for="ftm-details-review-feedback">Client Review Feedback Text</label>



                        <textarea id="ftm-details-review-feedback" class="ftm-textarea" rows="2" placeholder="Paste client's review comment here..."></textarea>



                    </div>



                </div>







                <!-- 5. Tabs: Timeline vs Notes vs Daily Progress -->



                <div class="ftm-details-tabs" style="display:flex; align-items:center;">



                    <button type="button" class="ftm-detail-tab-btn active" data-tab="timeline">Status Timeline</button>



                    <button type="button" class="ftm-detail-tab-btn" data-tab="notes">Project Notes (<span id="ftm-notes-count">0</span>)</button>



                    <button type="button" class="ftm-detail-tab-btn" data-tab="dailylogs">Daily Progress (<span id="ftm-dailylogs-count">0</span>)</button>
                    <button type="button" class="ftm-detail-tab-btn" data-tab="tasks">Tasks & Milestones (<span id="ftm-details-tasks-count">0</span>)</button>



                
                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs" id="ftm-project-open-chat-btn" style="margin-left:auto; display:inline-flex; align-items:center; gap:6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <span>Order Chat</span>
                    </button>
</div>







                <!-- Timeline Tab Content -->



                <div id="ftm-tab-timeline" class="ftm-tab-panel active">



                    <div id="ftm-timeline-list" class="ftm-timeline">



                        <!-- Populated dynamically via JS -->



                    </div>



                </div>







                <!-- Notes Tab Content -->



                <div id="ftm-tab-notes" class="ftm-tab-panel hidden">



                    <div class="ftm-new-note-box" style="margin-bottom:16px;">



                        <textarea id="ftm-new-note-text" class="ftm-textarea" rows="2" placeholder="Write an internal project update or note..."></textarea>



                        <div style="display:flex; justify-content:flex-end; margin-top:8px;">



                            <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-submit-note-btn">Add Note</button>



                        </div>



                    </div>



                    <div id="ftm-notes-feed-list" class="ftm-notes-feed">



                        <!-- Populated dynamically via JS -->



                    </div>



                </div>







                <!-- Daily Progress Logs Tab Content -->



                <div id="ftm-tab-dailylogs" class="ftm-tab-panel hidden">



                    <div class="ftm-details-dailylogs-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding:12px 14px; background:var(--ftm-surface-elevated, var(--ftm-card-bg)); border:1px solid var(--ftm-border); border-radius:8px;">
                        <div>
                            <div style="font-weight:700; font-size:13.5px; color:var(--ftm-text-primary);">Logged Daily Work Progress</div>
                            <div style="font-size:12px; color:var(--ftm-text-muted); margin-top:2px;">Chronological daily standup reports filed by team members for this project</div>
                        </div>
                        <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-project-log-standup-btn" style="white-space:nowrap; display:inline-flex; align-items:center; gap:6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            <span>Log Today's Progress</span>
                        </button>
                    </div>

                    <div id="ftm-dailylogs-feed-list" class="ftm-notes-feed">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>

                <!-- Tasks & Milestones Tab Content -->
                <div id="ftm-tab-tasks" class="ftm-tab-panel hidden">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding:12px 14px; background:var(--ftm-surface-elevated, var(--ftm-card-bg)); border:1px solid var(--ftm-border); border-radius:8px;">
                        <div>
                            <div style="font-weight:700; font-size:13.5px; color:var(--ftm-text-primary);">Pipeline Tasks &amp; Deliverables</div>
                            <div style="font-size:12px; color:var(--ftm-text-muted); margin-top:2px;">Automated standard milestones (5h Initial convo, 2-3d 1st draft, fixing, final update) &middot; completing a task auto-logs to Daily Progress</div>
                        </div>
                        <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-xs" id="ftm-details-add-task-btn" style="display:inline-flex; align-items:center; gap:5px; font-weight:600;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            <span>Add Task</span>
                        </button>
                    </div>
                    <div id="ftm-details-tasks-list" class="ftm-tasks-container" style="display:flex; flex-direction:column; gap:8px;">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>
            </div>
            <div class="ftm-modal-footer" style="display:flex; justify-content:space-between; align-items:center;">
                <button type="button" class="ftm-btn ftm-btn-sm" id="ftm-details-btn-delete-order" style="background:var(--ftm-rose-soft); color:var(--ftm-rose); border:1px solid rgba(244,63,94,0.3); font-weight:600;">️ Delete Order</button>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Close</button>
                    <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-details-btn-edit-order">✏️ Edit Order</button>
                </div>
            </div>



        </div>



    </div>







    <!-- MODAL: EDIT MEMBER PROFILE (INFO, JOINING DATE, SKILLS, BIO) -->



    <div id="ftm-modal-edit-member-profile" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog ftm-modal-md">



            <div class="ftm-modal-header">



                <h3 id="ftm-modal-edit-member-title">Edit Professional Profile</h3>



                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>



            </div>



            <form id="ftm-form-edit-member-profile" class="ftm-modal-body">



                <input type="hidden" id="ftm-edit-profile-user-id" value="">



                



                <!-- 1-Time Email Update Container -->



                <div class="ftm-form-group" style="background:var(--ftm-bg, #f8fafc); border:1px solid var(--ftm-border); border-radius:10px; padding:12px; margin-bottom:14px;">



                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">



                        <label class="ftm-label" for="ftm-edit-profile-email" style="margin:0; font-weight:700;">Official Work Email Address *</label>



                        <span id="ftm-profile-email-badge" class="ftm-badge bg-emerald-soft text-emerald hidden" style="font-size:11px;"> Verified & Locked</span>



                    </div>



                    <input type="email" id="ftm-edit-profile-email" class="ftm-input" placeholder="e.g. yourname@agencyfbinternational.com" required>



                    <div id="ftm-profile-email-hint" class="ftm-text-muted ftm-text-xs" style="margin-top:6px; line-height:1.5;">



                        <span class="text-amber" style="font-weight:600;">⚠️ One-Time Self Update:</span> You can only set your official email address once. Once saved, it will be locked to prevent unauthorized changes.



                    </div>



                </div>

                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-edit-profile-dayoff">Designated Weekly Day-Off</label>
                    <select id="ftm-edit-profile-dayoff" class="ftm-select">
                        <option value="friday">Friday</option>
                        <option value="saturday">Saturday</option>
                        <option value="sunday">Sunday</option>
                        <option value="monday">Monday</option>
                        <option value="tuesday">Tuesday</option>
                        <option value="wednesday">Wednesday</option>
                        <option value="thursday">Thursday</option>
                    </select>
                    <div class="ftm-text-muted ftm-text-xs" style="margin-top:4px;">Weekly recurring rest day for automated attendance and work scheduling.</div>
                </div>







                <div class="ftm-form-grid ftm-form-grid-2">



                    <div class="ftm-form-group">



                        <label class="ftm-label" for="ftm-edit-profile-name">Full Display Name *</label>



                        <input type="text" id="ftm-edit-profile-name" class="ftm-input" required>



                    </div>



                    <div class="ftm-form-group">



                        <label class="ftm-label" for="ftm-edit-profile-callsign">Call Sign / Nickname</label>



                        <input type="text" id="ftm-edit-profile-callsign" class="ftm-input" placeholder="e.g. Phoenix / Maverick">



                    </div>



                </div>







                <div class="ftm-form-grid ftm-form-grid-2">



                    <div class="ftm-form-group">



                        <label class="ftm-label" for="ftm-edit-profile-title">Professional Title / Specialty *</label>



                        <input type="text" id="ftm-edit-profile-title" class="ftm-input" placeholder="e.g. Custom Theme & Plugin Developer | Full Stack" required>



                    </div>



                    <div class="ftm-form-group">



                        <label class="ftm-label" for="ftm-edit-profile-joining">Joining Date *</label>



                        <input type="text" id="ftm-edit-profile-joining" class="ftm-input" placeholder="e.g. 15 Jan 2024" required>



                    </div>



                </div>



                <div class="ftm-form-group">

                    <label class="ftm-label" for="ftm-edit-profile-birthday">Date of Birth (Birthday) 🎂</label>

                    <input type="date" id="ftm-edit-profile-birthday" class="ftm-input">

                    <span class="ftm-text-muted ftm-text-xs" style="margin-top:3px; display:block;">Specialist's birthday for automated agency celebration notices.</span>

                </div>



                <div class="ftm-form-group">



                    <label class="ftm-label" for="ftm-edit-profile-skills">Skills & Expertise (comma separated)</label>



                    <input type="text" id="ftm-edit-profile-skills" class="ftm-input" placeholder="WordPress, Custom Themes, Plugin Development, PHP, WooCommerce">



                    <span class="ftm-text-muted ftm-text-xs" style="margin-top:3px; display:block;">Enter skills separated by commas. These will render as skill badges on your profile.</span>



                </div>







                <div class="ftm-form-group">



                    <label class="ftm-label" for="ftm-edit-profile-bio">Professional Bio</label>



                    <textarea id="ftm-edit-profile-bio" class="ftm-textarea" rows="3" placeholder="Brief professional overview of your background, technical focus, and agency responsibilities."></textarea>



                </div>







                <div class="ftm-form-grid ftm-form-grid-2">



                    <div class="ftm-form-group">



                        <label class="ftm-label" for="ftm-edit-profile-avatar">Custom Avatar URL (Optional)</label>



                        <input type="url" id="ftm-edit-profile-avatar" class="ftm-input" placeholder="https://...">



                    </div>



                    <div class="ftm-form-group">



                        <label class="ftm-label" for="ftm-edit-profile-password">Set Personal Password (Optional)</label>



                        <input type="password" id="ftm-edit-profile-password" class="ftm-input" placeholder="Leave blank to keep current" autocomplete="new-password">



                    </div>



                </div>







                <div class="ftm-modal-footer" style="padding:16px 0 0; margin-top:16px; border-top:1px solid var(--ftm-border); display:flex; justify-content:flex-end; gap:8px;">



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Cancel</button>



                    <button type="submit" class="ftm-btn ftm-btn-primary" id="ftm-btn-save-member-profile">Save Changes</button>



                </div>



            </form>



        </div>



    </div>







    <!-- MODAL: ADD CAREER MILESTONE -->
    <div id="ftm-modal-add-milestone" class="ftm-modal-backdrop hidden">
        <div class="ftm-modal-dialog ftm-modal-sm">
            <div class="ftm-modal-header">
                <h3 class="ftm-modal-title">Add Career Milestone</h3>
                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>
            </div>
            <form id="ftm-form-add-milestone" class="ftm-modal-body">
                <input type="hidden" id="ftm-milestone-user-id" value="">
                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-milestone-title">Milestone Title *</label>
                    <input type="text" id="ftm-milestone-title" class="ftm-input" placeholder="e.g. Completed 10+ Projects Milestone" required>
                </div>
                <div class="ftm-form-grid ftm-form-grid-2">
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-milestone-tag">Category / Tag *</label>
                        <select id="ftm-milestone-tag" class="ftm-select" required>
                            <option value="Milestone">Milestone</option>
                            <option value="Joining">Joining</option>
                            <option value="Project">Project</option>
                            <option value="Skill Growth">Skill Growth</option>
                            <option value="Promotion">Promotion</option>
                            <option value="Quality Award">Quality Award</option>
                        </select>
                    </div>
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-milestone-date">Milestone Date *</label>
                        <input type="text" id="ftm-milestone-date" class="ftm-input" placeholder="e.g. 15 Aug 2024" required>
                    </div>
                </div>
                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-milestone-color">Status Badge Color</label>
                    <select id="ftm-milestone-color" class="ftm-select">
                        <option value="emerald">Emerald Green (Success / Milestone)</option>
                        <option value="blue" selected>Royal Blue (Project / Active)</option>
                        <option value="purple">Purple (Advanced / Achievement)</option>
                        <option value="amber">Amber / Orange (Skill Growth)</option>
                    </select>
                </div>
                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-milestone-desc">Description *</label>
                    <textarea id="ftm-milestone-desc" class="ftm-textarea" rows="3" placeholder="Brief description of this achievement or milestone" required></textarea>
                </div>
                <div class="ftm-modal-footer" style="padding:16px 0 0; margin-top:16px; border-top:1px solid var(--ftm-border); display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Cancel</button>
                    <button type="submit" class="ftm-btn ftm-btn-primary" id="ftm-btn-save-milestone">Save Milestone</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD / EDIT FIVERR PROFILE -->



    <div id="ftm-modal-profile" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog">



            <div class="ftm-modal-header">



                <h3 id="ftm-modal-profile-title">Profile</h3>



                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>



            </div>



            <form id="ftm-profile-form" class="ftm-modal-body">



                <input type="hidden" id="ftm-form-profile-id" value="">



                <div class="ftm-form-group">



                    <label class="ftm-label" for="ftm-profile-name-input">Profile Name *</label>



                    <input type="text" id="ftm-profile-name-input" class="ftm-input" placeholder="e.g. DesignPro_FB" required>



                </div>



                <div class="ftm-form-group">



                    <label class="ftm-label" for="ftm-profile-url-input">Profile URL</label>



                    <input type="url" id="ftm-profile-url-input" class="ftm-input" placeholder="https://">



                </div>



                <div class="ftm-form-group">



                    <label class="ftm-label" for="ftm-profile-status-input">Status</label>



                    <select id="ftm-profile-status-input" class="ftm-select">



                        <option value="active">Active</option>



                        <option value="inactive">Inactive</option>



                    </select>



                </div>



                <div class="ftm-modal-footer" style="padding:16px 0 0 0; margin-top:16px; border-top:1px solid var(--ftm-border); display:flex; justify-content:space-between;">



                    <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-sm ftm-text-danger hidden" id="ftm-delete-profile-btn">Delete Profile</button>



                    <div style="display:flex; gap:8px; margin-left:auto;">



                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-modal-close-btn">Cancel</button>



                        <button type="submit" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-save-profile-btn">Save Profile</button>



                    </div>



                </div>



            </form>



        </div>



    </div>







    <!-- MODAL: MEMBER ACTIVITY REPORT -->



    <div id="ftm-modal-member-activity" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog ftm-modal-lg ftm-act-modal-dialog">



            <div class="ftm-modal-header ftm-act-modal-header">



                <div class="ftm-act-member-info">



                    <div class="ftm-act-avatar-wrap">



                        <img src="" id="ftm-activity-member-avatar" class="ftm-avatar" alt="">



                    </div>



                    <div class="ftm-act-member-meta">



                        <div class="ftm-act-name-row">



                            <h3 id="ftm-activity-member-name" class="ftm-act-member-name">Member Name</h3>



                            <span id="ftm-activity-member-custom-id" class="ftm-badge ftm-badge-outline ftm-badge-sm">#00</span>



                        </div>



                        <div class="ftm-act-details-row">



                            <span id="ftm-activity-member-badge" class="ftm-badge ftm-badge-outline ftm-badge-xs">Team</span>



                            <span class="ftm-act-dot">&bull;</span>



                            <span id="ftm-activity-member-email" class="ftm-act-email">email</span>



                        </div>



                    </div>



                </div>



                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn" aria-label="Close">&times;</button>



            </div>



            <div class="ftm-modal-body ftm-act-modal-body">



                <!-- Period Controls -->



                <div class="ftm-act-toolbar">



                    <div class="ftm-btn-group ftm-period-btn-group">



                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-period-btn active" data-period="current_month">Current Month</button>



                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-period-btn" data-period="last_month">Last Month</button>



                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-period-btn" data-period="all_time">All Time</button>



                    </div>



                    <span id="ftm-activity-period-label" class="hidden">Current Month</span>



                </div>
                <div id="ftm-activity-archive-note" class="ftm-act-archive-note hidden"></div>







                <!-- 2-Tier Symmetrical Metrics Box -->



                <div class="ftm-act-metrics-panel">



                    <!-- Tier 1: Primary Outcome Highlights (3 Columns: 100% Symmetrical) -->



                    <div class="ftm-act-hero-metrics">



                        <div class="ftm-act-metric-card ftm-act-hero-card">



                            <span class="ftm-act-m-label">Total Assigned</span>



                            <span class="ftm-act-m-val" id="ftm-act-total">0</span>



                            <span class="ftm-act-m-sub" id="ftm-act-total-sub">All Work Items</span>



                        </div>



                        <div class="ftm-act-metric-card ftm-act-hero-card ftm-act-hero-emerald">



                            <span class="ftm-act-m-label">Delivered</span>



                            <span class="ftm-act-m-val text-emerald" id="ftm-act-accepted">0</span>



                            <span class="ftm-act-m-sub" id="ftm-act-accepted-sub">Accepted Milestones</span>



                        </div>



                        <div class="ftm-act-metric-card ftm-act-hero-card ftm-act-hero-amber">



                            <span class="ftm-act-m-label">Satisfaction</span>



                            <span class="ftm-act-m-val text-amber" id="ftm-act-rating">5.0 ★</span>



                            <span class="ftm-act-m-sub" id="ftm-act-reviews-count">0 Reviews</span>



                        </div>



                    </div>







                    <!-- Tier 2: Production Pipeline Breakdown (4 Columns: 100% Symmetrical) -->



                    <div class="ftm-act-pipeline-metrics">



                        <div class="ftm-act-metric-card ftm-act-pipe-card">



                            <span class="ftm-act-pipe-dot dot-amber"></span>



                            <div class="ftm-act-pipe-info">



                                <span class="ftm-act-m-val text-amber" id="ftm-act-active">0</span>



                                <span class="ftm-act-pipe-label">Active</span>



                            </div>



                        </div>



                        <div class="ftm-act-metric-card ftm-act-pipe-card">



                            <span class="ftm-act-pipe-dot dot-blue"></span>



                            <div class="ftm-act-pipe-info">



                                <span class="ftm-act-m-val text-blue" id="ftm-act-submitted">0</span>



                                <span class="ftm-act-pipe-label">Submitted</span>



                            </div>



                        </div>



                        <div class="ftm-act-metric-card ftm-act-pipe-card">



                            <span class="ftm-act-pipe-dot dot-purple"></span>



                            <div class="ftm-act-pipe-info">



                                <span class="ftm-act-m-val text-purple" id="ftm-act-revision">0</span>



                                <span class="ftm-act-pipe-label">Revision</span>



                            </div>



                        </div>



                        <div class="ftm-act-metric-card ftm-act-pipe-card">
                            <span class="ftm-act-pipe-dot dot-rose"></span>
                            <div class="ftm-act-pipe-info">
                                <span class="ftm-act-m-val text-rose" id="ftm-act-cancelled">0</span>
                                <span class="ftm-act-pipe-label">Cancelled</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tier 3: Quality & Precision Tracking -->
                    <div class="ftm-act-pipeline-metrics" style="margin-top:10px;">
                        <div class="ftm-act-metric-card ftm-act-pipe-card">
                            <span class="ftm-act-pipe-dot dot-amber"></span>
                            <div class="ftm-act-pipe-info">
                                <span class="ftm-act-m-val text-amber" id="ftm-act-extensions">0</span>
                                <span class="ftm-act-pipe-label">Extensions</span>
                            </div>
                        </div>
                        <div class="ftm-act-metric-card ftm-act-pipe-card">
                            <span class="ftm-act-pipe-dot dot-emerald"></span>
                            <div class="ftm-act-pipe-info">
                                <span class="ftm-act-m-val text-emerald" id="ftm-act-ontime">0</span>
                                <span class="ftm-act-pipe-label">On-Time</span>
                            </div>
                        </div>
                        <div class="ftm-act-metric-card ftm-act-pipe-card">
                            <span class="ftm-act-pipe-dot dot-rose"></span>
                            <div class="ftm-act-pipe-info">
                                <span class="ftm-act-m-val text-rose" id="ftm-act-late">0</span>
                                <span class="ftm-act-pipe-label">Late Orders</span>
                            </div>
                        </div>
                        <div class="ftm-act-metric-card ftm-act-pipe-card">
                            <span class="ftm-act-pipe-dot dot-purple"></span>
                            <div class="ftm-act-pipe-info">
                                <span class="ftm-act-m-val text-purple" id="ftm-act-tips">$0.00</span>
                                <span class="ftm-act-pipe-label">Tips</span>
                            </div>
                        </div>
                    </div>



                </div>







                <!-- Assigned Projects Section -->



                <div class="ftm-act-projects-header">



                    <div class="ftm-act-projects-title-wrap">



                        <span class="ftm-act-section-title">Assigned Projects</span>



                        <span class="ftm-badge ftm-badge-outline ftm-badge-sm" id="ftm-activity-projects-count">0 projects</span>



                    </div>



                    <span class="ftm-badge bg-emerald-soft text-emerald ftm-badge-sm" id="ftm-activity-completion-badge">100% Completion Rate</span>



                </div>







                <div class="ftm-table-responsive ftm-activity-projects-desktop-table" style="max-height:340px; overflow-y:auto; overflow-x:auto; -webkit-overflow-scrolling:touch;">
                    <table class="ftm-table ftm-table-condensed" style="min-width:760px; width:100%;">



                        <thead>



                             <tr>



                                 <th>#</th>



                                 <th>Order</th>



                                 <th>Profile</th>



                                 <th>Client</th>



                                 <th>Net Value</th>



                                 <th>Deadline</th>



                                 <th>Status</th>



                                 <th>Review</th>



                                 <th class="ftm-text-right">Action</th>



                             </tr>



                        </thead>



                        <tbody id="ftm-activity-projects-tbody">



                            <!-- Populated dynamically via JS -->



                        </tbody>



                    </table>



                </div>







                <!-- Mobile Cards for Member Activity Projects -->



                <div id="ftm-activity-projects-mobile-cards" class="ftm-activity-projects-mobile-cards"></div>



            </div>



            <div class="ftm-modal-footer ftm-act-modal-footer">



                <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Close</button>



            </div>



        </div>



    </div>







    <!-- EXTEND DELIVERY MODAL -->



    <div id="ftm-modal-extend-delivery" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog">



            <div class="ftm-modal-header">



                <div>



                    <h3 class="ftm-font-bold" style="font-size:16px; margin:0;">Extend Delivery Deadline</h3>



                    <div class="ftm-text-muted ftm-text-xs" id="ftm-extend-modal-sub" style="margin-top:2px;">Log client-agreed delivery extension</div>



                </div>



                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>



            </div>



            <form id="ftm-extend-delivery-form" class="ftm-modal-body" style="padding:20px; display:flex; flex-direction:column; gap:16px;">



                <input type="hidden" id="ftm-extend-project-id" value="">



                <div>



                    <label class="ftm-label" for="ftm-extend-date-input" style="font-size:12px; font-weight:600; margin-bottom:6px; display:block;">New Delivery Deadline</label>
                    <div class="ftm-ext-current">Current deadline: <strong id="ftm-extend-current-deadline">-</strong></div>
                    <input type="date" id="ftm-extend-date-input" class="ftm-input" required>
                    <input type="hidden" id="ftm-extend-days-input" value="3">
                    <div class="ftm-ext-summary" id="ftm-extend-summary" aria-live="polite">Pick the new deadline from the calendar.</div>



                </div>



                <div>



                    <label class="ftm-label" style="font-size:12px; font-weight:600; margin-bottom:6px; display:block;">Reason for Extension (Optional)</label>



                    <input type="text" id="ftm-extend-reason-input" class="ftm-input ftm-input-sm" placeholder="e.g. Client requested revisions, delayed client feedback...">



                </div>



                <div class="ftm-modal-footer" style="padding:16px 0 0 0; margin-top:8px; border-top:1px solid var(--ftm-border); display:flex; justify-content:flex-end; gap:8px;">



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-modal-close-btn">Cancel</button>



                    <button type="submit" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-btn-submit-extend">Extend Deadline</button>



                </div>



            </form>



        </div>



    </div>







    <!-- QUICK UPDATE PROJECT PROGRESS MODAL -->



    <div id="ftm-modal-update-progress" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog" style="max-width: 440px;">



            <div class="ftm-modal-header">



                <div>



                    <h3 class="ftm-font-bold" style="font-size:16px; margin:0;">Update Project Progress</h3>



                    <div class="ftm-text-muted ftm-text-xs" id="ftm-update-progress-project-name" style="margin-top:2px;">Adjust completion milestone</div>



                </div>



                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>



            </div>



            <form id="ftm-update-progress-form" class="ftm-modal-body" style="padding:20px; display:flex; flex-direction:column; gap:16px;">



                <input type="hidden" id="ftm-update-progress-pid" value="">



                <div>



                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">



                        <label class="ftm-label" style="font-size:13px; font-weight:600; margin:0;">Progress Completion</label>



                        <span id="ftm-update-progress-val-badge" class="ftm-badge bg-blue-soft text-blue" style="font-size:14px; font-weight:700; padding:4px 10px;">65%</span>



                    </div>



                    <input type="range" id="ftm-update-progress-slider" min="0" max="100" step="5" value="65" style="width:100%; accent-color:#2563eb; cursor:pointer;">



                    <div style="display:flex; justify-content:space-between; font-size:11px; color:var(--ftm-text-muted); margin-top:4px;">



                        <span>0%</span>



                        <span>25%</span>



                        <span>50%</span>



                        <span>75%</span>



                        <span>100%</span>



                    </div>



                </div>



                <!-- Quick Preset Percent Buttons -->



                <div style="display:flex; gap:6px; flex-wrap:wrap;">



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-prog-preset-btn" data-prog="25">25%</button>



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-prog-preset-btn" data-prog="50">50%</button>



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-prog-preset-btn" data-prog="75">75%</button>



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-prog-preset-btn" data-prog="90">90%</button>



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs ftm-prog-preset-btn" data-prog="100">100% (Done)</button>



                </div>



                <div class="ftm-modal-footer" style="padding:16px 0 0 0; margin-top:4px; border-top:1px solid var(--ftm-border); display:flex; justify-content:flex-end; gap:8px;">



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-modal-close-btn">Cancel</button>



                    <button type="submit" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-btn-submit-quick-prog">Save Progress</button>



                </div>



            </form>



        </div>



    </div>







    <!-- MODAL: QUICK ASSIGN TEAM MEMBERS -->



    <div id="ftm-modal-quick-assign" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog">



            <div class="ftm-modal-header">



                <div>



                    <h3 style="margin:0;">Assign Team Members</h3>



                    <span class="ftm-text-muted ftm-text-xs" id="ftm-quick-assign-project-order">#FO000000</span>



                </div>



                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>



            </div>



            <form id="ftm-quick-assign-form" class="ftm-modal-body">



                <input type="hidden" id="ftm-quick-assign-project-id" value="">

                <div class="ftm-form-group" style="margin-bottom:12px;">
                    <label class="ftm-label" for="ftm-quick-assign-team-select" style="font-size:12px; margin-bottom:4px; font-weight:600;">Assigned Team / Squad</label>
                    <select id="ftm-quick-assign-team-select" class="ftm-select ftm-select-sm" style="width:100%;">
                        <option value="WpCraft">WpCraft</option>
                        <option value="WpNexus">WpNexus</option>
                        <option value="WpTitans">WpTitans</option>
                    </select>
                </div>

                <div class="ftm-form-group">

                    <input type="text" id="ftm-quick-assign-search" class="ftm-input ftm-input-sm" placeholder="Filter team members by name...">

                </div>



                <div id="ftm-quick-assign-members-list" class="ftm-assignee-picker-box" style="max-height:260px; overflow-y:auto;">



                    <!-- Populated dynamically via JS -->



                </div>



                <div class="ftm-modal-footer" style="padding:16px 0 0 0; margin-top:16px; border-top:1px solid var(--ftm-border); display:flex; justify-content:flex-end; gap:8px;">



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-modal-close-btn">Cancel</button>



                    <button type="submit" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-quick-assign-submit-btn">Save Assignees</button>



                </div>



            </form>



        </div>



    </div>







    <!-- MODAL: MANAGER REVIEW & INSPECT DAILY REPORT -->



    <div id="ftm-modal-report-review" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog ftm-modal-lg" style="max-width: 860px; width: 95%; max-height: 92vh; display: flex; flex-direction: column;">



            <div class="ftm-modal-header" style="display:flex; justify-content:space-between; align-items:center; padding:18px 24px; border-bottom:1px solid var(--ftm-border); background:var(--ftm-card-bg);">



                <div style="display:flex; align-items:center; gap:12px;">



                    <img id="ftm-review-member-avatar" src="" class="ftm-avatar ftm-avatar-md" alt="">



                    <div>



                        <div style="display:flex; align-items:center; gap:8px;">



                            <h3 id="ftm-review-member-name" class="ftm-font-bold" style="font-size:17px; margin:0; color:var(--ftm-text-primary);">Member Name</h3>



                            <span id="ftm-review-team-badge" class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">Team</span>



                            <span id="ftm-review-status-badge" class="ftm-badge bg-emerald-soft text-emerald ftm-badge-xs">Approved</span>



                        </div>



                        <div style="font-size:12px; color:var(--ftm-text-muted); margin-top:2px;">



                            <span>Role: <strong id="ftm-review-member-role">Team Member</strong></span> &bull; 



                            <span>Report Date: <strong id="ftm-review-report-date">Oct 24, 2026</strong></span>



                        </div>



                    </div>



                </div>



                <button type="button" class="ftm-modal-close-icon" id="ftm-modal-review-close" style="background:none; border:none; font-size:24px; line-height:1; cursor:pointer; color:var(--ftm-text-muted);">&times;</button>



            </div>



            <div id="ftm-review-modal-body" class="ftm-modal-body" style="padding:22px 24px; overflow-y:auto; flex:1;">



                <!-- Populated dynamically via JS -->



            </div>



            <div class="ftm-modal-footer" style="padding:14px 24px; border-top:1px solid var(--ftm-border); background:var(--ftm-card-bg); display:flex; justify-content:space-between; align-items:center;">



                <div id="ftm-review-author-actions" style="display:flex; gap:8px;">



                    <!-- If viewer is author, allow editing own report -->



                </div>



                <div style="display:flex; align-items:center; gap:10px;">



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-review-close">Close</button>

                    <button type="button" class="ftm-btn ftm-btn-outline ftm-btn-sm text-rose" id="ftm-btn-review-reset" style="border-color:rgba(244,63,94,0.4);" title="Reset/cancel this report if submitted by mistake so member can submit work deliverables">
                        <span>↺ Reset Mistake</span>
                    </button>



                    <button type="button" class="ftm-btn ftm-btn-amber ftm-btn-sm" id="ftm-btn-review-revision" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; font-weight:700;">



                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>



                        <span>Request Revision</span>



                    </button>



                    <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-btn-review-approve" style="font-weight:700;">



                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>



                        <span>Approve Report</span>



                    </button>



                </div>



            </div>



        </div>



    </div>



    <!-- MODAL: DAILY REPORT REVIEW REMARKS / FEEDBACK -->
    <div id="ftm-modal-report-feedback" class="ftm-modal-backdrop hidden">
        <div class="ftm-modal-dialog" style="max-width: 520px; width: 92%;">
            <div class="ftm-modal-header" style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid var(--ftm-border); background:var(--ftm-card-bg);">
                <h3 id="ftm-modal-feedback-title" class="ftm-font-bold" style="font-size:16px; margin:0; color:var(--ftm-text-primary);">Review Report</h3>
                <button type="button" class="ftm-modal-close-icon" id="ftm-modal-feedback-close" style="background:none; border:none; font-size:22px; line-height:1; cursor:pointer; color:var(--ftm-text-muted);">&times;</button>
            </div>
            <div class="ftm-modal-body" style="padding:20px;">
                <input type="hidden" id="ftm-feedback-report-id" value="">
                <input type="hidden" id="ftm-feedback-action" value="">
                <label id="ftm-feedback-label" for="ftm-feedback-notes" style="display:block; font-size:13px; font-weight:600; color:var(--ftm-text-primary); margin-bottom:8px;">Enter Details / Remarks:</label>
                <textarea id="ftm-feedback-notes" class="ftm-textarea" rows="4" style="width:100%; border-radius:8px; font-size:13px; padding:10px 12px; resize:vertical; box-sizing:border-box;" placeholder="Enter details..."></textarea>
                <p id="ftm-feedback-helper" style="font-size:11.5px; color:var(--ftm-text-muted); margin:6px 0 0 0;">This note will be saved with the report review.</p>
            </div>
            <div class="ftm-modal-footer" style="padding:12px 20px; border-top:1px solid var(--ftm-border); background:var(--ftm-card-bg); display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-modal-feedback-cancel">Cancel</button>
                <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-modal-feedback-confirm" style="font-weight:700;">Confirm</button>
            </div>
        </div>
    </div>

    <!-- MODAL: TIME TRACKER & WORK SCREENSHOTS REVIEW GALLERY -->
    <div id="ftm-modal-tracker-gallery" class="ftm-modal-backdrop hidden">
        <div class="ftm-modal-dialog ftm-modal-lg" style="max-width: 960px; width: 95%; max-height: 92vh; display: flex; flex-direction: column;">
            <div class="ftm-modal-header" style="display:flex; justify-content:space-between; align-items:center; padding:16px 22px; border-bottom:1px solid var(--ftm-border); background:var(--ftm-card-bg);">
                <div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:20px;">📸</span>
                        <h3 id="ftm-tracker-gallery-title" class="ftm-font-bold" style="font-size:17px; margin:0; color:var(--ftm-text-primary);">Work Screenshots & Time Tracking</h3>
                        <span id="ftm-tracker-gallery-badge" class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">0 Screenshots</span>
                    </div>
                    <div style="font-size:12px; color:var(--ftm-text-muted); margin-top:2px;" id="ftm-tracker-gallery-sub">Review specialist activity captured automatically every 10 minutes.</div>
                </div>
                <button type="button" class="ftm-modal-close-icon" id="ftm-modal-tracker-gallery-close" style="background:none; border:none; font-size:24px; line-height:1; cursor:pointer; color:var(--ftm-text-muted);">&times;</button>
            </div>
            <div id="ftm-tracker-gallery-body" class="ftm-modal-body" style="padding:22px; overflow-y:auto; flex:1;">
                <div id="ftm-tracker-gallery-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:18px;">
                    <!-- Snapshots cards populated by JS -->
                </div>
            </div>
            <div class="ftm-modal-footer" style="padding:12px 22px; border-top:1px solid var(--ftm-border); background:var(--ftm-card-bg); display:flex; justify-content:space-between; align-items:center;">
                <div style="font-size:12px; color:var(--ftm-text-muted);">
                    Screenshots are captured automatically via member browser screen stream.
                </div>
                <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-tracker-gallery-close">Close Gallery</button>
            </div>
        </div>
    </div>



    <!-- MODAL: DAILY PROGRESS STANDUP SUBMISSION (MEMBER) -->



    <div id="ftm-modal-standup" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog ftm-modal-lg" style="max-width: 860px; width: 95%;">



            <div class="ftm-modal-header" style="display:flex; justify-content:space-between; align-items:center;">



                <div>



                    <h3 id="ftm-modal-standup-title" class="ftm-font-bold" style="font-size:18px; margin:0;">Update & Resubmit Daily Progress Report</h3>



                    <div class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">Review or revise daily deliverables, time allocation, and project milestones</div>



                </div>



                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>



            </div>



            <form id="ftm-standup-form" class="ftm-modal-body" style="padding:20px; display:flex; flex-direction:column; gap:16px;">



                <input type="hidden" id="ftm-standup-report-id" value="">



                <div id="ftm-standup-feedback-banner" class="ftm-standup-feedback-card hidden" style="margin-bottom:4px; margin-top:0;"></div>



                <div style="display:flex; align-items:center; justify-content:space-between; background:var(--ftm-bg, #f8fafc); border:1px solid var(--ftm-border); border-radius:10px; padding:12px 16px;">



                    <div>



                        <label class="ftm-form-label" for="ftm-standup-date" style="font-size:12px; font-weight:700; margin:0;">Reporting Date</label>



                        <div class="ftm-text-muted ftm-text-xs">Logged date for team standup review</div>



                    </div>



                    <input type="date" id="ftm-standup-date" class="ftm-input ftm-input-sm" style="width:180px; font-weight:600;" value="<?php echo esc_attr(current_time('Y-m-d')); ?>" required>



                </div>







                <!-- Standup Line Items Container -->

                <!-- Active Assigned Tasks & Milestones (Auto-integration) -->
                <div id="ftm-standup-active-tasks-wrap" class="hidden" style="background:var(--ftm-bg, #f8fafc); border:1px solid var(--ftm-border, #e2e8f0); border-radius:10px; padding:12px 16px; margin-bottom:14px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="font-weight:700; font-size:13px; color:var(--ftm-text-main, #0f172a); display:flex; align-items:center; gap:6px;">
                            <span>My Active Assigned Tasks & Milestones</span>
                            <span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs" id="ftm-standup-tasks-count-badge">0</span>
                        </div>
                        <span class="ftm-text-muted ftm-text-xs">Click [+ Done] or [+ WIP] to include in report</span>
                    </div>
                    <div id="ftm-standup-tasks-pills" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;">
                        <!-- Rendered dynamically -->
                    </div>
                </div>



                <div id="ftm-standup-items-wrap" style="display:flex; flex-direction:column; gap:16px;">



                    <!-- Project line item cards inserted dynamically -->



                </div>







                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-add-standup-project">



                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>



                        <span>Add Another Assigned Project</span>



                    </button>



                    <div class="ftm-text-muted ftm-text-xs">All deliverables sync with project progress & KPIs</div>



                </div>







                <!-- Manager Review & Feedback Section (Visible to Leader / Admin) -->



                <div id="ftm-manager-feedback-section" class="hidden" style="margin-top:10px; background:var(--ftm-card-bg, #fff); border:1px solid var(--ftm-border); border-radius:10px; padding:16px;">



                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">



                        <label class="ftm-form-label" style="font-weight:700; margin:0; display:flex; align-items:center; gap:6px;">



                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>



                            Manager Review & Feedback



                        </label>



                        <span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs" id="ftm-manager-feedback-status">Approved</span>



                    </div>



                    <textarea id="ftm-manager-feedback-input" class="ftm-textarea" rows="2" placeholder="Leave manager comment, feedback, or specify required corrections for this report..."></textarea>



                    <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:10px;">



                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm" id="ftm-btn-save-manager-comment">



                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>



                            <span>Save Comment</span>



                        </button>



                        <button type="button" class="ftm-btn ftm-btn-warning ftm-btn-sm" id="ftm-btn-request-revision">



                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>



                            <span>Request Revision</span>



                        </button>



                    </div>



                </div>







                <div class="ftm-modal-footer" style="padding:16px 0 0 0; margin-top:8px; border-top:1px solid var(--ftm-border); display:flex; justify-content:flex-end; gap:10px;">



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Close</button>



                    <button type="submit" class="ftm-btn ftm-btn-primary" id="ftm-btn-submit-standup">Submit Report to Leader</button>



                </div>



            </form>



        </div>



    </div>







    <!-- MODAL: IMPORT HISTORICAL CSV -->



    <div id="ftm-modal-import-csv" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog">



            <div class="ftm-modal-header">



                <h3>Import Past Monthly Sheet (CSV)</h3>



                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>



            </div>



            <form id="ftm-form-import-csv" class="ftm-modal-body">



                <div class="ftm-form-group">



                    <label class="ftm-label" for="ftm-import-reporting-month">Reporting Month (Key)</label>



                    <input type="text" id="ftm-import-reporting-month" class="ftm-input" value="September_2026" placeholder="e.g. September_2026 or 2026-09" required>



                    <span class="ftm-text-muted ftm-text-xs">Use the month key to store and retrieve historical records.</span>



                </div>



                <div class="ftm-form-group">



                    <label class="ftm-label" for="ftm-import-csv-file">Select CSV File</label>



                    <input type="file" id="ftm-import-csv-file" class="ftm-input" accept=".csv" required>



                    <span class="ftm-text-muted ftm-text-xs">Select exported Google Sheet CSV with standard 17 columns. Summary/footer rows are automatically filtered out.</span>



                </div>



                <div class="ftm-modal-footer" style="padding:16px 0 0 0; margin-top:16px; border-top:1px solid var(--ftm-border); display:flex; justify-content:flex-end; gap:8px;">



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-sm ftm-modal-close-btn">Cancel</button>



                    <button type="submit" class="ftm-btn ftm-btn-primary ftm-btn-sm" id="ftm-btn-submit-import-csv">Upload and Import</button>



                </div>



            </form>



        </div>



    </div>







    <!-- MODAL: DATE RANGE FILTER (CENTERED MODAL WITH QUICK PRESETS & CUSTOM RANGE) -->



    <div id="ftm-modal-date-range" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog ftm-date-range-dialog" style="max-width:440px; width:92%; margin:auto;">



            <div class="ftm-modal-header" style="padding:16px 20px; border-bottom:1px solid var(--ftm-border); display:flex; justify-content:space-between; align-items:center;">



                <div style="display:flex; align-items:center; gap:8px;">



                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--ftm-primary);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>



                    <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--ftm-text-primary);">Select Date Range</h3>



                </div>



                <button type="button" class="ftm-modal-close-icon" id="ftm-date-range-modal-close" style="background:none; border:none; font-size:24px; line-height:1; cursor:pointer; color:var(--ftm-text-muted);">&times;</button>



            </div>







            <!-- Date Range Mode Tabs (Quick Select vs Custom Range) -->



            <div class="ftm-date-range-tabs" style="display:flex; padding:8px 16px; background:var(--ftm-bg, #f8fafc); border-bottom:1px solid var(--ftm-border); gap:6px;">



                <button type="button" class="ftm-date-tab-btn active" data-tab="quick" style="flex:1; padding:8px 12px; border-radius:6px; font-size:13px; font-weight:600; border:none; cursor:pointer; transition:all 0.15s ease;">Quick Select</button>



                <button type="button" class="ftm-date-tab-btn" data-tab="custom" style="flex:1; padding:8px 12px; border-radius:6px; font-size:13px; font-weight:600; border:none; cursor:pointer; transition:all 0.15s ease;">Custom Range</button>



            </div>







            <div class="ftm-modal-body" style="padding:16px 20px; max-height:65vh; overflow-y:auto;">



                <!-- Tab 1: Quick Presets List (Reference Image 4 Screen 2) -->



                <div id="ftm-date-panel-quick" class="ftm-date-tab-panel">



                    <div class="ftm-date-quick-presets" style="display:flex; flex-direction:column; gap:6px;">



                        <button type="button" class="ftm-date-preset-row" data-preset="today">Today</button>



                        <button type="button" class="ftm-date-preset-row" data-preset="yesterday">Yesterday</button>



                        <button type="button" class="ftm-date-preset-row active" data-preset="last_7_days">Last 7 Days</button>



                        <button type="button" class="ftm-date-preset-row" data-preset="last_30_days">Last 30 Days</button>



                        <button type="button" class="ftm-date-preset-row" data-preset="this_month">This Month</button>



                        <button type="button" class="ftm-date-preset-row" data-preset="last_month">Last Month</button>



                        <button type="button" class="ftm-date-preset-row" data-preset="this_year">This Year</button>



                        <button type="button" class="ftm-date-preset-row" data-preset="all">All Time</button>



                    </div>



                </div>







                <!-- Tab 2: Custom Range Inputs (Reference Image 4 Screen 3) -->



                <div id="ftm-date-panel-custom" class="ftm-date-tab-panel hidden">



                    <div style="display:flex; flex-direction:column; gap:14px;">



                        <div class="ftm-form-group" style="margin:0;">



                            <label class="ftm-label" for="ftm-modal-date-start" style="font-size:12px; font-weight:600; color:var(--ftm-text-secondary); margin-bottom:4px; display:block;">Start Date</label>



                            <input type="date" id="ftm-modal-date-start" class="ftm-input" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--ftm-border); background:var(--ftm-card-bg); color:var(--ftm-text-primary); font-size:14px;">



                        </div>



                        <div class="ftm-form-group" style="margin:0;">



                            <label class="ftm-label" for="ftm-modal-date-end" style="font-size:12px; font-weight:600; color:var(--ftm-text-secondary); margin-bottom:4px; display:block;">End Date</label>



                            <input type="date" id="ftm-modal-date-end" class="ftm-input" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--ftm-border); background:var(--ftm-card-bg); color:var(--ftm-text-primary); font-size:14px;">



                        </div>



                        <div class="ftm-text-muted ftm-text-xs" style="line-height:1.4;">Select the start and end dates to filter project orders and deliverables across the selected period.</div>



                    </div>



                </div>



            </div>







            <div class="ftm-modal-footer" style="padding:14px 20px; border-top:1px solid var(--ftm-border); display:flex; justify-content:flex-end; gap:10px;">



                <button type="button" class="ftm-btn ftm-btn-secondary" id="ftm-btn-date-range-cancel" style="padding:8px 18px;">Cancel</button>



                <button type="button" class="ftm-btn ftm-btn-primary" id="ftm-btn-date-range-apply" style="padding:8px 22px;">Apply Range</button>



            </div>



        </div>



    </div>







    <!-- EXECUTIVE BRIEFING DOCUMENT MODAL (PRINT & PDF READY) -->



    <div id="ftm-modal-executive-doc" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog" style="max-width:900px; width:95%; max-height:92vh; display:flex; flex-direction:column; padding:0; border-radius:16px; overflow:hidden; background:var(--ftm-card-bg);">



            <!-- Modal Action Bar -->



            <div class="ftm-modal-header ftm-exec-doc-header">



                <div class="ftm-exec-doc-header-brand">



                    <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" style="font-weight:700;">Executive Document</span>



                    <strong class="ftm-exec-doc-header-title">Operations Daily Briefing</strong>



                </div>



                <div class="ftm-exec-doc-header-actions">



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs" id="ftm-btn-copy-doc-text" title="Copy as formatted text">



                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>



                        <span>Copy Text</span>



                    </button>



                    <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-xs" id="ftm-btn-print-doc" title="Print or save as PDF">



                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>



                        <span>Print / Save PDF</span>



                    </button>



                    <button type="button" class="ftm-modal-close-icon" id="ftm-modal-exec-doc-close" style="background:none; border:none; font-size:24px; line-height:1; cursor:pointer; color:var(--ftm-text-muted);">&times;</button>



                </div>



            </div>







            <!-- Team Filter Pills for Executive Doc -->



            <div class="ftm-hide-print ftm-exec-doc-filter-strip">



                <div class="ftm-exec-doc-pill-wrap">



                    <span style="font-size:12px; font-weight:600; color:var(--ftm-text-muted); white-space:nowrap;">Team Brief:</span>



                    <div class="ftm-team-pill-group" id="ftm-exec-team-pills">



                        <button type="button" class="ftm-team-pill active" data-team="all">All Teams</button>



                        <button type="button" class="ftm-team-pill" data-team="WpCraft">WpCraft</button>



                        <button type="button" class="ftm-team-pill" data-team="WpNexus">WpNexus</button>



                        <button type="button" class="ftm-team-pill" data-team="WpTitans">WpTitans</button>



                    </div>



                </div>



                <div class="ftm-text-muted ftm-text-xs ftm-hide-mobile">Export or print separate unit briefs for managers & leadership</div>



            </div>







            <!-- Printable Document Body -->



            <div class="ftm-modal-body ftm-exec-doc-printable" id="ftm-exec-doc-body" style="padding:32px 40px; overflow-y:auto; background:#ffffff; color:#0f172a; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">



                <!-- Populated dynamically via JS -->



            </div>



        </div>



    </div>







    <!-- MODAL: WEEKLY OPERATIONS & PERFORMANCE REPORT -->



    <div id="ftm-modal-weekly-report" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog" style="max-width:1050px; width:95%; max-height:92vh; display:flex; flex-direction:column; padding:0; border-radius:16px; overflow:hidden; background:var(--ftm-card-bg);">



            <!-- Modal Header -->



            <div class="ftm-modal-header" style="background:var(--ftm-card-bg); padding:16px 24px; border-bottom:1px solid var(--ftm-border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">



                <div>



                    <div style="display:flex; align-items:center; gap:8px;">



                        <span class="ftm-badge bg-blue-soft text-blue ftm-badge-sm" style="font-weight:700;">Weekly Operations</span>



                        <h3 class="ftm-font-bold" style="font-size:17px; margin:0; color:var(--ftm-text-primary);">Weekly Performance & Capacity Rollup</h3>



                    </div>



                    <div class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">Consolidated 7-day work hours, deliverables, project status, and blockers</div>



                </div>







                <div style="display:flex; align-items:center; gap:10px;">



                    <!-- Week Range Navigator -->



                    <div style="display:flex; align-items:center; background:var(--ftm-bg, #f8fafc); border:1px solid var(--ftm-border); border-radius:8px; padding:2px;">



                        <button type="button" class="ftm-btn ftm-btn-ghost ftm-btn-xs" id="ftm-btn-prev-week" style="padding:4px 8px;" title="Previous Week">&larr; Prev Week</button>



                        <span id="ftm-weekly-range-label" style="font-size:12px; font-weight:700; padding:0 10px; color:var(--ftm-text-primary); white-space:nowrap;">Loading week...</span>



                        <button type="button" class="ftm-btn ftm-btn-ghost ftm-btn-xs" id="ftm-btn-next-week" style="padding:4px 8px;" title="Next Week">Next Week &rarr;</button>



                    </div>







                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs" id="ftm-btn-print-weekly" title="Print or save as PDF">



                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>



                        <span>Print</span>



                    </button>



                    <button type="button" class="ftm-modal-close-icon" id="ftm-modal-weekly-close" style="background:none; border:none; font-size:24px; line-height:1; cursor:pointer; color:var(--ftm-text-muted);">&times;</button>



                </div>



            </div>







            <!-- Team Filter Pills for Weekly Report -->



            <div class="ftm-hide-print" style="background:var(--ftm-bg, #f8fafc); border-bottom:1px solid var(--ftm-border); padding:10px 24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">



                <div style="display:flex; align-items:center; gap:8px;">



                    <span style="font-size:12px; font-weight:600; color:var(--ftm-text-muted);">Filter Team:</span>



                    <div class="ftm-team-pill-group" id="ftm-weekly-team-pills">



                        <button type="button" class="ftm-team-pill active" data-team="all">All Teams</button>



                        <button type="button" class="ftm-team-pill" data-team="WpCraft">WpCraft</button>



                        <button type="button" class="ftm-team-pill" data-team="WpNexus">WpNexus</button>



                        <button type="button" class="ftm-team-pill" data-team="WpTitans">WpTitans</button>



                    </div>



                </div>



                <div class="ftm-text-muted ftm-text-xs">Summary of logged work, weekends, leaves & project deliverables</div>



            </div>







            <!-- Weekly Report Content (KPIs + Detailed Member Breakdown) -->



            <div class="ftm-modal-body" id="ftm-weekly-report-content" style="padding:24px; overflow-y:auto;">



                <!-- Populated dynamically via JS -->



            </div>



        </div>



    </div>







    <!-- MODAL: PERFORMANCE LEADERBOARD METHODOLOGY -->



    <div id="ftm-modal-leaderboard-methodology" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog">



            <div class="ftm-modal-header">



                <div>



                    <h3 class="ftm-font-bold" style="font-size:17px; margin:0; color:var(--ftm-text-primary); display:flex; align-items:center; gap:8px;">



                        <svg width="20" height="20" viewBox="0 0 24 24" fill="#f59e0b" stroke="#d97706" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>



                        <span>Leaderboard Scoring Methodology</span>



                    </h3>



                    <p class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">Deterministic, Merit-Based Scoring Architecture (Scale 0 – 100)</p>



                </div>



                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>



            </div>



            <div class="ftm-modal-body">



                <p style="font-size:13px; color:var(--ftm-text-secondary); line-height:1.55; margin-bottom:18px;">



                    The FB International Leaderboard applies a strictly transparent, server-side scoring algorithm across four core operational pillars. Every team member and leader is ranked under identical, verifiable rules with zero manual bias.



                </p>







                <div class="ftm-lb-methodology-pillars">



                    <div class="ftm-lb-methodology-pillar">



                        <div class="ftm-lb-pillar-badge-wrap" style="display:flex; align-items:center; gap:8px;">



                            <span class="ftm-badge ftm-badge-primary" style="font-weight:700;">Pillar 1 • 40% Weight</span>



                            <span style="font-size:13px; font-weight:700; color:var(--ftm-text-primary);">Delivered Revenue Volume (40 pts)</span>



                        </div>



                        <p style="font-size:12.5px; color:var(--ftm-text-muted); margin:6px 0 0 0; line-height:1.5;">



                            Top agency priority. Directly evaluates delivered revenue volume in the period scaled relative to the highest deliverer in the active cohort:



                            <code style="display:block; margin-top:4px; padding:6px 10px; background:var(--ftm-bg); border-radius:6px; font-size:11.5px;">P1 = min(40.0, (Delivered Revenue / Max Cohort Revenue) &times; 40)</code>



                        </p>



                    </div>







                    <div class="ftm-lb-methodology-pillar" style="margin-top:14px;">



                        <div class="ftm-lb-pillar-badge-wrap" style="display:flex; align-items:center; gap:8px;">



                            <span class="ftm-badge ftm-badge-warning" style="font-weight:700;">Pillar 2 • 30% Weight</span>



                            <span style="font-size:13px; font-weight:700; color:var(--ftm-text-primary);">Client Satisfaction Quality & 5-Star Reviews (30 pts)</span>



                        </div>



                        <p style="font-size:12.5px; color:var(--ftm-text-muted); margin:6px 0 0 0; line-height:1.5;">



                            Second priority. Evaluates average review rating (up to 20 pts) plus a volume bonus for verified 5-star client ratings (up to 10 pts):



                            <code style="display:block; margin-top:4px; padding:6px 10px; background:var(--ftm-bg); border-radius:6px; font-size:11.5px;">P2 = ((Average Rating / 5.0) &times; 20) + min(10.0, 5-Star Count &times; 2.0)</code>



                            <strong>Fairness Protection:</strong> When orders have no client reviews logged, a neutral baseline of <strong>12.0 pts</strong> (60% standard / 3.0★) is automatically applied.



                        </p>



                    </div>







                    <div class="ftm-lb-methodology-pillar" style="margin-top:14px;">



                        <div class="ftm-lb-pillar-badge-wrap" style="display:flex; align-items:center; gap:8px;">



                            <span class="ftm-badge ftm-badge-info" style="font-weight:700;">Pillar 3 • 15% Weight</span>



                            <span style="font-size:13px; font-weight:700; color:var(--ftm-text-primary);">On-Time Delivery Velocity (15 pts)</span>



                        </div>



                        <p style="font-size:12.5px; color:var(--ftm-text-muted); margin:6px 0 0 0; line-height:1.5;">



                            Measures delivery discipline and commitment to client milestones:



                            <code style="display:block; margin-top:4px; padding:6px 10px; background:var(--ftm-bg); border-radius:6px; font-size:11.5px;">P3 = min(1.0, Delivered Without Extension / Completed Orders) &times; 15</code>



                        </p>



                    </div>







                    <div class="ftm-lb-methodology-pillar" style="margin-top:14px;">



                        <div class="ftm-lb-pillar-badge-wrap" style="display:flex; align-items:center; gap:8px;">



                            <span class="ftm-badge ftm-badge-success" style="font-weight:700;">Pillar 4 • 15% Weight</span>



                            <span style="font-size:13px; font-weight:700; color:var(--ftm-text-primary);">Order Retention & Reliability (15 pts)</span>



                        </div>



                        <p style="font-size:12.5px; color:var(--ftm-text-muted); margin:6px 0 0 0; line-height:1.5;">



                            Rewards flawless project completion without cancellations or client disputes:



                            <code style="display:block; margin-top:4px; padding:6px 10px; background:var(--ftm-bg); border-radius:6px; font-size:11.5px;">P4 = (1.0 - [Cancellations / (Completed + Cancellations)]) &times; 15</code>



                        </p>



                    </div>



                </div>







                <div style="margin-top:20px; padding:12px 16px; background:var(--ftm-bg); border-left:3px solid #3b82f6; border-radius:0 8px 8px 0; font-size:12px; color:var(--ftm-text-secondary); line-height:1.5;">



                    <strong>Standby / Insufficient Data Policy:</strong>



                    Team members with 0 completed deliveries in the queried period are placed in Standby status (Score: 0 / Insufficient Data) to maintain statistically rigorous, transparent comparisons.



                </div>



            </div>



            <div class="ftm-modal-footer">
                <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Close</button>
            </div>



        </div>



    </div>







    <!-- MODAL: BROADCAST AGENCY NOTICE -->



    <div id="ftm-modal-notice" class="ftm-modal-backdrop hidden">



        <div class="ftm-modal-dialog" style="max-width:560px; width:95%; padding:0; border-radius:16px; overflow:hidden; background:var(--ftm-card-bg);">



            <div class="ftm-modal-header" style="padding:18px 24px; border-bottom:1px solid var(--ftm-border); display:flex; justify-content:space-between; align-items:center;">



                <div>



                    <h3 class="ftm-font-bold" style="font-size:17px; margin:0; color:var(--ftm-text-primary);">Broadcast Agency Notice</h3>



                    <p class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">Publish an announcement, directive, or update across the agency.</p>



                </div>



                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>



            </div>



            <form id="ftm-notice-form" class="ftm-modal-body" style="padding:24px;">



                <div class="ftm-form-group">



                    <label class="ftm-label" for="ftm-notice-title">Notice Title *</label>



                    <input type="text" id="ftm-notice-title" class="ftm-input" placeholder="e.g. Server Maintenance Window / Priority Client Assets" required>



                </div>







                <div class="ftm-form-row ftm-grid-2">



                    <div class="ftm-form-group">



                        <label class="ftm-label" for="ftm-notice-type">Notice Category *</label>



                        <select id="ftm-notice-type" class="ftm-select" required>



                            <option value="announcement">General Announcement</option>



                            <option value="important">Important Directive</option>



                            <option value="urgent">Urgent Action Required</option>



                            <option value="kudos">Team Recognition / Kudos</option>



                        </select>



                    </div>



                    <div class="ftm-form-group">



                        <label class="ftm-label" for="ftm-notice-target-type">Target Audience *</label>



                        <select id="ftm-notice-target-type" class="ftm-select" required>



                            <option value="all">Entire Agency (All Members)</option>



                            <option value="team">Specific Team</option>



                            <option value="member">Specific Specialist</option>



                        </select>



                    </div>



                </div>







                <div class="ftm-form-group hidden" id="ftm-notice-team-group">



                    <label class="ftm-label" for="ftm-notice-target-team">Select Target Team</label>



                    <select id="ftm-notice-target-team" class="ftm-select">



                        <option value="WpCraft">WpCraft</option>



                        <option value="WpNexus">WpNexus</option>



                        <option value="WpTitans">WpTitans</option>

                    </select>



                </div>







                <div class="ftm-form-group hidden" id="ftm-notice-member-group">



                    <label class="ftm-label" for="ftm-notice-target-member">Select Specialist</label>



                    <select id="ftm-notice-target-member" class="ftm-select">



                        <option value="">Select Specialist...</option>



                    </select>



                </div>







                <div class="ftm-form-group">



                    <label class="ftm-label" for="ftm-notice-message">Notice Message *</label>



                    <textarea id="ftm-notice-message" class="ftm-textarea" rows="4" placeholder="Enter notice details, instructions, or recognition..." required></textarea>



                </div>







                <div class="ftm-form-group" style="margin-bottom:0;">



                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:600; color:var(--ftm-text-primary);">



                        <input type="checkbox" id="ftm-notice-pin" value="1" style="width:16px; height:16px; accent-color:#2563eb;">



                        <span>Pin to top of Notice Board & Dashboard Banner</span>



                    </label>



                </div>







                <div class="ftm-modal-footer" style="padding:16px 0 0 0; margin-top:20px; border-top:1px solid var(--ftm-border); display:flex; justify-content:flex-end; gap:10px;">



                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Cancel</button>



                    <button type="submit" class="ftm-btn ftm-btn-primary" id="ftm-btn-submit-notice">



                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>



                        <span>Publish Notice</span>



                    </button>



                </div>



            </form>



        </div>



    </div>







    <!-- MOBILE BOTTOM NAVIGATION (NATIVE APP EXPERIENCE - INSPIRED BY MOCKUPS) -->



    <nav id="ftm-mobile-bottom-nav" class="ftm-mobile-bottom-nav">



        <a href="#dashboard" class="ftm-mob-nav-item active" data-view="dashboard">



            <div class="ftm-mob-nav-icon">



                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>



            </div>



            <span class="ftm-mob-nav-label">Dashboard</span>



        </a>







        <a href="#my-projects" class="ftm-mob-nav-item" data-view="my-projects">



            <div class="ftm-mob-nav-icon">



                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>



            </div>



            <span class="ftm-mob-nav-label">My Work</span>



        </a>







        <!-- Center Elevated (+) Action Button -->



        <div class="ftm-mob-action-center">



            <button type="button" class="ftm-mob-action-btn" id="ftm-mob-quick-action-btn" aria-label="Open Actions">



                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>



            </button>



        </div>







        <a href="#monthly-reports" class="ftm-mob-nav-item" data-view="monthly-reports">



            <div class="ftm-mob-nav-icon">



                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>



            </div>



            <span class="ftm-mob-nav-label">Reports</span>



        </a>







        <a href="#team-members" class="ftm-mob-nav-item" data-view="team-members">



            <div class="ftm-mob-nav-icon">



                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>



            </div>



            <span class="ftm-mob-nav-label">Team</span>



        </a>



    </nav>







    <!-- MOBILE QUICK ACTION BOTTOM SHEET -->



    <div id="ftm-mobile-action-sheet-backdrop" class="ftm-sheet-backdrop hidden">



        <div class="ftm-sheet-dialog">



            <div class="ftm-sheet-handle"></div>



            <div class="ftm-sheet-header">



                <div class="ftm-sheet-title">Quick Actions</div>



                <button type="button" class="ftm-modal-close-icon" id="ftm-sheet-close-btn">&times;</button>



            </div>



            <div class="ftm-sheet-items">



                <?php if (!$is_admin && !$is_deputy): ?>



                <button type="button" class="ftm-sheet-action-item" id="ftm-sheet-btn-standup">



                    <div class="ftm-sheet-action-icon text-blue bg-blue-soft">



                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>



                    </div>



                    <div class="ftm-sheet-action-text">



                        <strong>Log Daily Progress</strong>



                        <span>Submit today's task hours and deliverables</span>



                    </div>



                </button>



                <?php else: ?>



                <button type="button" class="ftm-sheet-action-item" id="ftm-sheet-btn-notice">



                    <div class="ftm-sheet-action-icon text-blue bg-blue-soft">



                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>



                    </div>



                    <div class="ftm-sheet-action-text">



                        <strong>Broadcast Agency Notice</strong>



                        <span>Publish directive or announcement to members</span>



                    </div>



                </button>



                <?php endif; ?>







                <?php if ($is_leader && !$is_deputy): ?>



                <button type="button" class="ftm-sheet-action-item" id="ftm-sheet-btn-project">



                    <div class="ftm-sheet-action-icon text-emerald bg-emerald-soft">



                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>



                    </div>



                    <div class="ftm-sheet-action-text">



                        <strong>New Order / Project</strong>



                        <span>Assign new incoming order</span>



                    </div>



                </button>







                <button type="button" class="ftm-sheet-action-item" id="ftm-sheet-btn-profile">



                    <div class="ftm-sheet-action-icon text-amber bg-amber-soft">



                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1 4-10z"></path></svg>



                    </div>



                    <div class="ftm-sheet-action-text">



                        <strong>Add Profile</strong>



                        <span>Register a seller profile account</span>



                    </div>



                </button>



                <?php endif; ?>







                <button type="button" class="ftm-sheet-action-item" id="ftm-sheet-btn-theme">



                    <div class="ftm-sheet-action-icon text-purple bg-purple-soft">



                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>



                    </div>



                    <div class="ftm-sheet-action-text">



                        <strong>Toggle Dark / Light Mode</strong>



                        <span>Switch dashboard visual appearance</span>



                    </div>



                </button>







                <a href="#settings" class="ftm-sheet-action-item" id="ftm-sheet-btn-settings" style="text-decoration:none; color:inherit;">



                    <div class="ftm-sheet-action-icon text-gray bg-gray-soft">



                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>



                    </div>



                    <div class="ftm-sheet-action-text">



                        <strong>Account Settings</strong>



                        <span>Profile details and preferences</span>



                    </div>



                </a>



            </div>



        </div>



    </div>







    
    <!-- MODAL: ASSIGN / ADD TASK -->
    <div id="ftm-modal-add-task" class="ftm-modal-backdrop hidden" style="z-index: 99999 !important;">
        <div class="ftm-modal-dialog ftm-modal-md">
            <div class="ftm-modal-header">
                <div>
                    <h3 id="ftm-modal-task-title">Assign Milestone / Task</h3>
                    <p class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">Set task title, assign project, priority level, and delivery deadline.</p>
                </div>
                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>
            </div>
            <form id="ftm-task-form" class="ftm-modal-body">
                <input type="hidden" id="ftm-form-task-id" value="">
                <input type="hidden" id="ftm-form-task-member-id" value="">
                
                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-form-task-project-id">Associated Project *</label>
                    <select id="ftm-form-task-project-id" class="ftm-select" required>
                        <option value="">Select Project...</option>
                    </select>
                </div>

                <div class="ftm-form-group" id="ftm-form-task-assignees-wrap">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; gap:8px;">
                        <label class="ftm-label" for="ftm-form-task-assignee-search" style="margin:0;">Assign Person(s)</label>
                        <span id="ftm-form-task-assignees-count" class="ftm-badge bg-blue-soft text-blue ftm-badge-xs" style="font-weight:700;">Auto-selected from project</span>
                    </div>
                    <div id="ftm-form-task-selected-chips" style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:8px;"></div>
                    <input type="text" id="ftm-form-task-assignee-search" class="ftm-input ftm-input-sm" placeholder="Search member to add or remove..." style="margin-bottom:6px;">
                    <div id="ftm-form-task-assignees-list" style="max-height:210px; overflow-y:auto; border:1px solid var(--ftm-border); border-radius:8px; background:var(--ftm-bg, rgba(15,23,42,0.35)); padding:8px;">
                        <div class="ftm-text-muted ftm-text-xs" style="padding:8px; text-align:center;">Loading team members...</div>
                    </div>
                    <small class="ftm-text-muted" style="font-size:11px; display:block; margin-top:4px;">Selecting a project automatically checks its assigned member(s). You can add additional members or select multiple people.</small>
                </div>

                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-form-task-title-input">Task Title *</label>
                    <input type="text" id="ftm-form-task-title-input" class="ftm-input" placeholder="e.g. 1st Draft, Initial Conversation, Header Revision" required>
                    <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:6px;">
                        <button type="button" class="ftm-badge ftm-badge-outline ftm-badge-xs ftm-task-preset-btn" data-title="Initial conversation started" data-priority="urgent">Initial convo (5h)</button>
                        <button type="button" class="ftm-badge ftm-badge-outline ftm-badge-xs ftm-task-preset-btn" data-title="1st Draft" data-priority="high">1st Draft</button>
                        <button type="button" class="ftm-badge ftm-badge-outline ftm-badge-xs ftm-task-preset-btn" data-title="Fixing / Revisions" data-priority="medium">Fixing / Revisions</button>
                        <button type="button" class="ftm-badge ftm-badge-outline ftm-badge-xs ftm-task-preset-btn" data-title="Final update & delivery" data-priority="high">Final update</button>
                    </div>
                </div>

                <div class="ftm-form-row ftm-grid-2">
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-form-task-priority">Priority Status *</label>
                        <select id="ftm-form-task-priority" class="ftm-select" required>
                            <option value="urgent">Urgent (Immediate / 5h)</option>
                            <option value="high" selected>⚡ High (Milestone / Draft)</option>
                            <option value="medium">Medium (Normal / Fixing)</option>
                            <option value="low">☕ Low (Backlog / Minor)</option>
                        </select>
                    </div>

                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-form-task-deadline">Deadline (Optional)</label>
                        <input type="datetime-local" id="ftm-form-task-deadline" class="ftm-input">
                    </div>
                </div>

                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-form-task-desc">Description / Instructions</label>
                    <textarea id="ftm-form-task-desc" class="ftm-textarea" rows="2" placeholder="Specific requirements or checklist..."></textarea>
                </div>

                <div class="ftm-modal-footer" style="padding:14px 0 0; margin-top:14px; border-top:1px solid var(--ftm-border); display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Cancel</button>
                    <button type="submit" class="ftm-btn ftm-btn-primary" id="ftm-btn-save-task">Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: APPLY FOR LEAVE / DAY OFF SWITCH -->
    <div id="ftm-modal-apply-leave" class="ftm-modal-backdrop hidden">
        <div class="ftm-modal-dialog ftm-modal-md">
            <div class="ftm-modal-header">
                <div>
                    <h3 id="ftm-modal-leave-title">Apply for Leave / Adjust Day-Off</h3>
                    <p class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">Submit request for team leader verification and executive manager approval.</p>
                </div>
                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>
            </div>
            <form id="ftm-form-apply-leave" class="ftm-modal-body">
                <input type="hidden" id="ftm-leave-edit-id" value="">
                <div id="ftm-leave-edit-member-banner" class="hidden" style="margin-bottom:14px; font-size:12.5px; padding:10px 14px; background:var(--ftm-surface, rgba(255,255,255,0.05)); border:1px solid var(--ftm-border); border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                    <span>Editing leave for: <strong id="ftm-leave-edit-member-name" style="color:var(--ftm-text-primary);">Member</strong></span>
                    <span class="ftm-badge bg-blue-soft text-blue ftm-badge-xs">Edit Mode</span>
                </div>
                <div id="ftm-leave-member-select-group" class="ftm-form-group hidden">
                    <label class="ftm-label" for="ftm-leave-member-id">Select Team Member *</label>
                    <input type="text" id="ftm-leave-member-search" class="ftm-input" placeholder="Quick filter member by name or squad..." style="margin-bottom:6px; font-size:12px;">
                    <select id="ftm-leave-member-id" class="ftm-select"></select>
                </div>
                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-leave-request-type">Request Type *</label>
                    <select id="ftm-leave-request-type" class="ftm-select">
                        <option value="leave" selected>Leave Notice (Casual, Sick, Vacation, Emergency)</option>
                        <option value="day_off_switch">Switch / Adjust Weekly Day-Off</option>
                    </select>
                </div>

                <div id="ftm-leave-type-group" class="ftm-form-group">
                    <label class="ftm-label" for="ftm-leave-category">Leave Category *</label>
                    <select id="ftm-leave-category" class="ftm-select">
                        <option value="casual_leave">Casual Leave (Full Day)</option>
                        <option value="half_day_morning">Half Day Leave (Morning / 1st Half - 0.5 Day)</option>
                        <option value="half_day_evening">Half Day Leave (Evening / 2nd Half - 0.5 Day)</option>
                        <option value="sick_leave">Sick Leave / Medical</option>
                        <option value="vacation">Planned Vacation</option>
                        <option value="emergency">Family / Emergency</option>
                    </select>
                </div>

                <div id="ftm-leave-dates-row" class="ftm-form-row ftm-grid-2">
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-leave-start-date">Start Date *</label>
                        <input type="date" id="ftm-leave-start-date" class="ftm-input" value="<?php echo esc_attr(current_time('Y-m-d')); ?>" required>
                    </div>
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-leave-end-date">End Date *</label>
                        <input type="date" id="ftm-leave-end-date" class="ftm-input" value="<?php echo esc_attr(current_time('Y-m-d')); ?>" required>
                    </div>
                </div>

                <div id="ftm-switch-dates-row" class="ftm-form-row ftm-grid-2 hidden">
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-switch-from-date">Original Scheduled Day-Off *</label>
                        <input type="date" id="ftm-switch-from-date" class="ftm-input">
                    </div>
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-switch-to-date">Desired Switched Off-Day *</label>
                        <input type="date" id="ftm-switch-to-date" class="ftm-input">
                    </div>
                </div>

                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-leave-reason">Reason / Notes *</label>
                    <textarea id="ftm-leave-reason" class="ftm-textarea" rows="3" placeholder="Provide detailed reason or handover notes..." required></textarea>
                </div>

                <div id="ftm-leave-admin-controls" class="hidden" style="margin-top:12px; padding:12px; border:1px solid rgba(59,130,246,0.28); border-radius:10px; background:rgba(59,130,246,0.06);">
                    <div style="font-size:11.5px; font-weight:700; color:#60a5fa; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:10px;">Super Admin Overrides</div>
                    <div class="ftm-form-row ftm-grid-2">
                        <div class="ftm-form-group">
                            <label class="ftm-label" for="ftm-leave-status-override">Approval Status</label>
                            <select id="ftm-leave-status-override" class="ftm-select">
                                <option value="approved">Approved (Immediate Sync)</option>
                                <option value="leader_approved">Leader Verified (Awaiting Manager)</option>
                                <option value="pending">Pending Leader Review</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="ftm-form-group">
                            <label class="ftm-label" for="ftm-leave-days-override">Days Count Override</label>
                            <input type="number" id="ftm-leave-days-override" class="ftm-input" step="0.5" min="0" placeholder="Auto-calculated">
                        </div>
                    </div>
                    <div class="ftm-form-group" style="margin-bottom:0;">
                        <label class="ftm-label" for="ftm-leave-manager-feedback">Manager Feedback / Adjustment Note</label>
                        <input type="text" id="ftm-leave-manager-feedback" class="ftm-input" placeholder="Optional manager note...">
                    </div>
                </div>

                <div class="ftm-modal-footer" style="padding:14px 0 0; margin-top:14px; border-top:1px solid var(--ftm-border); display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Cancel</button>
                    <button type="submit" class="ftm-btn ftm-btn-primary" id="ftm-btn-submit-leave">Submit Application</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: SUPER ADMIN LEAVE QUOTA & BALANCE ADJUSTMENT -->
    <div id="ftm-modal-leave-quota" class="ftm-modal-backdrop hidden">
        <div class="ftm-modal-dialog ftm-modal-sm">
            <div class="ftm-modal-header">
                <div>
                    <h3>Adjust Leave Quota & Balance</h3>
                    <p class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">Member: <strong id="ftm-quota-member-name">Specialist</strong></p>
                </div>
                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>
            </div>
            <form id="ftm-form-leave-quota" class="ftm-modal-body">
                <input type="hidden" id="ftm-quota-user-id" value="">
                <div class="ftm-form-row ftm-grid-2">
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-quota-annual-days">Annual Quota (Days)</label>
                        <input type="number" id="ftm-quota-annual-days" class="ftm-input" step="0.5" min="0" value="18" required>
                    </div>
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-quota-adjustment-days">Balance Adjustment (+/-)</label>
                        <input type="number" id="ftm-quota-adjustment-days" class="ftm-input" step="0.5" value="0">
                    </div>
                </div>
                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-quota-day-off">Weekly Scheduled Day-Off</label>
                    <select id="ftm-quota-day-off" class="ftm-select">
                        <option value="friday">Friday</option>
                        <option value="saturday">Saturday</option>
                        <option value="sunday">Sunday</option>
                        <option value="monday">Monday</option>
                        <option value="tuesday">Tuesday</option>
                        <option value="wednesday">Wednesday</option>
                        <option value="thursday">Thursday</option>
                    </select>
                </div>
                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-quota-note">Adjustment Reason / Note</label>
                    <textarea id="ftm-quota-note" class="ftm-textarea" rows="2" placeholder="e.g., Compensatory leave bonus, custom contract quota..."></textarea>
                </div>
                <div class="ftm-modal-footer" style="padding:14px 0 0; margin-top:14px; border-top:1px solid var(--ftm-border); display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Cancel</button>
                    <button type="submit" class="ftm-btn ftm-btn-primary">Save Quota Settings</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: SUPER ADMIN CREATE / EDIT TEAM MEMBER -->
    <div id="ftm-modal-manage-member" class="ftm-modal-backdrop hidden">
        <div class="ftm-modal-dialog ftm-modal-md">
            <div class="ftm-modal-header">
                <div>
                    <h3 id="ftm-modal-member-title">Add New Team Member</h3>
                    <p class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">Create an agency account or adjust squad, role, leave quota, weekly day-off, and status.</p>
                </div>
                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>
            </div>
            <form id="ftm-form-manage-member" class="ftm-modal-body">
                <input type="hidden" id="ftm-member-edit-id" value="">
                <div class="ftm-form-row ftm-grid-2">
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-member-name">Full Name *</label>
                        <input type="text" id="ftm-member-name" class="ftm-input" placeholder="e.g. Md. Rakibul Islam" required>
                    </div>
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-member-email">Official Email *</label>
                        <input type="email" id="ftm-member-email" class="ftm-input" placeholder="member@fbinternationalbd.com" required>
                    </div>
                </div>
                <div class="ftm-form-row ftm-grid-2">
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-member-team">Squad / Team *</label>
                        <select id="ftm-member-team" class="ftm-select">
                            <option value="WpCraft">WpCraft</option>
                            <option value="WpNexus">WpNexus</option>
                            <option value="WpTitans">WpTitans</option>
                            <option value="Management">Management</option>
                        </select>
                    </div>
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-member-role">Agency Role *</label>
                        <select id="ftm-member-role" class="ftm-select">
                            <option value="member">Specialist / Developer</option>
                            <option value="co_leader">Co-Leader</option>
                            <option value="leader">Team Leader</option>
                            <option value="executive">Executive (View Only)</option>
                            <option value="management">Management (View Only - All Access)</option>
                            <option value="admin">Super Admin / Manager</option>
                        </select>
                    </div>
                </div>
                <div class="ftm-form-row ftm-grid-2">
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-member-title">Professional Title</label>
                        <input type="text" id="ftm-member-title" class="ftm-input" placeholder="WordPress Developer">
                    </div>
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-member-custom-id">Badge / Member ID</label>
                        <input type="text" id="ftm-member-custom-id" class="ftm-input" placeholder="Auto if blank">
                    </div>
                </div>
                <div class="ftm-form-row ftm-grid-2">
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-member-day-off">Weekly Day-Off</label>
                        <select id="ftm-member-day-off" class="ftm-select">
                            <option value="friday">Friday</option>
                            <option value="saturday">Saturday</option>
                            <option value="sunday">Sunday</option>
                            <option value="monday">Monday</option>
                            <option value="tuesday">Tuesday</option>
                            <option value="wednesday">Wednesday</option>
                            <option value="thursday">Thursday</option>
                        </select>
                    </div>
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-member-status">Account Status</label>
                        <select id="ftm-member-status" class="ftm-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive / Resigned</option>
                        </select>
                    </div>
                </div>
                <div class="ftm-form-row ftm-grid-2">
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-member-quota">Annual Leave Quota (Days)</label>
                        <input type="number" id="ftm-member-quota" class="ftm-input" step="0.5" min="0" value="18">
                    </div>
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-member-leave-adj">Leave Balance Adjustment (+/-)</label>
                        <input type="number" id="ftm-member-leave-adj" class="ftm-input" step="0.5" value="0">
                    </div>
                </div>
                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-member-password" id="ftm-member-password-label">Password * (min 6 chars)</label>
                    <input type="password" id="ftm-member-password" class="ftm-input" placeholder="Enter password (min 6 characters)">
                </div>
                <div class="ftm-modal-footer" style="padding:14px 0 0; margin-top:14px; border-top:1px solid var(--ftm-border); display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Cancel</button>
                    <button type="submit" class="ftm-btn ftm-btn-primary" id="ftm-btn-save-member">Create Member</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: MANAGE & REVIEW LEAVE REQUESTS -->
    <div id="ftm-modal-manage-leaves" class="ftm-modal-backdrop hidden">
        <div class="ftm-modal-dialog ftm-modal-lg">
            <div class="ftm-modal-header">
                <div>
                    <h3>Leave & Day-Off Applications</h3>
                    <p class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">Two-stage verification: Leader review followed by executive manager approval.</p>
                </div>
                <button type="button" class="ftm-modal-close-icon ftm-modal-close-btn">&times;</button>
            </div>
            <div class="ftm-modal-body">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
                    <div style="display:flex; gap:8px; align-items:center;">
                        <select id="ftm-leaves-filter-status" class="ftm-select ftm-select-sm" style="width:180px;">
                            <option value="all">All Applications</option>
                            <option value="pending" selected>Pending Review</option>
                            <option value="leader_approved">Leader Verified (Awaiting Manager)</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <button type="button" class="ftm-btn ftm-btn-secondary ftm-btn-xs" id="ftm-leaves-refresh-btn">Refresh</button>
                    </div>
                    <button type="button" class="ftm-btn ftm-btn-primary ftm-btn-xs" id="ftm-btn-apply-leave-inside">+ Apply New</button>
                </div>
                <div id="ftm-leaves-table-container" style="max-height:420px; overflow-y:auto;">
                    <!-- Leaves list populated dynamically via JS -->
                </div>
            </div>
            <div class="ftm-modal-footer">
                <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Close</button>
            </div>
        </div>
    </div>

    <!-- Persistent Floating PIP Live Tracker Widget (Fixed Bottom-Left Corner) -->
    <div id="ftm-tracker-pip-container" class="ftm-tracker-pip-container hidden" style="position:fixed; bottom:24px; left:24px; z-index:999999; display:none; flex-direction:column; gap:10px; font-family:var(--ftm-font-family, system-ui, -apple-system, sans-serif);">
        <!-- Captured Screenshot Flyout Toast Preview -->
        <div id="ftm-pip-snap-toast" class="ftm-pip-snap-toast hidden" style="display:none; width:340px; background:#0f172a; border:1px solid #3b82f6; box-shadow:0 12px 30px rgba(0,0,0,0.5); border-radius:12px; padding:12px 14px; color:#fff; transition:all 0.3s ease;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                <div style="display:flex; align-items:center; gap:6px;">
                    <span style="font-size:14px;">📸</span>
                    <strong style="font-size:12px; font-weight:700; color:#60a5fa;" id="ftm-pip-toast-headline">Work Activity Captured!</strong>
                </div>
                <button type="button" id="ftm-pip-toast-close" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:14px; padding:0 4px;">✕</button>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
                <div style="width:96px; height:60px; border-radius:6px; overflow:hidden; background:#1e293b; border:1px solid rgba(255,255,255,0.1); flex-shrink:0; cursor:pointer;" id="ftm-pip-toast-thumb-wrap">
                    <img id="ftm-pip-toast-thumb" src="" alt="Captured Screenshot" style="width:100%; height:100%; object-fit:cover; display:block;">
                </div>
                <div style="font-size:11.5px; line-height:1.4; color:#cbd5e1;">
                    <div style="font-weight:700; color:#fff;" id="ftm-pip-toast-order">#ORDER</div>
                    <div>Uploaded to Leadership Review</div>
                    <div style="font-size:10.5px; color:#94a3b8; margin-top:2px;" id="ftm-pip-toast-time">Just now</div>
                </div>
            </div>
        </div>

        <!-- Main PIP Card -->
        <div id="ftm-pip-main-card" class="ftm-pip-main-card" style="width:340px; background:#0b1120; border:1px solid #1e293b; box-shadow:0 16px 40px rgba(0,0,0,0.6); border-radius:14px; overflow:hidden; color:#fff;">
            <!-- Header -->
            <div id="ftm-pip-header" style="background:#131c31; border-bottom:1px solid #1e293b; padding:10px 14px; display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span id="ftm-pip-pulse-dot" style="width:10px; height:10px; border-radius:50%; background:#10b981; box-shadow:0 0 10px #10b981; display:inline-block;"></span>
                    <span id="ftm-pip-mode-badge" style="font-size:11px; font-weight:800; letter-spacing:0.04em; color:#10b981; text-transform:uppercase;">⏱️ REGULAR TRACKER</span>
                </div>
                <div style="display:flex; align-items:center; gap:6px;">
                    <button type="button" id="ftm-pip-toggle-min" title="Minimize / Expand" style="background:rgba(255,255,255,0.08); border:none; color:#cbd5e1; border-radius:4px; cursor:pointer; font-size:12px; width:22px; height:22px; display:flex; align-items:center; justify-content:center;">_</button>
                </div>
            </div>

            <!-- Body -->
            <div id="ftm-pip-body" style="padding:12px 14px;">
                <div style="font-size:11px; color:#94a3b8; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">Working On</div>
                <div style="font-size:13.5px; font-weight:700; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" id="ftm-pip-project-title">#FO12345678 - Client</div>

                <div style="display:flex; align-items:center; justify-content:space-between; margin-top:8px; margin-bottom:8px;">
                    <div style="font-family:'Consolas',monospace; font-size:26px; font-weight:900; color:#60a5fa;" id="ftm-pip-clock">00:00:00</div>
                    <div style="display:inline-flex; align-items:center; gap:6px; font-size:11px; color:#10b981; font-weight:700; background:rgba(16,185,129,0.1); padding:3px 8px; border-radius:6px; border:1px solid rgba(16,185,129,0.25);">
                        <span style="width:6px; height:6px; border-radius:50%; background:#10b981; display:inline-block;"></span> Active Tracking
                    </div>
                </div>

                <!-- Live Mouse & Keyboard Counters -->
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px; font-size:11.5px; color:#cbd5e1; background:rgba(255,255,255,0.05); padding:5px 9px; border-radius:6px; border:1px solid rgba(255,255,255,0.08);">
                    <span>🖱️ <strong id="ftm-pip-mouse-count" style="color:#60a5fa;">0</strong> clicks</span>
                    <span style="opacity:0.4;">•</span>
                    <span>⌨️ <strong id="ftm-pip-key-count" style="color:#c084fc;">0</strong> keys</span>
                </div>

                <!-- Stream disconnected notice (if refreshed) -->
                <div id="ftm-pip-reconnect-bar" class="hidden" style="display:none; background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.3); border-radius:8px; padding:6px 10px; margin-bottom:10px; font-size:11px; color:#f59e0b;">
                    <span>Screen disconnected on reload.</span>
                    <button type="button" id="ftm-pip-btn-reconnect" style="margin-left:6px; background:#f59e0b; color:#000; border:none; padding:2px 8px; border-radius:4px; font-weight:700; cursor:pointer;">⚡ Connect Screen</button>
                </div>

                <!-- Mini controls -->
                <div style="display:flex; gap:8px;">
                    <button type="button" id="ftm-pip-btn-stop" style="flex:1; background:#dc2626; color:#fff; border:none; border-radius:6px; padding:8px 12px; font-size:12.5px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                        <span>⏹</span> Stop & Log Deliverables
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Extra Time Work Submission Modal -->
    <div id="ftm-modal-extra-time-report" class="ftm-modal-backdrop hidden" style="z-index:99999;">
        <div class="ftm-modal-dialog" style="max-width:540px;">
            <div class="ftm-modal-header" style="background:rgba(245,158,11,0.08); border-bottom:1px solid rgba(245,158,11,0.2);">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:36px; height:36px; border-radius:8px; background:rgba(245,158,11,0.2); display:flex; align-items:center; justify-content:center; font-size:18px;">⚡</div>
                    <div>
                        <h3 class="ftm-modal-title" style="margin:0; font-size:16px; color:#d97706;">Submit Extra Time Work (After 6:20 PM)</h3>
                        <div class="ftm-text-muted ftm-text-xs" style="margin-top:2px;">Shift ended at 6:20 PM &bull; Record overtime deliverables</div>
                    </div>
                </div>
                <button type="button" class="ftm-modal-close-btn" style="background:none; border:none; color:var(--ftm-text-muted); font-size:20px; cursor:pointer;">&times;</button>
            </div>
            <form id="ftm-form-extra-time-report" class="ftm-modal-body" style="padding:22px;">
                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-extra-project-select">Assigned Project *</label>
                    <select id="ftm-extra-project-select" class="ftm-select" required>
                        <option value="" disabled selected>Select project worked on...</option>
                        <!-- Populated via JS -->
                        <option value="custom">+ Custom Project / Urgent Revision</option>
                    </select>
                </div>

                <div id="ftm-extra-custom-wrap" class="ftm-form-row ftm-grid-2 hidden" style="margin-bottom:12px;">
                    <div class="ftm-form-group" style="margin:0;">
                        <label class="ftm-label" for="ftm-extra-custom-order">Order ID</label>
                        <input type="text" id="ftm-extra-custom-order" class="ftm-input" placeholder="#FO...">
                    </div>
                    <div class="ftm-form-group" style="margin:0;">
                        <label class="ftm-label" for="ftm-extra-custom-client">Client Name</label>
                        <input type="text" id="ftm-extra-custom-client" class="ftm-input" placeholder="Client Name">
                    </div>
                </div>

                <div class="ftm-form-row ftm-grid-2">
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-extra-date">Date *</label>
                        <input type="date" id="ftm-extra-date" class="ftm-input" required>
                    </div>
                    <div class="ftm-form-group">
                        <label class="ftm-label" for="ftm-extra-hours">Extra Hours Worked *</label>
                        <input type="text" id="ftm-extra-hours" class="ftm-input" placeholder="e.g. 1h 30m or 1.5" required>
                    </div>
                </div>

                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-extra-reason-select">Reason for Overtime Work *</label>
                    <select id="ftm-extra-reason-select" class="ftm-select" style="margin-bottom:6px;">
                        <option value="Urgent client delivery deadline">Urgent client delivery deadline</option>
                        <option value="Immediate client revision requested">Immediate client revision requested</option>
                        <option value="Critical bug fix / production issue">Critical bug fix / production issue</option>
                        <option value="Completing milestone ahead of schedule">Completing milestone ahead of schedule</option>
                        <option value="other">Other reason (specify below)...</option>
                    </select>
                    <input type="text" id="ftm-extra-reason-text" class="ftm-input hidden" placeholder="Specify why extra time was required...">
                </div>

                <div class="ftm-form-group">
                    <label class="ftm-label" for="ftm-extra-tasks">Tasks Completed / Deliverables *</label>
                    <textarea id="ftm-extra-tasks" class="ftm-textarea" rows="3" placeholder="Describe the specific deliverables completed during this extra time session..." required></textarea>
                </div>

                <div class="ftm-modal-footer" style="padding:16px 0 0; margin-top:10px; display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="ftm-btn ftm-btn-secondary ftm-modal-close-btn">Cancel</button>
                    <button type="submit" class="ftm-btn ftm-btn-primary" id="ftm-btn-submit-extra-report-save" style="background:#d97706; border-color:#d97706; font-weight:700;">
                        <span>⚡</span> Submit Extra Time Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Screenshot Inspection Lightbox Modal -->
    <div id="ftm-modal-snap-lightbox" class="ftm-modal-backdrop hidden" style="z-index:999999;">
        <div class="ftm-modal-dialog" style="max-width:900px; width:95%;">
            <div class="ftm-modal-header" style="padding:12px 18px; border-bottom:1px solid var(--ftm-border-color);">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; border-radius:50%; background:#1e293b; display:flex; align-items:center; justify-content:center; font-size:16px;">📸</div>
                    <div>
                        <h3 class="ftm-modal-title" style="margin:0; font-size:15px;" id="ftm-snap-lightbox-title">Screenshot Inspection</h3>
                        <div class="ftm-text-muted ftm-text-xs" id="ftm-snap-lightbox-sub">Specialist &bull; Order &bull; Captured Time</div>
                    </div>
                </div>
                <button type="button" class="ftm-modal-close-btn" style="background:none; border:none; color:var(--ftm-text-muted); font-size:20px; cursor:pointer;">&times;</button>
            </div>
            <div class="ftm-modal-body" style="padding:16px 18px;">
                <div style="background:#090d16; border-radius:10px; overflow:hidden; border:1px solid rgba(255,255,255,0.08); text-align:center; min-height:360px; max-height:65vh; display:flex; align-items:center; justify-content:center;">
                    <img id="ftm-snap-lightbox-img" src="" alt="Full Screenshot" style="max-width:100%; max-height:65vh; object-fit:contain; display:block; margin:auto;">
                </div>
                <!-- Audit Notes & Details Bar -->
                <div style="display:flex; align-items:center; justify-content:space-between; margin-top:14px; gap:12px; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span id="ftm-snap-lightbox-badge" class="ftm-badge bg-blue-soft text-blue ftm-badge-sm">Regular Shift</span>
... [truncated for diff preview]