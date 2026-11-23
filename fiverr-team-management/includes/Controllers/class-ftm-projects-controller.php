<?php
/**
 * FTM Projects REST Controller (Extended with Financials & Non-Duplication)
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Projects_Controller extends FTM_REST_Controller {

    /**
     * Automatically derive on-time status from completion status and extension count.
     * Any extension (> 0) marks the project NOT on-time; completed with 0 extensions is On-Time.
     */
    public static function compute_delivered_without_extension($status, $extension_count, $extension_days = 0) {
        $ext = max(0, (int)$extension_count);
        $days = max(0, (int)$extension_days);
        if ($ext > 0 || $days > 0) {
            return 0;
        }
        return in_array(strtolower((string)$status), ['delivery_accepted', 'completed', 'delivered'], true) ? 1 : 0;
    }

    public function register_routes() {
        // Projects List & Creation
        register_rest_route($this->namespace, '/projects', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_projects'],
                'permission_callback' => [$this, 'check_auth_permission']
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_project'],
                'permission_callback' => [$this, 'check_auth_permission']
            ]
        ]);

        // Single Project Management
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_project'],
                'permission_callback' => [$this, 'check_auth_permission']
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_project'],
                'permission_callback' => [$this, 'check_leader_permission']
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_project'],
                'permission_callback' => [$this, 'check_leader_permission']
            ]
        ]);

        // Status Update
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)/review', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_review'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)/settlement', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_settlement'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)/tip', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_tip'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)/split', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_split'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)/status', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_status'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)/ontime', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_ontime'],
            'permission_callback' => [$this, 'check_leader_permission']
        ]);

        // Delivery Extension
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)/extend', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'extend_delivery'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Progress Percent Update
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)/progress', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_progress'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Assignees Management
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)/assignees', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_assignees'],
            'permission_callback' => [$this, 'check_leader_permission']
        ]);

        // Notes Management
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)/notes', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'add_note'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Statistics & Financial Totals
        register_rest_route($this->namespace, '/stats', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_stats'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);
    }

    public function get_projects($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_projects';
        $current_user_id = get_current_user_id();

        $page = max(1, (int)$request->get_param('page'));
        $per_page = max(1, min(500, (int)($request->get_param('per_page') ?: 100)));
        $offset = ($page - 1) * $per_page;

        $status = sanitize_text_field($request->get_param('status') ?: 'all');
        $search = sanitize_text_field($request->get_param('search') ?: '');
        $view_filter = sanitize_text_field($request->get_param('view_filter') ?: '');
        $requested_team = sanitize_text_field($request->get_param('team') ?: 'all');
        $requested_month = sanitize_text_field($request->get_param('month') ?: 'all');
        $start_date = sanitize_text_field($request->get_param('start_date') ?: '');
        $end_date = sanitize_text_field($request->get_param('end_date') ?: '');

        $where = ["1=1"];
        $params = [];

        // Month Filter & Carry Forward Scoping
        if ($requested_month === 'carry_forward') {
            $current_month_start = current_time('Y-m-01');
            $where[] = "delivery_date < %s AND status IN ('wip', 'nra', 'revision')";
            $params[] = $current_month_start;
        } elseif ($requested_month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $requested_month)) {
            $month_start = $requested_month . '-01';
            $month_end = date('Y-m-t', strtotime($month_start));
            
            // If the user is specifically searching by keyword/order_id, do not strictly restrict by month
            // so completed orders from past months can be easily found and updated!
            if (empty($search)) {
                // Scoping: include fresh assignments in this month, deliveries in this month,
                // orders with tips received in this month, and carry-forward active orders (wip, nra, revision).
                $where[] = "(
                    (created_at BETWEEN %s AND %s)
                    OR (delivery_date BETWEEN %s AND %s)
                    OR (delivered_date IS NOT NULL AND delivered_date BETWEEN %s AND %s)
                    OR (tip_amount > 0 AND tip_date IS NOT NULL AND tip_date BETWEEN %s AND %s)
                    OR (status IN ('wip', 'nra', 'revision') AND created_at <= %s)
                ) AND NOT (status IN ('delivered', 'delivery_accepted', 'completed', 'cancelled') AND COALESCE(delivered_date, delivery_date) < %s AND (tip_amount <= 0 OR tip_date IS NULL OR tip_date < %s))";
                $params[] = $month_start . ' 00:00:00';
                $params[] = $month_end . ' 23:59:59';
                $params[] = $month_start;
                $params[] = $month_end;
                $params[] = $month_start . ' 00:00:00';
                $params[] = $month_end . ' 23:59:59';
                $params[] = $month_start;
                $params[] = $month_end;
                $params[] = $month_end . ' 23:59:59';
                $params[] = $month_start;
                $params[] = $month_start;
            }
        }

        // Server-Side Team & Assignment Scoping Enforcement (Supporting Cross-Team Distribution)
        $assigned_param = $request->get_param('assigned');
        $member_id_param = $request->get_param('member_id');
        $is_lead_or_exec = FTM_Permissions::is_leader($current_user_id) || FTM_Permissions::is_executive($current_user_id);
        $is_my_assigned_query = ($view_filter === 'my' || !empty($assigned_param) || $member_id_param === 'mine' || !$is_lead_or_exec);

        if (!FTM_Permissions::is_super_admin($current_user_id) && !FTM_Permissions::is_executive($current_user_id)) {
            $user_team = FTM_Permissions::get_user_team($current_user_id);
            if ($is_my_assigned_query) {
                // When viewing assigned projects or for specialists: include projects assigned to this user across ANY team
                $where[] = "(team_name = %s OR assigned_user_id = %d OR FIND_IN_SET(%d, assigned_user_ids) OR id IN (SELECT project_id FROM {$wpdb->prefix}ftm_project_assignees WHERE user_id = %d))";
                $params[] = $user_team;
                $params[] = $current_user_id;
                $params[] = $current_user_id;
                $params[] = $current_user_id;
            } else {
                // Team Leaders viewing all projects: show projects owned by their squad OR projects where squad members are assigned
                $where[] = "(team_name = %s OR id IN (
                    SELECT fa.project_id FROM {$wpdb->prefix}ftm_project_assignees fa 
                    JOIN {$wpdb->usermeta} um ON um.user_id = fa.user_id 
                    WHERE um.meta_key = 'ftm_team' AND um.meta_value = %s
                ))";
                $params[] = $user_team;
                $params[] = $user_team;
            }
        } elseif ($requested_team !== 'all' && !empty($requested_team)) {
            $where[] = "(team_name = %s OR id IN (
                SELECT fa.project_id FROM {$wpdb->prefix}ftm_project_assignees fa 
                JOIN {$wpdb->usermeta} um ON um.user_id = fa.user_id 
                WHERE um.meta_key = 'ftm_team' AND um.meta_value = %s
            ))";
            $params[] = $requested_team;
            $params[] = $requested_team;
        }

        // Date Range Filtering (Reference B Standard)
        if (!empty($start_date) && !empty($end_date)) {
            $where[] = "delivery_date BETWEEN %s AND %s";
            $params[] = $start_date;
            $params[] = $end_date;
        } elseif (!empty($start_date)) {
            $where[] = "delivery_date >= %s";
            $params[] = $start_date;
        } elseif (!empty($end_date)) {
            $where[] = "delivery_date <= %s";
            $params[] = $end_date;
        } else {
            // Default: hide delivered/completed projects prior to October 2026 from live lists (keep open WIP/NRA/revisions)
            $where[] = "(delivery_date >= '2026-10-01' OR status IN ('wip', 'nra', 'revision'))";
        }

        // View filter for "my projects", "assigned=1", "member_id=mine", or non-leaders
        if ($is_my_assigned_query) {
            $where[] = "(assigned_user_id = %d OR FIND_IN_SET(%d, assigned_user_ids) OR id IN (SELECT project_id FROM {$wpdb->prefix}ftm_project_assignees WHERE user_id = %d))";
            $params[] = $current_user_id;
            $params[] = $current_user_id;
            $params[] = $current_user_id;
        } elseif (!empty($member_id_param) && is_numeric($member_id_param) && (int)$member_id_param > 0) {
            $target_mid = (int)$member_id_param;
            $where[] = "(assigned_user_id = %d OR FIND_IN_SET(%d, assigned_user_ids) OR id IN (SELECT project_id FROM {$wpdb->prefix}ftm_project_assignees WHERE user_id = %d))";
            $params[] = $target_mid;
            $params[] = $target_mid;
            $params[] = $target_mid;
        }

        if ($view_filter === 'overdue') {
            $today = current_time('Y-m-d');
            $where[] = "delivery_date < %s AND status NOT IN ('delivery_accepted', 'delivery_submitted', 'cancelled')";
            $params[] = $today;
        } elseif ($view_filter === 'due_today') {
            $today = current_time('Y-m-d');
            $where[] = "delivery_date = %s AND status NOT IN ('delivery_accepted', 'delivery_submitted', 'cancelled')";
            $params[] = $today;
        }

        if ($status !== 'all' && !empty($status)) {
            if ($status === 'submitted' || $status === 'delivery_submitted') {
                $where[] = "(status = 'submitted' OR status = 'delivery_submitted')";
            } elseif ($status === 'accepted' || $status === 'delivery_accepted') {
                $where[] = "(status = 'accepted' OR status = 'delivery_accepted' OR status = 'completed')";
            } elseif ($status === 'active') {
                $where[] = "status IN ('wip', 'nra')";
            } elseif ($status === 'delivered_all' || $status === 'completed_all') {
                $where[] = "status IN ('delivery_submitted', 'submitted', 'delivery_accepted', 'accepted', 'completed', 'delivered')";
            } elseif ($status === 'repeat' || $status === 'repeat_client') {
                $where[] = "order_source = 'Repeat Client'";
            } else {
                $where[] = "status = %s";
                $params[] = $status;
            }
        }

        if (!empty($search)) {
            $wildcard = '%' . $wpdb->esc_like($search) . '%';
            $matching_uids = $wpdb->get_col($wpdb->prepare(
                "SELECT ID FROM {$wpdb->users} WHERE display_name LIKE %s OR user_login LIKE %s",
                $wildcard, $wildcard
            ));
            if (!empty($matching_uids)) {
                $user_or_clauses = [];
                foreach ($matching_uids as $muid) {
                    $user_or_clauses[] = "(assigned_user_id = " . (int)$muid . " OR FIND_IN_SET(" . (int)$muid . ", assigned_user_ids))";
                }
                $where[] = "(client_name LIKE %s OR order_id LIKE %s OR profile_name LIKE %s OR " . implode(' OR ', $user_or_clauses) . ")";
            } else {
                $where[] = "(client_name LIKE %s OR order_id LIKE %s OR profile_name LIKE %s)";
            }
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
        }

        // Filter options (clients, teams) reflect what this user can see, before the client filter is applied.
        $scope_sql = implode(' AND ', $where);
        $scope_params = $params;
        $client_filter = sanitize_text_field($request->get_param('client') ?: 'all');
        if ($client_filter !== 'all' && $client_filter !== '') {
            $where[] = "client_name = %s";
            $params[] = $client_filter;
        }

        $where_sql = implode(' AND ', $where);

        $total_query = "SELECT COUNT(*) FROM $table WHERE $where_sql";
        $total = !empty($params) ? (int)$wpdb->get_var($wpdb->prepare($total_query, $params)) : (int)$wpdb->get_var($total_query);

        $sort = sanitize_text_field($request->get_param('sort') ?: 'delivery_asc');
        // Default: open work first (earliest deadline on top), finished orders below (most recently delivered first)
        $order_by = "(status IN ('delivery_accepted', 'delivery_submitted', 'cancelled')) ASC, CASE WHEN status IN ('delivery_accepted', 'delivery_submitted', 'cancelled') THEN NULL ELSE delivery_date END ASC, CASE WHEN status IN ('delivery_accepted', 'delivery_submitted', 'cancelled') THEN COALESCE(delivered_date, delivery_date) END DESC";
        if ($sort === 'delivery_desc') {
            $order_by = "delivery_date DESC";
        } elseif ($sort === 'created_desc') {
            $order_by = "id DESC";
        }

        // Backfilled (seeded) records always sink below real projects.
        $data_query = "SELECT * FROM $table WHERE $where_sql ORDER BY (order_id LIKE 'SEED-%%') ASC, $order_by, id DESC LIMIT %d OFFSET %d";
        $p_params = $params;
        $p_params[] = $per_page;
        $p_params[] = $offset;

        $items = $wpdb->get_results($wpdb->prepare($data_query, $p_params));

        // Format items with days remaining, assignees avatar stack, and permissions
        $formatted = array_map([$this, 'format_project_item'], $items);

        $client_sql = "SELECT DISTINCT client_name FROM $table WHERE $scope_sql AND client_name <> '' ORDER BY client_name ASC LIMIT 300";
        $team_sql = "SELECT DISTINCT team_name FROM $table WHERE $scope_sql AND team_name <> '' ORDER BY team_name ASC";
        $client_options = !empty($scope_params) ? $wpdb->get_col($wpdb->prepare($client_sql, $scope_params)) : $wpdb->get_col($client_sql);
        $team_options = !empty($scope_params) ? $wpdb->get_col($wpdb->prepare($team_sql, $scope_params)) : $wpdb->get_col($team_sql);

        return rest_ensure_response([
            'items' => $formatted,
            'clients' => array_values($client_options),
            'teams' => array_values($team_options),
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'pages' => ceil($total / $per_page)
        ]);
    }

    public function create_project($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_projects';

        $current_user_id = get_current_user_id();
        if (FTM_Permissions::is_deputy($current_user_id)) {
            return new WP_Error('rest_forbidden', 'Deputy Manager has read-only access.', ['status' => 403]);
        }
        $is_admin = FTM_Permissions::is_super_admin($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id);
        $user_team = FTM_Permissions::get_user_team($current_user_id);

        if (!$is_admin && !$is_leader) {
            return new WP_Error('rest_forbidden', 'Only Super Admin and Team Leaders can add new projects.', ['status' => 403]);
        }

        $data = $request->get_json_params() ?: $request->get_params() ?: [];
        $team = sanitize_text_field($data['team_name'] ?? $data['team'] ?? $user_team);
        if (!$is_admin) {
            $team = $user_team; // Leaders can only add projects to their own assigned team
        }

        // Validate team permissions
        if (!FTM_Permissions::can_access_team($team, $current_user_id)) {
            return $this->format_error(__('Unauthorized team access.', 'fiverr-team-management'), 403);
        }

        $gross = (float)($data['gross_order_value'] ?? $data['order_value'] ?? 0.00);
        $fee_percent = (isset($data['fiverr_fee_percent']) && $data['fiverr_fee_percent'] !== '') ? max(0.0, (float)$data['fiverr_fee_percent']) : 20.00;
        $financials = FTM_Financial::calculate_breakdown($gross, $fee_percent);

        $assigned_ids = (array)($data['assigned_user_ids'] ?? []);
        if (empty($assigned_ids) && !empty($data['assignees']) && is_array($data['assignees'])) {
            foreach ($data['assignees'] as $a) {
                if (is_array($a) && isset($a['user_id'])) {
                    $assigned_ids[] = (int)$a['user_id'];
                } elseif (is_numeric($a)) {
                    $assigned_ids[] = (int)$a;
                }
            }
        }
        if (empty($assigned_ids) && !$is_admin && !$is_leader) {
            $assigned_ids = [$current_user_id];
        }
        $primary_id = !empty($assigned_ids) ? (int)$assigned_ids[0] : (int)($data['assigned_user_id'] ?? $current_user_id);

        $ext_count = max(0, (int)($data['extension_count'] ?? 0));
        $ext_days = max(0, (int)($data['extension_days'] ?? 0));
        $status_val = sanitize_text_field($data['status'] ?? 'nra');
        $is_completed_status = in_array(strtolower($status_val), ['delivery_accepted', 'completed', 'delivered'], true);
        $has_ext = ($ext_count > 0 || $ext_days > 0);
        $auto_ontime = ($is_completed_status && !$has_ext) ? 1 : 0;
        $ontime_flag = isset($data['is_ontime']) ? (!empty($data['is_ontime']) ? 1 : 0) : $auto_ontime;

        $insert_data = [
            'team_name' => $team,
            'fiverr_profile_id' => (int)($data['fiverr_profile_id'] ?? 0),
            'profile_name' => sanitize_text_field($data['profile_name'] ?? ''),
            'client_name' => sanitize_text_field($data['client_name'] ?? $data['buyer_username'] ?? ''),
            'order_id' => sanitize_text_field($data['order_id'] ?? ''),
            'gross_order_value' => $financials['gross_order_value'],
            'fiverr_fee_percent' => $financials['fiverr_fee_percent'],
            'fiverr_fee_amount' => $financials['fiverr_fee_amount'],
            'net_delivery_value' => $financials['net_delivery_value'],
            'order_total_value' => round(max(0, (float)($data['order_total_value'] ?? 0)), 2),
            'order_type' => $this->clean_order_type($data['order_type'] ?? 'standard'),
            'tip_amount' => max(0.0, (float)($data['tip_amount'] ?? 0.00)),
            'delivery_date' => sanitize_text_field($data['delivery_date'] ?? date('Y-m-d', strtotime('+3 days'))),
            'assigned_user_id' => $primary_id,
            'assigned_user_ids' => implode(',', array_map('intval', $assigned_ids)),
            'status' => $status_val,
            'progress_percent' => max(0, min(100, (int)($data['progress_percent'] ?? $data['progress'] ?? 0))),
            'project_notes' => sanitize_textarea_field($data['project_notes'] ?? $data['notes'] ?? ''),
            'client_reviewed' => in_array($status_val, ['delivery_accepted', 'completed'], true) ? sanitize_text_field($data['client_reviewed'] ?? 'no') : 'no',
            'star_rating' => (in_array($status_val, ['delivery_accepted', 'completed'], true) && ($data['client_reviewed'] ?? 'no') === 'yes') ? round(max(1, min(5, (float)($data['star_rating'] ?? 5))), 1) : 0,
            'review_feedback' => sanitize_textarea_field($data['review_feedback'] ?? ''),
            'extension_count' => $ext_count,
            'extension_days' => $ext_days,
            'extension_reason' => sanitize_text_field($data['extension_reason'] ?? ''),
            'is_ontime' => $ontime_flag,
            'delivered_without_extension' => $ontime_flag,
            'project_sheet_link' => sanitize_text_field($data['project_sheet_link'] ?? ($data['sheet_link'] ?? '')),
            'order_link' => sanitize_text_field($data['order_link'] ?? ''),
            'order_source' => sanitize_text_field($data['order_source'] ?? 'Query'),
            'seller_name' => sanitize_text_field($data['seller_name'] ?? ''),
            'remarks' => sanitize_textarea_field($data['remarks'] ?? '')
        ];

        if (!empty($data['assign_date'])) {
            $insert_data['created_at'] = sanitize_text_field($data['assign_date']) . ' 10:00:00';
        }
        if (!empty($data['delivered_date'])) {
            $raw_del = sanitize_text_field($data['delivered_date']);
            if (strlen($raw_del) === 10) $raw_del .= ' 18:00:00';
            $insert_data['delivered_date'] = $raw_del;
        }
        if (!empty($data['tip_date'])) {
            $insert_data['tip_date'] = sanitize_text_field($data['tip_date']);
        }

        $insert_data = array_merge($insert_data, $this->subscription_fields($data, $insert_data['order_type']));
        $wpdb->insert($table, $insert_data);
        $project_id = $wpdb->insert_id;

        // Auto-generate standard 4-stage milestone pipeline tasks (5h initial convo, 2-3d draft, fixing, final update)
        // and 4 Dedicated Team Leader Quality Assurance & Approval tasks
        if (class_exists('FTM_Tasks_Controller')) {
            FTM_Tasks_Controller::auto_generate_project_milestones($project_id, $primary_id, $insert_data['delivery_date'], $current_user_id);
            FTM_Tasks_Controller::auto_generate_leader_approval_tasks($project_id, $insert_data['team_name'] ?? '', $insert_data['delivery_date'] ?? '', $current_user_id);
        }
        if (isset($data['splits'])) {
            $this->save_splits($project_id, $data['splits']);
        }

        // Sync assignees table
        $assignees_table = $wpdb->prefix . 'ftm_project_assignees';
        $effective_assigned = !empty($assigned_ids) ? $assigned_ids : [$primary_id];
        $shares = $this->assignee_shares($effective_assigned, $data);
        foreach ($effective_assigned as $idx => $uid) {
            $wpdb->insert($assignees_table, [
                'project_id' => $project_id,
                'user_id' => (int)$uid,
                'assignment_role' => ($idx === 0) ? 'primary_lead' : 'contributor',
                'split_percentage' => $shares[(int)$uid] ?? round(100 / max(1, count($effective_assigned)), 2)
            ]);
        }

        // Log creation in history
        $wpdb->insert($wpdb->prefix . 'ftm_project_history', [
            'project_id' => $project_id,
            'user_id' => get_current_user_id(),
            'user_name' => wp_get_current_user()->display_name,
            'old_status' => '',
            'new_status' => $insert_data['status'],
            'note' => 'Project created and assigned to ' . $this->assignee_names($assigned_ids) . '.'
        ]);

        if (class_exists('FTM_Mailer')) {
            FTM_Mailer::send_project_assigned_email($project_id, $effective_assigned, true);
        }

        return $this->format_success(['id' => $project_id], __('Project created successfully.', 'fiverr-team-management'));
    }

    public function get_project($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $project = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ftm_projects WHERE id = %d", $id));

        if (!$project) {
            return $this->format_error(__('Project not found.', 'fiverr-team-management'), 404);
        }

        $history = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ftm_project_history WHERE project_id = %d ORDER BY id DESC",
            $id
        ));

        $notes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ftm_project_notes WHERE project_id = %d ORDER BY id DESC",
            $id
        ));

        $formatted_notes = array_map(function($n) {
            $name = get_the_author_meta('display_name', $n->user_id) ?: 'Team Member';
            $custom_avatar = get_user_meta($n->user_id, 'ftm_custom_avatar', true);
            $avatar_url = !empty($custom_avatar) ? $custom_avatar : ('https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=2563eb&color=fff&size=128&bold=true');
            return [
                'id' => $n->id,
                'user_name' => $name,
                'user_avatar' => $avatar_url,
                'note' => esc_html($n->note),
                'date_formatted' => date('M j, Y g:i A', strtotime($n->created_at))
            ];
        }, $notes);

        $formatted_history = array_map(function($h) {
            return [
                'id' => $h->id,
                'user_name' => $h->user_name,
                'new_status' => $h->new_status,
                'new_label' => ucwords(str_replace('_', ' ', $h->new_status)),
                'is_event' => ($h->old_status !== '' && $h->old_status === $h->new_status),
                'note' => (string)$h->note,
                'date_formatted' => date('M j, Y g:i A', strtotime($h->created_at))
            ];
        }, $history);

        // Daily Progress Logs recorded for this project
        $daily_logs = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, r.report_date, r.user_id, u.display_name as member_name
             FROM {$wpdb->prefix}ftm_daily_report_items i
             JOIN {$wpdb->prefix}ftm_daily_reports r ON i.report_id = r.id
             LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
             WHERE i.project_id = %d OR (i.order_id = %s AND i.order_id != '')
             ORDER BY r.report_date DESC, i.id DESC",
            $id, $project->order_id
        ));

        $formatted_daily_logs = array_map(function($l) {
            $mem_name = $l->member_name ?: 'Specialist';
            $custom_avatar = get_user_meta($l->user_id, 'ftm_custom_avatar', true);
            $avatar_url = !empty($custom_avatar) ? $custom_avatar : ('https://ui-avatars.com/api/?name=' . urlencode($mem_name) . '&background=2563eb&color=fff&size=128&bold=true');
            return [
                'id' => (int)$l->id,
                'member_name' => $mem_name,
                'member_avatar' => $avatar_url,
                'report_date' => date('M j, Y', strtotime($l->report_date)),
                'tasks_completed' => html_entity_decode(wp_unslash($l->tasks_completed), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'work_in_progress' => html_entity_decode(wp_unslash($l->work_in_progress), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'next_day_plan' => html_entity_decode(wp_unslash($l->next_day_plan), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'hours_spent' => (float)$l->hours_spent,
                'progress_percent' => (int)$l->progress_percent,
                'has_blocker' => (bool)$l->has_blocker,
                'blocker_description' => html_entity_decode(wp_unslash($l->blocker_description), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            ];
        }, $daily_logs);

        $tasks_data = null;
        $leader_approvals = null;
        if (class_exists('FTM_Tasks_Controller')) {
            $tasks_ctrl = new FTM_Tasks_Controller();
            $tasks_data = $tasks_ctrl->get_project_tasks_data($project->id);
            $leader_approvals = $tasks_data['leader_approvals'] ?? FTM_Tasks_Controller::get_leader_approvals_summary($project->id);
        }

        return rest_ensure_response([
            'project' => $this->format_project_item($project),
            'combined' => $this->combined_summary($project),
            'assignee_shares' => $this->get_assignee_shares($project->id),
            'history' => $formatted_history,
            'notes_list' => $formatted_notes,
            'daily_logs' => $formatted_daily_logs,
            'tasks' => $tasks_data,
            'leader_approvals' => $leader_approvals
        ]);
    }

    public function update_project($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $table = $wpdb->prefix . 'ftm_projects';
        $project = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

        if (!$project) {
            return $this->format_error(__('Project not found.', 'fiverr-team-management'), 404);
        }

        if (!FTM_Permissions::can_edit_project($id)) {
            return $this->format_error(__('Unauthorized project edit.', 'fiverr-team-management'), 403);
        }

        $data = $request->get_json_params() ?: $request->get_params() ?: [];
        $team = !empty($data['team_name']) ? sanitize_text_field($data['team_name']) : $project->team_name;

        $current_user_id = get_current_user_id();
        $is_admin_or_leader = FTM_Permissions::is_super_admin($current_user_id) || FTM_Permissions::is_leader($current_user_id) || FTM_Permissions::is_executive($current_user_id);
        if (!$is_admin_or_leader && !FTM_Permissions::can_access_team($team)) {
            return $this->format_error(__('Unauthorized team change.', 'fiverr-team-management'), 403);
        }

        // Financials: recalculate if gross is passed, or preserve existing
        $gross = isset($data['gross_order_value']) ? (float)$data['gross_order_value'] : (float)$project->gross_order_value;
        $fee_percent = (isset($data['fiverr_fee_percent']) && $data['fiverr_fee_percent'] !== '') ? max(0.0, (float)$data['fiverr_fee_percent']) : (float)$project->fiverr_fee_percent;
        $financials = FTM_Financial::calculate_breakdown($gross, $fee_percent);

        if (isset($data['assigned_user_ids']) && is_array($data['assigned_user_ids']) && !empty(array_filter(array_map('intval', $data['assigned_user_ids'])))) {
            $assigned_ids = array_values(array_filter(array_map('intval', $data['assigned_user_ids'])));
            $primary_id = $assigned_ids[0];
        } else {
            $assigned_ids = array_values(array_filter(array_map('intval', explode(',', (string)$project->assigned_user_ids))));
            if (empty($assigned_ids) && (int)$project->assigned_user_id > 0) {
                $assigned_ids = [(int)$project->assigned_user_id];
            }
            $primary_id = !empty($assigned_ids) ? $assigned_ids[0] : (int)$project->assigned_user_id;
        }

        $new_status = sanitize_text_field($data['status'] ?? $project->status);
        $old_status = $project->status;

        $new_profile_name = isset($data['profile_name']) ? trim(sanitize_text_field($data['profile_name'])) : '';
        $effective_profile_name = (!empty($new_profile_name) && strtolower($new_profile_name) !== 'select profile...') ? $new_profile_name : $project->profile_name;
        $effective_profile_id = !empty($data['fiverr_profile_id']) ? (int)$data['fiverr_profile_id'] : (int)$project->fiverr_profile_id;

        // If user is a regular member editing their assigned project, lock down restricted fields:
        if (!$is_admin_or_leader) {
            $team = $project->team_name;
            $gross = (float)$project->gross_order_value;
            $fee_percent = (float)$project->fiverr_fee_percent;
            $financials = FTM_Financial::calculate_breakdown($gross, $fee_percent);
            $effective_profile_name = $project->profile_name;
            $effective_profile_id = (int)$project->fiverr_profile_id;
            $assigned_ids = array_values(array_filter(array_map('intval', explode(',', (string)$project->assigned_user_ids))));
            if (empty($assigned_ids) && (int)$project->assigned_user_id > 0) {
                $assigned_ids = [(int)$project->assigned_user_id];
            }
            $primary_id = !empty($assigned_ids) ? $assigned_ids[0] : (int)$project->assigned_user_id;
        }

        $update_data = [
            'team_name' => $team,
            'fiverr_profile_id' => $effective_profile_id,
            'profile_name' => $effective_profile_name,
            'client_name' => isset($data['client_name']) && trim($data['client_name']) !== '' ? sanitize_text_field($data['client_name']) : $project->client_name,
            'order_id' => isset($data['order_id']) && trim($data['order_id']) !== '' ? sanitize_text_field($data['order_id']) : $project->order_id,
            'gross_order_value' => $financials['gross_order_value'],
            'fiverr_fee_percent' => $financials['fiverr_fee_percent'],
            'fiverr_fee_amount' => $financials['fiverr_fee_amount'],
            'net_delivery_value' => $financials['net_delivery_value'],
            'order_total_value' => isset($data['order_total_value']) ? round(max(0, (float)$data['order_total_value']), 2) : (float)$project->order_total_value,
            'order_type' => isset($data['order_type']) ? $this->clean_order_type($data['order_type']) : ($project->order_type ?? 'standard'),
            'tip_amount' => isset($data['tip_amount']) ? max(0.0, (float)$data['tip_amount']) : (float)$project->tip_amount,
            'delivery_date' => isset($data['delivery_date']) ? sanitize_text_field($data['delivery_date']) : $project->delivery_date,
            'assigned_user_id' => $primary_id,
            'assigned_user_ids' => implode(',', array_map('intval', $assigned_ids)),
            'status' => $new_status,
            'project_notes' => isset($data['project_notes']) ? sanitize_textarea_field($data['project_notes']) : $project->project_notes,
            'client_reviewed' => in_array($new_status, ['delivery_accepted', 'completed'], true) ? (isset($data['client_reviewed']) ? sanitize_text_field($data['client_reviewed']) : ($project->client_reviewed ?: 'no')) : 'no',
            'star_rating' => in_array($new_status, ['delivery_accepted', 'completed'], true) ? ((isset($data['client_reviewed']) && $data['client_reviewed'] === 'no') ? 0 : (isset($data['star_rating']) ? round(max(0, min(5, (float)$data['star_rating'])), 1) : (float)$project->star_rating)) : 0,
            'review_feedback' => isset($data['review_feedback']) ? sanitize_textarea_field($data['review_feedback']) : $project->review_feedback,
            'project_sheet_link' => isset($data['project_sheet_link']) ? sanitize_text_field($data['project_sheet_link']) : (isset($data['sheet_link']) ? sanitize_text_field($data['sheet_link']) : ($project->project_sheet_link ?? '')),
            'order_link' => isset($data['order_link']) ? sanitize_text_field($data['order_link']) : ($project->order_link ?? ''),
            'order_source' => isset($data['order_source']) ? sanitize_text_field($data['order_source']) : ($project->order_source ?? 'Query'),
            'seller_name' => isset($data['seller_name']) ? sanitize_text_field($data['seller_name']) : ($project->seller_name ?? ''),
            'remarks' => isset($data['remarks']) ? sanitize_textarea_field($data['remarks']) : ($project->remarks ?? '')
        ];

        if (!empty($data['delivered_date'])) {
            $raw_del = sanitize_text_field($data['delivered_date']);
            if (strlen($raw_del) === 10) {
                $raw_del .= ' 18:00:00';
            }
            $update_data['delivered_date'] = $raw_del;
        } elseif (in_array($new_status, ['delivery_accepted', 'delivery_submitted'], true) && empty($project->delivered_date)) {
            $update_data['delivered_date'] = current_time('mysql');
        } elseif ($new_status === 'cancelled') {
            $update_data['cancelled_value'] = $financials['net_delivery_value'];
        }

        if (is_array($data) && array_key_exists('tip_date', $data)) {
            $update_data['tip_date'] = !empty($data['tip_date']) ? sanitize_text_field($data['tip_date']) : null;
        }

        if (isset($data['progress_percent']) || isset($data['progress'])) {
            $update_data['progress_percent'] = max(0, min(100, (int)($data['progress_percent'] ?? $data['progress'])));
        }

        if (isset($data['extension_count'])) {
            $update_data['extension_count'] = max(0, (int)$data['extension_count']);
        }
        if (isset($data['extension_days'])) {
            $update_data['extension_days'] = max(0, (int)$data['extension_days']);
        }
        if (isset($data['extension_reason'])) {
            $update_data['extension_reason'] = sanitize_text_field($data['extension_reason']);
        }
        $effective_ext_count = isset($update_data['extension_count'])
            ? (int)$update_data['extension_count']
            : max(0, (int)$project->extension_count);
        $effective_ext_days = isset($update_data['extension_days'])
            ? (int)$update_data['extension_days']
            : max(0, (int)($project->extension_days ?? 0));
        $is_completed_status = in_array(strtolower($new_status), ['delivery_accepted', 'completed', 'delivered'], true);
        $has_ext = ($effective_ext_count > 0 || $effective_ext_days > 0);

        if (isset($data['is_ontime'])) {
            $ontime_flag = !empty($data['is_ontime']) ? 1 : 0;
            $update_data['is_ontime'] = $ontime_flag;
            $update_data['delivered_without_extension'] = $ontime_flag;
        } elseif ($is_completed_status) {
            if (!isset($project->is_ontime) || isset($data['extension_count']) || isset($data['extension_days']) || $old_status !== $new_status) {
                $auto_ontime = $has_ext ? 0 : 1;
                $update_data['is_ontime'] = $auto_ontime;
                $update_data['delivered_without_extension'] = $auto_ontime;
            }
        } else {
            $update_data['is_ontime'] = 0;
            $update_data['delivered_without_extension'] = 0;
        }

        if (!empty($data['assign_date'])) {
            $update_data['created_at'] = sanitize_text_field($data['assign_date']) . ' 10:00:00';
        } elseif (!empty($data['created_at'])) {
            $update_data['created_at'] = sanitize_text_field($data['created_at']);
        }

        $update_data = array_merge($update_data, $this->subscription_fields($data, $update_data['order_type'], $project));
        $wpdb->update($table, $update_data, ['id' => $id]);
        if (isset($data['splits'])) {
            $before = wp_json_encode($this->get_splits($id));
            $this->save_splits($id, $data['splits']);
            if ($before !== wp_json_encode($this->get_splits($id))) {
                $this->log_event($id, $new_status, 'Combined order split updated.');
            }
        }

        // Sync assignees table
        $assignees_table = $wpdb->prefix . 'ftm_project_assignees';
        $shares_before = $this->get_assignee_shares($id);
        $wpdb->delete($assignees_table, ['project_id' => $id]);
        $effective_assigned = !empty($assigned_ids) ? $assigned_ids : [$primary_id];
        $shares = $this->assignee_shares($effective_assigned, $data);
        foreach ($effective_assigned as $idx => $uid) {
            $wpdb->insert($assignees_table, [
                'project_id' => $id,
                'user_id' => (int)$uid,
                'assignment_role' => ($idx === 0) ? 'primary_lead' : 'contributor',
                'split_percentage' => $shares[(int)$uid] ?? round(100 / max(1, count($effective_assigned)), 2)
            ]);
        }
        if (count($effective_assigned) > 1 && $shares_before && $shares_before != $shares) {
            $parts = [];
            foreach ($shares as $uid => $pct) {
                $parts[] = (get_the_author_meta('display_name', $uid) ?: ('User #' . $uid)) . ' ' . rtrim(rtrim(number_format($pct, 2), '0'), '.') . '%';
            }
            $this->log_event($id, $new_status, 'Value split: ' . implode(', ', $parts));
        }

        $edit_prev_ids = array_values(array_filter(array_map('intval', explode(',', (string)$project->assigned_user_ids))));
        $edit_new_ids = array_values(array_filter(array_map('intval', $assigned_ids)));
        if ($edit_prev_ids !== $edit_new_ids) {
            $this->log_event($id, $new_status, sprintf('Assignees changed: %s → %s', $this->assignee_names($edit_prev_ids), $this->assignee_names($edit_new_ids)));
        }
        if ($old_status !== $new_status) {
            $wpdb->insert($wpdb->prefix . 'ftm_project_history', [
                'project_id' => $id,
                'user_id' => get_current_user_id(),
                'user_name' => wp_get_current_user()->display_name,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'note' => 'Status changed during project edit.'
            ]);
        }

        if (!$is_admin_or_leader) {
            $curr_u = wp_get_current_user();
            $wpdb->insert($wpdb->prefix . 'ftm_project_history', [
                'project_id' => $id,
                'user_id' => $curr_u->ID,
                'user_name' => $curr_u->display_name,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'note' => "Project updated by member {$curr_u->display_name} (Status: {$new_status}, Tip: \${$update_data['tip_amount']}; awaiting leader verification)"
            ]);

            if (class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'broadcast_to_leadership')) {
                try {
                    FTM_Notifications_Engine::broadcast_to_leadership([
                        'title' => '📝 Project Details Updated by Member',
                        'message' => "{$curr_u->display_name} updated order #{$project->order_id} ({$project->client_name}) - status: {$new_status}, tip: \${$update_data['tip_amount']}. Awaiting leader verification.",
                        'type' => 'project_updated_by_member',
                        'target_id' => $id,
                        'team' => $project->team_name
                    ]);
                } catch (\Throwable $e) {}
            }
        }

        if (class_exists('FTM_Tasks_Controller')) {
            FTM_Tasks_Controller::auto_generate_leader_approval_tasks($id, $team, $update_data['delivery_date'] ?? $project->delivery_date);
        }

        return $this->format_success(['id' => $id], __('Project updated successfully.', 'fiverr-team-management'));
    }

    public function delete_project($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $current_user_id = get_current_user_id();

        if (!FTM_Permissions::can_delete_project($id, $current_user_id)) {
            return $this->format_error(__('Only Super Admin can delete projects.', 'fiverr-team-management'), 403);
        }

        $project = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ftm_projects WHERE id = %d", $id));
        if (!$project) {
            return $this->format_error(__('Project not found.', 'fiverr-team-management'), 404);
        }

        $wpdb->delete($wpdb->prefix . 'ftm_projects', ['id' => $id]);
        $wpdb->delete($wpdb->prefix . 'ftm_project_history', ['project_id' => $id]);
        $wpdb->delete($wpdb->prefix . 'ftm_project_splits', ['project_id' => $id]);
        $wpdb->delete($wpdb->prefix . 'ftm_project_notes', ['project_id' => $id]);
        $wpdb->delete($wpdb->prefix . 'ftm_project_assignees', ['project_id' => $id]);

        return $this->format_success([], __('Project deleted successfully.', 'fiverr-team-management'));
    }

    public function update_assignees($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $table = $wpdb->prefix . 'ftm_projects';
        $project = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

        if (!$project) {
            return $this->format_error(__('Project not found.', 'fiverr-team-management'), 404);
        }

        if (!FTM_Permissions::can_edit_project($id)) {
            return $this->format_error(__('Unauthorized assignee edit.', 'fiverr-team-management'), 403);
        }

        $data = $request->get_json_params() ?: $request->get_params() ?: [];
        $raw_assigned_ids = array_map('intval', (array)($data['assigned_user_ids'] ?? []));
        $assigned_ids = array_values(array_unique(array_filter($raw_assigned_ids, function($uid) { return $uid > 0; })));

        if (empty($assigned_ids)) {
            return $this->format_error(__('At least one assignee is required.', 'fiverr-team-management'), 400);
        }

        $primary_id = $assigned_ids[0];
        $ids_string = implode(',', $assigned_ids);
        $previous_ids = array_values(array_filter(array_map('intval', explode(',', (string)$project->assigned_user_ids))));
        if (empty($previous_ids) && (int)$project->assigned_user_id > 0) {
            $previous_ids = [(int)$project->assigned_user_id];
        }

        $update_fields = [
            'assigned_user_id' => $primary_id,
            'assigned_user_ids' => $ids_string
        ];
        if (!empty($data['team_name'])) {
            $new_team = sanitize_text_field($data['team_name']);
            $allowed_teams = ['wpcraft' => 'WpCraft', 'wpnexus' => 'WpNexus', 'wptitans' => 'WpTitans', 'wphorizon' => 'WpHorizon'];
            $clean_key = strtolower(str_replace([' ', '-', '_'], '', $new_team));
            if (isset($allowed_teams[$clean_key])) {
                $update_fields['team_name'] = $allowed_teams[$clean_key];
            }
        }
        $wpdb->update($table, $update_fields, ['id' => $id]);

        // Sync with wp_ftm_project_assignees
        $assignees_table = $wpdb->prefix . 'ftm_project_assignees';
        $wpdb->delete($assignees_table, ['project_id' => $id]);
        $split = round(100.00 / count($assigned_ids), 2);
        foreach ($assigned_ids as $idx => $uid) {
            $wpdb->insert($assignees_table, [
                'project_id' => $id,
                'user_id' => $uid,
                'assignment_role' => ($idx === 0) ? 'primary_lead' : 'contributor',
                'split_percentage' => $split
            ]);
        }

        if ($previous_ids !== $assigned_ids) {
            $this->log_event($id, $project->status, sprintf('Assignees changed: %s → %s', $this->assignee_names($previous_ids), $this->assignee_names($assigned_ids)));
            $newly_assigned = array_values(array_diff($assigned_ids, $previous_ids));
            if (!empty($newly_assigned) && class_exists('FTM_Mailer')) {
                FTM_Mailer::send_project_assigned_email($id, $newly_assigned, false);
            }
        }
        return $this->format_success([
            'assigned_user_id' => $primary_id,
            'assigned_user_ids' => $assigned_ids
        ], __('Assignees updated successfully.', 'fiverr-team-management'));
    }

    /**
     * Record a non-status event (assignment, extension) in the project timeline.
     */
    /**
     * Replace a project's combined-order shares (other parties) from request data.
     * Each entry: party_label, party_kind (team|member|external), skill, share_net, note.
     */
    private function save_splits($project_id, $splits) {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_project_splits';
        $wpdb->delete($table, ['project_id' => (int)$project_id]);
        foreach ((array)$splits as $sp) {
            $label = sanitize_text_field($sp['party_label'] ?? '');
            $amount = round(max(0, (float)($sp['share_net'] ?? 0)), 2);
            if ($label === '' && $amount <= 0) {
                continue;
            }
            $kind = $sp['party_kind'] ?? 'team';
            if (!in_array($kind, ['team', 'member', 'external'], true)) {
                $kind = 'team';
            }
            $wpdb->insert($table, [
                'project_id' => (int)$project_id,
                'party_label' => mb_substr($label, 0, 190),
                'party_kind' => $kind,
                'skill' => mb_substr(sanitize_text_field($sp['skill'] ?? ''), 0, 60),
                'share_net' => $amount,
                'note' => sanitize_textarea_field($sp['note'] ?? '')
            ]);
        }
    }

    private function get_splits($project_id) {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, party_label, party_kind, skill, share_net, note FROM {$wpdb->prefix}ftm_project_splits WHERE project_id = %d ORDER BY id ASC",
            (int)$project_id
        ), ARRAY_A);
        return array_map(function ($r) {
            $r['share_net'] = (float)$r['share_net'];
            return $r;
        }, (array)$rows);
    }

    /**
     * Summary of how a combined order's value is divided: mine vs others vs not yet accounted for.
     */
    private function combined_summary($project) {
        $splits = $this->get_splits($project->id);
        $mine = (float)$project->net_delivery_value;
        $others = 0.0;
        foreach ($splits as $sp) {
            $others += $sp['share_net'];
        }
        $total = (float)($project->order_total_value ?? 0);
        if ($total <= 0 && !empty($splits)) {
            $total = $mine + $others;
        }
        return [
            'is_combined' => ($total > 0 && ($total - $mine) > 0.009) || !empty($splits),
            'order_total_value' => round($total, 2),
            'my_share' => round($mine, 2),
            'my_share_percent' => $total > 0 ? round($mine / $total * 100, 1) : 100,
            'others_total' => round($others, 2),
            'unallocated' => round(max(0, $total - $mine - $others), 2),
            'over_allocated' => round(max(0, $mine + $others - $total), 2),
            'splits' => $splits
        ];
    }

    /**
     * Subscription settings from the request. Only subscription orders can renew.
     */
    private function subscription_fields($data, $order_type, $current = null) {
        if ($order_type !== 'subscription') {
            return ['subscription_status' => '', 'subscription_next_date' => null, 'subscription_day' => 0];
        }
        $status = isset($data['subscription_status']) ? ($data['subscription_status'] === 'active' ? 'active' : 'stopped') : ($current->subscription_status ?? '');
        $next = isset($data['subscription_next_date']) ? sanitize_text_field($data['subscription_next_date']) : ($current->subscription_next_date ?? '');
        if ($next !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $next) || !strtotime($next))) {
            $next = '';
        }
        $fields = [
            'subscription_status' => $status,
            'subscription_next_date' => $next !== '' ? $next : null,
            'subscription_day' => $next !== '' ? (int)date('j', strtotime($next)) : (int)($current->subscription_day ?? 0),
        ];
        if (isset($data['subscription_term_days'])) {
            $fields['subscription_term_days'] = max(1, min(60, (int)$data['subscription_term_days']));
        }
        return $fields;
    }

    /**
     * Value share per assignee in percent. Uses the shares sent by the form when they are valid
     * (each 0-100, together 100), otherwise splits equally. The last member absorbs rounding.
     * @return array user_id => percent
     */
    private function assignee_shares($ids, $data) {
        $ids = array_values(array_unique(array_map('intval', (array)$ids)));
        $n = max(1, count($ids));
        $equal = [];
        foreach ($ids as $uid) {
            $equal[$uid] = round(100 / $n, 2);
        }
        $raw = (is_array($data) && isset($data['assignee_splits']) && is_array($data['assignee_splits'])) ? $data['assignee_splits'] : null;
        if (!$raw || count($ids) < 2) {
            return $equal;
        }
        $shares = [];
        $sum = 0.0;
        foreach ($ids as $uid) {
            if (!isset($raw[$uid]) && !isset($raw[(string)$uid])) {
                return $equal;
            }
            $v = (float)($raw[$uid] ?? $raw[(string)$uid]);
            if ($v < 0 || $v > 100) {
                return $equal;
            }
            $shares[$uid] = round($v, 2);
            $sum += $shares[$uid];
        }
        if (abs($sum - 100) > 0.5) {
            return $equal;
        }
        $last = end($ids);
        $shares[$last] = round($shares[$last] + (100 - $sum), 2);
        return $shares;
    }

    private function get_assignee_shares($project_id) {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, split_percentage FROM {$wpdb->prefix}ftm_project_assignees WHERE project_id = %d", (int)$project_id
        ), ARRAY_A);
        $out = [];
        foreach ((array)$rows as $r) {
            $out[(int)$r['user_id']] = (float)$r['split_percentage'];
        }
        return $out;
    }

    private function clean_order_type($type) {
        $type = sanitize_key((string)$type);
        return in_array($type, ['standard', 'subscription', 'outside'], true) ? $type : 'standard';
    }

    private function log_event($project_id, $status, $note) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'ftm_project_history', [
            'project_id' => (int)$project_id,
            'user_id' => get_current_user_id(),
            'user_name' => wp_get_current_user()->display_name,
            'old_status' => $status,
            'new_status' => $status,
            'note' => $note
        ]);
    }

    private function assignee_names($ids) {
        $names = [];
        foreach ((array)$ids as $uid) {
            $uid = (int)$uid;
            if ($uid > 0) {
                $names[] = get_the_author_meta('display_name', $uid) ?: ('User #' . $uid);
            }
        }
        return $names ? implode(', ', $names) : 'nobody';
    }

    public function extend_delivery($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $project = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ftm_projects WHERE id = %d", $id));

        if (!$project) {
            return $this->format_error(__('Project not found.', 'fiverr-team-management'), 404);
        }

        if (!FTM_Permissions::can_extend_project($id)) {
            return $this->format_error(__('Unauthorized to extend project delivery.', 'fiverr-team-management'), 403);
        }

        $data = $request->get_json_params() ?: $request->get_params() ?: [];
        $reason = sanitize_text_field($data['reason'] ?? 'Delivery extension agreed with client');
        $current_delivery = !empty($project->delivery_date) ? substr($project->delivery_date, 0, 10) : current_time('Y-m-d');
        $picked = sanitize_text_field($data['new_date'] ?? $data['new_delivery_date'] ?? $data['delivery_date'] ?? '');
        if ($picked !== '') {
            $picked = substr($picked, 0, 10);
            // Calendar picker: the number of added days is worked out from the chosen date
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $picked) || !strtotime($picked)) {
                return $this->format_error(__('Invalid deadline date.', 'fiverr-team-management'), 400);
            }
            $days_to_add = (int)round((strtotime($picked) - strtotime($current_delivery)) / DAY_IN_SECONDS);
            if ($days_to_add < 1 && isset($data['days']) && (int)$data['days'] > 0) {
                $days_to_add = (int)$data['days'];
            }
            $days_to_add = max(1, $days_to_add);
            $new_delivery_date = $picked;
        } else {
            $days_to_add = max(1, (int)($data['days'] ?? $data['extension_days'] ?? 3));
            $new_delivery_date = date('Y-m-d', strtotime("+$days_to_add days", strtotime($current_delivery)));
        }

        $new_count = ((int)$project->extension_count) + 1;
        $new_ext_days = ((int)($project->extension_days ?? 0)) + $days_to_add;

        $update_data = [
            'delivery_date' => $new_delivery_date,
            'extension_count' => $new_count,
            'extension_days' => $new_ext_days,
            'extension_reason' => $reason,
            'delivered_without_extension' => 0,
            'is_ontime' => 0,
            'updated_at' => current_time('mysql')
        ];

        $wpdb->update("{$wpdb->prefix}ftm_projects", $update_data, ['id' => $id]);

        $ext_note = sprintf(
            "\n[%s] Extension #%d: Added +%d days (New deadline: %s). Reason: %s",
            current_time('Y-m-d H:i'),
            $new_count,
            $days_to_add,
            $new_delivery_date,
            $reason
        );
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ftm_projects SET project_notes = CONCAT(IFNULL(project_notes, ''), %s) WHERE id = %d", $ext_note, $id));
        $this->log_event($id, $project->status, sprintf('Delivery extended +%d days (new deadline %s). Reason: %s', $days_to_add, $new_delivery_date, $reason));

        $updated_project = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ftm_projects WHERE id = %d", $id));
        $formatted = $this->format_project_item($updated_project);
        $formatted['success'] = true;
        return rest_ensure_response($formatted);
    }

    public function update_progress($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $project = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ftm_projects WHERE id = %d", $id));

        if (!$project) {
            return $this->format_error(__('Project not found.', 'fiverr-team-management'), 404);
        }

        if (!FTM_Permissions::can_edit_project($id)) {
            return $this->format_error(__('Unauthorized to update project progress.', 'fiverr-team-management'), 403);
        }

        $data = $request->get_json_params() ?: $request->get_params() ?: [];
        $progress = max(0, min(100, (int)($data['progress_percent'] ?? $data['progress'] ?? 0)));

        $wpdb->update(
            "{$wpdb->prefix}ftm_projects",
            [
                'progress_percent' => $progress,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $id]
        );

        $updated_project = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ftm_projects WHERE id = %d", $id));
        $formatted = $this->format_project_item($updated_project);
        $formatted['success'] = true;
        return rest_ensure_response($formatted);
    }

    /**
     * Change only who gets which share of a project's value. Status, review and dates stay as they are.
     */
    public function update_split($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $project = $wpdb->get_row($wpdb->prepare("SELECT id, status FROM {$wpdb->prefix}ftm_projects WHERE id = %d", $id));
        if (!$project) {
            return $this->format_error(__('Project not found.', 'fiverr-team-management'), 404);
        }
        if (!FTM_Permissions::can_edit_project($id)) {
            return $this->format_error(__('Unauthorized to change the value split.', 'fiverr-team-management'), 403);
        }
        $data = $request->get_json_params() ?: $request->get_params() ?: [];
        $current = $this->get_assignee_shares($id);
        if (count($current) < 2) {
            return $this->format_error(__('A value split needs at least two assignees.', 'fiverr-team-management'), 400);
        }
        $raw = (is_array($data) && isset($data['assignee_splits']) && is_array($data['assignee_splits'])) ? $data['assignee_splits'] : [];
        $sum = 0.0;
        foreach (array_keys($current) as $uid) {
            if (!isset($raw[$uid]) && !isset($raw[(string)$uid])) {
                return $this->format_error(__('Give a share for every assignee.', 'fiverr-team-management'), 400);
            }
            $v = (float)($raw[$uid] ?? $raw[(string)$uid]);
            if ($v < 0 || $v > 100) {
                return $this->format_error(__('Each share must be between 0 and 100.', 'fiverr-team-management'), 400);
            }
            $sum += $v;
        }
        if (abs($sum - 100) > 0.5) {
            return $this->format_error(__('The shares must add up to 100%.', 'fiverr-team-management'), 400);
        }
        $shares = $this->assignee_shares(array_keys($current), $data);
        $parts = [];
        foreach ($shares as $uid => $pct) {
            $wpdb->update($wpdb->prefix . 'ftm_project_assignees', ['split_percentage' => $pct], ['project_id' => $id, 'user_id' => (int)$uid]);
            $parts[] = (get_the_author_meta('display_name', $uid) ?: ('User #' . $uid)) . ' ' . rtrim(rtrim(number_format($pct, 2), '0'), '.') . '%';
        }
        if ($shares != $current) {
            $this->log_event($id, $project->status, 'Value split: ' . implode(', ', $parts) . '.');
        }
        return rest_ensure_response(['success' => true, 'assignee_shares' => $shares]);
    }

    /**
     * Client tip on one project. The assigned member can set it on their own project; nothing else is touched.
     */
    public function update_tip($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $project = $wpdb->get_row($wpdb->prepare("SELECT id, status, tip_amount FROM {$wpdb->prefix}ftm_projects WHERE id = %d", $id));
        if (!$project) {
            return $this->format_error(__('Project not found.', 'fiverr-team-management'), 404);
        }
        if (!FTM_Permissions::can_change_project_status($id)) {
            return $this->format_error(__('Unauthorized to change the tip.', 'fiverr-team-management'), 403);
        }
        $data = $request->get_json_params() ?: $request->get_params() ?: [];
        $update_data = [];

        if (isset($data['tip_amount']) && is_numeric($data['tip_amount'])) {
            $tip = round((float)$data['tip_amount'], 2);
            $update_data['tip_amount'] = max(0, min(100000, $tip));
        }

        if (is_array($data) && array_key_exists('tip_date', $data)) {
            $update_data['tip_date'] = !empty($data['tip_date']) ? sanitize_text_field($data['tip_date']) : null;
        }

        if (is_array($data) && array_key_exists('delivered_date', $data)) {
            $raw_del = !empty($data['delivered_date']) ? sanitize_text_field($data['delivered_date']) : null;
            if ($raw_del && strlen($raw_del) === 10) $raw_del .= ' 18:00:00';
            $update_data['delivered_date'] = $raw_del;
        }

        if (!empty($update_data)) {
            $wpdb->update($wpdb->prefix . 'ftm_projects', $update_data, ['id' => $id]);
            $curr_user = wp_get_current_user();
            $uname = $curr_user ? $curr_user->display_name : 'User';
            $this->log_event($id, $project->status, "Client tip / settlement dates updated by {$uname}.");

            $is_lead_or_admin = FTM_Permissions::is_super_admin($curr_user->ID) || FTM_Permissions::is_leader($curr_user->ID);
            if (!$is_lead_or_admin && class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'broadcast_to_leadership')) {
                try {
                    FTM_Notifications_Engine::broadcast_to_leadership([
                        'title' => 'Project Tip Updated by Member',
                        'message' => "{$uname} updated client tip for order #{$project->order_id} ({$project->client_name}). Please review & verify.",
                        'type' => 'project_tip_update',
                        'target_id' => $id,
                        'team' => $project->team_name
                    ]);
                } catch (\Throwable $e) {}
            }
        }

        return rest_ensure_response([
            'success' => true,
            'tip_amount' => $update_data['tip_amount'] ?? $project->tip_amount,
            'tip_date' => $update_data['tip_date'] ?? null
        ]);
    }

    /**
     * Dedicated endpoint to update monthly settlement dates and tip info directly from details modal
     */
    public function update_settlement($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $project = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ftm_projects WHERE id = %d", $id));
        if (!$project) {
            return $this->format_error(__('Project not found.', 'fiverr-team-management'), 404);
        }
        if (!FTM_Permissions::can_edit_project($id) && !FTM_Permissions::can_change_project_status($id)) {
            return $this->format_error(__('Unauthorized to update settlement.', 'fiverr-team-management'), 403);
        }
        $data = $request->get_json_params() ?: $request->get_params() ?: [];
        $update_data = [];

        if (is_array($data) && array_key_exists('delivered_date', $data)) {
            $raw_del = !empty($data['delivered_date']) ? sanitize_text_field($data['delivered_date']) : null;
            if ($raw_del && strlen($raw_del) === 10) $raw_del .= ' 18:00:00';
            $update_data['delivered_date'] = $raw_del;
        }

        if (is_array($data) && array_key_exists('tip_date', $data)) {
            $update_data['tip_date'] = !empty($data['tip_date']) ? sanitize_text_field($data['tip_date']) : null;
        }

        if (isset($data['tip_amount']) && is_numeric($data['tip_amount'])) {
            $update_data['tip_amount'] = max(0, min(100000, round((float)$data['tip_amount'], 2)));
        }

        if (!empty($update_data)) {
            $wpdb->update($wpdb->prefix . 'ftm_projects', $update_data, ['id' => $id]);
            $current_user = wp_get_current_user();
            $uname = $current_user ? $current_user->display_name : 'User';
            $this->log_event($id, $project->status, "Monthly settlement attribution updated by {$uname}.");

            $is_lead_or_admin = FTM_Permissions::is_super_admin($current_user->ID) || FTM_Permissions::is_leader($current_user->ID);
            if (!$is_lead_or_admin && class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'broadcast_to_leadership')) {
                try {
                    FTM_Notifications_Engine::broadcast_to_leadership([
                        'title' => 'Project Settlement/Tip Updated by Member',
                        'message' => "{$uname} updated settlement dates/tip for order #{$project->order_id} ({$project->client_name}). Please review & verify.",
                        'type' => 'project_settlement_update',
                        'target_id' => $id,
                        'team' => $project->team_name
                    ]);
                } catch (\Throwable $e) {}
            }
        }

        return rest_ensure_response([
            'success' => true,
            'delivered_date' => $update_data['delivered_date'] ?? $project->delivered_date,
            'tip_date' => $update_data['tip_date'] ?? $project->tip_date,
            'tip_amount' => $update_data['tip_amount'] ?? $project->tip_amount
        ]);
    }

    /**
     * Dedicated endpoint to update client review, star rating, and review feedback notes
     */
    public function update_review($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $project = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ftm_projects WHERE id = %d", $id));
        if (!$project) {
            return $this->format_error(__('Project not found.', 'fiverr-team-management'), 404);
        }
        if (!FTM_Permissions::can_change_project_status($id) && !FTM_Permissions::can_edit_project($id)) {
            return $this->format_error(__('Unauthorized to update review details.', 'fiverr-team-management'), 403);
        }
        $data = $request->get_json_params() ?: $request->get_params() ?: [];
        $update_data = [];

        if (isset($data['client_reviewed'])) {
            $is_rev = sanitize_text_field($data['client_reviewed']) === 'yes' ? 'yes' : 'no';
            $update_data['client_reviewed'] = $is_rev;
            if ($is_rev === 'no') {
                $update_data['star_rating'] = 0;
                $update_data['review_feedback'] = '';
            }
        }

        $effective_rev = $update_data['client_reviewed'] ?? $project->client_reviewed;

        if ($effective_rev === 'yes') {
            if (isset($data['star_rating'])) {
                $star = round(max(1, min(5, (float)$data['star_rating'])), 1);
                $update_data['star_rating'] = $star;
            }
            if (isset($data['review_feedback'])) {
                $update_data['review_feedback'] = sanitize_textarea_field($data['review_feedback']);
            }
        } else {
            $update_data['star_rating'] = 0;
            $update_data['review_feedback'] = '';
        }

        if (!empty($update_data)) {
            $wpdb->update($wpdb->prefix . 'ftm_projects', $update_data, ['id' => $id]);
            $current_user = wp_get_current_user();
            $uname = $current_user ? $current_user->display_name : 'User';
            $rating_str = isset($update_data['star_rating']) && (float)$update_data['star_rating'] > 0 ? " (Rating: {$update_data['star_rating']}★)" : ' (No Review)';
            $this->log_event($id, $project->status, "Client review details updated by {$uname}{$rating_str}.");

            $is_lead_or_admin = FTM_Permissions::is_super_admin($current_user->ID) || FTM_Permissions::is_leader($current_user->ID);
            if (!$is_lead_or_admin && class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'broadcast_to_leadership')) {
                try {
                    FTM_Notifications_Engine::broadcast_to_leadership([
                        'title' => 'Project Review Updated by Member',
                        'message' => "{$uname} updated client review for order #{$project->order_id} ({$project->client_name}){$rating_str}. Please review & verify.",
                        'type' => 'project_review_update',
                        'target_id' => $id,
                        'team' => $project->team_name
                    ]);
                } catch (\Throwable $e) {}
            }
        }

        return rest_ensure_response([
            'success' => true,
            'client_reviewed' => $update_data['client_reviewed'] ?? $project->client_reviewed,
            'star_rating' => $update_data['star_rating'] ?? $project->star_rating,
            'review_feedback' => $update_data['review_feedback'] ?? $project->review_feedback,
            'message' => __('Review details updated successfully.', 'fiverr-team-management')
        ]);
    }

    public function update_status($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $project = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ftm_projects WHERE id = %d", $id));

        if (!$project) {
            return $this->format_error(__('Project not found.', 'fiverr-team-management'), 404);
        }

        if (!FTM_Permissions::can_change_project_status($id)) {
            return $this->format_error(__('Unauthorized status edit.', 'fiverr-team-management'), 403);
        }

        $data = $request->get_json_params() ?: $request->get_params() ?: [];
        $new_status = sanitize_text_field($data['status'] ?? $project->status);
        $old_status = $project->status;

        $update_data = [
            'status' => $new_status,
            'delivered_without_extension' => self::compute_delivered_without_extension($new_status, (int)($project->extension_count ?? 0)),
        ];

        if ($new_status === 'delivery_accepted') {
            if (!empty($data['delivered_date'])) {
                $raw_del = sanitize_text_field($data['delivered_date']);
                if (strlen($raw_del) === 10) $raw_del .= ' 18:00:00';
                $update_data['delivered_date'] = $raw_del;
            } else {
                // Auto-set accepted date to current time when accepted!
                $update_data['delivered_date'] = current_time('mysql');
            }
            $is_rev = sanitize_text_field($data['client_reviewed'] ?? 'no');
            $update_data['client_reviewed'] = ($is_rev === 'yes') ? 'yes' : 'no';
            $update_data['star_rating'] = ($update_data['client_reviewed'] === 'yes') ? round(max(1, min(5, (float)($data['star_rating'] ?? 5))), 1) : 0;
            $update_data['review_feedback'] = ($update_data['client_reviewed'] === 'yes') ? sanitize_textarea_field($data['review_feedback'] ?? '') : '';
            if (isset($data['tip_amount'])) {
                $update_data['tip_amount'] = (float)$data['tip_amount'];
            }
            if (is_array($data) && array_key_exists('tip_date', $data)) {
                $update_data['tip_date'] = !empty($data['tip_date']) ? sanitize_text_field($data['tip_date']) : null;
            }
            if (isset($data['is_ontime'])) {
                $ontime_flag = !empty($data['is_ontime']) ? 1 : 0;
                $update_data['is_ontime'] = $ontime_flag;
                $update_data['delivered_without_extension'] = $ontime_flag;
            } else {
                $has_ext = ((int)($project->extension_count ?? 0) > 0 || (int)($project->extension_days ?? 0) > 0);
                $auto_ontime = $has_ext ? 0 : 1;
                $update_data['is_ontime'] = $auto_ontime;
                $update_data['delivered_without_extension'] = $auto_ontime;
            }
        } elseif ($new_status === 'delivery_submitted') {
            if (!empty($data['delivered_date'])) {
                $raw_del = sanitize_text_field($data['delivered_date']);
                if (strlen($raw_del) === 10) $raw_del .= ' 18:00:00';
                $update_data['delivered_date'] = $raw_del;
            } elseif (empty($project->delivered_date)) {
                $update_data['delivered_date'] = current_time('mysql');
            }
            if (isset($data['tip_amount'])) {
                $update_data['tip_amount'] = (float)$data['tip_amount'];
            }
            if (is_array($data) && array_key_exists('tip_date', $data)) {
                $update_data['tip_date'] = !empty($data['tip_date']) ? sanitize_text_field($data['tip_date']) : null;
            }
            if (isset($data['is_ontime'])) {
                $ontime_flag = !empty($data['is_ontime']) ? 1 : 0;
                $update_data['is_ontime'] = $ontime_flag;
                $update_data['delivered_without_extension'] = $ontime_flag;
            }
        } elseif ($new_status === 'cancelled') {
            $update_data['cancelled_value'] = $project->net_delivery_value;
            $update_data['is_ontime'] = 0;
            $update_data['delivered_without_extension'] = 0;
        } else {
            $update_data['is_ontime'] = 0;
            $update_data['delivered_without_extension'] = 0;
        }

        $wpdb->update($wpdb->prefix . 'ftm_projects', $update_data, ['id' => $id]);

        // Value split decided at delivery: keep the same assignees, change only their shares
        $split_note = '';
        if (isset($data['assignee_splits']) && is_array($data['assignee_splits'])) {
            $current = $this->get_assignee_shares($id);
            if (count($current) > 1) {
                $shares = $this->assignee_shares(array_keys($current), $data);
                if ($shares != $current) {
                    foreach ($shares as $uid => $pct) {
                        $wpdb->update($wpdb->prefix . 'ftm_project_assignees', ['split_percentage' => $pct], ['project_id' => $id, 'user_id' => (int)$uid]);
                    }
                    $parts = [];
                    foreach ($shares as $uid => $pct) {
                        $parts[] = (get_the_author_meta('display_name', $uid) ?: ('User #' . $uid)) . ' ' . rtrim(rtrim(number_format($pct, 2), '0'), '.') . '%';
                    }
                    $split_note = ' Value split: ' . implode(', ', $parts) . '.';
                }
            }
        }

        // Record history
        $curr_user = wp_get_current_user();
        $is_lead_or_admin = FTM_Permissions::is_super_admin($curr_user->ID) || FTM_Permissions::is_leader($curr_user->ID);
        $status_label = ucwords(str_replace('_', ' ', $new_status));
        $history_note = trim(sanitize_text_field($data['note'] ?? 'Status updated.') . $split_note);
        if (!$is_lead_or_admin) {
            $history_note .= " (Updated by member {$curr_user->display_name}; awaiting leader verification)";
        }

        $wpdb->insert($wpdb->prefix . 'ftm_project_history', [
            'project_id' => $id,
            'user_id' => $curr_user->ID,
            'user_name' => $curr_user->display_name,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'note' => $history_note
        ]);

        // If member changed status, broadcast notice to team leadership for verification
        if (!$is_lead_or_admin && class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'broadcast_to_leadership')) {
            try {
                FTM_Notifications_Engine::broadcast_to_leadership([
                    'title' => 'Project Status Updated by Member',
                    'message' => "{$curr_user->display_name} updated order #{$project->order_id} ({$project->client_name}) to {$status_label}. Please review & verify.",
                    'type' => 'project_status_update',
                    'target_id' => $id,
                    'team' => $project->team_name
                ]);
            } catch (\Throwable $e) {}
        }

        return rest_ensure_response([
            'success' => true,
            'new_status' => $new_status,
            'new_label' => $status_label
        ]);
    }

    /**
     * Leader/Admin toggle On-Time status for completed/delivered projects
     * POST /projects/{id}/ontime
     */
    public function update_ontime($request) {
        global $wpdb;
        $project_id = (int)$request['id'];
        $params = $request->get_json_params();
        $is_ontime = !empty($params['is_ontime']) ? 1 : 0;

        $project = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ftm_projects WHERE id = %d",
            $project_id
        ));
        if (!$project) {
            return $this->format_error(__('Project not found', 'fiverr-team-management'), 404);
        }

        $completed_statuses = ['delivery_accepted', 'delivered', 'completed'];
        if (!in_array(strtolower((string)$project->status), $completed_statuses, true)) {
            return $this->format_error(__('On-Time status can only be toggled for delivered or completed projects.', 'fiverr-team-management'), 400);
        }

        $wpdb->update(
            "{$wpdb->prefix}ftm_projects",
            [
                'is_ontime' => $is_ontime,
                'delivered_without_extension' => $is_ontime,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $project_id]
        );

        return rest_ensure_response([
            'success' => true,
            'project_id' => $project_id,
            'is_ontime' => $is_ontime,
            'delivered_without_extension' => $is_ontime,
            'message' => sprintf(__('Project marked as %s successfully.', 'fiverr-team-management'), $is_ontime ? 'On-Time' : 'Not On-Time / Late')
        ]);
    }

    public function add_note($request) {
        global $wpdb;
        $id = (int)$request['id'];
        $note = sanitize_textarea_field($request->get_param('note') ?: '');

        if (empty($note)) {
            return $this->format_error(__('Note text cannot be empty.', 'fiverr-team-management'), 400);
        }

        $wpdb->insert($wpdb->prefix . 'ftm_project_notes', [
            'project_id' => $id,
            'user_id' => get_current_user_id(),
            'note' => $note
        ]);

        return $this->format_success([], __('Note added successfully.', 'fiverr-team-management'));
    }

    public function get_stats($request) {
        $current_user_id = get_current_user_id();
        $is_admin = FTM_Permissions::is_super_admin($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id);
        $is_deputy = FTM_Permissions::is_deputy($current_user_id);

        $requested_team = sanitize_text_field($request->get_param('team') ?: 'all');
        $requested_month = sanitize_text_field($request->get_param('month') ?: 'all');
        $start_date = sanitize_text_field($request->get_param('start_date') ?: '');
        $end_date = sanitize_text_field($request->get_param('end_date') ?: '');
        $requested_client = sanitize_text_field($request->get_param('client') ?: '');
        $requested_profile = (int)$request->get_param('profile_id');
        $requested_user = (int)($request->get_param('user_id') ?: $request->get_param('member_id'));

        $team = $requested_team;
        $user_id = 0;

        if (!$is_admin && !$is_leader && !$is_deputy) {
            // For a regular team member: stats must be scoped strictly to their own assigned projects and team!
            $team = FTM_Permissions::get_user_team($current_user_id);
            $user_id = $current_user_id;
        } elseif ($is_leader && !$is_admin && !$is_deputy) {
            // For a team leader: stats are scoped to their team or accessible scopes
            $leader_team = FTM_Permissions::get_user_team($current_user_id);
            if ($requested_team !== 'all' && !empty($requested_team)) {
                if (strcasecmp($requested_team, $leader_team) === 0) {
                    $team = $requested_team;
                } elseif (class_exists('FTM_Manager_Scopes') && in_array(strtolower($requested_team), array_map('strtolower', FTM_Manager_Scopes::get_accessible_teams($current_user_id)), true)) {
                    $team = $requested_team;
                } else {
                    $team = $leader_team;
                }
            } else {
                $team = $leader_team;
            }

            if ($requested_user > 0) {
                $req_user_team = FTM_Permissions::get_user_team($requested_user);
                if (strcasecmp($req_user_team, $team) === 0 || $requested_user === $current_user_id) {
                    $user_id = $requested_user;
                }
            }
        } else {
            // Admin or Deputy Manager: can optionally filter by member if user_id or member_id param passed
            if ($requested_user > 0) {
                $user_id = $requested_user;
            }
        }

        // Determine period boundaries
        if (!empty($start_date) && !empty($end_date)) {
            $period_start = $start_date;
            $period_end = $end_date;
        } elseif ($requested_month !== 'all' && $requested_month !== 'carry_forward' && preg_match('/^\d{4}-\d{2}$/', $requested_month)) {
            $period_start = $requested_month . '-01';
            $period_end = date('Y-m-t', strtotime($period_start));
        } else {
            $period_start = null;
            $period_end = null;
        }

        $extra_filters = [];
        if (!empty($requested_client) && $requested_client !== 'all') {
            $extra_filters['client'] = $requested_client;
        }
        if ($requested_profile > 0) {
            $extra_filters['profile_id'] = $requested_profile;
        }

        // Compute period financial totals (accurate for the selected month/period!)
        $financial_totals = FTM_Financial::compute_period_totals($team, $period_start, $period_end, $user_id, $extra_filters);

        global $wpdb;
        $table = $wpdb->prefix . 'ftm_projects';
        
        $where_clauses = ["1=1"];
        $where_params = [];

        if ($team !== 'all') {
            $where_clauses[] = "team_name = %s";
            $where_params[] = $team;
        }

        if ($user_id > 0) {
            $where_clauses[] = "(assigned_user_id = %d OR FIND_IN_SET(%d, assigned_user_ids))";
            $where_params[] = $user_id;
            $where_params[] = $user_id;
        }

        if (!empty($requested_client) && $requested_client !== 'all') {
            $where_clauses[] = "client_name = %s";
            $where_params[] = $requested_client;
        }

        if ($requested_profile > 0) {
            $where_clauses[] = "profile_id = %d";
            $where_params[] = $requested_profile;
        }

        $today = current_time('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day', strtotime($today)));

        // Scope date-based queries with exact same where conditions
        $due_today_clauses = array_merge($where_clauses, ["delivery_date = %s", "status NOT IN ('delivery_accepted', 'delivery_submitted', 'cancelled')"]);
        $due_today_where = "WHERE " . implode(' AND ', $due_today_clauses);
        $due_today_params = array_merge($where_params, [$today]);
        $due_today = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table $due_today_where", $due_today_params));

        $due_tomorrow_clauses = array_merge($where_clauses, ["delivery_date = %s", "status NOT IN ('delivery_accepted', 'delivery_submitted', 'cancelled')"]);
        $due_tomorrow_where = "WHERE " . implode(' AND ', $due_tomorrow_clauses);
        $due_tomorrow_params = array_merge($where_params, [$tomorrow]);
        $due_tomorrow = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table $due_tomorrow_where", $due_tomorrow_params));

        $overdue_clauses = array_merge($where_clauses, ["delivery_date < %s", "status NOT IN ('delivery_accepted', 'delivery_submitted', 'cancelled')"]);
        $overdue_where = "WHERE " . implode(' AND ', $overdue_clauses);
        $overdue_params = array_merge($where_params, [$today]);
        $overdue_projects = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table $overdue_where", $overdue_params));

        // Repeat Client Metrics — uses exact same period window as get_projects() for 100% parity
        $repeat_where_clauses = array_merge($where_clauses, ["order_source = 'Repeat Client'"]);
        if ($requested_month === 'carry_forward') {
            $repeat_where_clauses[] = "delivery_date < %s AND status IN ('wip', 'nra', 'revision')";
            $repeat_where_params = array_merge($where_params, [current_time('Y-m-01')]);
        } elseif ($period_start && $period_end) {
            $repeat_where_clauses[] = "(
                (created_at BETWEEN %s AND %s)
                OR (delivery_date BETWEEN %s AND %s)
                OR (delivered_date IS NOT NULL AND delivered_date BETWEEN %s AND %s)
                OR (tip_amount > 0 AND tip_date IS NOT NULL AND tip_date BETWEEN %s AND %s)
                OR (status IN ('wip', 'nra', 'revision') AND created_at <= %s)
            ) AND NOT (status IN ('delivered', 'delivery_accepted', 'completed', 'cancelled') AND COALESCE(delivered_date, delivery_date) < %s AND (tip_amount <= 0 OR tip_date IS NULL OR tip_date < %s))";
            $repeat_where_params = array_merge($where_params, [
                $period_start . ' 00:00:00', $period_end . ' 23:59:59',
                $period_start, $period_end,
                $period_start . ' 00:00:00', $period_end . ' 23:59:59',
                $period_start, $period_end,
                $period_end . ' 23:59:59',
                $period_start,
                $period_start,
            ]);
        } else {
            $repeat_where_params = $where_params;
        }
        $repeat_where = "WHERE " . implode(' AND ', $repeat_where_clauses);
        $repeat_orders_count = (int)$wpdb->get_var(!empty($repeat_where_params) ? $wpdb->prepare("SELECT COUNT(*) FROM $table $repeat_where", $repeat_where_params) : "SELECT COUNT(*) FROM $table $repeat_where");
        $repeat_gross_revenue = round((float)$wpdb->get_var(!empty($repeat_where_params) ? $wpdb->prepare("SELECT COALESCE(SUM(gross_order_value), 0) FROM $table $repeat_where", $repeat_where_params) : "SELECT COALESCE(SUM(gross_order_value), 0) FROM $table $repeat_where"), 2);
        $repeat_net_revenue = round((float)$wpdb->get_var(!empty($repeat_where_params) ? $wpdb->prepare("SELECT COALESCE(SUM(net_delivery_value), 0) FROM $table $repeat_where", $repeat_where_params) : "SELECT COALESCE(SUM(net_delivery_value), 0) FROM $table $repeat_where"), 2);

        // Carry Forward & New Sales Metrics for the selected period
        if ($period_start && $period_end) {
            $cf_clauses = array_merge($where_clauses, [
                "DATE(created_at) < %s",
                "(delivered_date IS NULL OR DATE(delivered_date) >= %s)",
                "(status IN ('wip', 'nra', 'revision', 'delivery_submitted') OR (status IN ('delivery_accepted', 'completed') AND DATE(COALESCE(delivered_date, delivery_date)) >= %s))"
            ]);
            $cf_sql = "WHERE " . implode(' AND ', $cf_clauses);
            $cf_p = array_merge($where_params, [$period_start, $period_start, $period_start]);
            $carry_forward_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table $cf_sql", $cf_p));
            $carry_forward_volume = round((float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(net_delivery_value), 0) FROM $table $cf_sql", $cf_p)), 2);

            $new_clauses = array_merge($where_clauses, [
                "DATE(created_at) BETWEEN %s AND %s"
            ]);
            $new_sql = "WHERE " . implode(' AND ', $new_clauses);
            $new_p = array_merge($where_params, [$period_start, $period_end]);
            $new_sales_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table $new_sql", $new_p));
            $new_sales_volume = round((float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(net_delivery_value), 0) FROM $table $new_sql", $new_p)), 2);
        } else {
            $cf_start_date = current_time('Y-m-01');
            $cf_where_clauses = array_merge($where_clauses, ["delivery_date < %s", "status IN ('wip', 'nra', 'revision')"]);
            $cf_where = "WHERE " . implode(' AND ', $cf_where_clauses);
            $cf_params = array_merge($where_params, [$cf_start_date]);
            $carry_forward_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table $cf_where", $cf_params));
            $carry_forward_volume = (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(net_delivery_value), 0) FROM $table $cf_where", $cf_params));
            $new_sales_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE DATE(created_at) >= '$cf_start_date'");
            $new_sales_volume = (float)$wpdb->get_var("SELECT COALESCE(SUM(net_delivery_value), 0) FROM $table WHERE DATE(created_at) >= '$cf_start_date'");
        }
        $period_tips_val = (float)($financial_totals['total_tips_net'] ?? 0);
        $total_assigned_count = $new_sales_count + $carry_forward_count;
        $total_assigned_volume = round($new_sales_volume + $carry_forward_volume + $period_tips_val, 2);

        // Now compute accurate status counts and totals for this month/period
        if ($requested_month === 'carry_forward') {
            $total_count = $carry_forward_count;
            $active_projects = $carry_forward_count;
            $wip_count = $carry_forward_count;
            $nra_count = 0;
            $rev_count = 0;
            $del_count = 0;
            $subm_count = 0;
            $canc_count = 0;
            $delivered_total = 0;
        } elseif ($period_start && $period_end) {
            // Specific Month (e.g. November 2026):
            // Delivered orders strictly in this month:
            $del_clauses = array_merge($where_clauses, [
                "status IN ('delivery_accepted', 'delivered', 'completed')",
                "COALESCE(DATE(delivered_date), delivery_date) BETWEEN %s AND %s"
            ]);
            $del_sql = "WHERE " . implode(' AND ', $del_clauses);
            $del_p = array_merge($where_params, [$period_start, $period_end]);
            $del_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table $del_sql", $del_p));

            // Submitted in this month:
            $subm_clauses = array_merge($where_clauses, [
                "status = 'delivery_submitted'",
                "DATE(created_at) <= %s",
                "NOT (COALESCE(DATE(delivered_date), delivery_date) < %s AND status IN ('delivery_accepted', 'delivered', 'completed', 'cancelled'))"
            ]);
            $subm_sql = "WHERE " . implode(' AND ', $subm_clauses);
            $subm_p = array_merge($where_params, [$period_end, $period_start]);
            $subm_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table $subm_sql", $subm_p));

            // Cancelled in this month:
            $canc_clauses = array_merge($where_clauses, [
                "status = 'cancelled'",
                "delivery_date BETWEEN %s AND %s"
            ]);
            $canc_sql = "WHERE " . implode(' AND ', $canc_clauses);
            $canc_p = array_merge($where_params, [$period_start, $period_end]);
            $canc_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table $canc_sql", $canc_p));

            // Active WIP orders in this month (created on or before month end, not delivered/cancelled before month start)
            $wip_clauses = array_merge($where_clauses, [
                "status = 'wip'",
                "DATE(created_at) <= %s",
                "NOT (COALESCE(DATE(delivered_date), delivery_date) < %s AND status IN ('delivery_accepted', 'delivered', 'completed', 'cancelled'))"
            ]);
            $wip_sql = "WHERE " . implode(' AND ', $wip_clauses);
            $wip_p = array_merge($where_params, [$period_end, $period_start]);
            $wip_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table $wip_sql", $wip_p));

            // NRA in this month
            $nra_clauses = array_merge($where_clauses, [
                "status = 'nra'",
                "DATE(created_at) <= %s",
                "NOT (COALESCE(DATE(delivered_date), delivery_date) < %s AND status IN ('delivery_accepted', 'delivered', 'completed', 'cancelled'))"
            ]);
            $nra_sql = "WHERE " . implode(' AND ', $nra_clauses);
            $nra_p = array_merge($where_params, [$period_end, $period_start]);
            $nra_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table $nra_sql", $nra_p));

            // Revision in this month
            $rev_clauses = array_merge($where_clauses, [
                "status = 'revision'",
                "DATE(created_at) <= %s",
                "NOT (COALESCE(DATE(delivered_date), delivery_date) < %s AND status IN ('delivery_accepted', 'delivered', 'completed', 'cancelled'))"
            ]);
            $rev_sql = "WHERE " . implode(' AND ', $rev_clauses);
            $rev_p = array_merge($where_params, [$period_end, $period_start]);
            $rev_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table $rev_sql", $rev_p));

            $active_projects = $wip_count + $nra_count + $rev_count;
            $delivered_total = $del_count + $subm_count;
            $total_count = $active_projects + $delivered_total + $canc_count;
        } else {
            // All-Time
            $where_sql = "WHERE " . implode(' AND ', $where_clauses);
            $prepared_where = !empty($where_params) ? $wpdb->prepare($where_sql, $where_params) : $where_sql;
            $status_counts = $wpdb->get_results("SELECT status, COUNT(*) as count FROM $table $prepared_where GROUP BY status", OBJECT_K);

            $wip_count = (int)($status_counts['wip']->count ?? 0);
            $nra_count = (int)($status_counts['nra']->count ?? 0);
            $rev_count = (int)($status_counts['revision']->count ?? 0);
            $del_count = (int)($status_counts['delivery_accepted']->count ?? 0) + (int)($status_counts['delivered']->count ?? 0) + (int)($status_counts['completed']->count ?? 0);
            $subm_count = (int)($status_counts['delivery_submitted']->count ?? 0);
            $canc_count = (int)($status_counts['cancelled']->count ?? 0);
            $delivered_total = $del_count + $subm_count;
            $active_projects = $wip_count + $nra_count + $rev_count;
            $total_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table $prepared_where");
        }

        $in_revision = $rev_count;
        $awaiting_acceptance = $subm_count;
        // Note: FTM_Financial::compute_period_totals() already includes period tip_date tips in total_delivery_net
        $net_delivery_exact = round((float)($financial_totals['total_delivery_net'] ?? 0), 2);

        return rest_ensure_response([
            'total' => $total_count,
            'nra' => $nra_count,
            'wip' => $wip_count,
            'delivery_submitted' => $subm_count,
            'revision' => $rev_count,
            'delivery_accepted' => $del_count,
            'delivered_total' => $delivered_total,
            'cancelled' => $canc_count,
            'active_projects' => $active_projects,
            'due_today' => $due_today,
            'due_tomorrow' => $due_tomorrow,
            'overdue_projects' => $overdue_projects,
            'in_revision' => $in_revision,
            'awaiting_acceptance' => $awaiting_acceptance,
            'carry_forward_count' => $carry_forward_count,
            'carry_forward_volume' => $carry_forward_volume,
            'new_sales_count' => $new_sales_count,
            'new_sales_volume' => $new_sales_volume,
            'total_assigned_count' => $total_assigned_count,
            'total_assigned_volume' => $total_assigned_volume,
            'repeat_orders_count' => $repeat_orders_count,
            'repeat_gross_revenue' => $repeat_gross_revenue,
            'repeat_net_revenue' => $repeat_net_revenue,
            'total_delivery_net' => $net_delivery_exact,
            'total_net_revenue' => $net_delivery_exact,
            'total_gross_value' => round($net_delivery_exact / 0.8, 2),
            'active_wip_value' => (float)($financial_totals['total_wip_net'] ?? 0),
            'delivery_submitted_value' => (float)($financial_totals['total_submitted_net'] ?? 0),
            'total_submitted_value' => (float)($financial_totals['total_submitted_net'] ?? 0),
            'total_submitted_net' => (float)($financial_totals['total_submitted_net'] ?? 0),
            'active_revision_value' => (float)($financial_totals['total_revision_net'] ?? 0),
            'total_tips' => (float)($financial_totals['total_tips_net'] ?? 0),
            'total_work_assigned' => (float)($financial_totals['total_work_assigned'] ?? 0),
            'financials' => $financial_totals
        ]);
    }

    private function format_project_item($p) {
        $raw_ids = !empty($p->assigned_user_ids) ? explode(',', $p->assigned_user_ids) : [(int)$p->assigned_user_id];
        $assigned_ids = [];
        foreach ($raw_ids as $rid) {
            $uid = (int)trim($rid);
            if ($uid > 0 && !in_array($uid, $assigned_ids, true)) {
                $assigned_ids[] = $uid;
            }
        }
        if (empty($assigned_ids) && (int)$p->assigned_user_id > 0) {
            $assigned_ids[] = (int)$p->assigned_user_id;
        }
        $assigned_members = [];

        foreach ($assigned_ids as $uid) {
            if ($uid > 0) {
                $user = get_userdata($uid);
                if ($user) {
                    $custom_avatar = get_user_meta($uid, 'ftm_custom_avatar', true);
                    $avatar_url = !empty($custom_avatar) ? $custom_avatar : ('https://ui-avatars.com/api/?name=' . urlencode($user->display_name) . '&background=2563eb&color=fff&size=128&bold=true');
                    $assigned_members[] = [
                        'id' => $uid,
                        'name' => $user->display_name,
                        'avatar' => $avatar_url,
                        'team' => FTM_Permissions::get_user_team($uid),
                        'role_label' => FTM_Permissions::get_role_label(FTM_Permissions::get_user_role($uid))
                    ];
                }
            }
        }

        // Orders from teams that have no platform accounts yet: show the sheet's name instead of "undefined"
        if (empty($assigned_members)) {
            $ext_name = trim((string)$p->assigned_user_name) !== '' ? trim((string)$p->assigned_user_name) : 'Unassigned';
            $assigned_members[] = [
                'id' => 0,
                'name' => $ext_name,
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($ext_name) . '&background=64748b&color=fff&size=128&bold=true',
                'team' => $p->team_name,
                'role_label' => trim($p->team_name . ' · no platform account')
            ];
        }

        // Delivery and deadline timing logic
        $ext_count = max(0, (int)($p->extension_count ?? 0));
        $ext_days = max(0, (int)($p->extension_days ?? 0));
        $is_completed = in_array(strtolower((string)$p->status), ['delivery_accepted', 'completed'], true);
        $is_delivered = ($is_completed || $p->status === 'delivery_submitted');
        $delivery_date_ts = !empty($p->delivery_date) ? strtotime($p->delivery_date . ' 23:59:59') : null;
        $delivered_date_ts = !empty($p->delivered_date) ? strtotime($p->delivered_date) : null;

        $delivery_outcome = 'unknown';
        $delivery_outcome_label = '';
        $delivery_outcome_badge_class = '';
        $diff_days = 0;
        $urgency = 'safe';

        if ($is_delivered) {
            $diff_days = 0;
            if ($ext_count > 0) {
                $delivery_outcome = 'extended';
                $delivery_outcome_label = 'Extended (' . $ext_count . 'x' . ($ext_days > 0 ? ' · +' . $ext_days . 'd' : '') . ')';
                $delivery_outcome_badge_class = 'bg-amber-soft text-amber';
                $urgency = 'warning';
            } elseif ($delivery_date_ts && $delivered_date_ts && ($delivery_date_ts - $delivered_date_ts) >= 86400) {
                $delivery_outcome = 'ahead_of_schedule';
                $delivery_outcome_label = 'Ahead of Schedule';
                $delivery_outcome_badge_class = 'bg-blue-soft text-blue';
                $urgency = 'safe';
            } else {
                $delivery_outcome = 'on_time';
                $delivery_outcome_label = $is_completed ? 'On-Time Delivery' : 'Delivered (Pending Acceptance)';
                $delivery_outcome_badge_class = 'bg-emerald-soft text-emerald';
                $urgency = 'safe';
            }
            $short_badge = $delivery_outcome_label;
            $label = $delivery_outcome_label;
        } elseif ($p->status === 'cancelled') {
            $delivery_outcome = 'cancelled';
            $delivery_outcome_label = 'Cancelled';
            $delivery_outcome_badge_class = 'bg-rose-soft text-rose';
            $urgency = 'cancelled';
            $short_badge = 'Cancelled';
            $label = 'Cancelled';
            $diff_days = 0;
        } else {
            // Active / WIP / NRA / Revision
            if ($delivery_date_ts) {
                $now = time();
                $diff_days = ceil(($delivery_date_ts - $now) / 86400);
                if ($diff_days < 0) {
                    $delivery_outcome = 'overdue';
                    $delivery_outcome_label = abs($diff_days) . 'd Overdue';
                    $delivery_outcome_badge_class = 'bg-rose-soft text-rose';
                    $urgency = 'danger';
                    $short_badge = abs($diff_days) . 'd late';
                    $label = abs($diff_days) . 'd late';
                } elseif ($diff_days === 0) {
                    $delivery_outcome = 'due_today';
                    $delivery_outcome_label = 'Due Today';
                    $delivery_outcome_badge_class = 'bg-amber-soft text-amber';
                    $urgency = 'warning';
                    $short_badge = 'Due today';
                    $label = 'Due today';
                } else {
                    $delivery_outcome = 'in_progress';
                    $delivery_outcome_label = $diff_days . 'd left';
                    $delivery_outcome_badge_class = ($diff_days <= 2) ? 'bg-amber-soft text-amber' : 'bg-blue-soft text-blue';
                    $urgency = ($diff_days <= 2) ? 'warning' : 'safe';
                    $short_badge = $diff_days . 'd left';
                    $label = $diff_days . 'd remaining';
                }
            } else {
                $delivery_outcome = 'no_deadline';
                $delivery_outcome_label = 'No Deadline';
                $delivery_outcome_badge_class = 'bg-slate-soft text-muted';
                $urgency = 'safe';
                $short_badge = 'N/A';
                $label = 'No deadline';
                $diff_days = 0;
            }
        }

        $primary_name = !empty($assigned_members) ? $assigned_members[0]['name'] : 'Specialist';
        $primary_avatar = !empty($assigned_members) ? $assigned_members[0]['avatar'] : ('https://ui-avatars.com/api/?name=' . urlencode($primary_name) . '&background=2563eb&color=fff&size=128&bold=true');

        $created_ts = !empty($p->created_at) ? strtotime($p->created_at) : (!empty($p->delivery_date) ? strtotime($p->delivery_date) : time());
        $created_month = date('Y-m', $created_ts);
        $current_month = current_time('Y-m');
        $is_delivered_prior = (!empty($p->delivered_date) && date('Y-m', strtotime($p->delivered_date)) < $current_month);
        $is_carry_forward = ($created_month < $current_month) && !$is_delivered_prior;

        $tip_amount = (float)($p->tip_amount ?? 0);
        $delivered_ts = !empty($p->delivered_date) ? strtotime($p->delivered_date) : (!empty($p->delivery_date) ? strtotime($p->delivery_date) : 0);
        $delivered_month = $delivered_ts ? date('Y-m', $delivered_ts) : '';
        $tip_date_str = !empty($p->tip_date) ? $p->tip_date : (!empty($p->delivered_date) ? substr($p->delivered_date, 0, 10) : $p->delivery_date);
        $tip_ts = !empty($tip_date_str) ? strtotime($tip_date_str) : 0;
        $tip_month = $tip_ts ? date('Y-m', $tip_ts) : '';
        $is_cross_month_tip = ($tip_amount > 0 && !empty($tip_month) && !empty($delivered_month) && $tip_month !== $delivered_month);

        return [
            'id' => (int)$p->id,
            'team_name' => $p->team_name,
            'team' => $p->team_name,
            'fiverr_profile_id' => (int)$p->fiverr_profile_id,
            'profile_name' => $p->profile_name,
            'client_name' => $p->client_name,
            'client' => $p->client_name,
            'buyer_username' => $p->client_name,
            'order_id' => $p->order_id,
            'assign_date' => !empty($p->created_at) ? date('Y-m-d', strtotime($p->created_at)) : '',
            'assign_date_formatted' => !empty($p->created_at) ? date('M j, Y', strtotime($p->created_at)) : 'N/A',
            'created_at' => $p->created_at,
            'created_at_formatted' => !empty($p->created_at) ? date('M j, Y', strtotime($p->created_at)) : 'N/A',
            'is_carry_forward' => (bool)$is_carry_forward,
            'order_flow_type' => $is_carry_forward ? 'carry_forward' : 'new',
            'title' => !empty($p->project_title) ? $p->project_title : ($p->profile_name . ' - ' . $p->order_id),
            'gross_order_value' => (float)$p->gross_order_value,
            'order_value' => (float)$p->gross_order_value,
            'price' => (float)$p->gross_order_value,
            'fiverr_fee_percent' => (float)$p->fiverr_fee_percent,
            'fiverr_fee_amount' => (float)$p->fiverr_fee_amount,
            'net_delivery_value' => (float)$p->net_delivery_value,
            'net_value' => (float)$p->net_delivery_value,
            'net' => (float)$p->net_delivery_value,
            'order_total_value' => (float)($p->order_total_value ?? 0),
            'order_type' => $p->order_type ?? 'standard',
            'subscription_status' => $p->subscription_status ?? '',
            'subscription_next_date' => $p->subscription_next_date ?? null,
            'subscription_term_days' => (int)($p->subscription_term_days ?? 7),
            'subscription_parent_id' => (int)($p->subscription_parent_id ?? 0),
            'is_combined' => ((float)($p->order_total_value ?? 0) - (float)$p->net_delivery_value) > 0.009,
            'others_value' => round(max(0, (float)($p->order_total_value ?? 0) - (float)$p->net_delivery_value), 2),
            'combined' => $this->combined_summary($p),
            'assignee_shares' => $this->get_assignee_shares($p->id),
            'tip_amount' => (float)$p->tip_amount,
            'tips' => (float)$p->tip_amount,
            'cancelled_value' => (float)$p->cancelled_value,
            'partial_payout_value' => (float)$p->partial_payout_value,
            'delivery_date' => $p->delivery_date,
            'delivery_date_formatted' => $p->delivery_date ? date('M j, Y', strtotime($p->delivery_date)) : 'N/A',
            'delivered_date' => $p->delivered_date,
            'delivered_date_formatted' => !empty($p->delivered_date) ? date('M j, Y g:i A', strtotime($p->delivered_date)) : '',
            'tip_date' => $p->tip_date ?? null,
            'tip_date_formatted' => !empty($p->tip_date) ? date('M j, Y', strtotime($p->tip_date)) : '',
            'delivered_month' => $delivered_month,
            'tip_month' => $tip_month,
            'is_cross_month_tip' => $is_cross_month_tip,
            'is_delivered' => $is_delivered,
            'delivery_outcome' => [
                'status' => $delivery_outcome,
                'label' => $delivery_outcome_label,
                'badge_class' => $delivery_outcome_badge_class
            ],
            'days_remaining' => [
                'days' => $diff_days,
                'urgency' => $urgency,
                'short_badge' => $short_badge,
                'label' => $label,
                'is_delivered' => $is_delivered
            ],
            'assigned_user_id' => (int)$p->assigned_user_id,
            'assigned_user_ids' => $assigned_ids,
            'assigned_user_avatar' => $primary_avatar,
            'assigned_members' => $assigned_members,
            'assignees' => $assigned_members,
            'status' => $p->status,
            'status_label' => ucwords(str_replace('_', ' ', $p->status)),
            'progress' => isset($p->progress_percent) ? (int)$p->progress_percent : 0,
            'progress_percent' => isset($p->progress_percent) ? (int)$p->progress_percent : 0,
            'project_notes' => $p->project_notes,
            'client_reviewed' => $p->client_reviewed,
            'star_rating' => (float)$p->star_rating,
            'review_feedback' => $p->review_feedback,
            'extension_count' => (int)$p->extension_count,
            'extension_days' => (int)($p->extension_days ?? 0),
            'extension_reason' => $p->extension_reason ?? '',
            'is_ontime' => (in_array(strtolower((string)$p->status), ['delivery_accepted', 'completed', 'delivered'], true)) ? (isset($p->is_ontime) ? (int)$p->is_ontime : (((int)$p->extension_count === 0 && (int)($p->extension_days ?? 0) === 0) ? 1 : 0)) : 0,
            'delivered_without_extension' => (in_array(strtolower((string)$p->status), ['delivery_accepted', 'completed', 'delivered'], true)) ? (isset($p->is_ontime) ? (int)$p->is_ontime : (((int)$p->extension_count === 0 && (int)($p->extension_days ?? 0) === 0) ? 1 : 0)) : 0,
            'project_sheet_link' => $p->project_sheet_link ?? '',
            'order_link' => $p->order_link ?? '',
            'order_source' => $p->order_source ?? 'Query',
            'is_repeat_client' => (($p->order_source ?? '') === 'Repeat Client'),
            'seller_name' => $p->seller_name ?? '',
            'remarks' => $p->remarks ?? '',
            'is_financial_unrecorded' => ((float)$p->gross_order_value <= 0 && (float)$p->net_delivery_value <= 0),
            'can_edit' => FTM_Permissions::can_edit_project($p->id),
            'can_delete' => FTM_Permissions::can_delete_project($p->id),
            'can_change_status' => FTM_Permissions::can_change_project_status($p->id),
            'can_extend' => FTM_Permissions::can_extend_project($p->id)
        ];
    }
}

