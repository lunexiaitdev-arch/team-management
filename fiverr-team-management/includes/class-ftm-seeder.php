<?php
/**
 * FTM Historical Monthly & Performance Seeder
 * Seeds authentic agency Google Sheet reports for all 2026 months from verified production sheets:
 * Q1: January, February, March 2026
 * Q2: April, May, June 2026
 * Q3: July, August, September 2026
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Seeder {

    public static function run_all() {
        self::seed_historical_reports();
        self::ensure_real_october_projects();
        self::seed_member_profiles_data();
    }

    /**
     * The legacy seed below deletes and re-inserts whole months, so it must run once only:
     * running it on every plugin update wiped every later correction to the archive.
     */
    const ARCHIVE_SEED_VERSION = '2.8.2';

    public static function seed_historical_reports() {
        global $wpdb;
        if (get_option('ftm_archive_seed_version') === self::ARCHIVE_SEED_VERSION) {
            return;
        }
        $table = $wpdb->prefix . 'ftm_monthly_archive';

        $months_data = [
            'January_2026' => [
                ['member_custom_id' => '1038', 'member_name' => 'Naimul Islam', 'team_name' => 'WpTitans', 'call_sign' => 'Naim', 'total_delivery' => 1476.00, 'total_wip' => 256.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 4, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1035', 'member_name' => 'Zam zam Nishan', 'team_name' => 'WpTitans', 'call_sign' => 'Nishan', 'total_delivery' => 1618.00, 'total_wip' => 336.00, 'tips' => 0.00, 'cancelled' => 632.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 1, 'stars_1' => 0, 'total_cancel' => 2, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1027', 'member_name' => 'Tahrima Billal Biva', 'team_name' => 'WpNexus', 'call_sign' => 'Biva', 'total_delivery' => 1314.66, 'total_wip' => 0.00, 'tips' => 26.88, 'cancelled' => 96.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 3, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1032', 'member_name' => 'MD. Abu Jor', 'team_name' => 'WpTitans', 'call_sign' => 'Abu Jor', 'total_delivery' => 1184.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 320.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1055', 'member_name' => 'Jagobondhu Mahanto', 'team_name' => 'WpNexus', 'call_sign' => 'Mahanto', 'total_delivery' => 2112.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 9, 'stars_5' => 7, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1068', 'member_name' => 'Rayhan Kabir', 'team_name' => 'WpTitans', 'call_sign' => 'Rayhan', 'total_delivery' => 1164.48, 'total_wip' => 0.00, 'tips' => 10.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 9, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1069', 'member_name' => 'Nasim Uddin', 'team_name' => 'WpTitans', 'call_sign' => 'Nasim', 'total_delivery' => 1496.00, 'total_wip' => 720.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 1, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1078', 'member_name' => 'Sahariar Alam Redwan', 'team_name' => 'WpTitans', 'call_sign' => 'Redwan', 'total_delivery' => 272.00, 'total_wip' => 186.40, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 1, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1013', 'member_name' => 'Maksuda Akter', 'team_name' => 'WpCraft', 'call_sign' => 'Maksuda', 'total_delivery' => 2796.00, 'total_wip' => 400.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1041', 'member_name' => 'Shahadath Hasan', 'team_name' => 'WpCraft', 'call_sign' => 'Shahadath', 'total_delivery' => 1320.00, 'total_wip' => 32.00, 'tips' => 40.00, 'cancelled' => 2000.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 1, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1003', 'member_name' => 'Nure Alam', 'team_name' => 'WpCraft', 'call_sign' => 'Nure Alam', 'total_delivery' => 800.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 96.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1044', 'member_name' => 'Sifat Ullah', 'team_name' => 'WpCraft', 'call_sign' => 'Sifat', 'total_delivery' => 1240.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1049', 'member_name' => 'Habibur Rahman Romel', 'team_name' => 'WpCraft', 'call_sign' => 'Romel', 'total_delivery' => 664.00, 'total_wip' => 852.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 2, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1050', 'member_name' => 'Abu Saeed', 'team_name' => 'WpCraft', 'call_sign' => 'Abu Saeed', 'total_delivery' => 1137.50, 'total_wip' => 720.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1077', 'member_name' => 'F M Afzalul Bare', 'team_name' => 'WpCraft', 'call_sign' => 'Afzal', 'total_delivery' => 288.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 400.00, 'partial' => 0.00, 'total_completed' => 2, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1079', 'member_name' => 'MST Borsha Khatun', 'team_name' => 'WpCraft', 'call_sign' => 'Borsha', 'total_delivery' => 0.00, 'total_wip' => 224.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 0, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1080', 'member_name' => 'Susanto Ray', 'team_name' => 'WpCraft', 'call_sign' => 'Susanto', 'total_delivery' => 384.00, 'total_wip' => 280.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 2, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1082', 'member_name' => 'Md. Monirujjaman', 'team_name' => 'WpCraft', 'call_sign' => 'Monirujjaman', 'total_delivery' => 1320.00, 'total_wip' => 440.00, 'tips' => 40.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1081', 'member_name' => 'Mahfuz Hasan', 'team_name' => 'WpTitans', 'call_sign' => 'Mahfuz', 'total_delivery' => 200.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 1, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1083', 'member_name' => 'Hillol Das Gupta', 'team_name' => 'WpCraft', 'call_sign' => 'Hillol', 'total_delivery' => 0.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 1, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1084', 'member_name' => 'MS Hasan', 'team_name' => 'WpCraft', 'call_sign' => 'Hasan', 'total_delivery' => 0.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 1, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1086', 'member_name' => 'Md. Saidur Rahman', 'team_name' => 'WpCraft', 'call_sign' => 'Saidur', 'total_delivery' => 0.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 1, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0]
            ],
            'February_2026' => [
                ['member_custom_id' => '1013', 'member_name' => 'Maksuda Akter', 'team_name' => 'WpCraft', 'call_sign' => 'Maksuda', 'total_delivery' => 1385.60, 'total_wip' => 1048.00, 'tips' => 38.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 10, 'stars_5' => 1, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1027', 'member_name' => 'Tahrima Billal Biva', 'team_name' => 'WpNexus', 'call_sign' => 'Biva', 'total_delivery' => 394.00, 'total_wip' => 1444.00, 'tips' => 0.00, 'cancelled' => 280.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1032', 'member_name' => 'MD. Abu Jor', 'team_name' => 'WpCraft', 'call_sign' => 'Abu Jor', 'total_delivery' => 2236.80, 'total_wip' => 320.00, 'tips' => 125.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1035', 'member_name' => 'Zam zam Nishan', 'team_name' => 'WpTitans', 'call_sign' => 'Nishan', 'total_delivery' => 1366.40, 'total_wip' => 1376.00, 'tips' => 0.00, 'cancelled' => 240.00, 'partial' => 0.00, 'total_completed' => 9, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1038', 'member_name' => 'Naimul Islam', 'team_name' => 'WpTitans', 'call_sign' => 'Naim', 'total_delivery' => 1018.00, 'total_wip' => 680.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1041', 'member_name' => 'Shahadath Hasan', 'team_name' => 'WpCraft', 'call_sign' => 'Shahadath', 'total_delivery' => 1000.00, 'total_wip' => 392.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1044', 'member_name' => 'Sifat Ullah', 'team_name' => 'WpTitans', 'call_sign' => 'Sifat', 'total_delivery' => 2004.00, 'total_wip' => 560.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 8, 'stars_5' => 6, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1049', 'member_name' => 'Habibur Rahman Romel', 'team_name' => 'WpCraft', 'call_sign' => 'Romel', 'total_delivery' => 40.00, 'total_wip' => 812.00, 'tips' => 0.00, 'cancelled' => 240.00, 'partial' => 0.00, 'total_completed' => 1, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1050', 'member_name' => 'Abu Saeed', 'team_name' => 'WpCraft', 'call_sign' => 'Abu Saeed', 'total_delivery' => 1184.00, 'total_wip' => 568.00, 'tips' => 160.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1055', 'member_name' => 'Jagobondhu Mahanto', 'team_name' => 'WpNexus', 'call_sign' => 'Mahanto', 'total_delivery' => 1510.00, 'total_wip' => 160.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1068', 'member_name' => 'Rayhan Kabir', 'team_name' => 'WpNexus', 'call_sign' => 'Rayhan', 'total_delivery' => 1416.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1069', 'member_name' => 'Nasim Uddin', 'team_name' => 'WpTitans', 'call_sign' => 'Nasim', 'total_delivery' => 1785.20, 'total_wip' => 320.00, 'tips' => 0.00, 'cancelled' => 120.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 3, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1077', 'member_name' => 'F M Afzalul Bare', 'team_name' => 'WpCraft', 'call_sign' => 'Afzal', 'total_delivery' => 772.00, 'total_wip' => 160.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1078', 'member_name' => 'Sahariar Alam Redwan', 'team_name' => 'WpTitans', 'call_sign' => 'Redwan', 'total_delivery' => 1093.20, 'total_wip' => 544.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 8, 'stars_5' => 3, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1079', 'member_name' => 'MST Borsha Khatun', 'team_name' => 'WpCraft', 'call_sign' => 'Borsha', 'total_delivery' => 1229.60, 'total_wip' => 296.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 10, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1080', 'member_name' => 'Susanto Ray', 'team_name' => 'WpCraft', 'call_sign' => 'Susanto', 'total_delivery' => 876.00, 'total_wip' => 160.00, 'tips' => 0.00, 'cancelled' => 320.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 2, 'stars_4' => 2, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1081', 'member_name' => 'Mahfuz Hasan', 'team_name' => 'WpNexus', 'call_sign' => 'Mahfuz', 'total_delivery' => 1402.00, 'total_wip' => 320.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 8, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1082', 'member_name' => 'Md. Monirujjaman', 'team_name' => 'WpCraft', 'call_sign' => 'Monirujjaman', 'total_delivery' => 1380.00, 'total_wip' => 920.00, 'tips' => 30.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 8, 'stars_5' => 3, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1083', 'member_name' => 'Hillol Das Gupta', 'team_name' => 'WpNexus', 'call_sign' => 'Hillol', 'total_delivery' => 1430.00, 'total_wip' => 200.00, 'tips' => 0.00, 'cancelled' => 280.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1084', 'member_name' => 'MS Hasan', 'team_name' => 'WpTitans', 'call_sign' => 'Hasan', 'total_delivery' => 80.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 1, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1085', 'member_name' => 'Abu Abdullah Akash', 'team_name' => 'WpTitans', 'call_sign' => 'Akash', 'total_delivery' => 80.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 1, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1086', 'member_name' => 'Md. Saidur Rahman', 'team_name' => 'WpCraft', 'call_sign' => 'Saidur', 'total_delivery' => 800.00, 'total_wip' => 520.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 2, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1088', 'member_name' => 'Bijoya Sarkar', 'team_name' => 'WpTitans', 'call_sign' => 'Bijoya', 'total_delivery' => 164.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1089', 'member_name' => 'Sumaya Afrin Era', 'team_name' => 'WpNexus', 'call_sign' => 'Afrin', 'total_delivery' => 70.00, 'total_wip' => 160.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 1, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1090', 'member_name' => 'Junayedur Rahman', 'team_name' => 'WpCraft', 'call_sign' => 'Junayed', 'total_delivery' => 464.00, 'total_wip' => 320.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0]
            ],
            'March_2026' => [
                ['member_custom_id' => '1013', 'member_name' => 'Maksuda Akter', 'team_name' => 'WpCraft', 'call_sign' => 'Maksuda', 'total_delivery' => 1161.60, 'total_wip' => 288.00, 'tips' => 25.60, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1027', 'member_name' => 'Tahrima Billal Biva', 'team_name' => 'WpNexus', 'call_sign' => 'Biva', 'total_delivery' => 2116.00, 'total_wip' => 80.00, 'tips' => 10.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 11, 'stars_5' => 10, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1032', 'member_name' => 'MD. Abu Jor', 'team_name' => 'WpCraft', 'call_sign' => 'Abu Jor', 'total_delivery' => 1188.00, 'total_wip' => 656.00, 'tips' => 125.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1035', 'member_name' => 'Zam zam Nishan', 'team_name' => 'WpTitans', 'call_sign' => 'Nishan', 'total_delivery' => 528.00, 'total_wip' => 1376.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1038', 'member_name' => 'Naimul Islam', 'team_name' => 'WpTitans', 'call_sign' => 'Naim', 'total_delivery' => 1124.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1044', 'member_name' => 'Sifat Ullah', 'team_name' => 'WpTitans', 'call_sign' => 'Sifat', 'total_delivery' => 1124.00, 'total_wip' => 2160.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1049', 'member_name' => 'Habibur Rahman Romel', 'team_name' => 'WpCraft', 'call_sign' => 'Romel', 'total_delivery' => 1322.00, 'total_wip' => 200.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 9, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1050', 'member_name' => 'Abu Saeed', 'team_name' => 'WpCraft', 'call_sign' => 'Abu Saeed', 'total_delivery' => 392.00, 'total_wip' => 680.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 0, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1055', 'member_name' => 'Jagobondhu Mahanto', 'team_name' => 'WpNexus', 'call_sign' => 'Mahanto', 'total_delivery' => 1160.00, 'total_wip' => 360.00, 'tips' => 160.00, 'cancelled' => 552.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 3, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1068', 'member_name' => 'Rayhan Kabir', 'team_name' => 'WpNexus', 'call_sign' => 'Rayhan', 'total_delivery' => 448.00, 'total_wip' => 504.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1069', 'member_name' => 'Nasim Uddin', 'team_name' => 'WpTitans', 'call_sign' => 'Nasim', 'total_delivery' => 738.00, 'total_wip' => 800.00, 'tips' => 18.00, 'cancelled' => 440.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 2, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1077', 'member_name' => 'F M Afzalul Bare', 'team_name' => 'WpCraft', 'call_sign' => 'Afzal', 'total_delivery' => 750.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1078', 'member_name' => 'Sahariar Alam Redwan', 'team_name' => 'WpTitans', 'call_sign' => 'Redwan', 'total_delivery' => 1414.00, 'total_wip' => 240.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 5, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1079', 'member_name' => 'MST Borsha Khatun', 'team_name' => 'WpCraft', 'call_sign' => 'Borsha', 'total_delivery' => 708.00, 'total_wip' => 280.00, 'tips' => 12.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1080', 'member_name' => 'Susanto Ray', 'team_name' => 'WpCraft', 'call_sign' => 'Susanto', 'total_delivery' => 1280.00, 'total_wip' => 1004.00, 'tips' => 4.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1081', 'member_name' => 'Mahfuz Hasan', 'team_name' => 'WpNexus', 'call_sign' => 'Mahfuz', 'total_delivery' => 1004.00, 'total_wip' => 912.00, 'tips' => 0.00, 'cancelled' => 640.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 0, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 1, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1082', 'member_name' => 'Md. Monirujjaman', 'team_name' => 'WpCraft', 'call_sign' => 'Monirujjaman', 'total_delivery' => 1154.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1083', 'member_name' => 'Hillol Das Gupta', 'team_name' => 'WpNexus', 'call_sign' => 'Hillol', 'total_delivery' => 0.00, 'total_wip' => 0.00, 'tips' => 30.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 0, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1084', 'member_name' => 'MS Hasan', 'team_name' => 'WpTitans', 'call_sign' => 'Hasan', 'total_delivery' => 670.00, 'total_wip' => 160.00, 'tips' => 36.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 4, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1085', 'member_name' => 'Abu Abdullah Akash', 'team_name' => 'WpNexus', 'call_sign' => 'Akash', 'total_delivery' => 498.80, 'total_wip' => 400.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1086', 'member_name' => 'Md. Saidur Rahman', 'team_name' => 'WpTitans', 'call_sign' => 'Saidur', 'total_delivery' => 1200.00, 'total_wip' => 560.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1088', 'member_name' => 'Bijoya Sarkar', 'team_name' => 'WpTitans', 'call_sign' => 'Bijoya', 'total_delivery' => 236.80, 'total_wip' => 0.00, 'tips' => 8.96, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1089', 'member_name' => 'Sumaya Afrin Era', 'team_name' => 'WpNexus', 'call_sign' => 'Afrin', 'total_delivery' => 196.00, 'total_wip' => 224.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 2, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1090', 'member_name' => 'Junayedur Rahman', 'team_name' => 'WpCraft', 'call_sign' => 'Junayed', 'total_delivery' => 864.00, 'total_wip' => 120.00, 'tips' => 72.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 2, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0]
            ],
            'April_2026' => [
                ['member_custom_id' => '1013', 'member_name' => 'Maksuda Akter', 'team_name' => 'WpCraft', 'call_sign' => 'Maksuda', 'total_delivery' => 777.60, 'total_wip' => 200.00, 'tips' => 25.60, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 1, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1035', 'member_name' => 'Zam zam Nishan', 'team_name' => 'WpTitans', 'call_sign' => 'Nishan', 'total_delivery' => 1602.00, 'total_wip' => 220.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 3],
                ['member_custom_id' => '1038', 'member_name' => 'Naimul Islam', 'team_name' => 'WpTitans', 'call_sign' => 'Naim', 'total_delivery' => 1352.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 2, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1044', 'member_name' => 'Sifat Ullah', 'team_name' => 'WpTitans', 'call_sign' => 'Sifat', 'total_delivery' => 1664.00, 'total_wip' => 360.00, 'tips' => 0.00, 'cancelled' => 280.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 2],
                ['member_custom_id' => '1049', 'member_name' => 'Habibur Rahman Romel', 'team_name' => 'WpCraft', 'call_sign' => 'Romel', 'total_delivery' => 128.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 200.00, 'partial' => 0.00, 'total_completed' => 2, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1050', 'member_name' => 'Abu Saeed', 'team_name' => 'WpCraft', 'call_sign' => 'Abu Saeed', 'total_delivery' => 644.80, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1069', 'member_name' => 'Nasim Uddin', 'team_name' => 'WpTitans', 'call_sign' => 'Nasim', 'total_delivery' => 1280.00, 'total_wip' => 144.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 3],
                ['member_custom_id' => '1077', 'member_name' => 'F M Afzalul Bare', 'team_name' => 'WpCraft', 'call_sign' => 'Afzal', 'total_delivery' => 813.20, 'total_wip' => 200.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 5, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 1],
                ['member_custom_id' => '1078', 'member_name' => 'Sahariar Alam Redwan', 'team_name' => 'WpTitans', 'call_sign' => 'Redwan', 'total_delivery' => 1240.00, 'total_wip' => 0.00, 'tips' => 20.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 5],
                ['member_custom_id' => '1079', 'member_name' => 'MST Borsha Khatun', 'team_name' => 'WpCraft', 'call_sign' => 'Borsha', 'total_delivery' => 1045.60, 'total_wip' => 326.40, 'tips' => 12.00, 'cancelled' => 240.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 4],
                ['member_custom_id' => '1080', 'member_name' => 'Susanto Ray', 'team_name' => 'WpCraft', 'call_sign' => 'Susanto', 'total_delivery' => 1360.00, 'total_wip' => 240.00, 'tips' => 4.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 4],
                ['member_custom_id' => '1082', 'member_name' => 'Md. Monirujjaman', 'team_name' => 'WpCraft', 'call_sign' => 'Monirujjaman', 'total_delivery' => 472.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 160.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1084', 'member_name' => 'MS Hasan', 'team_name' => 'WpCraft', 'call_sign' => 'Hasan', 'total_delivery' => 948.00, 'total_wip' => 0.00, 'tips' => 30.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1086', 'member_name' => 'Md. Saidur Rahman', 'team_name' => 'WpTitans', 'call_sign' => 'Saidur', 'total_delivery' => 1888.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 5],
                ['member_custom_id' => '1088', 'member_name' => 'Bijoya Sarkar', 'team_name' => 'WpTitans', 'call_sign' => 'Bijoya', 'total_delivery' => 534.00, 'total_wip' => 72.00, 'tips' => 40.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0]
            ],
            'May_2026' => [
                ['member_custom_id' => '1013', 'member_name' => 'Maksuda Akter', 'team_name' => 'WpCraft', 'call_sign' => 'Maksuda', 'total_delivery' => 658.00, 'total_wip' => 40.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1035', 'member_name' => 'Zam zam Nishan', 'team_name' => 'WpTitans', 'call_sign' => 'Nishan', 'total_delivery' => 984.00, 'total_wip' => 200.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1038', 'member_name' => 'Naimul Islam', 'team_name' => 'WpTitans', 'call_sign' => 'Naim', 'total_delivery' => 596.00, 'total_wip' => 320.00, 'tips' => 0.00, 'cancelled' => 224.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1044', 'member_name' => 'Sifat Ullah', 'team_name' => 'WpTitans', 'call_sign' => 'Sifat', 'total_delivery' => 1000.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 3, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1049', 'member_name' => 'Habibur Rahman Romel', 'team_name' => 'WpCraft', 'call_sign' => 'Romel', 'total_delivery' => 312.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1050', 'member_name' => 'Abu Saeed', 'team_name' => 'WpCraft', 'call_sign' => 'Abu Saeed', 'total_delivery' => 222.00, 'total_wip' => 400.00, 'tips' => 0.00, 'cancelled' => 120.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1069', 'member_name' => 'Nasim Uddin', 'team_name' => 'WpTitans', 'call_sign' => 'Nasim', 'total_delivery' => 884.00, 'total_wip' => 200.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 2, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1077', 'member_name' => 'F M Afzalul Bare', 'team_name' => 'WpCraft', 'call_sign' => 'Afzal', 'total_delivery' => 1200.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 8, 'stars_5' => 4, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1078', 'member_name' => 'Sahariar Alam Redwan', 'team_name' => 'WpTitans', 'call_sign' => 'Redwan', 'total_delivery' => 850.00, 'total_wip' => 104.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1079', 'member_name' => 'MST Borsha Khatun', 'team_name' => 'WpCraft', 'call_sign' => 'Borsha', 'total_delivery' => 1104.40, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 1, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1080', 'member_name' => 'Susanto Ray', 'team_name' => 'WpCraft', 'call_sign' => 'Susanto', 'total_delivery' => 788.00, 'total_wip' => 400.00, 'tips' => 0.00, 'cancelled' => 440.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 1, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1082', 'member_name' => 'Md. Monirujjaman', 'team_name' => 'WpCraft', 'call_sign' => 'Monirujjaman', 'total_delivery' => 674.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1084', 'member_name' => 'MS Hasan', 'team_name' => 'WpTitans', 'call_sign' => 'Hasan', 'total_delivery' => 524.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1086', 'member_name' => 'Md. Saidur Rahman', 'team_name' => 'WpTitans', 'call_sign' => 'Saidur', 'total_delivery' => 700.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 1, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1088', 'member_name' => 'Bijoya Sarkar', 'team_name' => 'WpTitans', 'call_sign' => 'Bijoya', 'total_delivery' => 342.00, 'total_wip' => 144.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0]
            ],
            'June_2026' => [
                ['member_custom_id' => '1013', 'member_name' => 'Maksuda Akter', 'team_name' => 'WpCraft', 'call_sign' => 'Maksuda', 'total_delivery' => 530.00, 'total_wip' => 40.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 3],
                ['member_custom_id' => '1035', 'member_name' => 'Zam zam Nishan', 'team_name' => 'WpTitans', 'call_sign' => 'Nishan', 'total_delivery' => 706.00, 'total_wip' => 360.00, 'tips' => 12.00, 'cancelled' => 200.00, 'partial' => 0.00, 'total_completed' => 9, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1038', 'member_name' => 'Naimul Islam', 'team_name' => 'WpTitans', 'call_sign' => 'Naim', 'total_delivery' => 1130.00, 'total_wip' => 616.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1044', 'member_name' => 'Sifat Ullah', 'team_name' => 'WpTitans', 'call_sign' => 'Sifat', 'total_delivery' => 1350.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 144.00, 'partial' => 0.00, 'total_completed' => 10, 'stars_5' => 4, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1049', 'member_name' => 'Habibur Rahman Romel', 'team_name' => 'WpCraft', 'call_sign' => 'Romel', 'total_delivery' => 1364.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 4],
                ['member_custom_id' => '1050', 'member_name' => 'Abu Saeed', 'team_name' => 'WpCraft', 'call_sign' => 'Abu Saeed', 'total_delivery' => 864.00, 'total_wip' => 176.00, 'tips' => 0.00, 'cancelled' => 120.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1069', 'member_name' => 'Nasim Uddin', 'team_name' => 'WpTitans', 'call_sign' => 'Nasim', 'total_delivery' => 616.00, 'total_wip' => 1720.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1077', 'member_name' => 'F M Afzalul Bare', 'team_name' => 'WpCraft', 'call_sign' => 'Afzal', 'total_delivery' => 656.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 120.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1078', 'member_name' => 'Sahariar Alam Redwan', 'team_name' => 'WpTitans', 'call_sign' => 'Redwan', 'total_delivery' => 1092.00, 'total_wip' => 504.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1079', 'member_name' => 'MST Borsha Khatun', 'team_name' => 'WpCraft', 'call_sign' => 'Borsha', 'total_delivery' => 1128.00, 'total_wip' => 336.00, 'tips' => 0.00, 'cancelled' => 80.00, 'partial' => 0.00, 'total_completed' => 8, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 4],
                ['member_custom_id' => '1080', 'member_name' => 'Susanto Ray', 'team_name' => 'WpCraft', 'call_sign' => 'Susanto', 'total_delivery' => 1361.60, 'total_wip' => 0.00, 'tips' => 14.00, 'cancelled' => 440.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 1, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1082', 'member_name' => 'Md. Monirujjaman', 'team_name' => 'WpCraft', 'call_sign' => 'Monirujjaman', 'total_delivery' => 338.00, 'total_wip' => 144.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1084', 'member_name' => 'MS Hasan', 'team_name' => 'WpTitans', 'call_sign' => 'Hasan', 'total_delivery' => 796.00, 'total_wip' => 224.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 4, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1086', 'member_name' => 'Md. Saidur Rahman', 'team_name' => 'WpTitans', 'call_sign' => 'Saidur', 'total_delivery' => 1592.00, 'total_wip' => 120.00, 'tips' => 24.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 12, 'stars_5' => 5, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 9],
                ['member_custom_id' => '1088', 'member_name' => 'Bijoya Sarkar', 'team_name' => 'WpTitans', 'call_sign' => 'Bijoya', 'total_delivery' => 496.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0]
            ],
            'july_2026' => [
                ['member_custom_id' => '1013', 'member_name' => 'Maksuda Akter', 'team_name' => 'WpCraft', 'call_sign' => 'Maksuda', 'total_delivery' => 320.00, 'total_wip' => 464.00, 'tips' => 0.00, 'cancelled' => 200.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 2, 'delivered_without_extension' => 3],
                ['member_custom_id' => '1035', 'member_name' => 'Zam zam Nishan', 'team_name' => 'WpTitans', 'call_sign' => 'Nishan', 'total_delivery' => 1002.40, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 120.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1038', 'member_name' => 'Naimul Islam', 'team_name' => 'WpTitans', 'call_sign' => 'Naim', 'total_delivery' => 666.00, 'total_wip' => 1080.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1044', 'member_name' => 'Sifat Ullah', 'team_name' => 'WpTitans', 'call_sign' => 'Sifat', 'total_delivery' => 1208.60, 'total_wip' => 160.00, 'tips' => 0.00, 'cancelled' => 144.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1049', 'member_name' => 'Habibur Rahman Romel', 'team_name' => 'WpCraft', 'call_sign' => 'Romel', 'total_delivery' => 752.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 4],
                ['member_custom_id' => '1050', 'member_name' => 'Abu Saeed', 'team_name' => 'WpCraft', 'call_sign' => 'Abu Saeed', 'total_delivery' => 565.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 400.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1069', 'member_name' => 'Nasim Uddin', 'team_name' => 'WpTitans', 'call_sign' => 'Nasim', 'total_delivery' => 1226.00, 'total_wip' => 1080.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1077', 'member_name' => 'F M Afzalul Bare', 'team_name' => 'WpCraft', 'call_sign' => 'Afzal', 'total_delivery' => 390.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 400.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 2, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1078', 'member_name' => 'Sahariar Alam Redwan', 'team_name' => 'WpTitans', 'call_sign' => 'Redwan', 'total_delivery' => 1170.00, 'total_wip' => 440.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1079', 'member_name' => 'MST Borsha Khatun', 'team_name' => 'WpCraft', 'call_sign' => 'Borsha', 'total_delivery' => 1356.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 10, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 4],
                ['member_custom_id' => '1080', 'member_name' => 'Susanto Ray', 'team_name' => 'WpCraft', 'call_sign' => 'Susanto', 'total_delivery' => 1004.00, 'total_wip' => 240.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 1, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1082', 'member_name' => 'Md. Monirujjaman', 'team_name' => 'WpCraft', 'call_sign' => 'Monirujjaman', 'total_delivery' => 594.00, 'total_wip' => 800.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1084', 'member_name' => 'MS Hasan', 'team_name' => 'WpTitans', 'call_sign' => 'Hasan', 'total_delivery' => 690.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1086', 'member_name' => 'Md. Saidur Rahman', 'team_name' => 'WpTitans', 'call_sign' => 'Saidur', 'total_delivery' => 2201.00, 'total_wip' => 560.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 13, 'stars_5' => 10, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 9],
                ['member_custom_id' => '1088', 'member_name' => 'Bijoya Sarkar', 'team_name' => 'WpTitans', 'call_sign' => 'Bijoya', 'total_delivery' => 307.60, 'total_wip' => 400.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0]
            ],
            'August_2026' => [
                ['member_custom_id' => '1013', 'member_name' => 'Maksuda Akter', 'team_name' => 'WpCraft', 'call_sign' => 'Maksuda', 'total_delivery' => 1039.60, 'total_wip' => 232.00, 'tips' => 21.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 8, 'stars_5' => 5, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 3],
                ['member_custom_id' => '1035', 'member_name' => 'Zam zam Nishan', 'team_name' => 'WpCraft', 'call_sign' => 'Nishan', 'total_delivery' => 1017.80, 'total_wip' => 1284.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 2],
                ['member_custom_id' => '1038', 'member_name' => 'Naimul Islam', 'team_name' => 'WpTitans', 'call_sign' => 'Naim', 'total_delivery' => 1560.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 4, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1044', 'member_name' => 'Sifat Ullah', 'team_name' => 'WpTitans', 'call_sign' => 'Sifat', 'total_delivery' => 300.00, 'total_wip' => 544.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1032', 'member_name' => 'MD. Abu Jor', 'team_name' => 'WpCraft', 'call_sign' => 'Abu Jor', 'total_delivery' => 429.60, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 4],
                ['member_custom_id' => '1055', 'member_name' => 'Jagobondhu Mahanto', 'team_name' => 'WpNexus', 'call_sign' => 'Mahanto', 'total_delivery' => 1245.60, 'total_wip' => 536.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 11, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1068', 'member_name' => 'Rayhan Kabir', 'team_name' => 'WpTitans', 'call_sign' => 'Rayhan', 'total_delivery' => 699.80, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 9, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1077', 'member_name' => 'F M Afzalul Bare', 'team_name' => 'WpTitans', 'call_sign' => 'Afzal', 'total_delivery' => 324.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1078', 'member_name' => 'Sahariar Alam Redwan', 'team_name' => 'WpTitans', 'call_sign' => 'Redwan', 'total_delivery' => 964.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 4, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1079', 'member_name' => 'MST Borsha Khatun', 'team_name' => 'WpNexus', 'call_sign' => 'Borsha', 'total_delivery' => 1001.80, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 12, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 4],
                ['member_custom_id' => '1080', 'member_name' => 'Susanto Ray', 'team_name' => 'WpNexus', 'call_sign' => 'Susanto', 'total_delivery' => 360.00, 'total_wip' => 240.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1082', 'member_name' => 'Md. Monirujjaman', 'team_name' => 'WpNexus', 'call_sign' => 'Monirujjaman', 'total_delivery' => 746.00, 'total_wip' => 320.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1084', 'member_name' => 'MS Hasan', 'team_name' => 'WpNexus', 'call_sign' => 'Hasan', 'total_delivery' => 740.00, 'total_wip' => 136.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1086', 'member_name' => 'Md. Saidur Rahman', 'team_name' => 'WpCraft', 'call_sign' => 'Saidur', 'total_delivery' => 2756.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 8, 'stars_5' => 5, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 9],
                ['member_custom_id' => '1088', 'member_name' => 'Bijoya Sarkar', 'team_name' => 'WpCraft', 'call_sign' => 'Bijoya', 'total_delivery' => 630.00, 'total_wip' => 96.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                // WpHorizon transferred colleagues (Sister team under separate management from August)
                ['member_custom_id' => '1027', 'member_name' => 'Tahrima Billal Biva', 'team_name' => 'WpHorizon', 'call_sign' => 'Biva', 'total_delivery' => 0.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 0, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1069', 'member_name' => 'Nasim Uddin', 'team_name' => 'WpHorizon', 'call_sign' => 'Nasim', 'total_delivery' => 0.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 0, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1049', 'member_name' => 'Habibur Rahman Romel', 'team_name' => 'WpHorizon', 'call_sign' => 'Romel', 'total_delivery' => 0.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 0, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1050', 'member_name' => 'Abu Saeed', 'team_name' => 'WpHorizon', 'call_sign' => 'Abu Saeed', 'total_delivery' => 0.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 0, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0]
            ],
            'September_2026' => [
                ['member_custom_id' => '1013', 'member_name' => 'Maksuda Akter', 'team_name' => 'WpCraft', 'call_sign' => 'Maksuda', 'total_delivery' => 417.60, 'total_wip' => 760.00, 'tips' => 21.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 3],
                ['member_custom_id' => '1035', 'member_name' => 'Zam zam Nishan', 'team_name' => 'WpCraft', 'call_sign' => 'Nishan', 'total_delivery' => 1472.00, 'total_wip' => 80.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 3, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 2],
                ['member_custom_id' => '1038', 'member_name' => 'Naimul Islam', 'team_name' => 'WpTitans', 'call_sign' => 'Naim', 'total_delivery' => 570.00, 'total_wip' => 1419.20, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 4, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1044', 'member_name' => 'Sifat Ullah', 'team_name' => 'WpTitans', 'call_sign' => 'Sifat', 'total_delivery' => 950.00, 'total_wip' => 840.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 9, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1032', 'member_name' => 'MD. Abu Jor', 'team_name' => 'WpCraft', 'call_sign' => 'Abu Jor', 'total_delivery' => 1133.00, 'total_wip' => 240.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 4],
                ['member_custom_id' => '1055', 'member_name' => 'Jagobondhu Mahanto', 'team_name' => 'WpNexus', 'call_sign' => 'Mahanto', 'total_delivery' => 558.00, 'total_wip' => 96.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1068', 'member_name' => 'Rayhan Kabir', 'team_name' => 'WpTitans', 'call_sign' => 'Rayhan', 'total_delivery' => 452.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1077', 'member_name' => 'F M Afzalul Bare', 'team_name' => 'WpTitans', 'call_sign' => 'Afzal', 'total_delivery' => 844.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 280.00, 'partial' => 0.00, 'total_completed' => 6, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 1, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1078', 'member_name' => 'Sahariar Alam Redwan', 'team_name' => 'WpTitans', 'call_sign' => 'Redwan', 'total_delivery' => 320.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 4, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1079', 'member_name' => 'MST Borsha Khatun', 'team_name' => 'WpNexus', 'call_sign' => 'Borsha', 'total_delivery' => 596.00, 'total_wip' => 116.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 4],
                ['member_custom_id' => '1080', 'member_name' => 'Susanto Ray', 'team_name' => 'WpNexus', 'call_sign' => 'Susanto', 'total_delivery' => 1036.00, 'total_wip' => 280.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 7, 'stars_5' => 1, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1082', 'member_name' => 'Md. Monirujjaman', 'team_name' => 'WpNexus', 'call_sign' => 'Monirujjaman', 'total_delivery' => 1010.00, 'total_wip' => 832.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 5, 'stars_5' => 2, 'stars_4' => 1, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1084', 'member_name' => 'MS Hasan', 'team_name' => 'WpNexus', 'call_sign' => 'Hasan', 'total_delivery' => 1032.00, 'total_wip' => 520.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1086', 'member_name' => 'Md. Saidur Rahman', 'team_name' => 'WpCraft', 'call_sign' => 'Saidur', 'total_delivery' => 5880.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 4, 'stars_5' => 5, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 9],
                ['member_custom_id' => '1088', 'member_name' => 'Bijoya Sarkar', 'team_name' => 'WpCraft', 'call_sign' => 'Bijoya', 'total_delivery' => 274.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 3, 'stars_5' => 2, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                // WpHorizon transferred colleagues
                ['member_custom_id' => '1027', 'member_name' => 'Tahrima Billal Biva', 'team_name' => 'WpHorizon', 'call_sign' => 'Biva', 'total_delivery' => 0.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 0, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1069', 'member_name' => 'Nasim Uddin', 'team_name' => 'WpHorizon', 'call_sign' => 'Nasim', 'total_delivery' => 0.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 0, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1049', 'member_name' => 'Habibur Rahman Romel', 'team_name' => 'WpHorizon', 'call_sign' => 'Romel', 'total_delivery' => 0.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 0, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0],
                ['member_custom_id' => '1050', 'member_name' => 'Abu Saeed', 'team_name' => 'WpHorizon', 'call_sign' => 'Abu Saeed', 'total_delivery' => 0.00, 'total_wip' => 0.00, 'tips' => 0.00, 'cancelled' => 0.00, 'partial' => 0.00, 'total_completed' => 0, 'stars_5' => 0, 'stars_4' => 0, 'stars_3' => 0, 'stars_2' => 0, 'stars_1' => 0, 'total_cancel' => 0, 'delivered_without_extension' => 0]
            ]
        ];

        foreach ($months_data as $month_slug => $rows) {
            $wpdb->delete($table, ['reporting_month' => $month_slug]);
            $alt_slug = (strpos($month_slug, 'july') !== false) ? 'July_2026' : strtolower($month_slug);
            $wpdb->delete($table, ['reporting_month' => $alt_slug]);

            foreach ($rows as $r) {
                $wpdb->insert($table, [
                    'reporting_month' => $month_slug,
                    'member_custom_id' => $r['member_custom_id'],
                    'member_name' => $r['member_name'],
                    'team_name' => $r['team_name'],
                    'call_sign' => $r['call_sign'],
                    'total_delivery' => $r['total_delivery'],
                    'total_wip' => $r['total_wip'],
                    'tips' => $r['tips'],
                    'cancelled' => $r['cancelled'],
                    'partial' => $r['partial'],
                    'total_completed' => $r['total_completed'],
                    'stars_5' => $r['stars_5'],
                    'stars_4' => $r['stars_4'],
                    'stars_3' => $r['stars_3'],
                    'stars_2' => $r['stars_2'],
                    'stars_1' => $r['stars_1'],
                    'total_cancel' => $r['total_cancel'],
                    'delivered_without_extension' => $r['delivered_without_extension'],
                    'created_at' => current_time('mysql')
                ]);
            }
        }

        self::apply_archive_overlay();
        update_option('ftm_archive_seed_version', self::ARCHIVE_SEED_VERSION);
    }

    /**
     * Final, sheet-verified monthly figures (Jan-Sep 2026) layered over the legacy seed.
     * Existing rows keep their stars/tips/WIP and only take the verified delivery value, count and team;
     * members missing from the legacy seed are inserted.
     */
    private static function apply_archive_overlay() {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_monthly_archive';
        $file = dirname(__FILE__) . '/data/archive-overlay.json';
        if (!file_exists($file)) {
            return;
        }
        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data)) {
            return;
        }
        foreach ($data as $month => $rows) {
            foreach ($rows as $r) {
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table WHERE LOWER(reporting_month) = LOWER(%s) AND member_custom_id = %s LIMIT 1",
                    $month, $r['id']
                ));
                if ($existing) {
                    $wpdb->update($table, [
                        'total_delivery' => (float)$r['delivery'],
                        'total_completed' => (int)$r['completed'],
                        'team_name' => $r['team']
                    ], ['id' => $existing]);
                } else {
                    $wpdb->insert($table, [
                        'reporting_month' => $month,
                        'member_custom_id' => $r['id'],
                        'member_name' => $r['name'],
                        'team_name' => $r['team'],
                        'call_sign' => $r['call'],
                        'total_delivery' => (float)$r['delivery'],
                        'total_completed' => (int)$r['completed'],
                        'cancelled' => (float)$r['cancelled'],
                        'total_cancel' => (int)$r['cancel_count'],
                        'delivered_without_extension' => (int)$r['completed'],
                        'created_at' => current_time('mysql')
                    ]);
                }
            }
        }
    }

    /**
     * Ensures Saidur's 3 real October projects exist and purges mock projects.
     * NEVER truncates the projects table!
     */
    public static function ensure_real_october_projects() {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_projects';
        $assignees_table = $wpdb->prefix . 'ftm_project_assignees';

        // Clean out any mock projects with ID >= 4
        $wpdb->query("DELETE FROM $table WHERE id >= 4");
        $wpdb->query("DELETE FROM $assignees_table WHERE project_id >= 4");

        // Find Saidur Rahman's user id
        $saidur = get_user_by('login', 'md_saidur_rahman');
        $saidur_id = $saidur ? $saidur->ID : 1;

        $projects = [
            [
                'id' => 1,
                'title' => 'FO42333A26386 - arielreg (Wip)',
                'order_id' => 'FO42333A26386',
                'buyer_name' => 'arielreg',
                'service_type' => 'Full Website Build',
                'price' => 480.00,
                'net_value' => 384.00,
                'status' => 'in_progress',
                'progress_percent' => 90,
                'start_date' => '2026-10-01',
                'deadline' => '2026-10-25 18:00:00',
                'team_name' => 'WpCraft',
                'created_by' => 1,
                'notes' => 'Best Builders Group site corrections, staging verification, Twilio & MasterBrand setup',
                'priority' => 'high'
            ],
            [
                'id' => 2,
                'title' => 'FO71882B91024 - atiba15 (Wip)',
                'order_id' => 'FO71882B91024',
                'buyer_name' => 'atiba15',
                'service_type' => 'Custom WooCommerce Development',
                'price' => 400.00,
                'net_value' => 320.00,
                'status' => 'in_progress',
                'progress_percent' => 50,
                'start_date' => '2026-10-10',
                'deadline' => '2026-10-28 20:00:00',
                'team_name' => 'WpCraft',
                'created_by' => 1,
                'notes' => 'Custom WooCommerce portal integration ($250 base + $150 milestone)',
                'priority' => 'medium'
            ],
            [
                'id' => 3,
                'title' => 'FO19928C11099 - sunnychan450 (Delivered)',
                'order_id' => 'FO19928C11099',
                'buyer_name' => 'sunnychan450',
                'service_type' => 'Bug Fix & Theme Customization',
                'price' => 75.00,
                'net_value' => 60.00,
                'status' => 'completed',
                'progress_percent' => 100,
                'start_date' => '2026-10-05',
                'deadline' => '2026-10-12 12:00:00',
                'team_name' => 'WpCraft',
                'created_by' => 1,
                'notes' => 'Header layout fix and checkout responsiveness delivered and approved',
                'priority' => 'low'
            ]
        ];

        foreach ($projects as $proj) {
            $wpdb->replace($table, [
                'id' => $proj['id'],
                'title' => $proj['title'],
                'order_id' => $proj['order_id'],
                'buyer_name' => $proj['buyer_name'],
                'service_type' => $proj['service_type'],
                'price' => $proj['price'],
                'net_value' => $proj['net_value'],
                'status' => $proj['status'],
                'progress_percent' => $proj['progress_percent'],
                'start_date' => $proj['start_date'],
                'deadline' => $proj['deadline'],
                'team_name' => $proj['team_name'],
                'created_by' => $proj['created_by'],
                'notes' => $proj['notes'],
                'priority' => $proj['priority'],
                'extension_days' => 0,
                'extension_reason' => '',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]);

            // Assign to Saidur
            $wpdb->delete($assignees_table, ['project_id' => $proj['id']]);
            $wpdb->insert($assignees_table, [
                'project_id' => $proj['id'],
                'user_id' => $saidur_id,
                'created_at' => current_time('mysql')
            ]);
        }
    }

    /**
     * Seeds Reference A Member Portfolio details for Md. Saidur Rahman
     */
    public static function seed_member_profiles_data() {
        $saidur = get_user_by('login', 'md_saidur_rahman');
        if (!$saidur) {
            return;
        }
        $uid = $saidur->ID;

        update_user_meta($uid, 'ftm_custom_id', '1086');
        update_user_meta($uid, 'ftm_team', 'WpCraft');
        update_user_meta($uid, 'ftm_team_role', 'member');
        update_user_meta($uid, 'ftm_bio', 'Senior Full Stack WordPress Developer specializing in custom theme architecture, bespoke plugin development, WooCommerce solutions, REST API integrations, and scalable PHP/React web applications.');
        update_user_meta($uid, 'ftm_joined_date', '21 Jan 2026');
        update_user_meta($uid, 'ftm_skills', json_encode([
            'WordPress', 'Custom Themes', 'Plugin Development', 'Full Stack', 'PHP', 'React', 'WooCommerce', 'REST API', 'JavaScript', 'Tailwind CSS', 'Git'
        ]));
        update_user_meta($uid, 'ftm_achievements', json_encode([
            [
                'title' => 'Completed 50+ Projects',
                'date' => '24 Oct 2026',
                'description' => 'Successfully delivered over 50 client deliverables with flawless precision.'
            ],
            [
                'title' => 'Top Delivery Velocity Leader',
                'date' => '15 Sep 2026',
                'description' => 'Maintained top velocity across the agency with zero extensions in September.'
            ],
            [
                'title' => '100% 5-Star Quality Score',
                'date' => '01 Aug 2026',
                'description' => 'Recognized by management for outstanding customer satisfaction.'
            ]
        ]));
        update_user_meta($uid, 'ftm_journey', json_encode([
            [
                'date' => '21 Jan 2026',
                'title' => 'Joined FB International',
                'description' => 'Started development journey with core agency team.',
                'tag' => 'Joining',
                'color' => 'emerald'
            ],
            [
                'date' => '01 Aug 2026',
                'title' => 'Realigned to WpCraft Team',
                'description' => 'Transitioned to WpCraft to lead complex custom builds and high-value client portals.',
                'tag' => 'Team Restructure',
                'color' => 'blue'
            ],
            [
                'date' => '24 Oct 2026',
                'title' => 'Lead Full Stack Specialist',
                'description' => 'Directing key agency projects including Ariel BBG and Atiba WooCommerce.',
                'tag' => 'Milestone',
                'color' => 'purple'
            ]
        ]));

        // Transferred to WpHorizon in August 2026 (active agency colleagues in sister team)
        $horizon_roster = [
            'tahrima_billal_biva' => ['id' => '1027', 'name' => 'Tahrima Billal Biva', 'role' => 'leader', 'call' => 'Biva', 'title' => 'Team Leader | WpHorizon'],
            'nasim_uddin' => ['id' => '1069', 'name' => 'Nasim Uddin', 'role' => 'co-leader', 'call' => 'Nasim', 'title' => 'Co-Leader | WpHorizon'],
            'habibur_rahman_romel' => ['id' => '1049', 'name' => 'Habibur Rahman Romel', 'role' => 'member', 'call' => 'Romel', 'title' => 'WordPress Specialist | WpHorizon'],
            'abu_saeed' => ['id' => '1050', 'name' => 'Abu Saeed', 'role' => 'member', 'call' => 'Abu Saeed', 'title' => 'WordPress Specialist | WpHorizon']
        ];
        foreach ($horizon_roster as $h_login => $h_info) {
            $h_user = get_user_by('login', $h_login);
            if ($h_user) {
                update_user_meta($h_user->ID, 'ftm_custom_id', $h_info['id']);
                update_user_meta($h_user->ID, 'ftm_team', 'WpHorizon');
                update_user_meta($h_user->ID, 'ftm_team_role', $h_info['role']);
                update_user_meta($h_user->ID, 'ftm_status', 'Active');
                update_user_meta($h_user->ID, 'ftm_professional_title', $h_info['title']);
            }
        }

        // Former Members who departed the agency
        $redwan = get_user_by('login', 'sahariar_alam_redwan');
        if ($redwan) {
            update_user_meta($redwan->ID, 'ftm_custom_id', '1078');
            update_user_meta($redwan->ID, 'ftm_team', 'WpTitans');
            update_user_meta($redwan->ID, 'ftm_status', 'Resigned');
        }

        $bijoya = get_user_by('login', 'bijoya_sarkar');
        if ($bijoya) {
            update_user_meta($bijoya->ID, 'ftm_custom_id', '1088');
            update_user_meta($bijoya->ID, 'ftm_team', 'WpCraft'); // Realigned to WpCraft from August before departure
            update_user_meta($bijoya->ID, 'ftm_status', 'Resigned');
        }
    }
}
