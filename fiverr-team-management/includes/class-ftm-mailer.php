<?php
/**
 * FTM Mailer - Beautiful Branded Email Notifications for FB International BD
 * 
 * Handles automated operational emails for:
 * 1. Broadcast / Official Notices
 * 2. New Project Assignments
 * 3. New Task / Milestone Assignments
 * 4. Urgent Approaching Deadlines (< 24 Hours / Overdue)
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Mailer {

    const BRAND_NAME = 'FB International BD';
    const SENDER_EMAIL = 'support@fbinternationalbd.com';
    const DASHBOARD_URL = 'https://team.fbinternationalbd.com/team-dashboard/';

    public static function init() {
        // Hook hourly check to scan for approaching deadlines
        if (!wp_next_scheduled('ftm_hourly_deadline_email_check')) {
            wp_schedule_event(time(), 'hourly', 'ftm_hourly_deadline_email_check');
        }
        add_action('ftm_hourly_deadline_email_check', [__CLASS__, 'check_and_send_approaching_deadlines']);
    }

    /**
     * Send HTML Email with FB International BD Branding
     */
    public static function send_html($to, $subject, $headline, $badge_text, $badge_color, $details_rows, $message_html, $cta_label = 'Open Dashboard', $cta_url = '') {
        if (empty($to)) {
            return false;
        }

        // Clean recipient email
        if (!is_email($to) || str_ends_with($to, '.local') || str_ends_with($to, '.invalid')) {
            return false;
        }

        $cta_link = !empty($cta_url) ? esc_url($cta_url) : self::DASHBOARD_URL;
        $bst_time = wp_date('M j, Y g:i A \B\S\T', null, new DateTimeZone('Asia/Dhaka'));

        // Color mapping
        $badge_bg_map = [
            'red'    => '#fee2e2',
            'amber'  => '#fef3c7',
            'blue'   => '#dbeafe',
            'purple' => '#f3e8ff',
            'green'  => '#d1fae5'
        ];
        $badge_txt_map = [
            'red'    => '#dc2626',
            'amber'  => '#d97706',
            'blue'   => '#2563eb',
            'purple' => '#7c3aed',
            'green'  => '#059669'
        ];
        $badge_bg = $badge_bg_map[$badge_color] ?? '#dbeafe';
        $badge_txt = $badge_txt_map[$badge_color] ?? '#2563eb';

        // Build details table
        $details_html = '';
        if (!empty($details_rows) && is_array($details_rows)) {
            $details_html .= '<div style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px 20px; margin:22px 0;">';
            $details_html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">';
            foreach ($details_rows as $row) {
                $label = esc_html($row['label'] ?? '');
                $val = $row['value'] ?? '';
                $is_raw = !empty($row['raw']);
                $val_display = $is_raw ? $val : esc_html($val);
                $details_html .= '<tr>';
                $details_html .= '<td style="padding:6px 0; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; width:38%; vertical-align:top;">' . $label . '</td>';
                $details_html .= '<td style="padding:6px 0; font-size:14px; font-weight:700; color:#0f172a; width:62%; vertical-align:top;">' . $val_display . '</td>';
                $details_html .= '</tr>';
            }
            $details_html .= '</table></div>';
        }

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($subject) . '</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:580px; border-collapse:collapse; background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 10px 25px -5px rgba(0,0,0,0.06), 0 8px 10px -6px rgba(0,0,0,0.04); border:1px solid #e2e8f0;">
                    
                    <!-- Top Brand Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); padding:28px 32px; text-align:left;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <div style="font-size:18px; font-weight:900; letter-spacing:0.8px; color:#ffffff; text-transform:uppercase;">' . esc_html(self::BRAND_NAME) . '</div>
                                        <div style="font-size:11px; font-weight:600; letter-spacing:1.5px; color:#93c5fd; text-transform:uppercase; margin-top:3px;">TEAM OPERATIONS MANAGEMENT</div>
                                    </td>
                                    <td align="right">
                                        <div style="display:inline-block; padding:4px 10px; background:rgba(255,255,255,0.15); border-radius:6px; color:#ffffff; font-size:11px; font-weight:700; letter-spacing:0.5px;">OFFICIAL</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Card -->
                    <tr>
                        <td style="padding:32px 32px 28px 32px;">
                            <!-- Badge -->
                            <div style="margin-bottom:14px;">
                                <span style="display:inline-block; padding:5px 12px; font-size:11px; font-weight:800; letter-spacing:0.6px; text-transform:uppercase; border-radius:6px; background-color:' . $badge_bg . '; color:' . $badge_txt . ';">' . esc_html($badge_text) . '</span>
                            </div>

                            <!-- Headline -->
                            <h2 style="margin:0 0 12px 0; font-size:21px; font-weight:800; color:#0f172a; line-height:1.35;">' . esc_html($headline) . '</h2>

                            <!-- Details Table -->
                            ' . $details_html . '

                            <!-- Message Content -->
                            <div style="font-size:14px; line-height:1.65; color:#334155; margin:18px 0 26px 0;">
                                ' . $message_html . '
                            </div>

                            <!-- CTA Button -->
                            <div style="text-align:center; margin:28px 0 10px 0;">
                                <a href="' . $cta_link . '" target="_blank" style="display:inline-block; padding:13px 32px; background:linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; border-radius:8px; box-shadow:0 4px 12px rgba(37,99,235,0.28); letter-spacing:0.3px;">
                                    ' . esc_html($cta_label) . ' &rarr;
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8fafc; border-top:1px solid #e2e8f0; padding:22px 32px; text-align:center;">
                            <div style="font-size:12px; font-weight:600; color:#64748b;">
                                Dispatched: <strong style="color:#334155;">' . $bst_time . '</strong>
                            </div>
                            <div style="font-size:11px; color:#94a3b8; margin-top:6px; line-height:1.5;">
                                This is an automated notification from ' . esc_html(self::BRAND_NAME) . ' Team Operations System.<br>
                                Confidential &bull; Intended solely for authorized agency specialists and leadership.
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::BRAND_NAME . ' <' . self::SENDER_EMAIL . '>',
            'Reply-To: Support <' . self::SENDER_EMAIL . '>'
        ];

        return wp_mail($to, $subject, $html, $headers);
    }

    /**
     * Helper: Check if user is muted executive (Chairman / Executive monitor view-only)
     */
    private static function is_muted_executive($user_id) {
        $uid = (int)$user_id;
        if ($uid <= 0) return false;
        if (class_exists('FTM_Permissions')) {
            if (FTM_Permissions::is_super_admin($uid)) {
                return false; // Manager / Super Admin (Pranto) receives notifications
            }
            if (FTM_Permissions::is_executive($uid) || FTM_Permissions::is_deputy($uid)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 1. Send Broadcast Notice Email
     */
    public static function send_notice_email($notice_id, $title, $message, $notice_type, $target_type, $target_value) {
        global $wpdb;

        $badge_map = [
            'urgent'       => ['label' => 'URGENT NOTICE', 'color' => 'red'],
            'important'    => ['label' => 'IMPORTANT ANNOUNCEMENT', 'color' => 'amber'],
            'announcement' => ['label' => 'OFFICIAL NOTICE', 'color' => 'blue'],
            'kudos'        => ['label' => 'TEAM RECOGNITION', 'color' => 'green']
        ];
        $badge = $badge_map[$notice_type] ?? $badge_map['announcement'];

        // Determine recipient user IDs
        $recipient_uids = [];
        if ($target_type === 'member') {
            $mid = (int)$target_value;
            if ($mid > 0) $recipient_uids[] = $mid;
        } elseif ($target_type === 'team') {
            $team_name = sanitize_text_field($target_value);
            $uids = $wpdb->get_col($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'ftm_team' AND meta_value = %s",
                $team_name
            ));
            $recipient_uids = array_map('intval', $uids ?: []);
        } else {
            // All active members
            $uids = $wpdb->get_col("SELECT ID FROM {$wpdb->users}");
            $recipient_uids = array_map('intval', $uids ?: []);
        }

        $sent_count = 0;
        foreach ($recipient_uids as $uid) {
            if (self::is_muted_executive($uid)) {
                continue;
            }
            $user = get_userdata($uid);
            if (!$user || empty($user->user_email)) {
                continue;
            }

            $subject = '📢 [' . $badge['label'] . '] ' . $title;
            $headline = $title;
            $details_rows = [
                ['label' => 'Notice Type', 'value' => ucwords($notice_type)],
                ['label' => 'Audience', 'value' => ($target_type === 'team') ? ('Squad: ' . $target_value) : ($target_type === 'member' ? 'Direct Specialist' : 'All Agency Specialists')],
                ['label' => 'Published By', 'value' => 'Operations Management']
            ];

            $msg_formatted = nl2br(esc_html($message));
            $cta_url = self::DASHBOARD_URL . '#agency-notices';

            $sent = self::send_html(
                $user->user_email,
                $subject,
                $headline,
                $badge['label'],
                $badge['color'],
                $details_rows,
                $msg_formatted,
                'Read Notice in Dashboard',
                $cta_url
            );

            if ($sent) $sent_count++;
        }

        return $sent_count;
    }

    /**
     * 2. Send Project Assigned Email
     */
    public static function send_project_assigned_email($project_id, $assigned_user_ids, $is_new = true) {
        global $wpdb;
        $pid = (int)$project_id;
        $table = $wpdb->prefix . 'ftm_projects';

        $p = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $pid));
        if (!$p) return false;

        $order_id = $p->order_id ?: ('#' . $pid);
        $net_val = (float)($p->net_order_value ?: ($p->gross_order_value * 0.8));
        $delivery_date_str = !empty($p->delivery_date) ? wp_date('M j, Y g:i A \B\S\T', strtotime($p->delivery_date), new DateTimeZone('Asia/Dhaka')) : 'TBD';

        $sent_count = 0;
        foreach ($assigned_user_ids as $uid) {
            $uid = (int)$uid;
            if (self::is_muted_executive($uid)) continue;

            $user = get_userdata($uid);
            if (!$user || empty($user->user_email)) continue;

            $subject = '🎯 [Project Assigned] Order ' . $order_id . ' - ' . ($p->client_name ?: 'Client Deliverable');
            $headline = 'You Have Been Assigned to Order ' . $order_id;
            $badge_text = 'NEW PROJECT ASSIGNMENT';
            $badge_color = 'blue';

            $details_rows = [
                ['label' => 'Order ID', 'value' => $order_id],
                ['label' => 'Client', 'value' => $p->client_name ?: '—'],
                ['label' => 'Fiverr Profile', 'value' => $p->profile_name ?: '—'],
                ['label' => 'Squad', 'value' => $p->team_name ?: 'WpCraft'],
                ['label' => 'Order Net Value', 'value' => '$' . number_format($net_val, 2)],
                ['label' => 'Delivery Deadline', 'value' => $delivery_date_str]
            ];

            $msg_html = '<p>A project order has been assigned to you. Please review the project details, client requirements, and delivery timeline in your workspace.</p>';
            if (!empty($p->notes)) {
                $msg_html .= '<div style="margin-top:10px; padding:10px 14px; background:#f1f5f9; border-left:3px solid #2563eb; font-size:13px; color:#475569;"><strong>Client / Order Note:</strong> ' . esc_html($p->notes) . '</div>';
            }

            $cta_url = self::DASHBOARD_URL . '#my-projects';

            $sent = self::send_html(
                $user->user_email,
                $subject,
                $headline,
                $badge_text,
                $badge_color,
                $details_rows,
                $msg_html,
                'View Project in Workspace',
                $cta_url
            );

            if ($sent) $sent_count++;
        }

        return $sent_count;
    }

    /**
     * 3. Send Task / Milestone Assigned Email
     */
    public static function send_task_assigned_email($task_id, $project_id, $assigned_user_ids) {
        global $wpdb;
        $tid = (int)$task_id;
        $pid = (int)$project_id;
        $tasks_table = $wpdb->prefix . 'ftm_project_tasks';
        $projects_table = $wpdb->prefix . 'ftm_projects';

        $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tasks_table WHERE id = %d", $tid));
        if (!$task) return false;

        $project = $wpdb->get_row($wpdb->prepare("SELECT id, order_id, client_name, profile_name, team_name, delivery_date FROM $projects_table WHERE id = %d", $pid));
        $order_id = $project ? ($project->order_id ?: ('#' . $pid)) : 'Project #' . $pid;

        $due_str = !empty($task->deadline) ? wp_date('M j, Y g:i A \B\S\T', strtotime($task->deadline), new DateTimeZone('Asia/Dhaka')) : 'Prompt Delivery';

        $badge_map = [
            'urgent' => ['label' => 'URGENT TASK', 'color' => 'red'],
            'high'   => ['label' => 'HIGH PRIORITY TASK', 'color' => 'amber'],
            'medium' => ['label' => 'NEW TASK ASSIGNED', 'color' => 'blue'],
            'low'    => ['label' => 'NEW TASK ASSIGNED', 'color' => 'blue']
        ];
        $priority = strtolower($task->priority ?: 'medium');
        $badge = $badge_map[$priority] ?? $badge_map['medium'];

        $sent_count = 0;
        foreach ($assigned_user_ids as $uid) {
            $uid = (int)$uid;
            if (self::is_muted_executive($uid)) continue;

            $user = get_userdata($uid);
            if (!$user || empty($user->user_email)) continue;

            $subject = '📌 [' . $badge['label'] . '] ' . $task->title . ' (' . $order_id . ')';
            $headline = 'New Milestone / Task Assigned';

            $details_rows = [
                ['label' => 'Task Title', 'value' => $task->title],
                ['label' => 'Associated Order', 'value' => $order_id . ' (' . ($project->client_name ?? 'Client') . ')'],
                ['label' => 'Priority', 'value' => strtoupper($priority)],
                ['label' => 'Due Date', 'value' => $due_str]
            ];

            $msg_html = '<p>A task milestone has been assigned to you. Completing this task will automatically record your daily operational progress.</p>';
            if (!empty($task->description)) {
                $msg_html .= '<div style="margin-top:10px; padding:10px 14px; background:#f1f5f9; border-left:3px solid #3b82f6; font-size:13px; color:#475569;">' . nl2br(esc_html($task->description)) . '</div>';
            }

            $cta_url = self::DASHBOARD_URL . '#my-tasks';

            $sent = self::send_html(
                $user->user_email,
                $subject,
                $headline,
                $badge['label'],
                $badge['color'],
                $details_rows,
                $msg_html,
                'View & Track Task',
                $cta_url
            );

            if ($sent) $sent_count++;
        }

        return $sent_count;
    }

    /**
     * 4. Send Approaching / Urgent Deadline Warning Email (< 24 Hours / Overdue)
     */
    public static function send_deadline_warning_email($project_id, $hours_left) {
        global $wpdb;
        $pid = (int)$project_id;
        $projects_table = $wpdb->prefix . 'ftm_projects';
        $assignees_table = $wpdb->prefix . 'ftm_project_assignees';

        $p = $wpdb->get_row($wpdb->prepare("SELECT * FROM $projects_table WHERE id = %d", $pid));
        if (!$p) return false;

        // Skip completed or cancelled projects
        if (in_array($p->status, ['completed', 'cancelled', 'delivery_accepted', 'delivered', 'delivery_submitted'], true)) {
            return false;
        }

        $order_id = $p->order_id ?: ('#' . $pid);
        $hours_display = max(0, round($hours_left));
        $is_overdue = ($hours_left <= 0);

        // Anti-spam throttling: do not send more than once every 12 hours for the same project
        $transient_key = 'ftm_deadline_mail_' . $pid;
        if (get_transient($transient_key)) {
            return false;
        }
        set_transient($transient_key, time(), 12 * HOUR_IN_SECONDS);

        // Fetch assignees
        $assignee_uids = [];
        if (!empty($p->assigned_user_id)) {
            $assignee_uids[] = (int)$p->assigned_user_id;
        }
        if ($wpdb->get_var("SHOW TABLES LIKE '$assignees_table'") === $assignees_table) {
            $extra = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM $assignees_table WHERE project_id = %d", $pid));
            if (!empty($extra)) {
                $assignee_uids = array_unique(array_merge($assignee_uids, array_map('intval', $extra)));
            }
        }

        // Also notify squad leader
        if (class_exists('FTM_Notifications_Engine')) {
            $lead_ids = FTM_Notifications_Engine::get_leadership_user_ids($p->team_name);
            $assignee_uids = array_unique(array_merge($assignee_uids, $lead_ids));
        }

        $deadline_str = !empty($p->delivery_date) ? wp_date('M j, Y g:i A \B\S\T', strtotime($p->delivery_date), new DateTimeZone('Asia/Dhaka')) : 'Immediate';

        $subject = $is_overdue 
            ? '🚨 [OVERDUE ALERT] Order ' . $order_id . ' Delivery is Overdue!'
            : '⚠️ [URGENT DEADLINE] Order ' . $order_id . ' - Only ' . $hours_display . 'h Remaining!';
        
        $headline = $is_overdue 
            ? 'Action Required: Order ' . $order_id . ' Delivery is Overdue!'
            : 'Urgent Deadline Warning: Only ' . $hours_display . ' Hours Left!';

        $badge_text = $is_overdue ? 'DELIVERY OVERDUE' : 'URGENT DEADLINE (' . $hours_display . 'H LEFT)';
        $badge_color = 'red';

        $details_rows = [
            ['label' => 'Order ID', 'value' => $order_id],
            ['label' => 'Client', 'value' => $p->client_name ?: '—'],
            ['label' => 'Fiverr Profile', 'value' => $p->profile_name ?: '—'],
            ['label' => 'Squad', 'value' => $p->team_name ?: 'WpCraft'],
            ['label' => 'Current Status', 'value' => strtoupper($p->status ?: 'WIP')],
            ['label' => 'Delivery Deadline', 'value' => $deadline_str]
        ];

        $msg_html = '<div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px 16px; margin-bottom:14px; color:#991b1b; font-size:13px; font-weight:600;">
            ' . ($is_overdue 
                ? '⚠️ This order deadline has passed. Please deliver immediately or coordinate a client extension to avoid late delivery penalties.' 
                : '⏰ This order is due within ' . $hours_display . ' hours. Ensure all final QA checks are complete and submit the delivery.') . '
        </div>';
        $msg_html .= '<p style="margin:0; font-size:13px; color:#475569;">If you need a delivery extension or leadership support, contact your Team Leader immediately.</p>';

        $cta_url = self::DASHBOARD_URL . '#dashboard';

        $sent_count = 0;
        foreach ($assignee_uids as $uid) {
            if (self::is_muted_executive($uid)) continue;
            $user = get_userdata($uid);
            if (!$user || empty($user->user_email)) continue;

            $sent = self::send_html(
                $user->user_email,
                $subject,
                $headline,
                $badge_text,
                $badge_color,
                $details_rows,
                $msg_html,
                'Open Order in Workspace',
                $cta_url
            );
            if ($sent) $sent_count++;
        }

        return $sent_count;
    }

    /**
     * Periodic cron: Check active deliverables and send urgent deadline warnings (< 24h)
     */
    public static function check_and_send_approaching_deadlines() {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_projects';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return 0;
        }

        $now = time();
        // Fetch active projects
        $projects = $wpdb->get_results("
            SELECT id, delivery_date, status, order_id 
            FROM $table 
            WHERE status IN ('wip', 'nra', 'revision') 
              AND delivery_date IS NOT NULL 
              AND delivery_date != ''
        ");

        $sent = 0;
        foreach ($projects as $p) {
            $delivery_time = strtotime($p->delivery_date);
            if (!$delivery_time) continue;

            $hours_left = ($delivery_time - $now) / 3600;

            // Trigger for orders within 24 hours (or overdue up to 24h)
            if ($hours_left <= 24 && $hours_left >= -24) {
                if (self::send_deadline_warning_email($p->id, $hours_left)) {
                    $sent++;
                }
            }
        }

        return $sent;
    }
}

FTM_Mailer::init();
