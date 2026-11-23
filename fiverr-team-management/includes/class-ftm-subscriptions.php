<?php
/**
 * Monthly subscription orders (Fiverr-style): an active subscription project creates a fresh order every month.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Subscriptions {

    const CRON_HOOK = 'ftm_subscription_renewals';

    public static function init() {
        add_action(self::CRON_HOOK, [__CLASS__, 'run_renewals']);
        add_action('init', function () {
            if (!wp_next_scheduled(self::CRON_HOOK)) {
                wp_schedule_event(time() + 300, 'hourly', self::CRON_HOOK);
            }
        });
    }

    /**
     * Same day-of-month next month, clamped to the month's last day (31 Jan -> 28 Feb -> 31 Mar).
     */
    public static function add_month($date, $anchor_day) {
        // Step from the 1st of the month: '+1 month' on the 31st would overflow into the month after next
        $first = date('Y-m-01', strtotime(substr($date, 0, 7) . '-01 +1 month'));
        $last_day = (int)date('t', strtotime($first));
        $day = max(1, min((int)$anchor_day ?: (int)date('j', strtotime($date)), $last_day));
        return date('Y-m-', strtotime($first)) . str_pad((string)$day, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Creates every renewal that is due. Safe to run repeatedly: a renewal is created once per parent per due date.
     *
     * @return array [parent_id => [created_child_ids]]
     */
    public static function run_renewals($today = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_projects';
        $today = $today ?: current_time('Y-m-d');
        $created = [];

        $parents = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE subscription_status = 'active' AND subscription_next_date IS NOT NULL AND subscription_next_date <= %s",
            $today
        ));

        foreach ($parents as $parent) {
            $guard = 0;
            $next = $parent->subscription_next_date;
            while ($next && $next <= $today && $guard < 6) {
                $guard++;
                $child_id = self::create_renewal($parent, $next);
                if ($child_id) {
                    $created[(int)$parent->id][] = $child_id;
                }
                $next = self::add_month($next, (int)$parent->subscription_day);
            }
            $wpdb->update($table, ['subscription_next_date' => $next], ['id' => $parent->id]);
        }
        return $created;
    }

    private static function create_renewal($parent, $renewal_date) {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_projects';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE subscription_parent_id = %d AND DATE(created_at) = %s LIMIT 1",
            $parent->id, $renewal_date
        ));
        if ($exists) {
            return 0;
        }

        $root_order = preg_replace('/-R\d{6}$/', '', (string)$parent->order_id);
        $term = max(1, (int)$parent->subscription_term_days ?: 7);
        $created_at = $renewal_date . ' 09:00:00';

        $ok = $wpdb->insert($table, [
            'team_name' => $parent->team_name,
            'fiverr_profile_id' => (int)$parent->fiverr_profile_id,
            'profile_name' => $parent->profile_name,
            'client_name' => $parent->client_name,
            'order_id' => $root_order . '-R' . date('Ym', strtotime($renewal_date)),
            'delivery_date' => date('Y-m-d', strtotime($renewal_date . " +$term days")),
            'assigned_user_id' => (int)$parent->assigned_user_id,
            'assigned_user_name' => $parent->assigned_user_name,
            'assigned_user_ids' => $parent->assigned_user_ids,
            'status' => 'wip',
            'project_notes' => 'Auto-created monthly subscription order (renewal of ' . $parent->order_id . ').',
            'created_by' => (int)$parent->created_by,
            'created_at' => $created_at,
            'updated_at' => current_time('mysql'),
            'client_reviewed' => 'no',
            'star_rating' => 0,
            'gross_order_value' => $parent->gross_order_value,
            'fiverr_fee_percent' => $parent->fiverr_fee_percent,
            'fiverr_fee_amount' => $parent->fiverr_fee_amount,
            'net_delivery_value' => $parent->net_delivery_value,
            'order_total_value' => $parent->order_total_value,
            'order_type' => 'subscription',
            'tip_amount' => 0,
            'subscription_parent_id' => (int)$parent->id,
            'progress_percent' => 0,
            'delivered_without_extension' => 0,
        ]);
        if (!$ok) {
            return 0;
        }
        $child_id = (int)$wpdb->insert_id;

        $assignees = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, assignment_role, split_percentage FROM {$wpdb->prefix}ftm_project_assignees WHERE project_id = %d",
            $parent->id
        ));
        foreach ($assignees as $a) {
            $wpdb->insert($wpdb->prefix . 'ftm_project_assignees', [
                'project_id' => $child_id,
                'user_id' => (int)$a->user_id,
                'assignment_role' => $a->assignment_role,
                'split_percentage' => $a->split_percentage
            ]);
        }
        $wpdb->insert($wpdb->prefix . 'ftm_project_history', [
            'project_id' => $child_id,
            'user_id' => 0,
            'user_name' => 'System',
            'old_status' => '',
            'new_status' => 'wip',
            'note' => 'Monthly subscription renewed automatically (' . date('F Y', strtotime($renewal_date)) . ').',
            'created_at' => $created_at
        ]);
        return $child_id;
    }
}
