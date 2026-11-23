<?php
/**
 * FTM Isolation Controller
 *
 * Manages isolated teams and members (archived / isolation mode)
 * allowing 100% data preservation and full restorability on demand.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Isolation_Controller extends FTM_REST_Controller {

    public function register_routes() {
        register_rest_route($this->namespace, '/isolation/summary', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_isolation_summary'],
            'permission_callback' => [$this, 'check_executive_or_admin_permission']
        ]);

        register_rest_route($this->namespace, '/isolation/projects', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_isolated_projects'],
            'permission_callback' => [$this, 'check_executive_or_admin_permission']
        ]);

        register_rest_route($this->namespace, '/isolation/restore', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'restore_isolated_team'],
            'permission_callback' => [$this, 'check_admin_permission']
        ]);
    }

    /**
     * Get summary of all isolated teams, projects, and members
     */
    public function get_isolation_summary($request) {
        global $wpdb;

        $iso_p_table = $wpdb->prefix . 'ftm_isolated_projects';
        
        // Ensure isolation table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$iso_p_table'") === $iso_p_table;
        if (!$table_exists) {
            return rest_ensure_response([
                'success' => true,
                'total_isolated_projects' => 0,
                'total_isolated_members' => 0,
                'total_isolated_value' => 0.0,
                'isolated_teams' => []
            ]);
        }

        // Distinct isolated teams
        $teams = $wpdb->get_col("SELECT DISTINCT team_name FROM $iso_p_table WHERE team_name <> '' ORDER BY team_name ASC");
        if (empty($teams)) {
            $teams = ['WpHorizon'];
        }

        $isolated_teams = [];
        $grand_projects = 0;
        $grand_value = 0.0;
        $grand_members = 0;

        foreach ($teams as $team) {
            $projects = $wpdb->get_results($wpdb->prepare(
                "SELECT id, order_id, client_name, assigned_user_id, status, net_delivery_value, delivery_date FROM $iso_p_table WHERE team_name = %s",
                $team
            ));

            $p_count = count($projects);
            $t_value = 0.0;
            $wip_count = 0;
            $wip_value = 0.0;
            $completed_count = 0;
            $completed_value = 0.0;

            foreach ($projects as $p) {
                $val = floatval($p->net_delivery_value);
                $t_value += $val;
                if ($p->status === 'wip') {
                    $wip_count++;
                    $wip_value += $val;
                } elseif (in_array($p->status, ['delivery_accepted', 'completed', 'delivered'], true)) {
                    $completed_count++;
                    $completed_value += $val;
                }
            }

            // Find isolated members belonging to this team
            $members_raw = get_users([
                'meta_key' => 'ftm_isolated_team',
                'meta_value' => $team
            ]);

            // Fallback: check ftm_team if ftm_status is Isolated
            if (empty($members_raw)) {
                $members_raw = get_users([
                    'meta_key' => 'ftm_status',
                    'meta_value' => 'Isolated'
                ]);
            }

            $members = [];
            foreach ($members_raw as $u) {
                $uid = (int)$u->ID;
                $m_team = get_user_meta($uid, 'ftm_isolated_team', true) ?: get_user_meta($uid, 'ftm_team', true);
                if (strcasecmp($m_team, $team) !== 0) {
                    continue;
                }
                $members[] = [
                    'id' => $uid,
                    'name' => $u->display_name,
                    'user_login' => $u->user_login,
                    'role' => get_user_meta($uid, 'ftm_team_role', true) ?: 'member',
                    'title' => get_user_meta($uid, 'ftm_professional_title', true) ?: '',
                    'status' => get_user_meta($uid, 'ftm_status', true) ?: 'Isolated'
                ];
            }

            $grand_projects += $p_count;
            $grand_value += $t_value;
            $grand_members += count($members);

            $isolated_teams[] = [
                'team_name' => $team,
                'project_count' => $p_count,
                'total_value' => round($t_value, 2),
                'wip_count' => $wip_count,
                'wip_value' => round($wip_value, 2),
                'completed_count' => $completed_count,
                'completed_value' => round($completed_value, 2),
                'members' => $members
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'total_isolated_projects' => $grand_projects,
            'total_isolated_members' => $grand_members,
            'total_isolated_value' => round($grand_value, 2),
            'isolated_teams' => $isolated_teams
        ]);
    }

    /**
     * Get isolated projects list
     */
    public function get_isolated_projects($request) {
        global $wpdb;

        $iso_p_table = $wpdb->prefix . 'ftm_isolated_projects';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$iso_p_table'") === $iso_p_table;
        if (!$table_exists) {
            return rest_ensure_response(['success' => true, 'total' => 0, 'projects' => []]);
        }

        $team = sanitize_text_field($request->get_param('team') ?: 'WpHorizon');
        $search = sanitize_text_field($request->get_param('search') ?: '');

        $where = ["team_name = %s"];
        $params = [$team];

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = "(order_id LIKE %s OR client_name LIKE %s OR project_notes LIKE %s)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT * FROM $iso_p_table WHERE " . implode(' AND ', $where) . " ORDER BY id DESC";
        $projects = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        return rest_ensure_response([
            'success' => true,
            'total' => count($projects),
            'projects' => $projects
        ]);
    }

    /**
     * Restore an isolated team back to active operations (Super Admin only)
     */
    public function restore_isolated_team($request) {
        global $wpdb;

        $team = sanitize_text_field($request->get_param('team_name') ?: 'WpHorizon');

        $p_table = $wpdb->prefix . 'ftm_projects';
        $a_table = $wpdb->prefix . 'ftm_project_assignees';
        $t_table = $wpdb->prefix . 'ftm_project_tasks';
        $h_table = $wpdb->prefix . 'ftm_project_history';

        $iso_p_table = $wpdb->prefix . 'ftm_isolated_projects';
        $iso_a_table = $wpdb->prefix . 'ftm_isolated_project_assignees';
        $iso_t_table = $wpdb->prefix . 'ftm_isolated_project_tasks';
        $iso_h_table = $wpdb->prefix . 'ftm_isolated_project_history';

        $pids = $wpdb->get_col($wpdb->prepare("SELECT id FROM $iso_p_table WHERE team_name = %s", $team));

        if (empty($pids)) {
            return new WP_Error('not_found', __("No isolated projects found for team '{$team}'.", 'fiverr-team-management'), ['status' => 404]);
        }

        $pids_in = implode(',', array_map('intval', $pids));

        // 1. Move back to active projects
        $wpdb->query("INSERT IGNORE INTO $p_table SELECT * FROM $iso_p_table WHERE id IN ($pids_in)");
        if ($wpdb->get_var("SHOW TABLES LIKE '$iso_a_table'") === $iso_a_table) {
            $wpdb->query("INSERT IGNORE INTO $a_table SELECT * FROM $iso_a_table WHERE project_id IN ($pids_in)");
        }
        if ($wpdb->get_var("SHOW TABLES LIKE '$iso_t_table'") === $iso_t_table) {
            $wpdb->query("INSERT IGNORE INTO $t_table SELECT * FROM $iso_t_table WHERE project_id IN ($pids_in)");
        }
        if ($wpdb->get_var("SHOW TABLES LIKE '$iso_h_table'") === $iso_h_table) {
            $wpdb->query("INSERT IGNORE INTO $h_table SELECT * FROM $iso_h_table WHERE project_id IN ($pids_in)");
        }

        // 2. Remove from isolation vault
        $wpdb->query("DELETE FROM $iso_p_table WHERE id IN ($pids_in)");
        if ($wpdb->get_var("SHOW TABLES LIKE '$iso_a_table'") === $iso_a_table) {
            $wpdb->query("DELETE FROM $iso_a_table WHERE project_id IN ($pids_in)");
        }
        if ($wpdb->get_var("SHOW TABLES LIKE '$iso_t_table'") === $iso_t_table) {
            $wpdb->query("DELETE FROM $iso_t_table WHERE project_id IN ($pids_in)");
        }
        if ($wpdb->get_var("SHOW TABLES LIKE '$iso_h_table'") === $iso_h_table) {
            $wpdb->query("DELETE FROM $iso_h_table WHERE project_id IN ($pids_in)");
        }

        // 3. Restore members status back to Active
        $members = get_users([
            'meta_key' => 'ftm_isolated_team',
            'meta_value' => $team
        ]);
        if (empty($members)) {
            $members = get_users([
                'meta_key' => 'ftm_status',
                'meta_value' => 'Isolated'
            ]);
        }

        $restored_members = 0;
        foreach ($members as $u) {
            $uid = (int)$u->ID;
            $m_team = get_user_meta($uid, 'ftm_isolated_team', true) ?: get_user_meta($uid, 'ftm_team', true);
            if (strcasecmp($m_team, $team) === 0) {
                update_user_meta($uid, 'ftm_status', 'Active');
                delete_user_meta($uid, 'ftm_is_isolated');
                delete_user_meta($uid, 'ftm_isolated_at');
                delete_user_meta($uid, 'ftm_isolated_team');
                $restored_members++;
            }
        }

        return rest_ensure_response([
            'success' => true,
            'message' => sprintf(__("Team '%s' successfully restored from isolation vault! %d projects and %d members returned to active operations.", 'fiverr-team-management'), $team, count($pids), $restored_members),
            'restored_projects_count' => count($pids),
            'restored_members_count' => $restored_members
        ]);
    }
}
