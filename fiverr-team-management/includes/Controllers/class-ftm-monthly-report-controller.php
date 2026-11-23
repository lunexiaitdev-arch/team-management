<?php
/**
 * FTM Monthly Financial & Member Reporting Controller (Google Sheet Replacement)
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Monthly_Report_Controller extends FTM_REST_Controller {

    public function register_routes() {
        // Live Monthly Report Grid (Google Sheet Replacement)
        register_rest_route($this->namespace, '/reports/monthly', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_monthly_report'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Historical CSV Import (Google Sheets upload)
        register_rest_route($this->namespace, '/reports/monthly/import-csv', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'import_monthly_csv'],
            'permission_callback' => [$this, 'check_admin_permission']
        ]);

        // Performance Leaderboard Route
        register_rest_route($this->namespace, '/reports/leaderboard', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_leaderboard_data'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);
    }

    public static function get_dynamic_reporting_months() {
        $start_year = 2026;
        $start_month = 10;
        
        $cur_year = (int)current_time("Y");
        $cur_month = (int)current_time("n");
        
        if ($cur_year < 2026 || ($cur_year === 2026 && $cur_month < 10)) {
            $cur_year = 2026;
            $cur_month = 10;
        }
        
        $months = [];
        $y = $cur_year;
        $m = $cur_month;
        
        while ($y > $start_year || ($y === $start_year && $m >= $start_month)) {
            $key = sprintf("%04d-%02d", $y, $m);
            $time = mktime(0, 0, 0, $m, 1, $y);
            $label = date("F Y", $time);
            $months[] = [
                "key" => $key,
                "label" => ($y === $cur_year && $m === $cur_month) ? "Current Month ({$label})" : $label,
                "short_label" => $label,
                "is_current" => ($y === $cur_year && $m === $cur_month)
            ];
            
            $m--;
            if ($m < 1) {
                $m = 12;
                $y--;
            }
        }
        return $months;
    }

    public function get_monthly_report($request) {
        global $wpdb;
        $dynamic_months = self::get_dynamic_reporting_months();
        $allowed_keys = array_column($dynamic_months, "key");
        
        $raw_month = sanitize_text_field($request->get_param("month") ?: current_time("Y-m"));
        if (preg_match("/^([a-zA-Z]+)_(\\d{4})$/i", $raw_month, $m_matches)) {
            $timestamp = strtotime($m_matches[1] . " 1 " . $m_matches[2]);
            if ($timestamp) {
                $raw_month = date("Y-m", $timestamp);
            }
        }
        
        // Strict boundary: origin is October 2026, upper boundary is current month
        if (!in_array($raw_month, $allowed_keys, true)) {
            $month = current_time("Y-m");
            if (!in_array($month, $allowed_keys, true)) {
                $month = $allowed_keys[0];
            }
        } else {
            $month = $raw_month;
        }

        $current_user_id = get_current_user_id();
        if (!FTM_Permissions::can_view_monthly_reports($current_user_id)) {
            return new WP_Error('rest_forbidden', 'Access denied. Monthly financial reports are restricted to leadership and management.', ['status' => 403]);
        }

        if (!FTM_Permissions::is_super_admin($current_user_id) && !FTM_Permissions::is_deputy($current_user_id)) {
            // Team Leader / Co-Leader: STRICTLY locked to their own team! Cannot see all teams or other teams' revenue!
            $team = FTM_Permissions::get_user_team($current_user_id);
        } else {
            $team = sanitize_text_field($request->get_param("team") ?: "all");
        }
        $start_date_str = $month . "-01";
        $end_date_str = date("Y-m-t", strtotime($start_date_str));

        // Get members
        $users_args = ["meta_key" => "ftm_team", "orderby" => "display_name"];
        if ($team !== "all") {
            $users_args["meta_value"] = $team;
        }
        $members = get_users($users_args);

        $p_table = $wpdb->prefix . "ftm_projects";
        $assignees_table = $wpdb->prefix . "ftm_project_assignees";
        $archive_table = $wpdb->prefix . "ftm_monthly_archive";

        $arch_aliases = [$month];
        if (preg_match('/^(\d{4})-(\d{2})$/', $month, $m_parts)) {
            $arch_aliases[] = strtolower(date('F_Y', strtotime("{$m_parts[1]}-{$m_parts[2]}-01")));
            $arch_aliases[] = ucfirst(strtolower(date('F_Y', strtotime("{$m_parts[1]}-{$m_parts[2]}-01"))));
        }
        $arch_in = implode(',', array_fill(0, count($arch_aliases), '%s'));
        $archived_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $archive_table WHERE reporting_month IN ($arch_in)",
            $arch_aliases
        ));

        $is_archived_month = (!empty($archived_rows) && ($month === '2026-10' || $month < current_time('Y-m')));

        $rows = [];

        if ($is_archived_month) {
            $arch_map = [];
            foreach ($archived_rows as $ar) {
                $arch_map[$ar->member_custom_id] = $ar;
            }

            foreach ($members as $u) {
                $uid = (int)$u->ID;
                $user_team = get_user_meta($uid, "ftm_team", true) ?: "WpCraft";
                if (FTM_Permissions::is_management_team($user_team) || $uid === 1 || $u->user_login === "admin") {
                    continue;
                }
                $custom_id = get_user_meta($uid, "ftm_custom_id", true) ?: (string)$uid;
                $call_sign = get_user_meta($uid, "ftm_call_sign", true) ?: $u->first_name;
                $ar = $arch_map[$custom_id] ?? null;

                $del_val = $ar ? (float)$ar->total_delivery : 0.00;
                $del_cnt = $ar ? (int)$ar->total_completed : 0;
                $cancel_val = $ar ? (float)$ar->cancelled : 0.00;
                $cancel_cnt = $ar ? (int)$ar->total_cancel : 0;
                $tips_val = $ar ? (float)$ar->tips : 0.00;
                $ontime_cnt = $ar ? (int)$ar->delivered_without_extension : 0;
                $late_cnt = max(0, $del_cnt - $ontime_cnt);

                $assigned_cnt = $del_cnt + $cancel_cnt;
                $assigned_val = $del_val + $cancel_val;

                $c_avatar = get_user_meta($uid, "ftm_custom_avatar", true);
                $u_avatar = !empty($c_avatar) ? $c_avatar : ("https://ui-avatars.com/api/?name=" . urlencode($u->display_name) . "&background=2563eb&color=fff&size=128&bold=true");
                $u_role = FTM_Permissions::get_user_role($uid);

                $s5 = $ar ? (int)$ar->stars_5 : 0;
                $s4 = $ar ? (int)$ar->stars_4 : 0;
                $s3 = $ar ? (int)$ar->stars_3 : 0;
                $s2 = $ar ? (int)$ar->stars_2 : 0;
                $s1 = $ar ? (int)$ar->stars_1 : 0;
                $r_cnt = $s5 + $s4 + $s3 + $s2 + $s1;
                $r_sum = ($s5 * 5) + ($s4 * 4) + ($s3 * 3) + ($s2 * 2) + ($s1 * 1);
                $avg_r = $r_cnt > 0 ? round($r_sum / $r_cnt, 1) : 0.0;

                $rows[] = [
                    "user_id" => $uid,
                    "id" => $uid,
                    "member_custom_id" => $custom_id,
                    "custom_id" => $custom_id,
                    "badge_id" => $custom_id,
                    "member_name" => $u->display_name,
                    "name" => $u->display_name,
                    "display_name" => $u->display_name,
                    "avatar" => $u_avatar,
                    "role" => FTM_Permissions::get_role_label($u_role),
                    "team_name" => $user_team,
                    "team" => $user_team,
                    "call_sign" => $call_sign ?: $u->display_name,

                    "total_wip" => 0.00,
                    "wip_val" => 0.00,
                    "wip_count" => 0,

                    "total_submitted" => 0.00,
                    "submitted_val" => 0.00,
                    "submitted_count" => 0,

                    "nra" => 0,
                    "nra_count" => 0,
                    "nra_val" => 0.00,

                    "delivery" => round($del_val + $tips_val, 2),
                    "total_delivery" => round($del_val + $tips_val, 2),
                    "delivered_net" => round($del_val + $tips_val, 2),
                    "delivered_gross" => round(($del_val + $tips_val) / 0.8, 2),
                    "order_delivery_net" => round($del_val, 2),
                    "delivery_count" => $del_cnt,
                    "deliveries" => $del_cnt,
                    "total_completed" => $del_cnt,

                    "cancelled" => round($cancel_val, 2),
                    "cancelled_val" => round($cancel_val, 2),
                    "cancelled_count" => $cancel_cnt,
                    "total_cancel" => $cancel_cnt,

                    "new_assigned" => $assigned_cnt,
                    "new_assigned_count" => $assigned_cnt,
                    "new_assigned_val" => round($assigned_val, 2),

                    "carry_forward" => 0,
                    "carry_forward_count" => 0,
                    "carry_forward_val" => 0.00,

                    "total_assigned" => $assigned_cnt,
                    "total_assigned_count" => $assigned_cnt,
                    "total_assigned_val" => round($assigned_val, 2),

                    "active_wip_gross" => 0.00,
                    "active_wip_net" => 0.00,
                    "tips" => round($tips_val, 2),
                    "total_tips" => round($tips_val, 2),
                    "partial" => 0.00,

                    "stars_5" => $s5,
                    "stars_4" => $s4,
                    "stars_3" => $s3,
                    "stars_2" => $s2,
                    "stars_1" => $s1,
                    "delivered_without_extension" => $ontime_cnt,
                    "extension_count" => 0,
                    "extension_days" => 0,
                    "late_count" => $late_cnt,
                    "tip_count" => ($tips_val > 0 ? 1 : 0),
                    "average_rating" => $avg_r,
                    "ratings_count" => $r_cnt
                ];
            }
        } else {
        $seen_repeat_pids = [];
        $team_seen_repeat_pids = [];
        foreach ($members as $u) {
            $uid = (int)$u->ID;
            $user_team = get_user_meta($uid, "ftm_team", true) ?: "WpCraft";
            if (FTM_Permissions::is_management_team($user_team) || $uid === 1 || $u->user_login === "admin") {
                continue;
            }
            $custom_id = get_user_meta($uid, "ftm_custom_id", true) ?: (string)$uid;
            $call_sign = get_user_meta($uid, "ftm_call_sign", true) ?: $u->first_name;

            // Query projects where user is primary or assigned
            $sql = $wpdb->prepare(
                "SELECT * FROM $p_table 
                 WHERE (assigned_user_id = %d OR FIND_IN_SET(%d, assigned_user_ids) OR id IN (SELECT project_id FROM $assignees_table WHERE user_id = %d))
                 AND (
                     (DATE(COALESCE(created_at, delivery_date)) BETWEEN %s AND %s)
                     OR (DATE(COALESCE(created_at, delivery_date)) < %s AND status IN ('wip', 'nra', 'revision', 'delivery_submitted'))
                     OR (DATE(COALESCE(created_at, delivery_date)) < %s AND status IN ('delivery_accepted', 'completed') AND DATE(COALESCE(delivered_date, delivery_date)) BETWEEN %s AND %s)
                     OR (status = 'cancelled' AND DATE(COALESCE(delivered_date, delivery_date, created_at)) BETWEEN %s AND %s)
                     OR (tip_amount > 0 AND DATE(COALESCE(tip_date, delivered_date, delivery_date)) BETWEEN %s AND %s)
                 )",
                $uid, $uid, $uid, $start_date_str, $end_date_str, $start_date_str, $start_date_str, $start_date_str, $end_date_str, $start_date_str, $end_date_str, $start_date_str, $end_date_str
            );
            $user_projects = $wpdb->get_results($sql);

            $del_val = 0.00;
            $del_cnt = 0;
            $submitted_val = 0.00;
            $submitted_cnt = 0;
            $wip_val = 0.00;
            $wip_cnt = 0;
            $nra_val = 0.00;
            $nra_cnt = 0;
            $cancelled_val = 0.00;
            $cancel_cnt = 0;
            $new_assigned_val = 0.00;
            $new_assigned_cnt = 0;
            $carry_forward_val = 0.00;
            $carry_forward_cnt = 0;
            $tips_val = 0.00;
            $partial_val = 0.00;
            $stars = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
            $on_time_cnt = 0;
            $ext_cnt = 0;
            $ext_days = 0;
            $late_cnt = 0;
            $tip_orders_cnt = 0;
            $repeat_cnt = 0;
            $repeat_val = 0.00;
            $ratings_sum = 0.0;
            $ratings_count = 0;

            foreach ($user_projects as $up) {
                $pid = (int)$up->id;

                // Determine user split percentage to prevent financial duplication & penny drift
                $split_rows = $wpdb->get_results($wpdb->prepare(
                    "SELECT user_id, split_percentage FROM $assignees_table WHERE project_id = %d ORDER BY user_id ASC",
                    $pid
                ));
                $raw_ids = !empty($up->assigned_user_ids) ? explode(",", $up->assigned_user_ids) : [(int)$up->assigned_user_id];
                $assignee_ids = [];
                foreach ($raw_ids as $rid) {
                    $aid = (int)trim($rid);
                    if ($aid > 0 && !in_array($aid, $assignee_ids, true)) {
                        $assignee_ids[] = $aid;
                    }
                }
                if (empty($assignee_ids) && (int)$up->assigned_user_id > 0) {
                    $assignee_ids[] = (int)$up->assigned_user_id;
                }
                $assignee_count = max(1, count($assignee_ids));

                $pct_map = [];
                $has_explicit_splits = false;
                if (!empty($split_rows)) {
                    $has_explicit_splits = true;
                    foreach ($split_rows as $sr) {
                        $pct_map[(int)$sr->user_id] = (float)$sr->split_percentage;
                    }
                }
                if ($has_explicit_splits) {
                    $ordered_uids = array_keys($pct_map);
                    sort($ordered_uids, SORT_NUMERIC);
                } else {
                    $ordered_uids = $assignee_ids;
                    sort($ordered_uids, SORT_NUMERIC);
                }

                $user_split = ($has_explicit_splits && isset($pct_map[$uid])) ? ($pct_map[$uid] / 100.0) : (1.0 / $assignee_count);
                if ($user_split <= 0) {
                    continue;
                }
                $calc_exact_share = function($total_amt) use ($uid, $ordered_uids, $has_explicit_splits, $pct_map, $assignee_count, $user_split) {
                    $total_amt = round((float)$total_amt, 2);
                    $n = count($ordered_uids);
                    $pos = array_search($uid, $ordered_uids, true);
                    if ($pos === false || $n <= 1) {
                        return round($total_amt * $user_split, 2);
                    }
                    if ($pos === $n - 1) {
                        $allocated = 0.0;
                        for ($i = 0; $i < $n - 1; $i++) {
                            $ouid = $ordered_uids[$i];
                            $sp = ($has_explicit_splits && isset($pct_map[$ouid])) ? ($pct_map[$ouid] / 100.0) : (1.0 / $assignee_count);
                            $allocated += round($total_amt * $sp, 2);
                        }
                        return round($total_amt - $allocated, 2);
                    }
                    return round($total_amt * $user_split, 2);
                };

                $net_val = $calc_exact_share($up->net_delivery_value);
                $created_date_actual = !empty($up->created_at) ? substr($up->created_at, 0, 10) : $up->delivery_date;
                $del_date_actual = !empty($up->delivered_date) ? substr($up->delivered_date, 0, 10) : $up->delivery_date;

                // 1. Is New Assigned in selected month?
                if ($created_date_actual >= $start_date_str && $created_date_actual <= $end_date_str) {
                    $new_assigned_cnt++;
                    $new_assigned_val += $net_val;
                }

                // 2. Is Carry Forward (created before selected month and entered active)?
                if ($created_date_actual < $start_date_str) {
                    $already_delivered_prior = (!empty($up->delivered_date) && substr($up->delivered_date, 0, 10) < $start_date_str);
                    $is_active_entering = !$already_delivered_prior && (
                        in_array($up->status, ["wip", "nra", "revision", "delivery_submitted"], true)
                        || (in_array($up->status, ["delivery_accepted", "completed"], true) && !empty($up->delivered_date) && substr($up->delivered_date, 0, 10) >= $start_date_str)
                    );
                    if ($is_active_entering) {
                        $carry_forward_cnt++;
                        $carry_forward_val += $net_val;
                    }
                }

                // Extension & Tip tracking for this member
                $ext_cnt += (int)($up->extension_count ?? 0);
                $ext_days += (int)($up->extension_days ?? 0);

                // Repeat Client Order attribution for this specialist in the selected month
                // Excludes orders closed prior to this month (matches get_projects() & get_stats() 100%)
                $is_repeat_order = (strcasecmp(trim((string)($up->order_source ?? '')), 'Repeat Client') === 0);
                $is_closed_before_month = (
                    in_array($up->status, ['delivered', 'delivery_accepted', 'completed', 'cancelled'], true)
                    && !empty($del_date_actual)
                    && $del_date_actual < $start_date_str
                );
                if ($is_repeat_order && !$is_closed_before_month) {
                    $repeat_cnt++;
                    $repeat_val += $net_val;
                    $seen_repeat_pids[$pid] = true;
                    $team_seen_repeat_pids[$user_team][$pid] = true;
                }

                // Tip evaluation: attribute tips to the month they were received (tip_date, falling back to delivered_date/delivery_date)
                // This matches the SQL window (line 262), Performance Leaderboard, and cross-month tip reconciliation.
                $tip_date_actual = !empty($up->tip_date) ? substr($up->tip_date, 0, 10) : $del_date_actual;
                $tip_in_month = ((float)$up->tip_amount > 0 && $tip_date_actual >= $start_date_str && $tip_date_actual <= $end_date_str);
                $tip_share = $tip_in_month ? $calc_exact_share(FTM_Financial::tip_net($up->tip_amount)) : 0.0;

                if ($tip_in_month) {
                    $tips_val += $tip_share;
                    $tip_orders_cnt++;
                }

                // 3. Operational status categorization in selected month
                // Domain Rule: Until an order is explicitly accepted or completed, it must NOT count in Delivery!
                // Orders in 'delivery_submitted' remain in Total WIP.
                // In addition, delivery value reflects net project delivery value ($net_val); tips are strictly counted in $tips_val.
                if (in_array($up->status, ["delivery_accepted", "completed"], true)) {
                    if ($del_date_actual >= $start_date_str && $del_date_actual <= $end_date_str) {
                        $del_val += $net_val;
                        $del_cnt++;
                        $seen_delivered_pids[$pid] = true;
                        $team_seen_delivered_pids[$user_team][$pid] = true;
                        if ($up->client_reviewed === "yes") {
                            $rv = (float)$up->star_rating; $r = (int)round($rv);
                            if ($r >= 1 && $r <= 5) {
                                $stars[$r]++;
                                $ratings_sum += $rv;
                                $ratings_count++;
                            }
                        }
                        $is_on_time = isset($up->is_ontime) ? ((int)$up->is_ontime === 1) : (isset($up->delivered_without_extension) ? ((int)$up->delivered_without_extension === 1) : ((int)($up->extension_count ?? 0) === 0));
                        if ($is_on_time) {
                            $on_time_cnt++;
                        } else {
                            $late_cnt++;
                        }
                    } elseif ($del_date_actual > $end_date_str) {
                        $wip_val += $net_val;
                        $wip_cnt++;
                    }
                } elseif ($up->status === "delivery_submitted") {
                    $submitted_val += $net_val;
                    $submitted_cnt++;
                } elseif ($up->status === "wip") {
                    $wip_val += $net_val;
                    $wip_cnt++;
                } elseif (in_array($up->status, ["nra", "revision"], true)) {
                    $nra_val += $net_val;
                    $nra_cnt++;
                } elseif ($up->status === "cancelled") {
                    $cancel_date = !empty($up->delivered_date) ? substr($up->delivered_date, 0, 10) : (!empty($up->delivery_date) && substr($up->delivery_date, 0, 10) <= substr($up->created_at, 0, 10) ? substr($up->delivery_date, 0, 10) : substr($up->created_at, 0, 10));
                    if ($cancel_date >= $start_date_str && $cancel_date <= $end_date_str) {
                        $c_val = (float)$up->cancelled_value ?: $net_val;
                        $cancelled_val += ($c_val * $user_split);
                        $cancel_cnt++;
                    }
                }

                if ((float)$up->partial_payout_value > 0) {
                    $partial_val += ((float)$up->partial_payout_value * $user_split);
                }
            }

            $total_assigned_cnt = $new_assigned_cnt + $carry_forward_cnt;
            $total_assigned_val = $new_assigned_val + $carry_forward_val + $tips_val;

            $member_status = get_user_meta($uid, "ftm_status", true);
            $is_former_member = !empty($member_status) && in_array(strtolower($member_status), ["inactive", "resigned", "former", "left", "isolated"], true);
            if ($is_former_member && $del_val <= 0 && $wip_val <= 0 && $total_assigned_cnt <= 0) {
                continue;
            }

            $c_avatar = get_user_meta($uid, "ftm_custom_avatar", true);
            $u_avatar = !empty($c_avatar) ? $c_avatar : ("https://ui-avatars.com/api/?name=" . urlencode($u->display_name) . "&background=2563eb&color=fff&size=128&bold=true");
            $u_role = FTM_Permissions::get_user_role($uid);

            $rows[] = [
                "user_id" => $uid,
                "id" => $uid,
                "member_custom_id" => $custom_id,
                "custom_id" => $custom_id,
                "badge_id" => $custom_id,
                "member_name" => $u->display_name,
                "name" => $u->display_name,
                "display_name" => $u->display_name,
                "avatar" => $u_avatar,
                "role" => FTM_Permissions::get_role_label($u_role),
                "team_name" => $user_team,
                "team" => $user_team,
                "call_sign" => $call_sign ?: $u->display_name,

                // The 7 Required Operational Progress Metrics:
                "total_wip" => round($wip_val, 2),
                "wip_val" => round($wip_val, 2),
                "wip_count" => $wip_cnt,

                "total_submitted" => round($submitted_val, 2),
                "submitted_val" => round($submitted_val, 2),
                "submitted_count" => $submitted_cnt,

                "nra" => $nra_cnt,
                "nra_count" => $nra_cnt,
                "nra_val" => round($nra_val, 2),

                "delivery" => round($del_val + $tips_val, 2),
                "total_delivery" => round($del_val + $tips_val, 2),
                "delivered_net" => round($del_val + $tips_val, 2),
                "delivered_gross" => round(($del_val + $tips_val) / 0.8, 2),
                "order_delivery_net" => round($del_val, 2),
                "delivery_count" => $del_cnt,
                "deliveries" => $del_cnt,
                "total_completed" => $del_cnt,

                "cancelled" => round($cancelled_val, 2),
                "cancelled_val" => round($cancelled_val, 2),
                "cancelled_count" => $cancel_cnt,
                "total_cancel" => $cancel_cnt,

                "new_assigned" => $new_assigned_cnt,
                "new_assigned_count" => $new_assigned_cnt,
                "new_assigned_val" => round($new_assigned_val, 2),

                "carry_forward" => $carry_forward_cnt,
                "carry_forward_count" => $carry_forward_cnt,
                "carry_forward_val" => round($carry_forward_val, 2),

                "total_assigned" => $total_assigned_cnt,
                "total_assigned_count" => $total_assigned_cnt,
                "total_assigned_val" => round($total_assigned_val, 2),

                // Auxiliary metrics
                "active_wip_gross" => round($wip_val / 0.8, 2),
                "active_wip_net" => round($wip_val, 2),
                "tips" => round($tips_val, 2),
                "total_tips" => round($tips_val, 2),
                "partial" => round($partial_val, 2),
                "stars_5" => $stars[5],
                "stars_4" => $stars[4],
                "stars_3" => $stars[3],
                "stars_2" => $stars[2],
                "stars_1" => $stars[1],
                "delivered_without_extension" => $on_time_cnt,
                "on_time_count" => $on_time_cnt,
                "extension_count" => $ext_cnt,
                "extension_days" => $ext_days,
                "late_count" => $late_cnt,
                "tip_count" => $tip_orders_cnt,
                "repeat_count" => $repeat_cnt,
                "repeat_orders" => $repeat_cnt,
                "repeat_val" => round($repeat_val, 2),
                "repeat_net_value" => round($repeat_val, 2),
                "avg_rating" => $ratings_count > 0 ? round($ratings_sum / $ratings_count, 1) : 5.0,
                "review_count" => $ratings_count
            ];
        }
        }

        // Sort rows by top delivery value descending, then by total assigned
        usort($rows, function($a, $b) {
            if ($b["total_delivery"] != $a["total_delivery"]) {
                return $b["total_delivery"] <=> $a["total_delivery"];
            }
            if ($b["total_assigned_val"] != $a["total_assigned_val"]) {
                return $b["total_assigned_val"] <=> $a["total_assigned_val"];
            }
            return strcasecmp($a["member_name"], $b["member_name"]);
        });

        $totals = $this->calculate_rows_summary($rows);
        $team_breakdown = $this->calculate_team_breakdown($rows);

        if (!$is_archived_month && isset($seen_repeat_pids)) {
            $unique_repeat_total = count($seen_repeat_pids);
            $totals['repeat_count'] = $unique_repeat_total;
            $totals['repeat_orders_count'] = $unique_repeat_total;
            foreach ($team_breakdown as &$tb_ref) {
                $tn = $tb_ref['team_name'] ?? '';
                if ($tn !== '' && isset($team_seen_repeat_pids[$tn])) {
                    $tb_ref['repeat_count'] = count($team_seen_repeat_pids[$tn]);
                }
            }
            unset($tb_ref);
        }

        if (!$is_archived_month && isset($seen_delivered_pids)) {
            $unique_del_total = count($seen_delivered_pids);
            $totals['unique_delivery_count'] = $unique_del_total;
            foreach ($team_breakdown as &$tb_ref) {
                $tn = $tb_ref['team_name'] ?? '';
                if ($tn !== '' && isset($team_seen_delivered_pids[$tn])) {
                    $tb_ref['unique_delivery_count'] = count($team_seen_delivered_pids[$tn]);
                }
            }
            unset($tb_ref);
        }

        // Include management/operations projects (e.g. unassigned or assigned to Operations Manager / User 1) into agency totals & team breakdown
        if (!$is_archived_month) {
            $extra_projects = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $p_table 
                 WHERE (assigned_user_id = 1 OR assigned_user_ids = '1' OR assigned_user_id IS NULL OR assigned_user_id = 0)
                 AND (
                     (DATE(COALESCE(created_at, delivery_date)) BETWEEN %s AND %s)
                     OR (DATE(COALESCE(created_at, delivery_date)) < %s AND status IN ('wip', 'nra', 'revision', 'delivery_submitted'))
                     OR (DATE(COALESCE(created_at, delivery_date)) < %s AND status IN ('delivery_accepted', 'completed') AND DATE(COALESCE(delivered_date, delivery_date)) BETWEEN %s AND %s)
                     OR (status = 'cancelled' AND DATE(COALESCE(delivered_date, delivery_date, created_at)) BETWEEN %s AND %s)
                     OR (tip_amount > 0 AND DATE(COALESCE(tip_date, delivered_date, delivery_date)) BETWEEN %s AND %s)
                 )",
                $start_date_str, $end_date_str, $start_date_str, $start_date_str, $start_date_str, $end_date_str, $start_date_str, $end_date_str, $start_date_str, $end_date_str
            ));
            foreach ($extra_projects as $xp) {
                $x_pid = (int)$xp->id;
                $x_net = (float)$xp->net_delivery_value;
                $x_team = $xp->team_name ?: 'WpCraft';
                $x_cdate = !empty($xp->created_at) ? substr($xp->created_at, 0, 10) : $xp->delivery_date;
                $x_ddate = !empty($xp->delivered_date) ? substr($xp->delivered_date, 0, 10) : $xp->delivery_date;

                $is_new = ($x_cdate >= $start_date_str && $x_cdate <= $end_date_str);
                $already_delivered_prior = (!empty($xp->delivered_date) && substr($xp->delivered_date, 0, 10) < $start_date_str);
                $is_cf = ($x_cdate < $start_date_str) && !$already_delivered_prior && (
                    in_array($xp->status, ['wip', 'nra', 'revision', 'delivery_submitted'], true)
                    || (in_array($xp->status, ['delivery_accepted', 'completed'], true) && !empty($xp->delivered_date) && substr($xp->delivered_date, 0, 10) >= $start_date_str)
                );

                if ($is_new) {
                    $totals['new_assigned']++;
                    $totals['new_assigned_count']++;
                    $totals['new_assigned_val'] = round($totals['new_assigned_val'] + $x_net, 2);
                    $totals['total_assigned']++;
                    $totals['total_assigned_count']++;
                    $totals['total_assigned_val'] = round($totals['total_assigned_val'] + $x_net, 2);
                } elseif ($is_cf) {
                    $totals['carry_forward']++;
                    $totals['carry_forward_count']++;
                    $totals['carry_forward_val'] = round($totals['carry_forward_val'] + $x_net, 2);
                    $totals['total_assigned']++;
                    $totals['total_assigned_count']++;
                    $totals['total_assigned_val'] = round($totals['total_assigned_val'] + $x_net, 2);
                }

                if ($xp->status === 'wip') {
                    $totals['total_wip'] = round($totals['total_wip'] + $x_net, 2);
                    $totals['total_wip_net'] = round($totals['total_wip_net'] + $x_net, 2);
                    $totals['wip_count']++;
                } elseif ($xp->status === 'delivery_submitted') {
                    $totals['total_submitted'] = round($totals['total_submitted'] + $x_net, 2);
                    $totals['submitted_val'] = round($totals['submitted_val'] + $x_net, 2);
                    $totals['submitted_count']++;
                } elseif (in_array($xp->status, ['delivery_accepted', 'completed'], true) && $x_ddate >= $start_date_str && $x_ddate <= $end_date_str) {
                    $totals['total_delivery'] = round($totals['total_delivery'] + $x_net, 2);
                    $totals['delivery_count']++;
                    $seen_delivered_pids[$x_pid] = true;
                }

                foreach ($team_breakdown as &$tb_ref) {
                    if (strcasecmp($tb_ref['team_name'] ?? '', $x_team) === 0) {
                        if ($is_new) {
                            $tb_ref['new_assigned_count']++;
                            $tb_ref['new_assigned_val'] = round($tb_ref['new_assigned_val'] + $x_net, 2);
                            $tb_ref['total_assigned_count']++;
                            $tb_ref['total_assigned_val'] = round($tb_ref['total_assigned_val'] + $x_net, 2);
                        } elseif ($is_cf) {
                            $tb_ref['carry_forward_count']++;
                            $tb_ref['carry_forward_val'] = round($tb_ref['carry_forward_val'] + $x_net, 2);
                            $tb_ref['total_assigned_count']++;
                            $tb_ref['total_assigned_val'] = round($tb_ref['total_assigned_val'] + $x_net, 2);
                        }
                        if ($xp->status === 'wip') {
                            $tb_ref['wip_val'] = round($tb_ref['wip_val'] + $x_net, 2);
                            $tb_ref['wip_count']++;
                        } elseif ($xp->status === 'delivery_submitted') {
                            $tb_ref['submitted_val'] = round($tb_ref['submitted_val'] + $x_net, 2);
                            $tb_ref['submitted_count']++;
                        }
                    }
                }
                unset($tb_ref);
            }

            // Exact unique project counts for Agency-Wide Scope (100% parity with All Projects tab)
            $unique_wip_total = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT id) FROM $p_table WHERE status = 'wip' AND DATE(created_at) <= %s",
                $end_date_str
            ));
            $totals['unique_wip_count'] = $unique_wip_total;

            $unique_subm_total = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT id) FROM $p_table WHERE status = 'delivery_submitted' AND DATE(created_at) <= %s",
                $end_date_str
            ));
            $totals['unique_submitted_count'] = $unique_subm_total;

            $unique_new_total = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT id) FROM $p_table WHERE DATE(created_at) BETWEEN %s AND %s",
                $start_date_str, $end_date_str
            ));
            $totals['unique_new_assigned_count'] = $unique_new_total;

            $unique_cf_total = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT id) FROM $p_table WHERE DATE(created_at) < %s AND (delivered_date IS NULL OR DATE(delivered_date) >= %s) AND (status IN ('wip', 'nra', 'revision', 'delivery_submitted') OR (status IN ('delivery_accepted', 'completed') AND DATE(COALESCE(delivered_date, delivery_date)) >= %s))",
                $start_date_str, $start_date_str, $start_date_str
            ));
            $totals['unique_carry_forward_count'] = $unique_cf_total;
            $totals['unique_total_assigned_count'] = $unique_new_total + $unique_cf_total;
        }

        return rest_ensure_response([
            "is_historical_archive" => $is_archived_month,
            "reporting_month" => $month,
            "available_months" => $dynamic_months,
            "rows" => $rows,
            "totals" => $totals,
            "team_breakdown" => $team_breakdown
        ]);
    }

    public function import_monthly_csv($request) {
        global $wpdb;
        $files = $request->get_file_params();
        if (empty($files['csv_file'])) {
            return $this->format_error(__('No CSV file uploaded.', 'fiverr-team-management'), 400);
        }

        $file_info = $files['csv_file'];
        $ext = strtolower(pathinfo($file_info['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            return $this->format_error(__('Invalid file format. Only .csv files are supported.', 'fiverr-team-management'), 400);
        }

        $file_path = $file_info['tmp_name'];
        $month = sanitize_text_field($request->get_param('reporting_month') ?: current_time('Y-m'));

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return $this->format_error(__('Failed to open CSV file.', 'fiverr-team-management'), 500);
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header || count($header) < 10) {
            fclose($handle);
            return $this->format_error(__('Invalid CSV structure: header row missing or has too few columns.', 'fiverr-team-management'), 400);
        }

        $imported = 0;
        $table = $wpdb->prefix . 'ftm_monthly_archive';

        // Delete existing for this month to prevent duplicate import
        $wpdb->delete($table, ['reporting_month' => $month]);

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row) || count($row) < 3) {
                continue;
            }

            $col0 = trim($row[0] ?? '');
            $col1 = trim($row[1] ?? '');

            // Skip empty rows
            if ($col0 === '' || $col1 === '') {
                continue;
            }

            // Filter out summary/footer rows (e.g. "Total:", "Total Member 15", "Total delivery ratio")
            if (stripos($col0, 'total') !== false || stripos($col1, 'total') !== false || stripos($col0, 'member id') !== false || stripos($col0, 'summary') !== false || stripos($col1, 'summary') !== false) {
                continue;
            }

            // Ensure not a metric summary row
            if (strpos($col0, '$') !== false || strpos($col0, '%') !== false) {
                continue;
            }

            $wpdb->insert($table, [
                'reporting_month' => $month,
                'member_custom_id' => sanitize_text_field($col0),
                'member_name' => sanitize_text_field($col1),
                'total_delivery' => (float)str_replace(['$', ','], '', $row[2] ?? 0),
                'total_wip' => (float)str_replace(['$', ','], '', $row[3] ?? 0),
                'tips' => (float)str_replace(['$', ','], '', $row[4] ?? 0),
                'cancelled' => (float)str_replace(['$', ','], '', $row[5] ?? 0),
                'partial' => (float)str_replace(['$', ','], '', $row[6] ?? 0),
                'total_completed' => (int)($row[7] ?? 0),
                'stars_5' => (int)($row[8] ?? 0),
                'stars_4' => (int)($row[9] ?? 0),
                'stars_3' => (int)($row[10] ?? 0),
                'stars_2' => (int)($row[11] ?? 0),
                'stars_1' => (int)($row[12] ?? 0),
                'total_cancel' => (int)($row[13] ?? 0),
                'delivered_without_extension' => (int)($row[14] ?? 0),
                'team_name' => sanitize_text_field($row[15] ?? 'WpCraft'),
                'call_sign' => sanitize_text_field($row[16] ?? '')
            ]);
            $imported++;
        }
        fclose($handle);

        return $this->format_success(
            ['imported_rows' => $imported],
            sprintf(__('Successfully imported %d valid member records for month %s.', 'fiverr-team-management'), $imported, $month)
        );
    }

    /**
     * Compute live summary totals from distinct projects directly to guarantee zero multi-assignee inflation
     */
    private function calculate_live_summary($team, $start_date, $end_date, $member_count) {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_projects';

        $team_where = "";
        $params = [$start_date, $end_date, $start_date, $end_date];
        if ($team !== 'all') {
            $team_where = " AND team_name = %s";
            $params[] = $team;
        }

        // Query cross-month tips: any tip received in this date window belongs to this month's tips & delivery value
        $tip_params = [$start_date, $end_date];
        $tip_team_where = "";
        if ($team !== 'all') {
            $tip_team_where = " AND team_name = %s";
            $tip_params[] = $team;
        }
        $raw_period_tips = (float)$wpdb->get_var($wpdb->prepare(
            "SELECT SUM(tip_amount) FROM $table 
             WHERE tip_amount > 0 
             AND DATE(COALESCE(tip_date, delivered_date, delivery_date)) BETWEEN %s AND %s
             $tip_team_where",
            $tip_params
        ));
        $net_period_tips = FTM_Financial::tip_net($raw_period_tips);

        $sql = "SELECT 
            status,
            COUNT(DISTINCT id) as order_count,
            SUM(net_delivery_value) as sum_net,
            SUM(cancelled_value) as sum_cancelled,
            SUM(partial_payout_value) as sum_partial,
            SUM(CASE WHEN ROUND(star_rating) = 5 AND client_reviewed = 'yes' THEN 1 ELSE 0 END) as count_stars_5,
            SUM(CASE WHEN status IN ('delivery_accepted', 'completed') AND COALESCE(is_ontime, delivered_without_extension, CASE WHEN extension_count = 0 THEN 1 ELSE 0 END) = 1 THEN 1 ELSE 0 END) as count_on_time
            FROM $table 
            WHERE (
                (status IN ('delivery_accepted', 'completed') AND COALESCE(DATE(delivered_date), delivery_date) BETWEEN %s AND %s)
                OR (status NOT IN ('delivery_accepted', 'completed') AND delivery_date BETWEEN %s AND %s)
            ) $team_where
            GROUP BY status";

        $results = $wpdb->get_results($wpdb->prepare($sql, $params));

        $tot = [
            'total_delivery' => 0.00,
            'total_submitted' => 0.00,
            'submitted_val' => 0.00,
            'submitted_count' => 0,
            'total_wip' => 0.00,
            'tips' => $net_period_tips,
            'cancelled' => 0.00,
            'partial' => 0.00,
            'total_completed' => 0,
            'stars_5' => 0,
            'total_cancel' => 0,
            'delivered_without_extension' => 0,
            'total_members' => $member_count
        ];

        $deliveries_net = 0.00;
        foreach ($results as $row) {
            $st = $row->status;
            $net = (float)$row->sum_net;
            $cancelled = (float)$row->sum_cancelled;
            $partial = (float)$row->sum_partial;
            $cnt = (int)$row->order_count;

            if (in_array($st, ['delivery_accepted', 'completed'], true)) {
                $deliveries_net += $net;
                $tot['total_completed'] += $cnt;
                $tot['stars_5'] += (int)$row->count_stars_5;
                $tot['delivered_without_extension'] += (int)$row->count_on_time;
            } elseif ($st === 'delivery_submitted') {
                $tot['total_submitted'] += $net;
                $tot['submitted_val'] += $net;
                $tot['submitted_count'] += $cnt;
            } elseif (in_array($st, ['wip', 'nra', 'revision'], true)) {
                $tot['total_wip'] += $net;
            } elseif ($st === 'cancelled') {
                $tot['cancelled'] += ($cancelled > 0 ? $cancelled : $net);
                $tot['total_cancel'] += $cnt;
            }

            if ($partial > 0) {
                $tot['partial'] += $partial;
            }
        }

        $tot['total_delivery'] = round($deliveries_net + $net_period_tips, 2);

        $tot['total_work_assigned'] = round($tot['total_delivery'] + $tot['total_wip'] + $tot['cancelled'] + $tot['partial'], 2);
        $tot['delivery_ratio'] = ($tot['total_work_assigned'] > 0) ? round(($tot['total_delivery'] / $tot['total_work_assigned']) * 100, 2) : 0;
        $tot['five_star_ratio'] = ($tot['total_completed'] > 0) ? round(($tot['stars_5'] / $tot['total_completed']) * 100, 2) : 0;
        $tot['average_delivery'] = ($tot['total_members'] > 0) ? round($tot['total_delivery'] / $tot['total_members'], 2) : 0;

        foreach ($tot as $k => $v) {
            if (is_float($v)) {
                $tot[$k] = round($v, 2);
            }
        }

        $tot['total_net'] = round($tot['total_delivery'], 2);
        $tot['total_gross'] = round($tot['total_delivery'] / 0.8, 2);
        $tot['net'] = round($tot['total_delivery'], 2);
        $tot['gross'] = round($tot['total_delivery'] / 0.8, 2);
        $tot['total_wip_net'] = round($tot['total_wip'], 2);
        $tot['total_wip_gross'] = round($tot['total_wip'] / 0.8, 2);
        $tot['total_deliveries'] = (int)($tot['total_completed'] ?? 0);
        $tot['deliveries'] = (int)($tot['total_completed'] ?? 0);

        return $tot;
    }

    private function calculate_team_breakdown($rows) {
        $breakdown = [];
        foreach ($rows as $r) {
            $tname = $r["team_name"] ?: "WpCraft";
            if (!isset($breakdown[$tname])) {
                $breakdown[$tname] = [
                    "team_name" => $tname,
                    "members_count" => 0,
                    "wip_count" => 0,
                    "wip_val" => 0.00,
                    "submitted_count" => 0,
                    "submitted_val" => 0.00,
                    "nra_count" => 0,
                    "nra_val" => 0.00,
                    "delivery_count" => 0,
                    "delivery_val" => 0.00,
                    "cancelled_count" => 0,
                    "cancelled_val" => 0.00,
                    "new_assigned_count" => 0,
                    "new_assigned_val" => 0.00,
                    "carry_forward_count" => 0,
                    "carry_forward_val" => 0.00,
                    "total_assigned_count" => 0,
                    "total_assigned_val" => 0.00,
                    "repeat_count" => 0,
                    "repeat_val" => 0.00
                ];
            }
            $breakdown[$tname]["members_count"]++;
            $breakdown[$tname]["wip_count"] += (int)($r["wip_count"] ?? 0);
            $breakdown[$tname]["wip_val"] += (float)($r["total_wip"] ?? 0);
            $breakdown[$tname]["submitted_count"] += (int)($r["submitted_count"] ?? 0);
            $breakdown[$tname]["submitted_val"] += (float)($r["submitted_val"] ?? $r["total_submitted"] ?? 0);
            $breakdown[$tname]["nra_count"] += (int)($r["nra_count"] ?? 0);
            $breakdown[$tname]["nra_val"] += (float)($r["nra_val"] ?? 0);
            $breakdown[$tname]["delivery_count"] += (int)($r["delivery_count"] ?? 0);
            $breakdown[$tname]["delivery_val"] += (float)($r["total_delivery"] ?? 0);
            $breakdown[$tname]["cancelled_count"] += (int)($r["cancelled_count"] ?? 0);
            $breakdown[$tname]["cancelled_val"] += (float)($r["cancelled"] ?? 0);
            $breakdown[$tname]["new_assigned_count"] += (int)($r["new_assigned_count"] ?? 0);
            $breakdown[$tname]["new_assigned_val"] += (float)($r["new_assigned_val"] ?? 0);
            $breakdown[$tname]["carry_forward_count"] += (int)($r["carry_forward_count"] ?? 0);
            $breakdown[$tname]["carry_forward_val"] += (float)($r["carry_forward_val"] ?? 0);
            $breakdown[$tname]["total_assigned_count"] += (int)($r["total_assigned_count"] ?? 0);
            $breakdown[$tname]["total_assigned_val"] += (float)($r["total_assigned_val"] ?? 0);
            $breakdown[$tname]["repeat_count"] += (int)($r["repeat_count"] ?? 0);
            $breakdown[$tname]["repeat_val"] += (float)($r["repeat_val"] ?? 0);
        }
        foreach ($breakdown as $k => $v) {
            $breakdown[$k]["wip_val"] = round($v["wip_val"], 2);
            $breakdown[$k]["submitted_val"] = round($v["submitted_val"], 2);
            $breakdown[$k]["nra_val"] = round($v["nra_val"], 2);
            $breakdown[$k]["delivery_val"] = round($v["delivery_val"], 2);
            $breakdown[$k]["cancelled_val"] = round($v["cancelled_val"], 2);
            $breakdown[$k]["new_assigned_val"] = round($v["new_assigned_val"], 2);
            $breakdown[$k]["carry_forward_val"] = round($v["carry_forward_val"], 2);
            $breakdown[$k]["total_assigned_val"] = round($v["total_assigned_val"], 2);
            $breakdown[$k]["repeat_val"] = round($v["repeat_val"], 2);
        }
        return array_values($breakdown);
    }

    private function calculate_rows_summary($rows) {
        $tot = [
            "total_delivery" => 0.00,
            "total_delivery_net" => 0.00,
            "total_delivery_gross" => 0.00,
            "total_submitted" => 0.00,
            "submitted_val" => 0.00,
            "submitted_count" => 0,
            "total_wip" => 0.00,
            "total_wip_net" => 0.00,
            "total_wip_gross" => 0.00,
            "wip_count" => 0,
            "nra" => 0,
            "nra_count" => 0,
            "nra_val" => 0.00,
            "delivery" => 0.00,
            "delivery_count" => 0,
            "cancelled" => 0.00,
            "cancelled_val" => 0.00,
            "cancelled_count" => 0,
            "new_assigned" => 0,
            "new_assigned_count" => 0,
            "new_assigned_val" => 0.00,
            "carry_forward" => 0,
            "carry_forward_count" => 0,
            "carry_forward_val" => 0.00,
            "total_assigned" => 0,
            "total_assigned_count" => 0,
            "total_assigned_val" => 0.00,
            "tips" => 0.00,
            "total_tips" => 0.00,
            "partial" => 0.00,
            "total_completed" => 0,
            "total_deliveries" => 0,
            "deliveries" => 0,
            "stars_5" => 0,
            "stars_4" => 0,
            "stars_3" => 0,
            "stars_2" => 0,
            "stars_1" => 0,
            "total_cancel" => 0,
            "delivered_without_extension" => 0,
            "extension_count" => 0,
            "extension_days" => 0,
            "late_count" => 0,
            "tip_count" => 0,
            "repeat_count" => 0,
            "repeat_orders_count" => 0,
            "repeat_val" => 0.00,
            "repeat_net_value" => 0.00,
            "total_members" => count($rows),
            "active_members" => count($rows)
        ];

        foreach ($rows as $r) {
            $is_arr = is_array($r);
            $del = $is_arr ? (float)($r["total_delivery"] ?? 0) : (float)($r->total_delivery ?? 0);
            $subval = $is_arr ? (float)($r["submitted_val"] ?? $r["total_submitted"] ?? 0) : (float)($r->submitted_val ?? $r->total_submitted ?? 0);
            $subcnt = $is_arr ? (int)($r["submitted_count"] ?? 0) : (int)($r->submitted_count ?? 0);
            $wip = $is_arr ? (float)($r["total_wip"] ?? 0) : (float)($r->total_wip ?? 0);
            $wcnt = $is_arr ? (int)($r["wip_count"] ?? 0) : 0;
            $nracnt = $is_arr ? (int)($r["nra_count"] ?? 0) : 0;
            $nraval = $is_arr ? (float)($r["nra_val"] ?? 0) : 0;
            $delcnt = $is_arr ? (int)($r["delivery_count"] ?? 0) : (int)($r->total_completed ?? 0);
            $cancval = $is_arr ? (float)($r["cancelled"] ?? 0) : (float)($r->cancelled ?? 0);
            $canccnt = $is_arr ? (int)($r["cancelled_count"] ?? 0) : (int)($r->total_cancel ?? 0);
            $newcnt = $is_arr ? (int)($r["new_assigned_count"] ?? 0) : 0;
            $newval = $is_arr ? (float)($r["new_assigned_val"] ?? 0) : 0;
            $carcnt = $is_arr ? (int)($r["carry_forward_count"] ?? 0) : 0;
            $carval = $is_arr ? (float)($r["carry_forward_val"] ?? 0) : 0;
            $tips = $is_arr ? (float)($r["tips"] ?? 0) : (float)($r->tips ?? 0);
            $partial = $is_arr ? (float)($r["partial"] ?? 0) : (float)($r->partial ?? 0);
            $s5 = $is_arr ? (int)($r["stars_5"] ?? 0) : (int)($r->stars_5 ?? 0);
            $ontime = $is_arr ? (int)($r["delivered_without_extension"] ?? 0) : (int)($r->delivered_without_extension ?? 0);

            $tot["total_delivery"] += $del;
            $tot["total_submitted"] += $subval;
            $tot["submitted_val"] += $subval;
            $tot["submitted_count"] += $subcnt;
            $tot["total_wip"] += $wip;
            $tot["wip_count"] += $wcnt;
            $tot["nra"] += $nracnt;
            $tot["nra_count"] += $nracnt;
            $tot["nra_val"] += $nraval;
            $tot["delivery"] += $del;
            $tot["delivery_count"] += $delcnt;
            $tot["cancelled"] += $cancval;
            $tot["cancelled_val"] += $cancval;
            $tot["cancelled_count"] += $canccnt;
            $tot["new_assigned"] += $newcnt;
            $tot["new_assigned_count"] += $newcnt;
            $tot["new_assigned_val"] += $newval;
            $tot["carry_forward"] += $carcnt;
            $tot["carry_forward_count"] += $carcnt;
            $tot["carry_forward_val"] += $carval;
            $tot["tips"] += $tips;
            $tot["total_tips"] += $tips;
            $tot["partial"] += $partial;
            $tot["total_completed"] += $delcnt;
            $tot["stars_5"] += $s5;
            $tot["total_cancel"] += $canccnt;
            $tot["delivered_without_extension"] += $ontime;
            $tot["extension_count"] += $is_arr ? (int)($r["extension_count"] ?? 0) : 0;
            $tot["extension_days"] += $is_arr ? (int)($r["extension_days"] ?? 0) : 0;
            $tot["late_count"] += $is_arr ? (int)($r["late_count"] ?? 0) : 0;
            $tot["tip_count"] += $is_arr ? (int)($r["tip_count"] ?? 0) : 0;
            $rep_cnt = $is_arr ? (int)($r["repeat_count"] ?? 0) : 0;
            $rep_val = $is_arr ? (float)($r["repeat_val"] ?? 0) : 0.0;
            $tot["repeat_count"] += $rep_cnt;
            $tot["repeat_orders_count"] += $rep_cnt;
            $tot["repeat_val"] += $rep_val;
            $tot["repeat_net_value"] += $rep_val;
        }

        $tot["total_assigned"] = $tot["new_assigned"] + $tot["carry_forward"];
        $tot["total_assigned_count"] = $tot["new_assigned_count"] + $tot["carry_forward_count"];
        $tot["total_assigned_val"] = round($tot["new_assigned_val"] + $tot["carry_forward_val"] + ($tot["total_tips"] ?? $tot["tips"] ?? 0), 2);

        $tot["total_delivery_net"] = round($tot["total_delivery"], 2);
        $tot["total_delivery_gross"] = round($tot["total_delivery"] / 0.8, 2);
        $tot["total_submitted"] = round($tot["total_submitted"], 2);
        $tot["submitted_val"] = round($tot["submitted_val"], 2);
        $tot["total_wip_net"] = round($tot["total_wip"], 2);
        $tot["total_wip_gross"] = round($tot["total_wip"] / 0.8, 2);
        $tot["total_deliveries"] = $tot["total_completed"];
        $tot["deliveries"] = $tot["total_completed"];

        $tot["total_work_assigned"] = round($tot["total_assigned_val"], 2);
        if ($tot["total_work_assigned"] <= 0) {
            $tot["total_work_assigned"] = round($tot["total_delivery"] + $tot["total_wip"] + $tot["cancelled"], 2);
        }
        $tot["delivery_ratio"] = ($tot["total_work_assigned"] > 0) ? round(($tot["total_delivery"] / $tot["total_work_assigned"]) * 100, 1) : 0.0;
        $tot["five_star_ratio"] = ($tot["total_completed"] > 0) ? round(($tot["stars_5"] / $tot["total_completed"]) * 100, 1) : 0.0;
        $tot["average_delivery"] = ($tot["active_members"] > 0) ? round($tot["total_delivery"] / $tot["active_members"], 2) : 0.0;

        foreach ($tot as $k => $v) {
            if (is_float($v)) {
                $tot[$k] = round($v, 2);
            }
        }
        return $tot;
    }

    public function get_leaderboard_data($request) {
        global $wpdb;

        $period = sanitize_text_field($request->get_param('period') ?: 'monthly');
        $period_key = sanitize_text_field($request->get_param('period_key') ?: ($request->get_param('month') ?: ''));
        $team = sanitize_text_field($request->get_param('team') ?: 'all');
        $sort = sanitize_text_field($request->get_param('sort') ?: 'score');

        $archive_table = $wpdb->prefix . 'ftm_monthly_archive';
        $p_table = $wpdb->prefix . 'ftm_projects';
        $assignees_table = $wpdb->prefix . 'ftm_project_assignees';

        // 1. Build Base Roster of Eligible Active Specialists (excluding admin & Team Elite)
        $users_args = ['meta_key' => 'ftm_team', 'orderby' => 'display_name'];
        $all_users = get_users($users_args);

        $roster = [];
        $team_counts = [
            'all' => 0,
            'WpCraft' => 0,
            'WpNexus' => 0,
            'WpTitans' => 0
        ];

        foreach ($all_users as $u) {
            $uid = (int)$u->ID;
            $user_team = get_user_meta($uid, 'ftm_team', true) ?: 'WpCraft';
            if (FTM_Permissions::is_management_team($user_team) || $uid === 1 || $u->user_login === 'admin') {
                continue;
            }

            $member_status = get_user_meta($uid, 'ftm_status', true);
            $is_resigned = !empty($member_status) && in_array(strtolower($member_status), ['inactive', 'resigned', 'former', 'left', 'isolated'], true);
            if ($is_resigned) {
                continue;
            }

            $custom_id = get_user_meta($uid, 'ftm_custom_id', true) ?: (string)$uid;
            $call_sign = get_user_meta($uid, 'ftm_call_sign', true) ?: $u->display_name;
            $role_slug = FTM_Permissions::get_user_role($uid);
            $role_label = FTM_Permissions::get_role_label($role_slug);
            $avatar_url = get_avatar_url($uid, ['size' => 128]);

            $team_counts['all']++;
            if (isset($team_counts[$user_team])) {
                $team_counts[$user_team]++;
            } else {
                $team_counts[$user_team] = 1;
            }

            $roster[$custom_id] = [
                'user_id' => $uid,
                'member_custom_id' => $custom_id,
                'member_name' => $u->display_name,
                'call_sign' => $call_sign,
                'team_name' => $user_team,
                'role_slug' => $role_slug,
                'role_label' => $role_label,
                'avatar_url' => $avatar_url,
                'total_delivery' => 0.00,
                'total_completed' => 0,
                'delivered_without_extension' => 0,
                'stars_5' => 0,
                'stars_4' => 0,
                'stars_3' => 0,
                'stars_2' => 0,
                'stars_1' => 0, 'stars_adj' => 0.0,
                'total_cancel' => 0,
                'total_wip' => 0.00
            ];
        }

        // Available periods dictionary for frontend switcher
        $live_period_rows = array_map(function($m) { return ['key' => $m['key'], 'label' => $m['label'] . (!empty($m['current']) ? ' (Live)' : '')]; }, ftm_live_months());
        $available_periods = [
            'monthly' => array_merge($live_period_rows, [
                ['key' => 'October_2026', 'label' => 'October 2026'],
                ['key' => 'September_2026', 'label' => 'September 2026'],
                ['key' => 'August_2026', 'label' => 'August 2026'],
                ['key' => 'July_2026', 'label' => 'July 2026'],
                ['key' => 'June_2026', 'label' => 'June 2026'],
                ['key' => 'May_2026', 'label' => 'May 2026'],
                ['key' => 'April_2026', 'label' => 'April 2026'],
                ['key' => 'March_2026', 'label' => 'March 2026'],
                ['key' => 'February_2026', 'label' => 'February 2026'],
                ['key' => 'January_2026', 'label' => 'January 2026']
            ]),
            'quarterly' => [
                ['key' => 'Q4_2026', 'label' => 'Q4 2026 (Oct - Dec)'],
                ['key' => 'Q3_2026', 'label' => 'Q3 2026 (Jul - Sep)'],
                ['key' => 'Q2_2026', 'label' => 'Q2 2026 (Apr - Jun)'],
                ['key' => 'Q1_2026', 'label' => 'Q1 2026 (Jan - Mar)']
            ],
            'all_time' => [
                ['key' => 'all_time', 'label' => 'All-Time Records (Jan 2026 - Present)']
            ]
        ];

        $period_label = '';
        $quarter_period = '';

        if ($period === 'monthly') {
            if (empty($period_key)) {
                $period_key = current_time('Y-m');
            }

            // Determine display label
            $display_label = $period_key;
            if (preg_match('/^(\d{4})-(\d{2})$/', $period_key, $m_parts)) {
                $display_label = date('F Y', mktime(0, 0, 0, (int)$m_parts[2], 1, (int)$m_parts[1]));
            } elseif (preg_match('/^([a-zA-Z]+)_(\d{4})$/', $period_key, $m_parts)) {
                $display_label = ucfirst(strtolower($m_parts[1])) . ' ' . $m_parts[2];
            }
            $period_label = $display_label;

            // Check if month is in archive
            $month_aliases = [$period_key];
            if (preg_match('/^([a-zA-Z]+)_(\d{4})$/i', $period_key, $m_parts)) {
                $month_aliases[] = strtolower($m_parts[1]) . '_' . $m_parts[2];
                $month_aliases[] = ucfirst(strtolower($m_parts[1])) . '_' . $m_parts[2];
            } elseif (preg_match('/^(\d{4})-(\d{2})$/', $period_key, $m_parts)) {
                $timestamp = mktime(0, 0, 0, (int)$m_parts[2], 1, (int)$m_parts[1]);
                $mname = date('F', $timestamp);
                $month_aliases[] = strtolower($mname) . '_' . $m_parts[1];
                $month_aliases[] = ucfirst($mname) . '_' . $m_parts[1];
            }
            $month_aliases = array_values(array_unique($month_aliases));
            $in_aliases = implode(',', array_fill(0, count($month_aliases), '%s'));

            $archived_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $archive_table WHERE reporting_month IN ($in_aliases)",
                $month_aliases
            ));

            if (!empty($archived_rows)) {
                foreach ($archived_rows as $ar) {
                    $cid = $ar->member_custom_id;
                    if (!isset($roster[$cid])) {
                        continue;
                    }
                    $roster[$cid]['total_delivery'] = (float)$ar->total_delivery;
                    $roster[$cid]['total_completed'] = (int)$ar->total_completed;
                    $roster[$cid]['delivered_without_extension'] = (int)$ar->delivered_without_extension;
                    $roster[$cid]['stars_5'] = (int)$ar->stars_5;
                    $roster[$cid]['stars_4'] = (int)$ar->stars_4;
                    $roster[$cid]['stars_3'] = (int)$ar->stars_3;
                    $roster[$cid]['stars_2'] = (int)$ar->stars_2;
                    $roster[$cid]['stars_1'] = (int)$ar->stars_1;
                    $roster[$cid]['total_cancel'] = (int)$ar->total_cancel;
                    $roster[$cid]['total_wip'] = (float)$ar->total_wip;
                }
            } else {
                // Compile live for current month
                $parsed_month = $period_key;
                if (preg_match('/^([a-zA-Z]+)_(\d{4})$/i', $period_key, $m_matches)) {
                    $ts = strtotime($m_matches[1] . ' 1 ' . $m_matches[2]);
                    if ($ts) {
                        $parsed_month = date('Y-m', $ts);
                    }
                }
                $start_date = $parsed_month . '-01 00:00:00';
                $end_date = date('Y-m-t 23:59:59', strtotime($start_date));
                $m_start = substr($start_date, 0, 10);
                $m_end = substr($end_date, 0, 10);

                $live_projects = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $p_table 
                     WHERE (
                         (status IN ('delivery_accepted', 'completed') AND COALESCE(DATE(delivered_date), delivery_date) BETWEEN %s AND %s)
                         OR (status = 'cancelled' AND delivery_date BETWEEN %s AND %s)
                         OR (status IN ('wip', 'nra', 'revision', 'delivery_submitted') AND DATE(created_at) <= %s)
                         OR (status IN ('delivery_accepted', 'completed') AND COALESCE(DATE(delivered_date), delivery_date) > %s AND DATE(created_at) <= %s)
                         OR (tip_amount > 0 AND DATE(COALESCE(tip_date, delivered_date, delivery_date)) BETWEEN %s AND %s)
                     )",
                    $start_date, $end_date, $start_date, $end_date, $end_date, $m_end, $end_date, $start_date, $end_date
                ));

                foreach ($live_projects as $up) {
                    $pid = (int)$up->id;
                    $raw_ids = !empty($up->assigned_user_ids) ? explode(',', $up->assigned_user_ids) : [(int)$up->assigned_user_id];
                    $assignee_ids = array_unique(array_filter(array_map('intval', $raw_ids)));
                    if (empty($assignee_ids) && (int)$up->assigned_user_id > 0) {
                        $assignee_ids = [(int)$up->assigned_user_id];
                    }
                    $assignee_count = max(1, count($assignee_ids));

                    $split_rows = $wpdb->get_results($wpdb->prepare(
                        "SELECT user_id, split_percentage FROM $assignees_table WHERE project_id = %d ORDER BY user_id ASC",
                        $pid
                    ));
                    $pct_map = [];
                    $has_explicit_splits = false;
                    if (!empty($split_rows)) {
                        $has_explicit_splits = true;
                        foreach ($split_rows as $sr) {
                            $pct_map[(int)$sr->user_id] = (float)$sr->split_percentage;
                        }
                    }
                    $ordered_uids = $has_explicit_splits ? array_keys($pct_map) : $assignee_ids;
                    sort($ordered_uids, SORT_NUMERIC);

                    foreach ($assignee_ids as $aid) {
                        $user_split = ($has_explicit_splits && isset($pct_map[$aid])) ? ($pct_map[$aid] / 100.0) : (1.0 / $assignee_count);
                        if ($user_split <= 0) {
                            continue;
                        }
                        $calc_exact_share = function($total_amt) use ($aid, $ordered_uids, $has_explicit_splits, $pct_map, $assignee_count, $user_split) {
                            $total_amt = round((float)$total_amt, 2);
                            $n = count($ordered_uids);
                            $pos = array_search($aid, $ordered_uids, true);
                            if ($pos === false || $n <= 1) {
                                return round($total_amt * $user_split, 2);
                            }
                            if ($pos === $n - 1) {
                                $allocated = 0.0;
                                for ($i = 0; $i < $n - 1; $i++) {
                                    $ouid = $ordered_uids[$i];
                                    $sp = ($has_explicit_splits && isset($pct_map[$ouid])) ? ($pct_map[$ouid] / 100.0) : (1.0 / $assignee_count);
                                    $allocated += round($total_amt * $sp, 2);
                                }
                                return round($total_amt - $allocated, 2);
                            }
                            return round($total_amt * $user_split, 2);
                        };

                        $ucid = get_user_meta($aid, 'ftm_custom_id', true) ?: (string)$aid;
                        if (!isset($roster[$ucid])) {
                            continue;
                        }

                        $del_actual = !empty($up->delivered_date) ? date('Y-m-d', strtotime($up->delivered_date)) : $up->delivery_date;
                        $tip_actual = !empty($up->tip_date) ? date('Y-m-d', strtotime($up->tip_date)) : $del_actual;
                        $tip_in_month = ((float)$up->tip_amount > 0 && $tip_actual >= $m_start && $tip_actual <= $m_end);
                        $tip_share = $tip_in_month ? $calc_exact_share(FTM_Financial::tip_net($up->tip_amount)) : 0.0;
                        $net_share = $calc_exact_share($up->net_delivery_value);

                        if (in_array($up->status, ['delivery_accepted', 'completed'], true)) {
                            if ($del_actual >= $m_start && $del_actual <= $m_end) {
                                $roster[$ucid]['total_delivery'] += ($net_share + $tip_share);
                                $roster[$ucid]['total_completed']++;
                                if ((int)($up->extension_count ?? 0) === 0) {
                                    $roster[$ucid]['delivered_without_extension']++;
                                }
                                if ($up->client_reviewed === 'yes') {
                                    $rv = (float)$up->star_rating; $r = (int)round($rv);
                                    if ($r >= 1 && $r <= 5) {
                                        $roster[$ucid]['stars_' . $r]++; $roster[$ucid]['stars_adj'] += ($rv - $r);
                                    }
                                }
                            } elseif ($del_actual < $m_start && $tip_in_month) {
                                $roster[$ucid]['total_delivery'] += $tip_share;
                            } elseif ($del_actual > $m_end) {
                                // Delivered in a later month, so it was still work in progress during this one
                                $roster[$ucid]['total_wip'] += $net_share;
                                if ($tip_in_month) {
                                    $roster[$ucid]['total_delivery'] += $tip_share;
                                }
                            }
                        } elseif (in_array($up->status, ['wip', 'nra', 'revision', 'delivery_submitted'], true)) {
                            $roster[$ucid]['total_wip'] += $net_share;
                            if ($tip_in_month) {
                                $roster[$ucid]['total_delivery'] += $tip_share;
                            }
                        } elseif ($up->status === 'cancelled') {
                            $roster[$ucid]['total_cancel']++;
                            if ($tip_in_month) {
                                $roster[$ucid]['total_delivery'] += $tip_share;
                            }
                        }
                    }
                }
            }

        } elseif ($period === 'quarterly') {
            if (empty($period_key)) {
                $period_key = 'Q4_2026';
            }
            if (preg_match('/^Q([1-4])(?:[-_](\d{4}))?$/i', $period_key, $q_matches)) {
                $quarter_num = (int)$q_matches[1];
                $year = !empty($q_matches[2]) ? $q_matches[2] : current_time('Y');
                $quarter_months_map = [
                    1 => ['january', 'february', 'march'],
                    2 => ['april', 'may', 'june'],
                    3 => ['july', 'august', 'september'],
                    4 => ['october', 'november', 'december']
                ];
                $month_names = $quarter_months_map[$quarter_num] ?? [];
                $period_label = "Q{$quarter_num} {$year}";
                $quarter_period = ucfirst($month_names[0]) . ' - ' . ucfirst(end($month_names)) . " {$year}";

                $search_months = [];
                foreach ($month_names as $mname) {
                    $search_months[] = strtolower($mname) . '_' . $year;
                    $search_months[] = ucfirst(strtolower($mname)) . '_' . $year;
                }
                $in_clause = implode(',', array_fill(0, count($search_months), '%s'));

                $archived_q = $wpdb->get_results($wpdb->prepare(
                    "SELECT 
                        member_custom_id,
                        SUM(total_delivery) as total_delivery,
                        SUM(total_completed) as total_completed,
                        SUM(delivered_without_extension) as delivered_without_extension,
                        SUM(stars_5) as stars_5,
                        SUM(stars_4) as stars_4,
                        SUM(stars_3) as stars_3,
                        SUM(stars_2) as stars_2,
                        SUM(stars_1) as stars_1,
                        SUM(total_cancel) as total_cancel,
                        SUM(total_wip) as total_wip
                     FROM $archive_table
                     WHERE reporting_month IN ($in_clause)
                     GROUP BY member_custom_id",
                    $search_months
                ));

                foreach ($archived_q as $aq) {
                    $cid = $aq->member_custom_id;
                    if (isset($roster[$cid])) {
                        $roster[$cid]['total_delivery'] += (float)$aq->total_delivery;
                        $roster[$cid]['total_completed'] += (int)$aq->total_completed;
                        $roster[$cid]['delivered_without_extension'] += (int)$aq->delivered_without_extension;
                        $roster[$cid]['stars_5'] += (int)$aq->stars_5;
                        $roster[$cid]['stars_4'] += (int)$aq->stars_4;
                        $roster[$cid]['stars_3'] += (int)$aq->stars_3;
                        $roster[$cid]['stars_2'] += (int)$aq->stars_2;
                        $roster[$cid]['stars_1'] += (int)$aq->stars_1;
                        $roster[$cid]['total_cancel'] += (int)$aq->total_cancel;
                    }
                }

                // If Q4 2026, also add live project deliveries
                if ($quarter_num === 4 && $year === '2026') {
                    $q_start = '2026-10-01 00:00:00';
                    $q_end = '2026-12-31 23:59:59';
                    $live_projects = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM $p_table 
                         WHERE (
                             (status IN ('delivery_accepted', 'completed') AND COALESCE(DATE(delivered_date), delivery_date) BETWEEN %s AND %s)
                             OR (status = 'cancelled' AND delivery_date BETWEEN %s AND %s)
                         )",
                        $q_start, $q_end, $q_start, $q_end
                    ));

                    foreach ($live_projects as $up) {
                        $pid = (int)$up->id;
                        $raw_ids = !empty($up->assigned_user_ids) ? explode(',', $up->assigned_user_ids) : [(int)$up->assigned_user_id];
                        $assignee_ids = array_unique(array_filter(array_map('intval', $raw_ids)));
                        $cnt = max(1, count($assignee_ids));

                        foreach ($assignee_ids as $aid) {
                            $split_pct = $wpdb->get_var($wpdb->prepare(
                                "SELECT split_percentage FROM $assignees_table WHERE project_id = %d AND user_id = %d",
                                $pid, $aid
                            ));
                            $user_split = ($split_pct !== null && (float)$split_pct > 0) ? ((float)$split_pct / 100.0) : (1.0 / $cnt);
                            $ucid = get_user_meta($aid, 'ftm_custom_id', true) ?: (string)$aid;
                            if (!isset($roster[$ucid])) continue;

                            if (in_array($up->status, ['delivery_accepted', 'completed'], true)) {
                                $roster[$ucid]['total_delivery'] += (((float)$up->net_delivery_value + FTM_Financial::tip_net($up->tip_amount)) * $user_split);
                                $roster[$ucid]['total_completed']++;
                                if ((int)($up->extension_count ?? 0) === 0) {
                                    $roster[$ucid]['delivered_without_extension']++;
                                }
                                if ($up->client_reviewed === 'yes') {
                                    $rv = (float)$up->star_rating; $r = (int)round($rv);
                                    if ($r >= 1 && $r <= 5) {
                                        $roster[$ucid]['stars_' . $r]++; $roster[$ucid]['stars_adj'] += ($rv - $r);
                                    }
                                }
                            } elseif ($up->status === 'cancelled') {
                                $roster[$ucid]['total_cancel']++;
                            }
                        }
                    }
                }
            }

        } elseif ($period === 'all_time') {
            $period_label = 'All-Time Leaderboard';

            // 1. Sum all 9 historical months from archive
            $all_archive = $wpdb->get_results(
                "SELECT 
                    member_custom_id,
                    SUM(total_delivery) as total_delivery,
                    SUM(total_completed) as total_completed,
                    SUM(delivered_without_extension) as delivered_without_extension,
                    SUM(stars_5) as stars_5,
                    SUM(stars_4) as stars_4,
                    SUM(stars_3) as stars_3,
                    SUM(stars_2) as stars_2,
                    SUM(stars_1) as stars_1,
                    SUM(total_cancel) as total_cancel
                 FROM $archive_table
                 GROUP BY member_custom_id"
            );

            foreach ($all_archive as $aa) {
                $cid = $aa->member_custom_id;
                if (isset($roster[$cid])) {
                    $roster[$cid]['total_delivery'] += (float)$aa->total_delivery;
                    $roster[$cid]['total_completed'] += (int)$aa->total_completed;
                    $roster[$cid]['delivered_without_extension'] += (int)$aa->delivered_without_extension;
                    $roster[$cid]['stars_5'] += (int)$aa->stars_5;
                    $roster[$cid]['stars_4'] += (int)$aa->stars_4;
                    $roster[$cid]['stars_3'] += (int)$aa->stars_3;
                    $roster[$cid]['stars_2'] += (int)$aa->stars_2;
                    $roster[$cid]['stars_1'] += (int)$aa->stars_1;
                    $roster[$cid]['total_cancel'] += (int)$aa->total_cancel;
                }
            }

            // 2. Add unarchived live projects delivered on or after Oct 1, 2026 (boundary safety)
            $live_projects = $wpdb->get_results(
                "SELECT * FROM $p_table 
                 WHERE (
                     (status IN ('delivery_accepted', 'completed') AND COALESCE(DATE(delivered_date), delivery_date) >= '2026-10-01')
                     OR (status = 'cancelled' AND delivery_date >= '2026-10-01')
                 )"
            );

            foreach ($live_projects as $up) {
                $pid = (int)$up->id;
                $raw_ids = !empty($up->assigned_user_ids) ? explode(',', $up->assigned_user_ids) : [(int)$up->assigned_user_id];
                $assignee_ids = array_unique(array_filter(array_map('intval', $raw_ids)));
                $cnt = max(1, count($assignee_ids));

                foreach ($assignee_ids as $aid) {
                    $split_pct = $wpdb->get_var($wpdb->prepare(
                        "SELECT split_percentage FROM $assignees_table WHERE project_id = %d AND user_id = %d",
                        $pid, $aid
                    ));
                    $user_split = ($split_pct !== null && (float)$split_pct > 0) ? ((float)$split_pct / 100.0) : (1.0 / $cnt);
                    $ucid = get_user_meta($aid, 'ftm_custom_id', true) ?: (string)$aid;
                    if (!isset($roster[$ucid])) continue;

                    if (in_array($up->status, ['delivery_accepted', 'completed'], true)) {
                        $roster[$ucid]['total_delivery'] += (((float)$up->net_delivery_value + FTM_Financial::tip_net($up->tip_amount)) * $user_split);
                        $roster[$ucid]['total_completed']++;
                        if ((int)($up->extension_count ?? 0) === 0) {
                            $roster[$ucid]['delivered_without_extension']++;
                        }
                        if ($up->client_reviewed === 'yes') {
                            $rv = (float)$up->star_rating; $r = (int)round($rv);
                            if ($r >= 1 && $r <= 5) {
                                $roster[$ucid]['stars_' . $r]++; $roster[$ucid]['stars_adj'] += ($rv - $r);
                            }
                        }
                    } elseif ($up->status === 'cancelled') {
                        $roster[$ucid]['total_cancel']++;
                    }
                }
            }
        }

        // Pre-pass: Find maximum delivered revenue in the active cohort (filtered by team if applicable)
        $max_cohort_revenue = 0.0;
        foreach ($roster as $cid => $m) {
            if ($team !== 'all' && strcasecmp($m['team_name'], $team) !== 0) {
                continue;
            }
            $m_rev = (float)$m['total_delivery'];
            if ($m_rev > $max_cohort_revenue) {
                $max_cohort_revenue = $m_rev;
            }
        }

        // 3. Score and format each specialist
        $ranked_list = [];
        foreach ($roster as $cid => $m) {
            // Apply team filter
            if ($team !== 'all' && strcasecmp($m['team_name'], $team) !== 0) {
                continue;
            }

            $completed = (int)$m['total_completed'];
            $delivered_rev = round((float)$m['total_delivery'], 2);
            $ontime_cnt = (int)$m['delivered_without_extension'];
            // Guardrail: on-time count can never exceed completed orders
            if ($completed > 0 && $ontime_cnt > $completed) {
                $ontime_cnt = $completed;
            }

            $cancel_cnt = (int)$m['total_cancel'];
            $s5 = (int)$m['stars_5'];
            $s4 = (int)$m['stars_4'];
            $s3 = (int)$m['stars_3'];
            $s2 = (int)$m['stars_2'];
            $s1 = (int)$m['stars_1'];
            $review_count = $s5 + $s4 + $s3 + $s2 + $s1;

            $ontime_rate = ($completed > 0) ? round(($ontime_cnt / $completed) * 100, 1) : 0.0;
            $total_orders = $completed + $cancel_cnt;
            $cancel_rate = ($total_orders > 0) ? round(($cancel_cnt / $total_orders) * 100, 1) : 0.0;
            $avg_rating = ($review_count > 0) ? round((5*$s5 + 4*$s4 + 3*$s3 + 2*$s2 + 1*$s1 + (float)($m['stars_adj'] ?? 0)) / $review_count, 1) : 0.0;

            if ($completed === 0) {
                $score = 0;
                $eligibility = 'insufficient_data';
                $score_breakdown = [
                    'p1_revenue' => 0.0,
                    'p2_satisfaction' => 0.0,
                    'p3_ontime' => 0.0,
                    'p4_retention' => 0.0,
                    'note' => 'Standby / Insufficient Data (0 completed orders)'
                ];
            } else {
                $eligibility = 'ranked';

                // Pillar 1: Delivered Revenue Volume (40 pts) - Top Company Priority
                if ($max_cohort_revenue > 0) {
                    $p1_ratio = min(1.0, max(0.0, $delivered_rev / $max_cohort_revenue));
                    $p1 = round($p1_ratio * 40.0, 2);
                } else {
                    $p1 = 0.0;
                }

                // Pillar 2: Client Satisfaction Quality & 5-Star Reviews (30 pts) - Second Company Priority
                // Rating quality: up to 20 pts (neutral 12.0 baseline for unreviewed orders)
                if ($review_count > 0) {
                    $p2_rating_ratio = min(1.0, max(0.0, $avg_rating / 5.0));
                    $p2_rating = round($p2_rating_ratio * 20.0, 2);
                } else {
                    $p2_rating = 12.0;
                }
                // 5-Star volume bonus: up to 10 pts (min(10.0, 5-Star Count * 2.0))
                $p2_s5_bonus = min(10.0, (float)$s5 * 2.0);
                $p2 = round($p2_rating + $p2_s5_bonus, 2);

                // Pillar 3: On-Time Delivery Velocity (15 pts)
                $p3_ratio = min(1.0, max(0.0, $ontime_cnt / $completed));
                $p3 = round($p3_ratio * 15.0, 2);

                // Pillar 4: Order Retention & Reliability (15 pts)
                $p4_ratio = max(0.0, 1.0 - ($cancel_cnt / max(1, $total_orders)));
                $p4 = round($p4_ratio * 15.0, 2);

                $score = (int)round($p1 + $p2 + $p3 + $p4);
                $score = max(0, min(100, $score));

                $score_breakdown = [
                    'p1_revenue' => $p1,
                    'p2_satisfaction' => $p2,
                    'p3_ontime' => $p3,
                    'p4_retention' => $p4,
                    'neutral_baseline_applied' => ($review_count === 0)
                ];
            }

            $ranked_list[] = [
                'user_id' => $m['user_id'],
                'id' => $m['user_id'],
                'member_custom_id' => $m['member_custom_id'],
                'custom_id' => $m['member_custom_id'],
                'badge_id' => '#' . ltrim($m['member_custom_id'], '#'),
                'member_name' => $m['member_name'],
                'name' => $m['member_name'],
                'display_name' => $m['member_name'],
                'call_sign' => $m['call_sign'],
                'team_name' => $m['team_name'],
                'team' => $m['team_name'],
                'role_slug' => $m['role_slug'],
                'role_label' => $m['role_label'],
                'avatar_url' => $m['avatar_url'],
                'avatar' => $m['avatar_url'],
                'net_delivered_revenue' => $delivered_rev,
                'total_delivery' => $delivered_rev,
                'net_revenue' => $delivered_rev,
                'net' => $delivered_rev,
                'gross_revenue' => round($delivered_rev / 0.8, 2),
                'total_delivery_gross' => round($delivered_rev / 0.8, 2),
                'completed_orders' => $completed,
                'total_completed' => $completed,
                'deliveries' => $completed,
                'on_time_count' => $ontime_cnt,
                'on_time_rate' => $ontime_rate,
                'client_rating' => $avg_rating,
                'rating' => $avg_rating,
                'review_count' => $review_count,
                'rating_breakdown' => [
                    '5' => $s5, '4' => $s4, '3' => $s3, '2' => $s2, '1' => $s1
                ],
                'cancellation_count' => $cancel_cnt,
                'cancellation_rate' => $cancel_rate,
                'performance_score' => $score,
                'score' => $score,
                'points' => $score,
                'eligibility_status' => $eligibility,
                'score_breakdown' => $score_breakdown,
                'active_wip' => round((float)$m['total_wip'], 2)
            ];
        }

        // 4. Sort Rankings - default to top delivery amount
        usort($ranked_list, function($a, $b) use ($sort) {
            if ($sort === 'revenue' || $sort === 'delivery') {
                if ($b['net_delivered_revenue'] != $a['net_delivered_revenue']) {
                    return $b['net_delivered_revenue'] <=> $a['net_delivered_revenue'];
                }
                if ($b['completed_orders'] !== $a['completed_orders']) {
                    return $b['completed_orders'] <=> $a['completed_orders'];
                }
                return $b['performance_score'] <=> $a['performance_score'];
            } elseif ($sort === 'completed') {
                if ($b['completed_orders'] !== $a['completed_orders']) {
                    return $b['completed_orders'] <=> $a['completed_orders'];
                }
                return $b['net_delivered_revenue'] <=> $a['net_delivered_revenue'];
            } elseif ($sort === 'ontime') {
                if ($b['on_time_rate'] != $a['on_time_rate']) {
                    return $b['on_time_rate'] <=> $a['on_time_rate'];
                }
                return $b['completed_orders'] <=> $a['completed_orders'];
            } elseif ($sort === 'rating') {
                if ($b['client_rating'] != $a['client_rating']) {
                    return $b['client_rating'] <=> $a['client_rating'];
                }
                return $b['review_count'] <=> $a['review_count'];
            } elseif ($sort === 'cancel') {
                if ($a['cancellation_rate'] != $b['cancellation_rate']) {
                    return $a['cancellation_rate'] <=> $b['cancellation_rate'];
                }
                return $b['completed_orders'] <=> $a['completed_orders'];
            }

            // Default 'score': Top priority = Score, then Delivered Revenue, then 5-Star Reviews, then Completed Orders
            if ($b['performance_score'] !== $a['performance_score']) {
                return $b['performance_score'] <=> $a['performance_score'];
            }
            if ($b['net_delivered_revenue'] != $a['net_delivered_revenue']) {
                return $b['net_delivered_revenue'] <=> $a['net_delivered_revenue'];
            }
            $b5 = (int)($b['rating_breakdown'][5] ?? 0);
            $a5 = (int)($a['rating_breakdown'][5] ?? 0);
            if ($b5 !== $a5) {
                return $b5 <=> $a5;
            }
            if ($b['completed_orders'] !== $a['completed_orders']) {
                return $b['completed_orders'] <=> $a['completed_orders'];
            }
            return strcasecmp($a['member_name'], $b['member_name']);
        });

        // 5. Assign Ranks and extract Podium
        // Rule: Only specialists with actual completed deliveries (completed_orders > 0 and performance_score > 0)
        // are eligible for numeric ranks and podium placement.
        // Standby members with 0 completed orders are assigned rank '-' to protect merit integrity.
        $podium = [];
        $active_rank = 1;
        foreach ($ranked_list as &$item) {
            $is_active_performer = ($sort === 'revenue' || $sort === 'delivery')
                ? ($item['net_delivered_revenue'] > 0 || $item['completed_orders'] > 0)
                : ($item['completed_orders'] > 0 && $item['performance_score'] > 0);
            if ($is_active_performer) {
                $item['rank'] = $active_rank;
                $item['is_top_performer'] = ($active_rank === 1);
                if (count($podium) < 3) {
                    $podium[] = $item;
                }
                $active_rank++;
            } else {
                $item['rank'] = '-';
                $item['is_top_performer'] = false;
            }
        }
        unset($item);

        return rest_ensure_response([
            'period' => $period,
            'period_key' => $period_key,
            'period_label' => $period_label,
            'quarter_period' => $quarter_period,
            'selected_team' => $team,
            'sort_by' => $sort,
            'team_counts' => $team_counts,
            'available_periods' => $available_periods,
            'podium' => $podium,
            'rankings' => $ranked_list,
            'methodology' => [
                'scale' => '0 to 100 points',
                'pillars' => [
                    ['name' => 'Delivered Revenue Volume', 'weight' => '40%', 'max_pts' => 40, 'formula' => '(Member Delivered Revenue / Max Cohort Revenue) * 40'],
                    ['name' => 'Client Satisfaction & 5-Star Reviews', 'weight' => '30%', 'max_pts' => 30, 'formula' => '(Rating / 5.0) * 20 + min(10, 5-Star Count * 2)'],
                    ['name' => 'On-Time Delivery Velocity', 'weight' => '15%', 'max_pts' => 15, 'formula' => 'min(1.0, On-Time / Completed) * 15'],
                    ['name' => 'Order Retention & Reliability', 'weight' => '15%', 'max_pts' => 15, 'formula' => '(1 - Cancellation Rate) * 15']
                ],
                'neutral_rating_policy' => 'Specialists with unreviewed orders receive a neutral 12.0 baseline for rating quality to protect them from penalty.',
                'minimum_orders_rule' => 'Members with 0 completed deliveries in the period are classified as Standby / Insufficient Data (Score 0).'
            ]
        ]);
    }
}