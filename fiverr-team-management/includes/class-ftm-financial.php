<?php
/**
 * FTM Precise Financial & Attribution Engine
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Financial {

    const DEFAULT_FIVERR_FEE = 20.00;

    /** Tips are entered as the client paid them; the marketplace keeps its fee on tips too. */
    public static function tip_net($amount) {
        return round((float)$amount * (1 - self::DEFAULT_FIVERR_FEE / 100), 2);
    }

    /**
     * Calculate financial breakdown for an order
     */
    public static function calculate_breakdown($gross_value, $fee_percent = null) {
        $gross = round((float)$gross_value, 2);
        if ($fee_percent === null || $fee_percent < 0) {
            $fee_percent = self::DEFAULT_FIVERR_FEE;
        } else {
            $fee_percent = (float)$fee_percent;
        }

        $fee_amount = round(($gross * ($fee_percent / 100)), 2);
        $net_value = round(($gross - $fee_amount), 2);

        return [
            'gross_order_value' => $gross,
            'fiverr_fee_percent' => $fee_percent,
            'fiverr_fee_amount' => $fee_amount,
            'net_delivery_value' => $net_value
        ];
    }

    /**
     * Compute agency-wide or team financial totals with strict non-duplication
     */
    public static function compute_period_totals($team_name = 'all', $start_date = null, $end_date = null, $user_id = 0, $extra_filters = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_projects';

        $where = ["1=1"];
        $params = [];

        if ($team_name !== 'all') {
            $where[] = "team_name = %s";
            $params[] = $team_name;
        }

        if ($user_id > 0) {
            $where[] = "(assigned_user_id = %d OR FIND_IN_SET(%d, assigned_user_ids))";
            $params[] = $user_id;
            $params[] = $user_id;
        }

        if (!empty($extra_filters['client']) && $extra_filters['client'] !== 'all') {
            $where[] = "client_name = %s";
            $params[] = $extra_filters['client'];
        }

        if (!empty($extra_filters['profile_id']) && (int)$extra_filters['profile_id'] > 0) {
            $where[] = "profile_id = %d";
            $params[] = (int)$extra_filters['profile_id'];
        }
        // A member's own totals count only their share of a shared project (equal split when no share was recorded)
        $share_join = '';
        $share = '1';
        if ($user_id > 0) {
            $share_join = $wpdb->prepare(" LEFT JOIN {$wpdb->prefix}ftm_project_assignees fa ON fa.project_id = $table.id AND fa.user_id = %d", $user_id);
            $share = "(COALESCE(fa.split_percentage, 100 / (1 + LENGTH(COALESCE($table.assigned_user_ids, '')) - LENGTH(REPLACE(COALESCE($table.assigned_user_ids, ''), ',', '')))) / 100)";
        }

        $date_filter = "";
        if ($start_date && $end_date) {
            $date_filter = $wpdb->prepare(" AND (
                (status IN ('delivery_accepted', 'completed') AND COALESCE(DATE(delivered_date), delivery_date) BETWEEN %s AND %s)
                OR (status = 'cancelled' AND delivery_date BETWEEN %s AND %s)
                OR (status IN ('wip', 'nra', 'revision', 'delivery_submitted') AND DATE($table.created_at) <= %s)
                OR (status IN ('delivery_accepted', 'completed') AND COALESCE(DATE(delivered_date), delivery_date) > %s AND DATE($table.created_at) <= %s)
            )", $start_date, $end_date, $start_date, $end_date, $end_date, $end_date, $end_date);
        }

        $where_sql = implode(' AND ', $where);
        // An order delivered after the period ends was still work-in-progress during it.
        $status_expr = ($start_date && $end_date)
            ? $wpdb->prepare("CASE WHEN status IN ('delivery_accepted', 'completed') AND COALESCE(DATE(delivered_date), delivery_date) > %s THEN 'wip' ELSE status END", $end_date)
            : 'status';
        $query = "SELECT 
            $status_expr AS status,
            COUNT(DISTINCT $table.id) as order_count,
            SUM($table.gross_order_value * $share) as sum_gross,
            SUM($table.net_delivery_value * $share) as sum_net,
            SUM($table.tip_amount * $share) as sum_tips,
            SUM($table.cancelled_value * $share) as sum_cancelled,
            SUM($table.partial_payout_value * $share) as sum_partial
            FROM $table $share_join 
            WHERE $where_sql $date_filter
            GROUP BY $status_expr";

        $results = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, $params)) : $wpdb->get_results($query);

        $summary = [
            'total_delivery_net' => 0.00,
            'total_submitted_net' => 0.00,
            'total_submitted_orders' => 0,
            'total_wip_net' => 0.00,
            'total_revision_net' => 0.00,
            'total_tips_net' => 0.00,
            'delivered_tips' => 0.00,
            'total_cancelled_net' => 0.00,
            'total_partial_net' => 0.00,
            'total_completed_orders' => 0,
            'total_cancelled_orders' => 0,
            'total_work_assigned' => 0.00
        ];

        $delivered_order_net = 0.00;
        $delivered_tips = 0.00;
        foreach ($results as $row) {
            $status = $row->status;
            $count = (int)$row->order_count;
            $net = (float)$row->sum_net;
            $tips = self::tip_net($row->sum_tips);
            $cancelled = (float)$row->sum_cancelled;
            $partial = (float)$row->sum_partial;

            if (in_array($status, ['delivery_accepted', 'completed'], true)) {
                $delivered_order_net += $net;
                $delivered_tips += $tips;
                $summary['total_completed_orders'] += $count;
            } elseif ($status === 'revision') {
                $summary['total_revision_net'] += $net;
            } elseif ($status === 'delivery_submitted') {
                $summary['total_submitted_net'] += $net;
                $summary['total_submitted_orders'] += $count;
            } elseif (in_array($status, ['wip', 'nra'], true)) {
                $summary['total_wip_net'] += $net;
            } elseif ($status === 'cancelled') {
                $summary['total_cancelled_net'] += ($cancelled > 0 ? $cancelled : $net);
                $summary['total_cancelled_orders'] += $count;
            }

            if ($partial > 0) {
                $summary['total_partial_net'] += $partial;
            }
        }

        // Period-specific Tip Attribution (Cross-Month Decoupled)
        // Tips belong strictly to the month of tip_date (falling back to delivered_date / delivery_date),
        // matching Monthly Sheet & Revenue and Performance Leaderboard to the exact cent.
        if ($start_date && $end_date) {
            $tip_query = "SELECT 
                COALESCE(SUM($table.tip_amount * (1 - COALESCE($table.fiverr_fee_percent, 20.0) / 100) * $share), 0) as period_tips_net
                FROM $table $share_join 
                WHERE $where_sql 
                AND $table.tip_amount > 0 
                AND $table.status IN ('delivery_accepted', 'completed', 'delivered', 'delivery_submitted')
                AND DATE(COALESCE($table.tip_date, $table.delivered_date, $table.delivery_date)) BETWEEN %s AND %s";
            $tip_params = array_merge($params, [$start_date, $end_date]);
            $summary['total_tips_net'] = round((float)$wpdb->get_var($wpdb->prepare($tip_query, $tip_params)), 2);
            $summary['total_delivery_net'] = round($delivered_order_net + $summary['total_tips_net'], 2);
        } else {
            $summary['total_tips_net'] = round($delivered_tips, 2);
            $summary['total_delivery_net'] = round($delivered_order_net + $delivered_tips, 2);
        }
        $summary['delivered_tips'] = round($delivered_tips, 2);

        // Total Work Assigned = Delivered (incl. period tips) + Active WIP + In Revision + Cancelled + Partial
        $summary['total_work_assigned'] = round(
            $summary['total_delivery_net'] + 
            $summary['total_wip_net'] + 
            $summary['total_revision_net'] + 
            $summary['total_cancelled_net'] + 
            $summary['total_partial_net'], 
            2
        );

        // Format to exact currency decimals
        foreach ($summary as $key => $val) {
            if (is_float($val)) {
                $summary[$key] = round($val, 2);
            }
        }

        return $summary;
    }
}

