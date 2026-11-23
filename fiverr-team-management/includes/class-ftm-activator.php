<?php
/**
 * FTM Activator & Database Schema Migration Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Activator {

    public static function activate() {
        self::migrate_database();
    }

    public static function migrate_database() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // 1. Projects Table (Extended with Financial & Extension Tracking)
        $table_projects = $wpdb->prefix . 'ftm_projects';
        $sql_projects = "CREATE TABLE $table_projects (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            team_name VARCHAR(100) NOT NULL DEFAULT 'WpCraft',
            fiverr_profile_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            profile_name VARCHAR(150) NOT NULL DEFAULT '',
            client_name VARCHAR(150) NOT NULL DEFAULT '',
            order_id VARCHAR(100) NOT NULL DEFAULT '',
            gross_order_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            fiverr_fee_percent DECIMAL(5,2) NOT NULL DEFAULT 20.00,
            fiverr_fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            net_delivery_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            tip_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            cancelled_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            partial_payout_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            delivery_date DATE NULL,
            delivered_date DATETIME NULL,
            extension_count INT(11) NOT NULL DEFAULT 0,
            extension_days INT(11) NOT NULL DEFAULT 0,
            extension_reason VARCHAR(255) NULL,
            delivered_without_extension TINYINT(1) NULL DEFAULT NULL,
            is_ontime TINYINT(1) NOT NULL DEFAULT 0,
            assigned_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            assigned_user_ids TEXT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'nra',
            project_notes TEXT NULL,
            client_reviewed VARCHAR(10) NOT NULL DEFAULT 'no',
            star_rating DECIMAL(2,1) NOT NULL DEFAULT 0.0,
            review_feedback TEXT NULL,
            priority TINYINT(2) NOT NULL DEFAULT 2,
            progress_percent INT(3) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY team_status (team_name, status),
            KEY delivery_date (delivery_date),
            KEY order_id (order_id)
        ) $charset_collate;";
        dbDelta($sql_projects);

        // 2. Project History Table
        $table_history = $wpdb->prefix . 'ftm_project_history';
        $sql_history = "CREATE TABLE $table_history (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            user_name VARCHAR(150) NOT NULL DEFAULT '',
            old_status VARCHAR(50) NOT NULL DEFAULT '',
            new_status VARCHAR(50) NOT NULL DEFAULT '',
            note TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY project_id (project_id)
        ) $charset_collate;";
        dbDelta($sql_history);

        // 3. Project Notes Table
        $table_notes = $wpdb->prefix . 'ftm_project_notes';
        $sql_notes = "CREATE TABLE $table_notes (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            note TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY project_id (project_id)
        ) $charset_collate;";
        dbDelta($sql_notes);

        // 4. Fiverr Profiles Table
        $table_profiles = $wpdb->prefix . 'ftm_profiles';
        $sql_profiles = "CREATE TABLE $table_profiles (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(150) NOT NULL,
            url VARCHAR(255) NOT NULL DEFAULT '',
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_profiles);

        // 5. Multi-Assignee Financial Allocation Table
        $table_assignees = $wpdb->prefix . 'ftm_project_assignees';
        $sql_assignees = "CREATE TABLE $table_assignees (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            assignment_role VARCHAR(50) NOT NULL DEFAULT 'contributor',
            split_percentage DECIMAL(5,2) NOT NULL DEFAULT 100.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY project_user (project_id, user_id)
        ) $charset_collate;";
        dbDelta($sql_assignees);

        // 6. Daily Reports Master Table
        $table_daily_reports = $wpdb->prefix . 'ftm_daily_reports';
        $sql_daily_reports = "CREATE TABLE $table_daily_reports (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            team_name VARCHAR(100) NOT NULL DEFAULT 'WpCraft',
            report_type VARCHAR(50) NOT NULL DEFAULT 'work',
            leave_start_date DATE NULL,
            leave_end_date DATE NULL,
            leave_type VARCHAR(50) NULL,
            leave_reason TEXT NULL,
            report_date DATE NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'submitted',
            reviewed_by BIGINT(20) UNSIGNED NULL,
            reviewed_at DATETIME NULL,
            leader_feedback TEXT NULL,
            forwarded_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_date (user_id, report_date),
            KEY team_date (team_name, report_date),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_daily_reports);

        // 7. Daily Report Line Items Table
        $table_daily_items = $wpdb->prefix . 'ftm_daily_report_items';
        $sql_daily_items = "CREATE TABLE $table_daily_items (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            project_id BIGINT(20) UNSIGNED NOT NULL,
            order_id VARCHAR(100) NOT NULL DEFAULT '',
            client_name VARCHAR(150) NOT NULL DEFAULT '',
            tasks_completed TEXT NOT NULL,
            work_in_progress TEXT NOT NULL,
            next_day_plan TEXT NOT NULL,
            has_blocker TINYINT(1) NOT NULL DEFAULT 0,
            blocker_description TEXT NULL,
            hours_spent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            progress_percent INT(3) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY report_id (report_id),
            KEY project_id (project_id)
        ) $charset_collate;";
        dbDelta($sql_daily_items);

        // 8. Daily Team Forwarded Reports (Leader -> Super Admin / Manager)
        $table_team_reports = $wpdb->prefix . 'ftm_daily_team_reports';
        $sql_team_reports = "CREATE TABLE $table_team_reports (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            team_name VARCHAR(100) NOT NULL,
            report_date DATE NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'forwarded',
            created_by BIGINT(20) UNSIGNED NOT NULL,
            forwarded_to BIGINT(20) UNSIGNED NULL,
            total_delivered_today DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            active_wip_count INT(11) NOT NULL DEFAULT 0,
            active_wip_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            open_blockers_count INT(11) NOT NULL DEFAULT 0,
            summary_notes TEXT NULL,
            risks_and_blockers TEXT NULL,
            team_availability TEXT NULL,
            forwarded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY team_date (team_name, report_date)
        ) $charset_collate;";
        dbDelta($sql_team_reports);

        // 9. Historical Monthly Snapshots Archive (Google Sheets Importer)
        $table_monthly_archive = $wpdb->prefix . 'ftm_monthly_archive';
        $sql_monthly_archive = "CREATE TABLE $table_monthly_archive (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            reporting_month VARCHAR(20) NOT NULL,
            member_custom_id VARCHAR(50) NOT NULL DEFAULT '',
            member_name VARCHAR(150) NOT NULL DEFAULT '',
            team_name VARCHAR(100) NOT NULL DEFAULT '',
            call_sign VARCHAR(100) NOT NULL DEFAULT '',
            total_delivery DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_wip DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            tips DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            cancelled DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            partial DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_completed INT(11) NOT NULL DEFAULT 0,
            stars_5 INT(11) NOT NULL DEFAULT 0,
            stars_4 INT(11) NOT NULL DEFAULT 0,
            stars_3 INT(11) NOT NULL DEFAULT 0,
            stars_2 INT(11) NOT NULL DEFAULT 0,
            stars_1 INT(11) NOT NULL DEFAULT 0,
            total_cancel INT(11) NOT NULL DEFAULT 0,
            delivered_without_extension INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY month_team (reporting_month, team_name)
        ) $charset_collate;";
        dbDelta($sql_monthly_archive);

        // 10. Agency Broadcast & Notices Table
        $table_notices = $wpdb->prefix . 'ftm_notices';
        $sql_notices = "CREATE TABLE $table_notices (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            notice_type VARCHAR(50) NOT NULL DEFAULT 'announcement',
            target_type VARCHAR(50) NOT NULL DEFAULT 'all',
            target_value VARCHAR(100) NOT NULL DEFAULT 'all',
            is_pinned TINYINT(1) NOT NULL DEFAULT 0,
            created_by BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY target (target_type, target_value),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_notices);

        // 10. Idempotent Backfill: Sync legacy project assignees into ftm_project_assignees
        $unmigrated_projects = $wpdb->get_results(
            "SELECT p.id, p.assigned_user_id, p.assigned_user_ids 
             FROM $table_projects p 
             LEFT JOIN $table_assignees a ON p.id = a.project_id 
             WHERE a.id IS NULL"
        );

        if (!empty($unmigrated_projects)) {
            foreach ($unmigrated_projects as $proj) {
                $assigned_ids = [];
                if (!empty($proj->assigned_user_ids)) {
                    $raw_ids = explode(',', $proj->assigned_user_ids);
                    foreach ($raw_ids as $rid) {
                        $uid = (int)trim($rid);
                        if ($uid > 0 && !in_array($uid, $assigned_ids, true)) {
                            $assigned_ids[] = $uid;
                        }
                    }
                }
                if (empty($assigned_ids) && (int)$proj->assigned_user_id > 0) {
                    $assigned_ids[] = (int)$proj->assigned_user_id;
                }

                if (!empty($assigned_ids)) {
                    $split = round(100.00 / count($assigned_ids), 2);
                    foreach ($assigned_ids as $idx => $uid) {
                        $wpdb->insert($table_assignees, [
                            'project_id' => (int)$proj->id,
                            'user_id' => $uid,
                            'assignment_role' => ($idx === 0) ? 'primary_lead' : 'contributor',
                            'split_percentage' => $split
                        ]);
                    }
                }
            }
        }

        // Ensure extension_days and extension_reason columns exist
        $has_ext_days = $wpdb->get_results("SHOW COLUMNS FROM {$table_projects} LIKE 'extension_days'");
        if (empty($has_ext_days)) {
            $wpdb->query("ALTER TABLE {$table_projects} ADD COLUMN extension_days INT(11) NOT NULL DEFAULT 0 AFTER extension_count");
        }
        $has_ext_reason = $wpdb->get_results("SHOW COLUMNS FROM {$table_projects} LIKE 'extension_reason'");
        if (empty($has_ext_reason)) {
            $wpdb->query("ALTER TABLE {$table_projects} ADD COLUMN extension_reason VARCHAR(255) NULL AFTER extension_days");
        }

        // Which manager ran which team in which months (executive totals follow this)
        FTM_Manager_Scopes::install();

        // Monthly subscriptions: an active subscription project renews itself every month
        foreach ([
            'subscription_status' => "VARCHAR(10) NOT NULL DEFAULT ''",
            'subscription_next_date' => 'DATE NULL',
            'subscription_day' => 'TINYINT(3) UNSIGNED NOT NULL DEFAULT 0',
            'subscription_term_days' => 'INT(11) NOT NULL DEFAULT 7',
            'subscription_parent_id' => 'BIGINT(20) UNSIGNED NOT NULL DEFAULT 0',
        ] as $col => $def) {
            if (empty($wpdb->get_results("SHOW COLUMNS FROM {$table_projects} LIKE '{$col}'"))) {
                $wpdb->query("ALTER TABLE {$table_projects} ADD COLUMN {$col} {$def}");
            }
        }

        // Order type: standard | subscription (monthly recurring) | outside (taken outside the marketplace). Neither of the last two gets a client review.
        $has_order_type = $wpdb->get_results("SHOW COLUMNS FROM {$table_projects} LIKE 'order_type'");
        if (empty($has_order_type)) {
            $wpdb->query("ALTER TABLE {$table_projects} ADD COLUMN order_type VARCHAR(20) NOT NULL DEFAULT 'standard' AFTER order_total_value");
        }

        // Client ratings can be decimal (4.7, 3.3 ...)
        $star_col = $wpdb->get_row("SHOW COLUMNS FROM {$table_projects} LIKE 'star_rating'");
        if ($star_col && stripos((string)$star_col->Type, 'int') !== false) {
            $wpdb->query("ALTER TABLE {$table_projects} MODIFY COLUMN star_rating DECIMAL(2,1) NOT NULL DEFAULT 5.0");
        }

        // Combined orders: total order value + the other parties' shares
        $has_order_total = $wpdb->get_results("SHOW COLUMNS FROM {$table_projects} LIKE 'order_total_value'");
        if (empty($has_order_total)) {
            $wpdb->query("ALTER TABLE {$table_projects} ADD COLUMN order_total_value DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER net_delivery_value");
        }
        $table_splits = $wpdb->prefix . 'ftm_project_splits';
        dbDelta("CREATE TABLE $table_splits (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT(20) UNSIGNED NOT NULL,
            party_label VARCHAR(190) NOT NULL DEFAULT '',
            party_kind VARCHAR(20) NOT NULL DEFAULT 'team',
            skill VARCHAR(60) NOT NULL DEFAULT '',
            share_net DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            note TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY project_id (project_id)
        ) " . $wpdb->get_charset_collate() . ";");

        if (class_exists('FTM_Seeder')) {
            FTM_Seeder::seed_historical_reports();
        }

        // Ensure daily reports table has report_type and leave columns
        $has_rep_type = $wpdb->get_results("SHOW COLUMNS FROM {$table_daily_reports} LIKE 'report_type'");
        if (empty($has_rep_type)) {
            $wpdb->query("ALTER TABLE {$table_daily_reports} ADD COLUMN report_type VARCHAR(50) NOT NULL DEFAULT 'work' AFTER team_name");
            $wpdb->query("ALTER TABLE {$table_daily_reports} ADD COLUMN leave_start_date DATE NULL AFTER report_type");
            $wpdb->query("ALTER TABLE {$table_daily_reports} ADD COLUMN leave_end_date DATE NULL AFTER leave_start_date");
            $wpdb->query("ALTER TABLE {$table_daily_reports} ADD COLUMN leave_type VARCHAR(50) NULL AFTER leave_end_date");
            $wpdb->query("ALTER TABLE {$table_daily_reports} ADD COLUMN leave_reason TEXT NULL AFTER leave_type");
        }

        // Ensure 6:15 PM cutoff and two-stage review columns exist
        $has_late = $wpdb->get_results("SHOW COLUMNS FROM {$table_daily_reports} LIKE 'is_late'");
        if (empty($has_late)) {
            $wpdb->query("ALTER TABLE {$table_daily_reports} ADD COLUMN is_late TINYINT(1) NOT NULL DEFAULT 0");
            $wpdb->query("ALTER TABLE {$table_daily_reports} ADD COLUMN submission_time TIME NULL");
            $wpdb->query("ALTER TABLE {$table_daily_reports} ADD COLUMN leader_reviewed_by BIGINT(20) UNSIGNED NULL");
            $wpdb->query("ALTER TABLE {$table_daily_reports} ADD COLUMN leader_reviewed_at DATETIME NULL");
            $wpdb->query("ALTER TABLE {$table_daily_reports} ADD COLUMN manager_reviewed_by BIGINT(20) UNSIGNED NULL");
            $wpdb->query("ALTER TABLE {$table_daily_reports} ADD COLUMN manager_reviewed_at DATETIME NULL");
            $wpdb->query("ALTER TABLE {$table_daily_reports} ADD COLUMN manager_feedback TEXT NULL");
        }

        if (class_exists('FTM_Tasks_Controller')) {
            FTM_Tasks_Controller::ensure_table();
        }
        if (class_exists('FTM_Leaves_Controller')) {
            FTM_Leaves_Controller::ensure_tables();
        }
        if (class_exists('FTM_Time_Tracker_Controller')) {
            FTM_Time_Tracker_Controller::ensure_tables();
        }

        // 9. Historical reports and database versioning
        update_option('ftm_db_version', FTM_DB_VERSION);
    }
}
