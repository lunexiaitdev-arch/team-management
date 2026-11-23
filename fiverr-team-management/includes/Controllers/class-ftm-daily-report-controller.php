<?php
/**
 * FTM Daily Progress & Team Status Report Controller
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Daily_Report_Controller extends FTM_REST_Controller {

    public function register_routes() {
        // Daily Report Submissions (Member / Team Queue)
        register_rest_route($this->namespace, '/daily-reports', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_daily_reports'],
                'permission_callback' => [$this, 'check_auth_permission']
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'submit_daily_report'],
                'permission_callback' => [$this, 'check_auth_permission']
            ]
        ]);

        // Leader Review Action (Approve / Request Revision)
        register_rest_route($this->namespace, '/daily-reports/(?P<id>\d+)/review', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'review_daily_report'],
            'permission_callback' => [$this, 'check_leader_permission']
        ]);

        // Team Leader Forward to Super Admin / Manager
        register_rest_route($this->namespace, '/daily-reports/team-forward', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'forward_team_report'],
            'permission_callback' => [$this, 'check_leader_permission']
        ]);

        // Agency-Wide Consolidated Daily Digest (Super Admin / Manager & Management)
        register_rest_route($this->namespace, '/daily-reports/agency-summary', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_agency_summary'],
            'permission_callback' => [$this, 'check_executive_or_admin_permission']
        ]);

        // Weekly Operations Summary & Breakdown (Manager / Team View)
        register_rest_route($this->namespace, '/daily-reports/weekly-summary', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_weekly_summary'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);
    }

    public function get_daily_reports($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $date = sanitize_text_field($request->get_param('date') ?: '');
        $date_from = sanitize_text_field($request->get_param('date_from') ?: '');
        $date_to = sanitize_text_field($request->get_param('date_to') ?: '');
        $status_filter = sanitize_text_field($request->get_param('status') ?: '');
        $search = sanitize_text_field($request->get_param('search') ?: '');
        $team = sanitize_text_field($request->get_param('team') ?: '');
        $scope = sanitize_text_field($request->get_param('scope') ?: '');
        $user_id_param = $request->get_param('user_id');

        $reports_table = $wpdb->prefix . 'ftm_daily_reports';
        $items_table = $wpdb->prefix . 'ftm_daily_report_items';

        $where = [];
        $params = [];

        $can_view_all = FTM_Permissions::is_super_admin($current_user_id) || FTM_Permissions::is_executive($current_user_id);
        $can_view_team = FTM_Permissions::is_leader($current_user_id);

        // Check if personal/own standups requested
        $is_mine = ($scope === 'mine' || $user_id_param === 'mine' || !empty($request->get_param('mine')));

        if ((!$can_view_all && !$can_view_team) || $is_mine) {
            // Standard member can ONLY ever see their own; or any user explicitly requesting their personal standups
            $where[] = "r.user_id = %d";
            $params[] = $current_user_id;
        } elseif (!empty($user_id_param) && is_numeric($user_id_param)) {
            $target_uid = (int)$user_id_param;
            if ($can_view_all || FTM_Permissions::can_manage_user($current_user_id, $target_uid)) {
                $where[] = "r.user_id = %d";
                $params[] = $target_uid;
            } else {
                $where[] = "r.user_id = %d";
                $params[] = $current_user_id;
            }
        } elseif (!$can_view_all) {
            // Squad Leader sees their team rollup (excluding super admin)
            $user_team = FTM_Permissions::get_user_team($current_user_id);
            $where[] = "r.team_name = %s";
            $params[] = $user_team;
        } elseif (!empty($team) && $team !== 'all') {
            // Manager / Executive filtering by specific team
            $where[] = "r.team_name = %s";
            $params[] = $team;
        }

        // Date filtering (supports date=all or date_from/date_to for managers & leaders):
        if (!empty($date_from) || !empty($date_to)) {
            if (!empty($date_from)) {
                $where[] = "r.report_date >= %s";
                $params[] = $date_from;
            }
            if (!empty($date_to)) {
                $where[] = "r.report_date <= %s";
                $params[] = $date_to;
            }
        } elseif (!empty($date) && strtolower($date) !== 'all') {
            $where[] = "r.report_date = %s";
            $params[] = $date;
        } elseif (empty($date) && !$is_mine) {
            // Default for single-date rollup callers without date=all
            $where[] = "r.report_date = %s";
            $params[] = current_time('Y-m-d');
        }

        if (!empty($status_filter) && $status_filter !== 'all') {
            $where[] = "r.status = %s";
            $params[] = $status_filter;
        }

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = "(u.display_name LIKE %s OR r.team_name LIKE %s OR EXISTS (SELECT 1 FROM $items_table si WHERE si.report_id = r.id AND (si.order_id LIKE %s OR si.client_name LIKE %s OR si.tasks_completed LIKE %s)))";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit_val = $is_mine ? 60 : ((strtolower($date) === 'all' || !empty($date_from) || !empty($date_to)) ? 200 : 150);

        $sql = "SELECT r.* FROM $reports_table r LEFT JOIN {$wpdb->users} u ON u.ID = r.user_id $where_sql ORDER BY r.report_date DESC, r.id DESC LIMIT %d";
        $params[] = $limit_val;
        $reports = $wpdb->get_results($wpdb->prepare($sql, $params));

        $output = [];
        foreach ($reports as $r) {
            $user = get_userdata($r->user_id);
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $items_table WHERE report_id = %d", $r->id));

            $custom_avatar = get_user_meta($r->user_id, 'ftm_custom_avatar', true);
            $avatar_url = !empty($custom_avatar) ? $custom_avatar : get_avatar_url($r->user_id, ['size' => 128, 'default' => 'mm']);

            $reviewer = (int)$r->reviewed_by > 0 ? get_userdata((int)$r->reviewed_by) : null;
            $reviewer_name = $reviewer ? $reviewer->display_name : '';

            // Member accomplishments & delivery tracking for this dedicated day
            $p_table = $wpdb->prefix . 'ftm_projects';
            $h_table = $wpdb->prefix . 'ftm_project_history';

            $delivered = $wpdb->get_results($wpdb->prepare(
                "SELECT id, order_id, client_name, profile_name, gross_order_value, net_delivery_value, star_rating, client_reviewed, extension_count, extension_days, extension_reason, delivered_without_extension, delivered_date 
                 FROM $p_table 
                 WHERE (assigned_user_id = %d OR FIND_IN_SET(%d, assigned_user_ids)) 
                   AND status IN ('delivery_accepted', 'delivery_submitted') 
                   AND (
                       DATE(delivered_date) = %s 
                       OR (delivered_date IS NULL AND DATE(updated_at) = %s)
                       OR EXISTS (
                           SELECT 1 FROM $h_table h 
                           WHERE h.project_id = id 
                             AND DATE(h.created_at) = %s 
                             AND h.new_status IN ('delivery_accepted', 'delivery_submitted')
                       )
                   )
                 ORDER BY id DESC",
                $r->user_id, $r->user_id, $r->report_date, $r->report_date, $r->report_date
            ));

            $extended = $wpdb->get_results($wpdb->prepare(
                "SELECT p.id, p.order_id, p.client_name, p.profile_name, p.delivery_date, 
                        p.extension_count, p.extension_days, p.extension_reason, p.status, 
                        h.note as history_note 
                 FROM $p_table p
                 INNER JOIN $h_table h ON h.project_id = p.id
                 WHERE (p.assigned_user_id = %d OR FIND_IN_SET(%d, p.assigned_user_ids)) 
                   AND DATE(h.created_at) = %s 
                   AND (h.note LIKE '%%Delivery extended%%' OR h.note LIKE '%%extend%%' OR h.note LIKE '%%Extension%%')
                   AND p.status NOT IN ('cancelled')
                 GROUP BY p.id
                 ORDER BY h.id DESC LIMIT 10",
                $r->user_id, $r->user_id, $r->report_date
            ));

            $late = $wpdb->get_results($wpdb->prepare(
                "SELECT id, order_id, client_name, profile_name, delivery_date, status, DATEDIFF(%s, delivery_date) as days_late 
                 FROM $p_table 
                 WHERE (assigned_user_id = %d OR FIND_IN_SET(%d, assigned_user_ids)) 
                   AND status IN ('wip', 'nra', 'revision') 
                   AND delivery_date < %s
                 ORDER BY delivery_date ASC LIMIT 10",
                $r->report_date, $r->user_id, $r->user_id, $r->report_date
            ));

            $leave_id = 0;
            if ($r->report_type === 'on_leave') {
                if (!empty($items)) {
                    foreach ($items as $it) {
                        if (!empty($it->order_id) && strpos($it->order_id, 'LEAVE-') === 0) {
                            $leave_id = (int)str_replace('LEAVE-', '', $it->order_id);
                            break;
                        }
                    }
                }
                if (!$leave_id) {
                    $leaves_tbl = $wpdb->prefix . 'ftm_leaves';
                    $leave_id = (int)$wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $leaves_tbl WHERE user_id = %d AND %s BETWEEN start_date AND end_date ORDER BY id DESC LIMIT 1",
                        $r->user_id, $r->report_date
                    ));
                }
            }

            $output[] = [
                'id' => (int)$r->id,
                'leave_id' => $leave_id,
                'user_id' => (int)$r->user_id,
                'user_name' => $user ? $user->display_name : 'Unknown',
                'call_sign' => get_user_meta($r->user_id, 'ftm_call_sign', true) ?: ($user ? $user->display_name : ''),
                'custom_id' => get_user_meta($r->user_id, 'ftm_custom_id', true) ?: ('#' . (int)$r->user_id),
                'role_label' => FTM_Permissions::get_role_label(FTM_Permissions::get_user_role($r->user_id)),
                'user_avatar' => $avatar_url,
                'team_name' => $r->team_name,
                'report_type' => !empty($r->report_type) ? $r->report_type : 'work',
                'leave_start_date' => $r->leave_start_date,
                'leave_end_date' => $r->leave_end_date,
                'leave_type' => $r->leave_type,
                'leave_type_label' => !empty($r->leave_type) ? ucwords(str_replace('_', ' ', $r->leave_type)) : '',
                'leave_reason' => $r->leave_reason,
                'report_date' => $r->report_date,
                'status' => $r->status,
                'status_label' => $this->get_report_status_label($r->status),
                'is_late' => (int)($r->is_late ?? 0),
                'submission_time' => $r->submission_time ?? null,
                'leader_reviewed_by' => (int)($r->leader_reviewed_by ?? 0),
                'leader_reviewed_at' => $r->leader_reviewed_at ?? null,
                'manager_reviewed_by' => (int)($r->manager_reviewed_by ?? 0),
                'manager_reviewed_at' => $r->manager_reviewed_at ?? null,
                'manager_feedback' => !empty($r->manager_feedback) ? html_entity_decode(wp_unslash($r->manager_feedback), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
                'leader_feedback' => !empty($r->leader_feedback) ? html_entity_decode(wp_unslash($r->leader_feedback), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
                'reviewed_by' => (int)$r->reviewed_by,
                'reviewer_name' => $reviewer_name,
                'reviewed_at' => $r->reviewed_at,
                'delivered_projects' => array_map(function($dp) {
                    $ext_count = max(0, (int)$dp->extension_count);
                    $is_on_time = ($ext_count === 0);
                    return [
                        'id' => (int)$dp->id,
                        'order_id' => '#' . ltrim($dp->order_id, '#'),
                        'client_name' => $dp->client_name,
                        'profile_name' => $dp->profile_name,
                        'net_value' => (float)$dp->net_delivery_value,
                        'star_rating' => (float)$dp->star_rating,
                        'extension_count' => $ext_count,
                        'extension_days' => (int)$dp->extension_days,
                        'extension_reason' => $dp->extension_reason ?: '',
                        'delivered_without_extension' => $is_on_time ? 1 : 0,
                        'on_time_label' => $is_on_time ? 'On-Time (No Extension)' : 'Extended (' . $ext_count . 'x · +' . (int)$dp->extension_days . 'd)'
                    ];
                }, $delivered ?: []),
                'extended_projects' => array_map(function($ep) {
                    $ext_days = (int)$ep->extension_days;
                    if (!empty($ep->history_note) && preg_match('/\+(\d+)\s*days?/i', $ep->history_note, $m_days)) {
                        $ext_days = (int)$m_days[1];
                    }
                    return [
                        'id' => (int)$ep->id,
                        'order_id' => '#' . ltrim($ep->order_id, '#'),
                        'client_name' => $ep->client_name,
                        'extension_count' => max(1, (int)$ep->extension_count),
                        'extension_days' => $ext_days,
                        'extension_reason' => $ep->extension_reason ?: 'Client requested revision/more time',
                        'status' => $ep->status
                    ];
                }, $extended ?: []),
                'late_projects' => array_map(function($lp) {
                    return [
                        'id' => (int)$lp->id,
                        'order_id' => '#' . ltrim($lp->order_id, '#'),
                        'client_name' => $lp->client_name,
                        'delivery_date' => $lp->delivery_date,
                        'days_late' => (int)$lp->days_late,
                        'status' => $lp->status
                    ];
                }, $late ?: []),
                'total_hours' => array_reduce($items ?: [], function($acc, $it) { return $acc + (float)$it->hours_spent; }, 0.0),
                'items' => array_map(function($item) {
                    $desc = html_entity_decode(wp_unslash($item->tasks_completed ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    return [
                        'id' => (int)$item->id,
                        'project_id' => (int)$item->project_id,
                        'order_id' => $item->order_id,
                        'client_name' => $item->client_name,
                        'tasks_completed' => $desc,
                        'task_description' => $desc,
                        'work_in_progress' => html_entity_decode(wp_unslash($item->work_in_progress ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                        'next_day_plan' => html_entity_decode(wp_unslash($item->next_day_plan ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                        'has_blocker' => (bool)$item->has_blocker,
                        'blocker_description' => html_entity_decode(wp_unslash($item->blocker_description ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                        'hours_spent' => (float)$item->hours_spent,
                        'progress_percent' => (int)$item->progress_percent
                    ];
                }, $items),
                'created_at' => $r->created_at
            ];
        }

        return rest_ensure_response($output);
    }

    private function get_report_status_label($status) {
        switch ($status) {
            case 'draft': return 'Draft';
            case 'submitted': return 'Awaiting Leader Review';
            case 'leader_approved': return 'Leader Verified (Awaiting Manager)';
            case 'approved': return 'Final Approved';
            case 'revision_requested': return 'Revision Requested';
            default: return ucwords(str_replace('_', ' ', $status));
        }
    }

    public static function parse_hours($val) {
        $str = strtolower(trim((string)$val));
        if (empty($str)) {
            return 0.00;
        }

        if (preg_match('/^(\d+)?\s*:\s*(\d{1,2})$/', $str, $m)) {
            $h = !empty($m[1]) ? (int)$m[1] : 0;
            $mins = !empty($m[2]) ? (int)$m[2] : 0;
            return round($h + ($mins / 60), 2);
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s*(?:h|hr|hrs|hours?)\s*(\d+(?:\.\d+)?)\s*(?:m|min|mins|minutes?)?$/i', $str, $m)) {
            $h = (float)$m[1];
            $mins = (float)$m[2];
            return round($h + ($mins / 60), 2);
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s*(?:h|hr|hrs|hours?)$/i', $str, $m)) {
            return round((float)$m[1], 2);
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s*(?:m|min|mins|minutes?)$/i', $str, $m)) {
            return round(((float)$m[1]) / 60, 2);
        }

        if (preg_match('/^(\d+)\.(\d{2})$/', $str, $m)) {
            $h = (int)$m[1];
            $mins = (int)$m[2];
            if ($mins === 30) return round($h + 0.5, 2);
            if ($mins === 15) return round($h + 0.25, 2);
            if ($mins === 45) return round($h + 0.75, 2);
            if ($mins < 60 && $mins % 5 === 0 && $mins !== 25 && $mins !== 50 && $mins !== 75) {
                return round($h + ($mins / 60), 2);
            }
        }

        if (is_numeric($str)) {
            $num = (float)$str;
            if ($num > 24 && floor($num) == $num) {
                return round($num / 60, 2);
            }
            return round($num, 2);
        }

        return 0.00;
    }

    public function submit_daily_report($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $data = $request->get_json_params();

        $report_type = sanitize_text_field($data['report_type'] ?? 'work');
        $date = sanitize_text_field($data['report_date'] ?? current_time('Y-m-d'));
        $team = FTM_Permissions::get_user_team($current_user_id);
        $reports_table = $wpdb->prefix . 'ftm_daily_reports';
        $items_table = $wpdb->prefix . 'ftm_daily_report_items';

        // 1. Weekend Submission
        if ($report_type === 'weekend') {
            $today = current_time('Y-m-d');
            $earliest = gmdate('Y-m-d', current_time('timestamp') - 14 * DAY_IN_SECONDS);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > $today || $date < $earliest) {
                return $this->format_error(__('Off days can only be recorded for today or the previous 14 days.', 'fiverr-team-management'), 400);
            }
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id, report_type FROM $reports_table WHERE user_id = %d AND report_date = %s",
                $current_user_id,
                $date
            ));
            if ($existing && $existing->report_type === 'work' && !FTM_Permissions::is_leader($current_user_id) && !FTM_Permissions::is_super_admin($current_user_id)) {
                return $this->format_error(__('A work report already exists for this date. Delete it first if this was an off day.', 'fiverr-team-management'), 409);
            }

            $is_leader_or_admin = FTM_Permissions::is_leader($current_user_id) || FTM_Permissions::is_super_admin($current_user_id);
            $initial_status = $is_leader_or_admin ? 'approved' : 'submitted';

            if ($existing) {
                $wpdb->update($reports_table, [
                    'report_type' => 'weekend',
                    'leave_start_date' => null,
                    'leave_end_date' => null,
                    'leave_type' => null,
                    'leave_reason' => null,
                    'status' => $initial_status,
                    'team_name' => $team
                ], ['id' => $existing->id]);
                $report_id = (int)$existing->id;
                $wpdb->delete($items_table, ['report_id' => $report_id]);
            } else {
                $wpdb->insert($reports_table, [
                    'user_id' => $current_user_id,
                    'team_name' => $team,
                    'report_type' => 'weekend',
                    'report_date' => $date,
                    'status' => $initial_status
                ]);
                $report_id = (int)$wpdb->insert_id;
            }

            $wpdb->insert($items_table, [
                'report_id' => $report_id,
                'project_id' => 0,
                'order_id' => 'WEEKEND',
                'client_name' => 'Weekly Off / Weekend',
                'tasks_completed' => 'Weekly Off / Weekend',
                'work_in_progress' => '',
                'next_day_plan' => '',
                'has_blocker' => 0,
                'hours_spent' => 0.00,
                'progress_percent' => 0
            ]);

            if (!$is_leader_or_admin && class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'broadcast_to_leadership')) {
                try {
                    $user_obj = get_userdata($current_user_id);
                    $u_name = $user_obj ? $user_obj->display_name : 'Member';
                    FTM_Notifications_Engine::broadcast_to_leadership([
                        'title' => '🌴 Weekend / Day-Off Submitted by Member',
                        'message' => "{$u_name} submitted weekend / off day for {$date}. Requires leader approval.",
                        'type' => 'weekend_submitted',
                        'target_id' => $report_id,
                        'team' => $team
                    ]);
                } catch (\Throwable $e) {}
            }

            $success_msg = $is_leader_or_admin 
                ? __('Weekend / Off Day recorded successfully!', 'fiverr-team-management')
                : __('Weekend / Off Day submitted successfully! Awaiting Squad Leader approval.', 'fiverr-team-management');

            return $this->format_success(['report_id' => $report_id, 'status' => $initial_status], $success_msg);
        }

        // 2. Leave Submission (From Date to To Date, with Reason and Leave Type)
        if ($report_type === 'on_leave') {
            $leave_start_date = sanitize_text_field($data['leave_start_date'] ?? $date);
            $leave_end_date = sanitize_text_field($data['leave_end_date'] ?? $leave_start_date);
            $leave_type = sanitize_text_field($data['leave_type'] ?? 'casual_leave');
            $leave_reason = sanitize_textarea_field($data['leave_reason'] ?? '');

            if (empty($leave_start_date) || empty($leave_end_date)) {
                return $this->format_error(__('Start date and end date are required for leave notice.', 'fiverr-team-management'), 400);
            }
            if ($leave_start_date > $leave_end_date) {
                return $this->format_error(__('Leave start date cannot be after end date.', 'fiverr-team-management'), 400);
            }

            $start_dt = new DateTime($leave_start_date);
            $end_dt = new DateTime($leave_end_date);
            $interval = $start_dt->diff($end_dt);
            $days = (int)$interval->days;
            if ($days > 60) {
                return $this->format_error(__('Leave span cannot exceed 60 days in one submission.', 'fiverr-team-management'), 400);
            }

            $period = new DatePeriod($start_dt, new DateInterval('P1D'), (clone $end_dt)->modify('+1 day'));
            $created_ids = [];

            $is_leader_or_admin = FTM_Permissions::is_leader($current_user_id) || FTM_Permissions::is_super_admin($current_user_id);
            $initial_status = $is_leader_or_admin ? 'approved' : 'submitted';

            foreach ($period as $dt) {
                $cur_date = $dt->format('Y-m-d');
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $reports_table WHERE user_id = %d AND report_date = %s",
                    $current_user_id,
                    $cur_date
                ));

                if ($existing) {
                    $wpdb->update($reports_table, [
                        'report_type' => 'on_leave',
                        'leave_start_date' => $leave_start_date,
                        'leave_end_date' => $leave_end_date,
                        'leave_type' => $leave_type,
                        'leave_reason' => $leave_reason,
                        'status' => $initial_status,
                        'team_name' => $team
                    ], ['id' => $existing->id]);
                    $r_id = (int)$existing->id;
                    $wpdb->delete($items_table, ['report_id' => $r_id]);
                } else {
                    $wpdb->insert($reports_table, [
                        'user_id' => $current_user_id,
                        'team_name' => $team,
                        'report_type' => 'on_leave',
                        'leave_start_date' => $leave_start_date,
                        'leave_end_date' => $leave_end_date,
                        'leave_type' => $leave_type,
                        'leave_reason' => $leave_reason,
                        'report_date' => $cur_date,
                        'status' => $initial_status
                    ]);
                    $r_id = (int)$wpdb->insert_id;
                }

                $wpdb->insert($items_table, [
                    'report_id' => $r_id,
                    'project_id' => 0,
                    'order_id' => 'LEAVE',
                    'client_name' => 'Leave: ' . ucwords(str_replace('_', ' ', $leave_type)),
                    'tasks_completed' => 'On Leave: ' . (!empty($leave_reason) ? $leave_reason : 'Scheduled leave notice'),
                    'work_in_progress' => '',
                    'next_day_plan' => '',
                    'has_blocker' => 0,
                    'hours_spent' => 0.00,
                    'progress_percent' => 0
                ]);
                $created_ids[] = $r_id;
            }

            if (!$is_leader_or_admin && class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'broadcast_to_leadership')) {
                try {
                    $user_obj = get_userdata($current_user_id);
                    $u_name = $user_obj ? $user_obj->display_name : 'Member';
                    FTM_Notifications_Engine::broadcast_to_leadership([
                        'title' => '🏖️ Leave Notice Submitted by Member',
                        'message' => "{$u_name} submitted leave ({$leave_start_date} to {$leave_end_date}). Requires leader approval.",
                        'type' => 'leave_submitted',
                        'target_id' => $created_ids[0] ?? 0,
                        'team' => $team
                    ]);
                } catch (\Throwable $e) {}
            }

            $leave_msg = $is_leader_or_admin
                ? sprintf(__('Leave notice recorded and approved for %d day(s) (%s to %s)!', 'fiverr-team-management'), count($created_ids), $leave_start_date, $leave_end_date)
                : sprintf(__('Leave application submitted for %d day(s) (%s to %s)! Awaiting Squad Leader approval.', 'fiverr-team-management'), count($created_ids), $leave_start_date, $leave_end_date);

            return $this->format_success([
                'report_ids' => $created_ids,
                'leave_start_date' => $leave_start_date,
                'leave_end_date' => $leave_end_date,
                'status' => $initial_status
            ], $leave_msg);
        }

        // 3. Regular Work Report
        $raw_status = sanitize_text_field($data['status'] ?? 'submitted'); // 'draft' or 'submitted'
        $final_status = ($raw_status === 'draft') ? 'draft' : 'submitted'; // Forwarded to Team Leader for review

        // 6:15 PM (18:15) Submission Cutoff check for daily report
        $is_today = ($date === current_time('Y-m-d'));
        $current_time_str = current_time('H:i');
        $is_late = ($is_today && $current_time_str > '18:15') || ($date < current_time('Y-m-d')) ? 1 : 0;
        $submission_time = current_time('H:i:s');

        $items = (array)($data['items'] ?? []);

        if (empty($items)) {
            return $this->format_error(__('At least one project work log is required.', 'fiverr-team-management'), 400);
        }

        $explicit_report_id = !empty($data['report_id']) ? (int)$data['report_id'] : 0;
        if ($explicit_report_id > 0) {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id, status, report_date FROM $reports_table WHERE id = %d AND user_id = %d",
                $explicit_report_id,
                $current_user_id
            ));
        } else {
            // Check if report already exists for today
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id, status, report_date FROM $reports_table WHERE user_id = %d AND report_date = %s",
                $current_user_id,
                $date
            ));
        }

        $replace_all = !empty($data['replace_all']);

        // Strict Validation for Submitted Reports
        if ($final_status === 'submitted') {
            // Rule 1: Every deliverable requires a valid time entry (hours_spent > 0)
            foreach ($items as $chk_idx => $chk_item) {
                $chk_h = self::parse_hours($chk_item['hours_spent'] ?? $chk_item['hours'] ?? 0.00);
                if ($chk_h <= 0.0001) {
                    $item_label = !empty($chk_item['order_id']) ? ('#' . $chk_item['order_id']) : ('Item ' . ($chk_idx + 1));
                    return $this->format_error(
                        sprintf(__('Every task deliverable requires a valid time entry (hours spent). Reports cannot be submitted without time entered (missing for %s).', 'fiverr-team-management'), $item_label),
                        400
                    );
                }
            }

            // Calculate incoming regular and extra hours
            $incoming_regular_hours = 0.00;
            $incoming_extra_hours = 0.00;
            $incoming_keys = [];

            foreach ($items as $it) {
                $h = self::parse_hours($it['hours_spent'] ?? $it['hours'] ?? 0.00);
                $is_ext = !empty($it['is_extra_time']);
                if ($is_ext) {
                    $ext_h = !empty($it['extra_time_hours']) ? self::parse_hours($it['extra_time_hours']) : $h;
                    $incoming_extra_hours += $ext_h;
                } else {
                    $incoming_regular_hours += $h;
                }
                $p_id = (int)($it['project_id'] ?? 0);
                $o_id = sanitize_text_field($it['order_id'] ?? '');
                $incoming_keys[] = $p_id > 0 ? "p_{$p_id}" : "o_{$o_id}";
            }

            // If not replace_all and existing report exists, add up existing other items not being updated
            $existing_regular_hours = 0.00;
            $existing_extra_hours = 0.00;
            if (!$replace_all && $existing) {
                $saved_items = $wpdb->get_results($wpdb->prepare(
                    "SELECT project_id, order_id, hours_spent, is_extra_time, extra_time_hours FROM $items_table WHERE report_id = %d",
                    $existing->id
                ));
                foreach ($saved_items ?: [] as $si) {
                    $si_key = ((int)$si->project_id > 0) ? "p_{$si->project_id}" : "o_{$si->order_id}";
                    if (!in_array($si_key, $incoming_keys, true)) {
                        if ((int)$si->is_extra_time === 1) {
                            $existing_extra_hours += (float)($si->extra_time_hours > 0 ? $si->extra_time_hours : $si->hours_spent);
                        } else {
                            $existing_regular_hours += (float)$si->hours_spent;
                        }
                    }
                }
            }

            $total_day_regular_hours = round($incoming_regular_hours + $existing_regular_hours, 2);
            $total_day_extra_hours = round($incoming_extra_hours + $existing_extra_hours, 2);

            // Rule 2: Regular work hours cannot exceed 8.00 hours per day
            if ($total_day_regular_hours > 8.00) {
                return $this->format_error(
                    sprintf(
                        __('Daily regular work report cannot exceed 8.00 hours (attempted: %sh). To record work beyond 8 hours (extra time/overtime), you must turn on and use the Work Tracker tool. Extra hours cannot be entered manually.', 'fiverr-team-management'),
                        number_format($total_day_regular_hours, 2)
                    ),
                    400
                );
            }

            // Rule 3: Extra hours / overtime requires active tracking via tracking tool
            if ($total_day_extra_hours > 0.00) {
                $sessions_table = $wpdb->prefix . 'ftm_time_tracker_sessions';
                $tracked_extra_seconds = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(total_seconds), 0) FROM $sessions_table 
                     WHERE user_id = %d AND DATE(start_time) = %s AND session_type = 'extra_time'",
                    $current_user_id,
                    $date
                ));
                $max_allowed_extra_hours = round($tracked_extra_seconds / 3600, 2);
                if ($total_day_extra_hours > ($max_allowed_extra_hours + 0.10)) { // 0.10h tolerance
                    return $this->format_error(
                        sprintf(
                            __('Extra work hours (%sh) exceed your actively tracked extra time sessions (%sh). Overtime work beyond 8 hours cannot be entered manually without turning on the Work Tracker tool.', 'fiverr-team-management'),
                            number_format($total_day_extra_hours, 2),
                            number_format($max_allowed_extra_hours, 2)
                        ),
                        400
                    );
                }
            }
        }

        if ($existing) {
            $wpdb->update($reports_table, [
                'report_type' => 'work',
                'leave_start_date' => null,
                'leave_end_date' => null,
                'leave_type' => null,
                'leave_reason' => null,
                'status' => $final_status,
                'team_name' => $team,
                'report_date' => $date,
                'is_late' => $is_late,
                'submission_time' => $submission_time
            ], ['id' => $existing->id]);
            $report_id = (int)$existing->id;

            // Clear any lingering dummy off-day items so only real deliverables remain
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $items_table WHERE report_id = %d AND order_id IN ('WEEKEND', 'LEAVE')",
                $report_id
            ));

            // Only clear items if explicit full-replace is requested
            if ($replace_all) {
                $wpdb->delete($items_table, ['report_id' => $report_id]);
            }
        } else {
            $wpdb->insert($reports_table, [
                'user_id' => $current_user_id,
                'team_name' => $team,
                'report_type' => 'work',
                'report_date' => $date,
                'status' => $final_status,
                'is_late' => $is_late,
                'submission_time' => $submission_time
            ]);
            $report_id = $wpdb->insert_id;
        }

        // Insert or Upsert Line Items (Preserving multi-project deliverables)
        foreach ($items as $item) {
            $project_id = (int)($item['project_id'] ?? 0);
            $order_id = sanitize_text_field($item['order_id'] ?? '');
            $client_name = sanitize_text_field($item['client_name'] ?? '');

            $existing_item = null;
            if (!$replace_all && $existing) {
                if ($project_id > 0) {
                    $existing_item = $wpdb->get_row($wpdb->prepare(
                        "SELECT id FROM $items_table WHERE report_id = %d AND project_id = %d",
                        $report_id,
                        $project_id
                    ));
                } elseif (!empty($order_id)) {
                    $existing_item = $wpdb->get_row($wpdb->prepare(
                        "SELECT id FROM $items_table WHERE report_id = %d AND order_id = %s",
                        $report_id,
                        $order_id
                    ));
                }
            }

            $is_extra = !empty($item['is_extra_time']) ? 1 : ($existing_item ? (int)$existing_item->is_extra_time : 0);
            $extra_h = $is_extra ? (!empty($item['extra_time_hours']) ? self::parse_hours($item['extra_time_hours']) : self::parse_hours($item['hours_spent'] ?? $item['hours'] ?? 0.00)) : 0.00;
            $extra_reason = sanitize_text_field($item['extra_time_reason'] ?? ($existing_item ? ($existing_item->extra_time_reason ?? '') : ''));

            $item_payload = [
                'report_id' => $report_id,
                'project_id' => $project_id,
                'order_id' => $order_id,
                'client_name' => $client_name,
                'tasks_completed' => sanitize_textarea_field(wp_unslash($item['tasks_completed'] ?? $item['task_description'] ?? $item['task'] ?? '')),
                'work_in_progress' => sanitize_textarea_field(wp_unslash($item['work_in_progress'] ?? '')),
                'next_day_plan' => sanitize_textarea_field(wp_unslash($item['next_day_plan'] ?? $item['tomorrow_plan'] ?? '')),
                'has_blocker' => (!empty($item['has_blocker']) || !empty($item['blocker_description'])) ? 1 : 0,
                'blocker_description' => sanitize_textarea_field(wp_unslash($item['blocker_description'] ?? $item['blocker'] ?? '')),
                'hours_spent' => self::parse_hours($item['hours_spent'] ?? $item['hours'] ?? 0.00),
                'is_extra_time' => $is_extra,
                'extra_time_hours' => $extra_h,
                'extra_time_reason' => $extra_reason,
                'progress_percent' => max(0, min(100, (int)($item['progress_percent'] ?? $item['progress'] ?? 0)))
            ];

            if ($existing_item) {
                $wpdb->update($items_table, $item_payload, ['id' => $existing_item->id]);
            } else {
                $wpdb->insert($items_table, $item_payload);
            }

            // Sync progress_percent directly to the project table
            if ($project_id > 0 && isset($item['progress_percent'])) {
                $prog_val = max(0, min(100, (int)$item['progress_percent']));
                $wpdb->update(
                    "{$wpdb->prefix}ftm_projects",
                    [
                        'progress_percent' => $prog_val,
                        'updated_at' => current_time('mysql')
                    ],
                    ['id' => $project_id]
                );
            }
        }

        // Sync completed task IDs if provided
        if (!empty($data['completed_task_ids']) && is_array($data['completed_task_ids'])) {
            $tasks_table = $wpdb->prefix . 'ftm_project_tasks';
            foreach ($data['completed_task_ids'] as $tid) {
                $tid = (int)$tid;
                if ($tid > 0) {
                    $wpdb->update($tasks_table, [
                        'status' => 'done',
                        'completed_at' => current_time('mysql'),
                        'completed_by' => $current_user_id
                    ], ['id' => $tid]);
                }
            }
        }

        // Sync to Attendance table
        $att_table = $wpdb->prefix . 'ftm_attendance';
        $wpdb->replace($att_table, [
            'user_id' => $current_user_id,
            'team_name' => $team,
            'attendance_date' => $date,
            'status' => ($is_late ? 'late' : 'present'),
            'notes' => ($is_late ? 'Late Submission (Past 6:15 PM Cutoff)' : 'Daily Report Submitted'),
            'marked_by' => $current_user_id,
            'marked_at' => current_time('mysql')
        ]);

        // Send notification to Team Leader
        if (class_exists('FTM_Notifications_Engine')) {
            $user_obj = get_userdata($current_user_id);
            $u_name = $user_obj ? $user_obj->display_name : 'Team Member';
            FTM_Notifications_Engine::broadcast_to_leadership([
                'title' => ($is_late ? '🚨 Late Daily Report' : '📋 Daily Report Submitted'),
                'message' => "{$u_name} submitted daily report for {$date}" . ($is_late ? " (Late past 6:15 PM)" : "") . ". Ready for leader verification.",
                'type' => 'daily_report_submitted',
                'target_id' => $report_id,
                'team' => $team
            ]);
        }

        $success_msg = ($final_status === 'draft')
            ? __('Daily report draft saved.', 'fiverr-team-management')
            : ($is_late 
                ? __('Daily report submitted (Marked late past 6:15 PM cutoff). Forwarded to Team Leader for verification.', 'fiverr-team-management')
                : __('Daily progress report submitted successfully! Forwarded to Team Leader for verification.', 'fiverr-team-management'));

        return $this->format_success([
            'report_id' => $report_id,
            'status' => $final_status,
            'is_late' => $is_late
        ], $success_msg);
    }

    public function review_daily_report($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $current_user_id = get_current_user_id();

        $report = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ftm_daily_reports WHERE id = %d", $id));
        if (!$report) {
            return $this->format_error(__('Daily report not found.', 'fiverr-team-management'), 404);
        }

        // BOLA / IDOR Protection: Enforce team scoping
        if (!FTM_Permissions::can_access_team($report->team_name, $current_user_id)) {
            return $this->format_error(__('Unauthorized: You can only review reports for your assigned team.', 'fiverr-team-management'), 403);
        }

        $data = $request->get_json_params() ?: $request->get_params() ?: [];

        if (FTM_Permissions::is_deputy($current_user_id)) {
            return $this->format_error(__('Executive oversight role is read-only.', 'fiverr-team-management'), 403);
        }

        $is_admin = FTM_Permissions::is_super_admin($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id);

        $raw_action = sanitize_text_field($data['action'] ?? '');
        if (empty($raw_action) && !empty($data['status'])) {
            $status_alias = sanitize_text_field($data['status']);
            if ($status_alias === 'approved') {
                $raw_action = $is_admin ? 'approve' : 'leader_approve';
            } elseif ($status_alias === 'leader_approved') {
                $raw_action = 'leader_approve';
            } elseif ($status_alias === 'revision_requested') {
                $raw_action = 'revision';
            } elseif ($status_alias === 'reset' || $status_alias === 'rejected') {
                $raw_action = 'reset';
            }
        }
        $action = $raw_action ?: ($is_admin ? 'approve' : 'leader_approve'); // 'leader_approve', 'approve', 'revision', 'reset', 'comment'
        if ($action === 'approve' && !$is_admin) {
            // Squad Leaders perform Tier-1 Leader Verification; Operations Manager grants Final Approval
            $action = 'leader_approve';
        }
        $feedback = sanitize_textarea_field(wp_unslash($data['feedback'] ?? $data['comment'] ?? ''));

        $update_payload = [];

        if ($action === 'leader_approve') {
            $new_status = 'leader_approved';
            $update_payload['status'] = $new_status;
            $update_payload['leader_reviewed_by'] = $current_user_id;
            $update_payload['leader_reviewed_at'] = current_time('mysql');
            $update_payload['leader_feedback'] = $feedback;
            $msg = __('Daily report verified by Team Leader and submitted to Executive Manager for final verification.', 'fiverr-team-management');

            // Notify Manager
            if (class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'broadcast_to_leadership')) {
                try {
                    $user_obj = get_userdata($report->user_id);
                    $u_name = $user_obj ? $user_obj->display_name : 'Member';
                    $note_suffix = !empty($feedback) ? " Note: {$feedback}" : "";
                    FTM_Notifications_Engine::broadcast_to_leadership([
                        'title' => '✓ Daily Report Verified by Leader',
                        'message' => "Daily report for {$u_name} ({$report->report_date}) verified by leader.{$note_suffix} Awaiting manager approval.",
                        'type' => 'daily_report_leader_approved',
                        'target_id' => $id,
                        'team' => $report->team_name
                    ]);
                } catch (\Throwable $e) {
                    error_log('FTM notification error: ' . $e->getMessage());
                }
            }

            // Also Notify Member
            if (class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'notify_user')) {
                try {
                    $note_suffix = !empty($feedback) ? " Remarks: {$feedback}" : "";
                    FTM_Notifications_Engine::notify_user($report->user_id, [
                        'title' => '✓ Daily Report Verified by Leader',
                        'message' => "Your daily report for {$report->report_date} was verified by your Team Leader.{$note_suffix}",
                        'type' => 'daily_report_leader_approved',
                        'target_id' => $id
                    ]);
                } catch (\Throwable $e) {}
            }
        } elseif ($action === 'approve') {
            $new_status = 'approved';
            $update_payload['status'] = $new_status;
            $update_payload['manager_reviewed_by'] = $current_user_id;
            $update_payload['manager_reviewed_at'] = current_time('mysql');
            $update_payload['manager_feedback'] = $feedback;
            $update_payload['reviewed_by'] = $current_user_id;
            $update_payload['reviewed_at'] = current_time('mysql');
            if (empty($report->leader_reviewed_by)) {
                $update_payload['leader_reviewed_by'] = $current_user_id;
                $update_payload['leader_reviewed_at'] = current_time('mysql');
            }
            $msg = __('Daily report given final approval by Executive Manager.', 'fiverr-team-management');

            // Notify Member
            if (class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'notify_user')) {
                try {
                    $note_suffix = !empty($feedback) ? " Remarks: {$feedback}" : "";
                    FTM_Notifications_Engine::notify_user($report->user_id, [
                        'title' => '🎉 Daily Report Approved',
                        'message' => "Your daily report for {$report->report_date} was approved by Executive Management.{$note_suffix}",
                        'type' => 'daily_report_approved',
                        'target_id' => $id
                    ]);
                } catch (\Throwable $e) {
                    error_log('FTM notification error: ' . $e->getMessage());
                }
            }
        } elseif ($action === 'reset' || $action === 'reject') {
            $wpdb->delete("{$wpdb->prefix}ftm_daily_report_items", ['report_id' => $id]);
            $wpdb->delete("{$wpdb->prefix}ftm_daily_reports", ['id' => $id]);

            // If this was an on_leave report, also clear attendance on_leave record for this day
            if ($report->report_type === 'on_leave') {
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}ftm_attendance WHERE user_id = %d AND attendance_date = %s AND status = 'on_leave'",
                    $report->user_id, $report->report_date
                ));
            }

            $msg = __('Report reset successfully. The member can now submit their daily report.', 'fiverr-team-management');

            if (class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'notify_user')) {
                try {
                    $reason = $feedback ?: 'Please submit your correct daily work deliverables.';
                    FTM_Notifications_Engine::notify_user($report->user_id, [
                        'title' => '⚠️ Daily Report Reset by Leader',
                        'message' => "Your report for {$report->report_date} was reset by leadership: {$reason}",
                        'type' => 'daily_report_reset',
                        'target_id' => $id
                    ]);
                } catch (\Throwable $e) {}
            }

            return $this->format_success([
                'status' => 'not_submitted',
                'deleted' => true
            ], $msg);
        } elseif ($action === 'revision') {
            $new_status = 'revision_requested';
            $update_payload['status'] = $new_status;
            $update_payload['leader_feedback'] = $feedback;
            if ($is_admin) {
                $update_payload['manager_feedback'] = $feedback;
            }
            $msg = __('Revision requested and feedback sent to member.', 'fiverr-team-management');

            // Notify Member
            if (class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'notify_user')) {
                try {
                    $note_suffix = !empty($feedback) ? ": {$feedback}" : ". Please review instructions and update.";
                    FTM_Notifications_Engine::notify_user($report->user_id, [
                        'title' => '⚠️ Daily Report Revision Requested',
                        'message' => "Correction requested on your daily report ({$report->report_date}){$note_suffix}",
                        'type' => 'daily_report_revision',
                        'target_id' => $id
                    ]);
                } catch (\Throwable $e) {
                    error_log('FTM notification error: ' . $e->getMessage());
                }
            }
        } else {
            // Keep current status if action is 'comment'
            $new_status = $report->status;
            $update_payload['leader_feedback'] = $feedback;
            $msg = __('Manager comment saved successfully.', 'fiverr-team-management');
        }

        $wpdb->update($wpdb->prefix . 'ftm_daily_reports', $update_payload, ['id' => $id]);

        return $this->format_success([
            'status' => $new_status,
            'leader_feedback' => $feedback
        ], $msg);
    }

    public function forward_team_report($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $data = $request->get_json_params();

        $user_team = FTM_Permissions::get_user_team($current_user_id);
        if (!FTM_Permissions::is_super_admin($current_user_id)) {
            $team = $user_team; // Strictly enforce user's own team for non-admin
        } else {
            $team = sanitize_text_field($data['team_name'] ?? $user_team);
        }

        if (!FTM_Permissions::can_access_team($team, $current_user_id)) {
            return $this->format_error(__('Unauthorized to forward report for this team.', 'fiverr-team-management'), 403);
        }

        $date = sanitize_text_field($data['report_date'] ?? current_time('Y-m-d'));

        // Calculate auto-compiled team metrics
        $financials = FTM_Financial::compute_period_totals($team, $date, $date);

        $blockers_count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ftm_daily_report_items i 
             JOIN {$wpdb->prefix}ftm_daily_reports r ON i.report_id = r.id 
             WHERE r.team_name = %s AND r.report_date = %s AND i.has_blocker = 1",
            $team,
            $date
        ));

        $active_wip_count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ftm_projects WHERE team_name = %s AND status IN ('wip', 'nra', 'revision')",
            $team
        ));

        $table = $wpdb->prefix . 'ftm_daily_team_reports';
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE team_name = %s AND report_date = %s", $team, $date));

        $report_payload = [
            'team_name' => $team,
            'report_date' => $date,
            'status' => 'forwarded',
            'created_by' => $current_user_id,
            'total_delivered_today' => $financials['total_delivery_net'],
            'active_wip_count' => $active_wip_count,
            'active_wip_value' => $financials['total_wip_net'],
            'open_blockers_count' => $blockers_count,
            'summary_notes' => sanitize_textarea_field($data['summary_notes'] ?? ''),
            'risks_and_blockers' => sanitize_textarea_field($data['risks_and_blockers'] ?? ''),
            'team_availability' => sanitize_textarea_field($data['team_availability'] ?? ''),
            'forwarded_at' => current_time('mysql')
        ];

        if ($existing) {
            $wpdb->update($table, $report_payload, ['id' => $existing]);
        } else {
            $wpdb->insert($table, $report_payload);
        }

        return $this->format_success([], __('Team Daily Status Report forwarded to Super Admin / Manager!', 'fiverr-team-management'));
    }

    public function get_agency_summary($request) {
        global $wpdb;
        $date = sanitize_text_field($request->get_param('date') ?: current_time('Y-m-d'));

        $team_reports = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ftm_daily_team_reports WHERE report_date = %s",
            $date
        ));

        // Open Blockers across agency
        $blockers = $wpdb->get_results($wpdb->prepare(
            "SELECT r.team_name, u.display_name as member_name, i.order_id, i.client_name, i.blocker_description 
             FROM {$wpdb->prefix}ftm_daily_report_items i 
             JOIN {$wpdb->prefix}ftm_daily_reports r ON i.report_id = r.id 
             JOIN {$wpdb->users} u ON r.user_id = u.ID 
             WHERE r.report_date = %s AND i.has_blocker = 1",
            $date
        ));

        return rest_ensure_response([
            'date' => $date,
            'team_reports' => $team_reports,
            'active_blockers' => $blockers,
            'financial_totals' => FTM_Financial::compute_period_totals('all', $date, $date)
        ]);
    }

    public function get_weekly_summary($request) {
        global $wpdb;

        $start_date = sanitize_text_field($request->get_param('start_date') ?: '');
        $end_date = sanitize_text_field($request->get_param('end_date') ?: '');
        $team = sanitize_text_field($request->get_param('team') ?: 'all');

        // Default to current week (Sunday to Saturday or last 7 days)
        if (empty($start_date) || empty($end_date)) {
            $ts = current_time('timestamp');
            $w = (int)date('w', $ts); // 0 (Sun) to 6 (Sat)
            $start_dt = new DateTime();
            $start_dt->setTimestamp($ts);
            $start_dt->modify("-{$w} days");
            $start_date = $start_dt->format('Y-m-d');

            $end_dt = clone $start_dt;
            $end_dt->modify('+6 days');
            $end_date = $end_dt->format('Y-m-d');
        }

        // Get working members
        $members = FTM_Permissions::get_team_members();
        $working_members = array_filter($members, function($m) use ($team) {
            $is_admin = ($m['team_role'] === 'super_admin' || FTM_Permissions::is_management_team($m['team']) || (isset($m['role_label']) && stripos($m['role_label'], 'admin') !== false));
            if ($is_admin) return false;
            return ($team === 'all' || $m['team'] === $team);
        });

        $reports_table = $wpdb->prefix . 'ftm_daily_reports';
        $items_table = $wpdb->prefix . 'ftm_daily_report_items';

        $member_summaries = [];
        $total_weekly_hours = 0;
        $total_tasks_completed = 0;
        $total_blockers = 0;
        $projects_set = [];

        foreach ($working_members as $m) {
            $uid = (int)$m['id'];
            $reports = $wpdb->get_results($wpdb->prepare(
                "SELECT r.id as report_id, r.report_type, r.report_date, r.status, r.leave_start_date, r.leave_end_date, r.leave_type, r.leave_reason,
                        i.project_id, i.order_id, i.client_name, i.tasks_completed, i.work_in_progress, i.has_blocker, i.blocker_description, i.hours_spent, i.progress_percent 
                 FROM $reports_table r 
                 LEFT JOIN $items_table i ON r.id = i.report_id 
                 WHERE r.user_id = %d AND r.report_date BETWEEN %s AND %s 
                 ORDER BY r.report_date ASC",
                $uid,
                $start_date,
                $end_date
            ));

            $days_worked = 0;
            $days_leave = 0;
            $days_weekend = 0;
            $member_hours = 0;
            $member_done = 0;
            $member_wip = 0;
            $member_blockers = 0;
            $days_logged = [];
            $projects_worked = [];

            foreach ($reports as $row) {
                $r_date = $row->report_date;
                if (!isset($days_logged[$r_date])) {
                    $days_logged[$r_date] = $row->report_type;
                    if ($row->report_type === 'weekend') {
                        $days_weekend++;
                    } elseif ($row->report_type === 'on_leave') {
                        $days_leave++;
                    } else {
                        $days_worked++;
                    }
                }

                $h = (float)($row->hours_spent ?? 0);
                $member_hours += $h;
                $total_weekly_hours += $h;

                if (!empty($row->tasks_completed) && !in_array($row->order_id, ['WEEKEND', 'LEAVE'], true)) {
                    $member_done++;
                    $total_tasks_completed++;
                }
                if (!empty($row->work_in_progress)) {
                    $member_wip++;
                }
                if (!empty($row->has_blocker)) {
                    $member_blockers++;
                    $total_blockers++;
                }

                $proj_title = !empty($row->client_name) ? $row->client_name : (!empty($row->order_id) ? '#' . $row->order_id : '');
                if ($proj_title && !in_array($proj_title, ['Weekly Off / Weekend', 'Leave: Casual Leave', 'Leave: Sick Leave', 'Leave: Vacation', 'Leave: Emergency'])) {
                    if (!in_array($proj_title, $projects_worked, true)) {
                        $projects_worked[] = $proj_title;
                    }
                    if (!in_array($proj_title, $projects_set, true)) {
                        $projects_set[] = $proj_title;
                    }
                }
            }

            $member_summaries[] = [
                'user_id' => $uid,
                'id' => $uid,
                'custom_id' => $m['custom_id'] ?? ('#' . $uid),
                'name' => $m['name'],
                'team' => $m['team'],
                'avatar' => $m['avatar'],
                'role_label' => $m['role_label'] ?? 'Team Member',
                'days_worked' => $days_worked,
                'days_leave' => $days_leave,
                'days_weekend' => $days_weekend,
                'total_hours' => round($member_hours, 2),
                'tasks_completed' => $member_done,
                'wip_count' => $member_wip,
                'blockers_count' => $member_blockers,
                'projects' => $projects_worked
            ];
        }

        return rest_ensure_response([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'team' => $team,
            'total_weekly_hours' => round($total_weekly_hours, 2),
            'total_tasks_completed' => $total_tasks_completed,
            'total_blockers' => $total_blockers,
            'active_projects_count' => count($projects_set),
            'members' => $member_summaries
        ]);
    }
}

