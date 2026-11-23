<?php
/**
 * FTM Server-Side Permissions and Access Control Service
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Permissions {

    /**
     * Executive / Deputy Manager: sits above the Operations Manager, can see everything but cannot change anything.
     * Special role executive who can see the report of each member, each team, each project across all scopes in read-only mode.
     * Read-only is enforced server-side for every non-GET ftm/v1 request (see ftm_deputy_write_guard).
     */
    public static function is_executive($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return false;
        }
        $role = strtolower((string)get_user_meta($user_id, 'ftm_team_role', true));
        if (in_array($role, ['executive', 'deputy', 'management', 'ftm_executive', 'ftm_deputy', 'ftm_management'], true)) {
            return true;
        }
        $role2 = strtolower((string)get_user_meta($user_id, 'ftm_role', true));
        if (in_array($role2, ['executive', 'deputy', 'management'], true)) {
            return true;
        }
        $user = get_userdata($user_id);
        if ($user) {
            $roles = (array)$user->roles;
            if (in_array('executive', $roles, true) || in_array('ftm_executive', $roles, true) ||
                in_array('deputy', $roles, true) || in_array('ftm_deputy', $roles, true) ||
                in_array('management', $roles, true) || in_array('ftm_management', $roles, true)) {
                return true;
            }
        }
        return false;
    }

    public static function is_deputy($user_id = null) {
        return self::is_executive($user_id);
    }

    /** Management accounts (Operations Manager, Deputy Manager) belong to no production team. 'Team Elite' is the legacy name. */
    public static function is_management_team($team) {
        $t = strtolower(trim((string)$team));
        return $t === 'management' || strpos($t, 'elite') !== false;
    }

    public static function is_super_admin($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return false;
        }

        // Deputy Manager is strictly a view-only executive observer, NOT a super admin.
        if (self::is_deputy($user_id)) {
            return false;
        }

        if ((int)$user_id === 1) {
            return true;
        }

        if (user_can($user_id, 'manage_options') || user_can($user_id, 'administrator')) {
            return true;
        }

        $ftm_role = get_user_meta($user_id, 'ftm_team_role', true);
        if (!$ftm_role) {
            $ftm_role = get_user_meta($user_id, 'ftm_role', true);
        }
        if ($ftm_role === 'admin') {
            return true;
        }

        $user = get_userdata($user_id);
        if ($user && in_array('administrator', (array)$user->roles, true)) {
            return true;
        }

        return false;
    }

    public static function is_leader($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return false;
        }

        // Deputy Manager is not a team leader
        if (self::is_deputy($user_id)) {
            return false;
        }

        if (self::is_super_admin($user_id)) {
            return true;
        }

        $ftm_role = get_user_meta($user_id, 'ftm_team_role', true);
        if (!$ftm_role) {
            $ftm_role = get_user_meta($user_id, 'ftm_role', true);
        }
        if (in_array($ftm_role, ['leader', 'co_leader'], true)) {
            return true;
        }

        $user = get_userdata($user_id);
        if ($user) {
            $wp_roles = (array)$user->roles;
            if (in_array('ftm_leader', $wp_roles, true) || in_array('ftm_co_leader', $wp_roles, true)) {
                return true;
            }
        }

        return false;
    }

    public static function get_user_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return 'guest';
        }

        if (self::is_executive($user_id)) {
            $r = strtolower((string)get_user_meta($user_id, 'ftm_team_role', true));
            if ($r === 'management' || strpos($r, 'management') !== false) {
                return 'management';
            }
            if ($r === 'executive' || strpos($r, 'executive') !== false) {
                return 'executive';
            }
            return 'deputy';
        }

        if (self::is_super_admin($user_id)) {
            return 'admin';
        }

        $role = get_user_meta($user_id, 'ftm_team_role', true);
        if (!$role) {
            $role = get_user_meta($user_id, 'ftm_role', true);
        }
        if ($role && in_array($role, ['admin', 'leader', 'co_leader', 'executive', 'deputy', 'management', 'member'], true)) {
            return $role;
        }

        $user = get_userdata($user_id);
        if ($user) {
            $wp_roles = (array)$user->roles;
            if (in_array('executive', $wp_roles, true) || in_array('ftm_executive', $wp_roles, true)) {
                return 'executive';
            }
            if (in_array('ftm_leader', $wp_roles, true)) {
                return 'leader';
            }
            if (in_array('ftm_co_leader', $wp_roles, true)) {
                return 'co_leader';
            }
            if (in_array('ftm_member', $wp_roles, true)) {
                return 'member';
            }
        }

        return 'member';
    }

    public static function get_role_label($role) {
        $labels = [
            'executive' => 'Executive Management',
            'deputy' => 'Deputy Manager',
            'management' => 'Management',
            'admin' => 'Super Admin / Manager',
            'leader' => 'Team Leader',
            'co_leader' => 'Co-Leader',
            'member' => 'WordPress Developer'
        ];
        return $labels[$role] ?? 'WordPress Developer';
    }

    public static function get_professional_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (self::is_deputy($user_id)) {
            $title = get_user_meta($user_id, 'ftm_professional_title', true);
            $fallback = self::get_user_role($user_id) === 'management' ? 'Management' : 'Deputy Manager';
            return $title ? preg_replace('/\s*\|.*$/', '', $title) : $fallback;
        }
        if (self::is_super_admin($user_id)) {
            return 'Super Admin & Operations Executive';
        }
        return 'WordPress Developer';
    }

    public static function get_leadership_label($user_id = null) {
        $role = self::get_user_role($user_id);
        if ($role === 'leader') {
            return 'Team Leader';
        }
        if ($role === 'co_leader') {
            return 'Co-Leader';
        }
        if ($role === 'deputy') {
            return 'Deputy Manager';
        }
        if ($role === 'management') {
            return 'Management';
        }
        if ($role === 'admin') {
            return 'Super Admin / Manager';
        }
        return '';
    }

    public static function get_user_team($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return 'WpCraft';
        }

        $team = get_user_meta($user_id, 'ftm_team', true);
        return $team ? $team : 'WpCraft';
    }

    /**
     * Enforce strict server-side team scoping
     */
    public static function can_access_team($team_name, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return false;
        }

        // Super Admin / Executive / Manager can access all teams
        if (self::is_super_admin($user_id) || self::is_executive($user_id)) {
            return true;
        }

        // Team Leaders and Members can only access their designated team
        $user_team = self::get_user_team($user_id);
        return (strcasecmp($user_team, $team_name) === 0);
    }

    /**
     * Enforce project level edit permission
     */
    /**
     * Enforce project level edit permission:
     * - Deputy: READ-ONLY (false)
     * - Super Admin: Full edit access (true)
     * - Leader / Co-Leader: Can ONLY edit projects belonging to their own team!
     * - Member: Cannot edit project structure/financials/assignees (false)
     */
    public static function can_change_project_status($project_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return false;
        }
        if (self::is_deputy($user_id)) {
            return false;
        }

        if (self::is_super_admin($user_id) || self::is_executive($user_id)) {
            return true;
        }

        global $wpdb;
        $project_team = $wpdb->get_var($wpdb->prepare(
            "SELECT team_name FROM {$wpdb->prefix}ftm_projects WHERE id = %d",
            $project_id
        ));
        $user_team = self::get_user_team($user_id);

        if (self::is_leader($user_id)) {
            return (strcasecmp($project_team, $user_team) === 0);
        }

        if ($user_team && $project_team && strcasecmp($project_team, $user_team) === 0) {
            return true;
        }

        // Regular assigned member can change status of their own project (including cross-team assignees)
        $is_assigned = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ftm_projects 
             WHERE id = %d AND (
                 assigned_user_id = %d 
                 OR FIND_IN_SET(%d, assigned_user_ids) 
                 OR id IN (SELECT project_id FROM {$wpdb->prefix}ftm_project_assignees WHERE user_id = %d)
             )",
            $project_id, $user_id, $user_id, $user_id
        ));
        return ((int)$is_assigned > 0);
    }

    public static function can_edit_project($project_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return false;
        }
        if (self::is_deputy($user_id)) {
            return false;
        }

        if (self::is_super_admin($user_id) || self::is_executive($user_id)) {
            return true;
        }

        if (self::is_leader($user_id)) {
            global $wpdb;
            $project_team = $wpdb->get_var($wpdb->prepare(
                "SELECT team_name FROM {$wpdb->prefix}ftm_projects WHERE id = %d",
                $project_id
            ));
            $user_team = self::get_user_team($user_id);
            return (strcasecmp($project_team, $user_team) === 0);
        }

        // Regular assigned members cannot edit project structure or metadata
        return false;
    }

    /**
     * Check if a user can extend a project's delivery deadline.
     * Allowed: Super Admin, Executives, Squad Leaders, and individual team members.
     * Only Deputy Manager is strictly view-only.
     */
    public static function can_extend_project($project_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return false;
        }
        // Deputy Manager is strictly a view-only executive role
        if (self::is_deputy($user_id)) {
            return false;
        }

        // Super admins, executives, leaders, and individual members are authorized to extend active delivery deadlines
        return true;
    }

    public static function can_create_project($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (self::is_deputy($user_id)) {
            return false;
        }
        return self::is_super_admin($user_id) || self::is_leader($user_id);
    }

    public static function can_delete_project($project_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (self::is_deputy($user_id)) {
            return false;
        }
        return self::is_super_admin($user_id);
    }

    public static function can_manage_members($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (self::is_deputy($user_id)) {
            return false;
        }
        return self::is_super_admin($user_id) || self::is_leader($user_id);
    }

    public static function can_view_monthly_reports($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return false;
        }
        return self::is_super_admin($user_id) || self::is_deputy($user_id) || self::is_leader($user_id);
    }

    public static function get_team_members($team_filter = 'all') {
        $users = get_users(['orderby' => 'display_name']);
        $output = [];
        foreach ($users as $u) {
            $uid = (int)$u->ID;
            $status = get_user_meta($uid, 'ftm_status', true);
            if (!empty($status) && in_array(strtolower($status), ['inactive', 'resigned', 'former', 'left', 'isolated'], true)) {
                continue;
            }

            // Deputy Manager is strictly a view-only executive, never assigned to any squad
            if (self::is_deputy($uid)) {
                continue;
            }

            $user_team = self::get_user_team($uid);
            $role = self::get_user_role($uid);
            $role_label = self::get_role_label($role);
            $custom_id = get_user_meta($uid, 'ftm_custom_id', true);

            // Super Admin (Pranto / ID 1) works globally across all squads
            $is_admin_worker = ($uid === 1 || $u->user_login === 'admin');

            if (self::is_management_team($user_team) && !$is_admin_worker) {
                continue;
            }

            if ($team_filter !== 'all' && strcasecmp($user_team, $team_filter) !== 0 && !$is_admin_worker) {
                continue;
            }

            $output[] = [
                'id' => $uid,
                'name' => $u->display_name,
                'team' => $user_team,
                'team_role' => $role,
                'role_label' => $role_label,
                'custom_id' => $custom_id ?: ('#' . $uid),
                'avatar' => get_avatar_url($uid, ['size' => 64])
            ];
        }
        return $output;
    }

    /**
     * Get team leader user IDs for a given team (primary leader first, co-leaders next)
     */
    public static function get_team_leaders_for_team($team_name) {
        if (empty($team_name) || strtolower($team_name) === 'management') {
            return [1];
        }

        $leaders = get_users([
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'ftm_team',
                    'value' => $team_name,
                    'compare' => '='
                ],
                [
                    'key' => 'ftm_team_role',
                    'value' => ['leader', 'co_leader'],
                    'compare' => 'IN'
                ]
            ],
            'fields' => 'ID'
        ]);

        if (!empty($leaders)) {
            usort($leaders, function($a, $b) {
                $ra = get_user_meta($a, 'ftm_team_role', true);
                $rb = get_user_meta($b, 'ftm_team_role', true);
                if ($ra === 'leader' && $rb !== 'leader') return -1;
                if ($rb === 'leader' && $ra !== 'leader') return 1;
                return 0;
            });
            return array_values(array_unique(array_map('intval', $leaders)));
        }

        return [1];
    }
}
