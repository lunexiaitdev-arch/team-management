<?php
/**
 * FTM Project Tasks & Automated Milestones Controller
 * Manages project milestone pipelines (Initial conversation 5h, 1st Draft 2-3d, Fixing, Final update),
 * custom task assignments by leaders/managers, member to-do lists, and priority workflows.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTM_Tasks_Controller extends FTM_REST_Controller {

    public function register_routes() {
        self::ensure_table();

        // All / My Tasks Board (Dedicated My Tasks workspace view)
        register_rest_route($this->namespace, '/tasks', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_tasks_board'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Project Tasks
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)/tasks', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_project_tasks'],
                'permission_callback' => [$this, 'check_auth_permission']
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_project_task'],
                'permission_callback' => [$this, 'check_auth_permission']
            ]
        ]);

        // Task Status & Details
        register_rest_route($this->namespace, '/tasks/(?P<id>\d+)/status', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_task_status'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        register_rest_route($this->namespace, '/tasks/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'update_task'],
                'permission_callback' => [$this, 'check_auth_permission']
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_task'],
                'permission_callback' => [$this, 'check_leader_or_admin_permission']
            ]
        ]);

        // Member Tasks & To-Do List (for Profile & Dashboards)
        register_rest_route($this->namespace, '/members/(?P<id>\d+)/tasks', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_member_tasks'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        register_rest_route($this->namespace, '/tasks/my-pending', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_my_pending_tasks'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);

        // Project Quality Gate Approval (Team Leaders & Admins)
        register_rest_route($this->namespace, '/projects/(?P<id>\d+)/approve-gate', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'approve_project_gate'],
            'permission_callback' => [$this, 'check_auth_permission']
        ]);
    }

    public static function ensure_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_project_tasks';
        $collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT(20) UNSIGNED NOT NULL,
            assigned_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            assigned_user_ids VARCHAR(255) NULL DEFAULT '',
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            task_type VARCHAR(50) NOT NULL DEFAULT 'custom',
            deadline DATETIME NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            priority VARCHAR(20) NOT NULL DEFAULT 'medium',
            created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            completed_at DATETIME NULL,
            completed_by BIGINT(20) UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_project (project_id),
            KEY idx_assigned_user (assigned_user_id, status),
            KEY idx_deadline (deadline),
            KEY idx_status (status)
        ) $collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Safe column check for existing installations
        $cols = $wpdb->get_col("DESCRIBE $table");
        if ($cols && !in_array('assigned_user_ids', $cols, true)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN assigned_user_ids VARCHAR(255) NULL DEFAULT '' AFTER assigned_user_id");
        }
    }

    /**
     * Automatically generate the standard 4-stage milestone pipeline upon project addition
     */
    public static function auto_generate_project_milestones($project_id, $assigned_user_id, $delivery_date, $created_by = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_project_tasks';
        $project_id = (int)$project_id;
        $assigned_user_id = (int)$assigned_user_id;

        // Check if milestones already created for this project
        $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE project_id = %d AND task_type IN ('initial_conversation', 'first_draft', 'fixing', 'final_update')", $project_id));
        if ($existing > 0) {
            return false;
        }

        $now_ts = current_time('timestamp');
        $del_ts = !empty($delivery_date) ? strtotime($delivery_date) : ($now_ts + (7 * 86400));
        $timeline_days = max(1, round(($del_ts - $now_ts) / 86400));

        // 1. Initial Conversation Started: 5 Hours deadline
        $wpdb->insert($table, [
            'project_id' => $project_id,
            'assigned_user_id' => $assigned_user_id,
            'title' => 'Initial conversation started',
            'description' => 'Review order requirements, send greeting to client, and confirm project scope.',
            'task_type' => 'initial_conversation',
            'deadline' => date('Y-m-d H:i:s', $now_ts + (5 * 3600)),
            'status' => 'pending',
            'priority' => 'urgent',
            'created_by' => $created_by,
            'created_at' => current_time('mysql')
        ]);

        // 2. 1st Draft: 3 days for 7+ days timeline, 2 days for <7 days timeline
        $first_draft_days = ($timeline_days >= 7) ? 3 : 2;
        $wpdb->insert($table, [
            'project_id' => $project_id,
            'assigned_user_id' => $assigned_user_id,
            'title' => '1st Draft',
            'description' => "Complete core structure and first milestone draft within {$first_draft_days} days.",
            'task_type' => 'first_draft',
            'deadline' => date('Y-m-d H:i:s', $now_ts + ($first_draft_days * 86400)),
            'status' => 'pending',
            'priority' => 'high',
            'created_by' => $created_by,
            'created_at' => current_time('mysql')
        ]);

        // 3. Fixing: No specific deadline (as needed based on revisions)
        $wpdb->insert($table, [
            'project_id' => $project_id,
            'assigned_user_id' => $assigned_user_id,
            'title' => 'Fixing / Revisions',
            'description' => 'Address internal QA adjustments, client feedback, and bug fixes.',
            'task_type' => 'fixing',
            'deadline' => null,
            'status' => 'pending',
            'priority' => 'medium',
            'created_by' => $created_by,
            'created_at' => current_time('mysql')
        ]);

        // 4. Final Update & Delivery: Delivery milestone
        $wpdb->insert($table, [
            'project_id' => $project_id,
            'assigned_user_id' => $assigned_user_id,
            'title' => 'Final update & delivery',
            'description' => 'Final verification, asset packaging, and on-time delivery submission.',
            'task_type' => 'final_update',
            'deadline' => !empty($delivery_date) ? date('Y-m-d 23:59:59', strtotime($delivery_date)) : null,
            'status' => 'pending',
            'priority' => 'high',
            'created_by' => $created_by,
            'created_at' => current_time('mysql')
        ]);

        return true;
    }

    /**
     * Auto-generate 4 Quality Assurance & Delivery Approval Tasks for the Team Leader
     * 1. Review Project & Requirements
     * 2. Approve 1st Delivery / Milestone Draft
     * 3. Review Done Before Final Delivery
     * 4. Give Permission for Final Delivery Followup
     */
    public static function auto_generate_leader_approval_tasks($project_id, $team_name = '', $delivery_date = '', $created_by = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_project_tasks';
        $p_table = $wpdb->prefix . 'ftm_projects';
        $project_id = (int)$project_id;

        $project = $wpdb->get_row($wpdb->prepare("SELECT * FROM $p_table WHERE id = %d", $project_id));
        if (!$project) {
            return false;
        }

        if (empty($team_name)) {
            $team_name = $project->team_name;
        }
        if (empty($team_name) && !empty($project->assigned_user_id)) {
            $team_name = get_user_meta($project->assigned_user_id, 'ftm_team', true);
        }
        if (empty($delivery_date)) {
            $delivery_date = $project->delivery_date;
        }

        // Leader IDs for this team
        $leader_ids = class_exists('FTM_Permissions') && method_exists('FTM_Permissions', 'get_team_leaders_for_team')
            ? FTM_Permissions::get_team_leaders_for_team($team_name)
            : [1];

        if (empty($leader_ids)) {
            $leader_ids = [1];
        }

        $primary_leader_id = (int)$leader_ids[0];
        $all_leader_ids_str = implode(',', array_map('intval', $leader_ids));

        $clean_order = '#' . ltrim($project->order_id ?: (string)$project_id, '#');
        $now_ts = current_time('timestamp');
        $del_ts = !empty($delivery_date) ? strtotime($delivery_date) : ($now_ts + (7 * 86400));
        $diff = max(3600, $del_ts - $now_ts);

        $gates = [
            [
                'task_type' => 'leader_review_project',
                'title' => "Review Project & Requirements ({$clean_order})",
                'description' => "Review project brief, client attachments, and initial instructions for Order {$clean_order} to ensure quality and alignment.",
                'deadline' => date('Y-m-d H:i:s', min($now_ts + (12 * 3600), $del_ts - 3600)),
                'priority' => 'urgent'
            ],
            [
                'task_type' => 'leader_approve_1st_delivery',
                'title' => "Approve 1st Delivery / Milestone Draft ({$clean_order})",
                'description' => "Audit and approve 1st draft / milestone deliverables before client submission for Order {$clean_order}.",
                'deadline' => date('Y-m-d H:i:s', $now_ts + round($diff * 0.4)),
                'priority' => 'high'
            ],
            [
                'task_type' => 'leader_review_before_final',
                'title' => "Review Done Before Final Delivery ({$clean_order})",
                'description' => "Perform comprehensive QA inspection and verify all client revisions are resolved for Order {$clean_order}.",
                'deadline' => date('Y-m-d H:i:s', max($now_ts + 3600, $del_ts - (12 * 3600))),
                'priority' => 'high'
            ],
            [
                'task_type' => 'leader_permission_final_delivery',
                'title' => "Give Permission for Final Delivery Followup ({$clean_order})",
                'description' => "Authorize final delivery package and grant formal permission for client delivery followup for Order {$clean_order}.",
                'deadline' => !empty($delivery_date) ? date('Y-m-d 23:59:59', strtotime($delivery_date)) : date('Y-m-d H:i:s', $del_ts),
                'priority' => 'urgent'
            ]
        ];

        foreach ($gates as $g) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE project_id = %d AND task_type = %s",
                $project_id,
                $g['task_type']
            ));

            if (!$exists) {
                $wpdb->insert($table, [
                    'project_id' => $project_id,
                    'assigned_user_id' => $primary_leader_id,
                    'assigned_user_ids' => $all_leader_ids_str,
                    'title' => $g['title'],
                    'description' => $g['description'],
                    'task_type' => $g['task_type'],
                    'deadline' => $g['deadline'],
                    'status' => 'pending',
                    'priority' => $g['priority'],
                    'created_by' => $created_by ?: get_current_user_id(),
                    'created_at' => current_time('mysql')
                ]);
            }
        }

        return true;
    }

    /**
     * Get summary of the 4 Leader Quality Gates for a project with approver details
     */
    public static function get_leader_approvals_summary($project_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ftm_project_tasks';
        $project_id = (int)$project_id;

        $gate_types = [
            'leader_review_project' => [
                'key' => 'leader_review_project',
                'short_title' => 'Project Review',
                'label' => 'Review Project & Requirements',
                'icon' => '📋',
                'step' => 1
            ],
            'leader_approve_1st_delivery' => [
                'key' => 'leader_approve_1st_delivery',
                'short_title' => '1st Delivery Approval',
                'label' => 'Approve 1st Delivery',
                'icon' => '🎨',
                'step' => 2
            ],
            'leader_review_before_final' => [
                'key' => 'leader_review_before_final',
                'short_title' => 'Pre-Final QA Review',
                'label' => 'Review Done Before Final Delivery',
                'icon' => '🔍',
                'step' => 3
            ],
            'leader_permission_final_delivery' => [
                'key' => 'leader_permission_final_delivery',
                'short_title' => 'Final Delivery Permission',
                'label' => 'Give Permission for Final Delivery',
                'icon' => '🚀',
                'step' => 4
            ]
        ];

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE project_id = %d AND task_type IN ('leader_review_project', 'leader_approve_1st_delivery', 'leader_review_before_final', 'leader_permission_final_delivery') ORDER BY id ASC",
            $project_id
        ));

        $keyed_rows = [];
        foreach ($rows ?: [] as $r) {
            $keyed_rows[$r->task_type] = $r;
        }

        $approvals = [];
        $approved_count = 0;

        foreach ($gate_types as $type => $meta) {
            $t = $keyed_rows[$type] ?? null;
            $is_done = ($t && $t->status === 'done');
            if ($is_done) {
                $approved_count++;
            }

            $approver_data = null;
            if ($t && !empty($t->completed_by)) {
                $approver = get_userdata((int)$t->completed_by);
                if ($approver) {
                    $c_avatar = get_user_meta($approver->ID, 'ftm_custom_avatar', true);
                    $u_team = get_user_meta($approver->ID, 'ftm_team', true) ?: '';
                    $u_role = get_user_meta($approver->ID, 'ftm_team_role', true) ?: 'Leader';
                    $role_label = ucwords(str_replace('_', ' ', $u_role));
                    if (!empty($u_team)) {
                        $role_label = $u_team . ' ' . $role_label;
                    }
                    if (FTM_Permissions::is_super_admin($approver->ID)) {
                        $role_label = 'Super Admin';
                    }

                    $approver_data = [
                        'id' => (int)$approver->ID,
                        'name' => $approver->display_name,
                        'role' => $role_label,
                        'avatar' => !empty($c_avatar) ? $c_avatar : get_avatar_url($approver->ID, ['size' => 64])
                    ];
                }
            }

            $approved_at_formatted = '';
            if ($t && !empty($t->completed_at) && $t->completed_at !== '0000-00-00 00:00:00') {
                $ts = strtotime($t->completed_at);
                $approved_at_formatted = date('M j, Y g:i A', $ts);
            }

            $approvals[] = [
                'task_id' => $t ? (int)$t->id : 0,
                'gate_type' => $type,
                'short_title' => $meta['short_title'],
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'step' => $meta['step'],
                'is_approved' => $is_done,
                'status' => $t ? $t->status : 'pending',
                'deadline' => $t ? $t->deadline : null,
                'deadline_formatted' => ($t && $t->deadline) ? date('M j, g:i A', strtotime($t->deadline)) : '',
                'approved_by' => $approver_data,
                'approved_at' => $t ? $t->completed_at : null,
                'approved_at_formatted' => $approved_at_formatted
            ];
        }

        return [
            'total_gates' => count($gate_types),
            'approved_gates' => $approved_count,
            'is_all_approved' => ($approved_count === count($gate_types)),
            'gates' => $approvals
        ];
    }

    /**
     * POST /projects/{id}/approve-gate
     * Approve or toggle a leader quality gate for a project
     */
    public function approve_project_gate($request) {
        global $wpdb;
        $project_id = (int)$request['id'];
        $current_user_id = get_current_user_id();

        if (!$current_user_id) {
            return new WP_Error('unauthorized', 'Authentication required.', ['status' => 401]);
        }

        $p_table = $wpdb->prefix . 'ftm_projects';
        $project = $wpdb->get_row($wpdb->prepare("SELECT * FROM $p_table WHERE id = %d", $project_id));
        if (!$project) {
            return new WP_Error('not_found', 'Project not found.', ['status' => 404]);
        }

        // Check permission: Leader of this team, Super Admin, or Executive
        $is_admin = FTM_Permissions::is_super_admin($current_user_id) || (method_exists('FTM_Permissions', 'is_executive') && FTM_Permissions::is_executive($current_user_id));
        $user_team = FTM_Permissions::get_user_team($current_user_id);
        $is_leader = FTM_Permissions::is_leader($current_user_id);

        if (!$is_admin) {
            if (!$is_leader || empty($user_team) || strcasecmp($user_team, $project->team_name) !== 0) {
                return new WP_Error('forbidden', 'Only squad leaders or administrators can approve this quality gate.', ['status' => 403]);
            }
        }

        $data = $request->get_json_params() ?: $request->get_params() ?: [];
        $gate_type = sanitize_text_field($data['gate_type'] ?? '');
        $task_id = (int)($data['task_id'] ?? 0);
        $action = sanitize_text_field($data['action'] ?? 'approve'); // 'approve' or 'unapprove'

        $t_table = $wpdb->prefix . 'ftm_project_tasks';

        // Auto ensure gates exist
        self::auto_generate_leader_approval_tasks($project_id, $project->team_name, $project->delivery_date);

        if ($task_id > 0) {
            $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t_table WHERE id = %d AND project_id = %d", $task_id, $project_id));
        } elseif (!empty($gate_type)) {
            $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t_table WHERE project_id = %d AND task_type = %s", $project_id, $gate_type));
        } else {
            return new WP_Error('bad_request', 'Missing gate_type or task_id parameter.', ['status' => 400]);
        }

        if (!$task) {
            return new WP_Error('not_found', 'Quality gate task not found.', ['status' => 404]);
        }

        $now = current_time('mysql');
        $approver_name = wp_get_current_user()->display_name;

        if ($action === 'unapprove') {
            $wpdb->update($t_table, [
                'status' => 'pending',
                'completed_at' => null,
                'completed_by' => null
            ], ['id' => $task->id]);

            // Log event in project history
            $wpdb->insert($wpdb->prefix . 'ftm_project_history', [
                'project_id' => $project_id,
                'user_id' => $current_user_id,
                'user_name' => $approver_name,
                'old_status' => $project->status,
                'new_status' => $project->status,
                'note' => sprintf('Quality gate "%s" reset to pending by %s.', $task->title, $approver_name)
            ]);

            $message = sprintf('Quality gate "%s" reset to pending.', $task->title);
        } else {
            $wpdb->update($t_table, [
                'status' => 'done',
                'completed_at' => $now,
                'completed_by' => $current_user_id
            ], ['id' => $task->id]);

            // Log event in project history
            $wpdb->insert($wpdb->prefix . 'ftm_project_history', [
                'project_id' => $project_id,
                'user_id' => $current_user_id,
                'user_name' => $approver_name,
                'old_status' => $project->status,
                'new_status' => $project->status,
                'note' => sprintf('Quality gate approved: "%s" by %s.', $task->title, $approver_name)
            ]);

            // Notification broadcast
            if (class_exists('FTM_Notifications_Engine') && method_exists('FTM_Notifications_Engine', 'broadcast_to_leadership')) {
                try {
                    FTM_Notifications_Engine::broadcast_to_leadership([
                        'title' => '🛡️ Quality Gate Approved',
                        'message' => "{$approver_name} approved '{$task->title}' for Order #{$project->order_id}.",
                        'type' => 'gate_approved',
                        'target_id' => $project_id,
                        'team' => $project->team_name
                    ]);
                } catch (\Throwable $e) {}
            }

            $message = sprintf('Quality gate "%s" successfully approved.', $task->title);
        }

        $summary = self::get_leader_approvals_summary($project_id);

        return rest_ensure_response([
            'success' => true,
            'message' => $message,
            'task_id' => (int)$task->id,
            'leader_approvals' => $summary
        ]);
    }

    /**
     * GET /projects/{id}/tasks
     */
    public function get_project_tasks($request) {
        $project_id = (int)$request['id'];
        return rest_ensure_response($this->get_project_tasks_data($project_id));
    }

    public function get_project_tasks_data($project_id) {
        global $wpdb;
        $project_id = (int)$project_id;
        $table = $wpdb->prefix . 'ftm_project_tasks';
        $p_table = $wpdb->prefix . 'ftm_projects';

        $proj = $wpdb->get_row($wpdb->prepare("SELECT id, assigned_user_id, team_name, delivery_date, created_by FROM $p_table WHERE id = %d", $project_id));
        if ($proj) {
            // Auto-seed milestones if project exists but milestones don't yet
            $milestones_count = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE project_id = %d AND task_type IN ('initial_conversation', 'first_draft', 'fixing', 'final_update')",
                $project_id
            ));
            if ($milestones_count === 0 && !empty($proj->assigned_user_id)) {
                self::auto_generate_project_milestones($proj->id, $proj->assigned_user_id, $proj->delivery_date, $proj->created_by);
            }

            // Auto-seed leader approval tasks if needed
            $leader_gates_count = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE project_id = %d AND task_type LIKE 'leader_%'",
                $project_id
            ));
            if ($leader_gates_count === 0) {
                self::auto_generate_leader_approval_tasks($proj->id, $proj->team_name, $proj->delivery_date, $proj->created_by);
            }
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE project_id = %d ORDER BY FIELD(status, 'pending', 'in_progress', 'done'), deadline ASC, id ASC",
            $project_id
        ));

        $formatted = array_map([$this, 'format_task_item'], $rows ?: []);

        $total = count($formatted);
        $done_count = count(array_filter($formatted, function($t) { return $t['status'] === 'done'; }));
        $pending_count = $total - $done_count;

        return [
            'project_id' => $project_id,
            'total_tasks' => $total,
            'completed_tasks' => $done_count,
            'pending_tasks' => $pending_count,
            'progress_percent' => ($total > 0) ? round(($done_count / $total) * 100) : 0,
            'tasks' => $formatted,
            'leader_approvals' => self::get_leader_approvals_summary($project_id)
        ];
    }

    /**
     * POST /projects/{id}/tasks (Add new custom task)
     */
    public function create_project_task($request) {
        global $wpdb;
        $project_id = (int)$request['id'];
        $current_user_id = get_current_user_id();

        $p_table = $wpdb->prefix . 'ftm_projects';
        $proj = $wpdb->get_row($wpdb->prepare("SELECT id, assigned_user_id, assigned_user_ids, team_name FROM $p_table WHERE id = %d", $project_id));
        if (!$proj) {
            return $this->format_error(__('Project not found.', 'fiverr-team-management'), 404);
        }

        $data = $request->get_json_params() ?: $request->get_params();
        $title = sanitize_text_field($data['title'] ?? '');
        if (empty($title)) {
            return $this->format_error(__('Task title is required.', 'fiverr-team-management'), 400);
        }

        $parsed_ids = [];
        if (isset($data['assigned_user_ids'])) {
            $raw_list = is_array($data['assigned_user_ids']) ? $data['assigned_user_ids'] : explode(',', (string)$data['assigned_user_ids']);
            foreach ($raw_list as $rid) {
                $uid_int = (int)$rid;
                if ($uid_int > 0 && !in_array($uid_int, $parsed_ids, true)) {
                    $parsed_ids[] = $uid_int;
                }
            }
        }
        if (empty($parsed_ids) && !empty($data['assigned_user_id'])) {
            $parsed_ids[] = (int)$data['assigned_user_id'];
        }
        if (empty($parsed_ids)) {
            if (!empty($proj->assigned_user_ids)) {
                foreach (explode(',', (string)$proj->assigned_user_ids) as $pid_raw) {
                    $pid_int = (int)trim($pid_raw);
                    if ($pid_int > 0 && !in_array($pid_int, $parsed_ids, true)) {
                        $parsed_ids[] = $pid_int;
                    }
                }
            }
            if (empty($parsed_ids) && (int)$proj->assigned_user_id > 0) {
                $parsed_ids[] = (int)$proj->assigned_user_id;
            }
        }

        $assigned_user_id = !empty($parsed_ids) ? (int)$parsed_ids[0] : 0;
        $assigned_user_ids_str = implode(',', $parsed_ids);

        $deadline = !empty($data['deadline']) ? sanitize_text_field($data['deadline']) : null;
        if ($deadline && strlen($deadline) === 10) {
            $deadline .= ' 23:59:59';
        }

        $priority = sanitize_text_field($data['priority'] ?? 'medium');
        if (!in_array($priority, ['low', 'medium', 'high', 'urgent'], true)) {
            $priority = 'medium';
        }

        $table = $wpdb->prefix . 'ftm_project_tasks';
        $wpdb->insert($table, [
            'project_id' => $project_id,
            'assigned_user_id' => $assigned_user_id,
            'assigned_user_ids' => $assigned_user_ids_str,
            'title' => $title,
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'task_type' => sanitize_text_field($data['task_type'] ?? 'custom'),
            'deadline' => $deadline,
            'status' => 'pending',
            'priority' => $priority,
            'created_by' => $current_user_id,
            'created_at' => current_time('mysql')
        ]);

        $task_id = $wpdb->insert_id;
        $created_task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $task_id));

        if (class_exists('FTM_Mailer') && !empty($parsed_ids)) {
            FTM_Mailer::send_task_assigned_email($task_id, $project_id, $parsed_ids);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Task added successfully.', 'fiverr-team-management'),
            'task' => $this->format_task_item($created_task)
        ]);
    }

    /**
     * POST /tasks/{id}/status (Toggle or set pending / done)
     */
    public function update_task_status($request) {
        global $wpdb;
        $task_id = (int)$request['id'];
        $current_user_id = get_current_user_id();
        $table = $wpdb->prefix . 'ftm_project_tasks';

        $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $task_id));
        if (!$task) {
            return $this->format_error(__('Task not found.', 'fiverr-team-management'), 404);
        }

        $data = $request->get_json_params() ?: $request->get_params();
        $new_status = sanitize_text_field($data['status'] ?? '');
        if (empty($new_status)) {
            // Toggle between done and pending
            $new_status = ($task->status === 'done') ? 'pending' : 'done';
        }

        if (!in_array($new_status, ['pending', 'in_progress', 'done'], true)) {
            $new_status = 'pending';
        }

        $completed_at = ($new_status === 'done') ? current_time('mysql') : null;
        $completed_by = ($new_status === 'done') ? $current_user_id : null;

        $wpdb->update($table, [
            'status' => $new_status,
            'completed_at' => $completed_at,
            'completed_by' => $completed_by
        ], ['id' => $task_id]);

        $updated_task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $task_id));

        $logged_to_report = false;
        $order_label = '';
        if ($new_status === 'done' && $updated_task) {
            $sync_res = $this->sync_completed_task_to_daily_report($updated_task, $current_user_id);
            $logged_to_report = !empty($sync_res['logged']);
            $order_label = $sync_res['order_id'] ?? '';
        }

        $msg = ($new_status === 'done')
            ? ($logged_to_report && $order_label
                ? sprintf(__('Task marked as completed & logged to %s daily report! 👍', 'fiverr-team-management'), $order_label)
                : __('Task marked as completed & logged to daily report! 👍', 'fiverr-team-management'))
            : __('Task reopened.', 'fiverr-team-management');

        return rest_ensure_response([
            'success' => true,
            'message' => $msg,
            'logged_to_daily_report' => $logged_to_report,
            'task' => $this->format_task_item($updated_task)
        ]);
    }

    /**
     * Automatically log a completed task into today's Daily Progress Report for that dedicated order.
     */
    private function sync_completed_task_to_daily_report($task, $actor_user_id) {
        global $wpdb;
        if (!$task || empty($task->project_id)) {
            return ['logged' => false];
        }

        $p_table = $wpdb->prefix . 'ftm_projects';
        $proj = $wpdb->get_row($wpdb->prepare(
            "SELECT id, order_id, client_name, team_name, assigned_user_id FROM $p_table WHERE id = %d",
            (int)$task->project_id
        ));
        if (!$proj) {
            return ['logged' => false];
        }

        $assigned_uid = (int)$task->assigned_user_id ?: (int)$proj->assigned_user_id;
        $is_lead_or_admin = FTM_Permissions::is_super_admin($actor_user_id) || FTM_Permissions::is_leader($actor_user_id);
        $report_user_id = ($is_lead_or_admin && $assigned_uid > 0) ? $assigned_uid : ($actor_user_id ?: $assigned_uid);
        if ($report_user_id <= 0) {
            return ['logged' => false];
        }

        $today = current_time('Y-m-d');
        $reports_table = $wpdb->prefix . 'ftm_daily_reports';
        $items_table = $wpdb->prefix . 'ftm_daily_report_items';
        $tasks_table = $wpdb->prefix . 'ftm_project_tasks';

        $team_name = !empty($proj->team_name) ? $proj->team_name : (get_user_meta($report_user_id, 'ftm_team', true) ?: 'WpCraft');

        $report = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status FROM $reports_table WHERE user_id = %d AND report_date = %s",
            $report_user_id, $today
        ));

        if (!$report) {
            $wpdb->insert($reports_table, [
                'user_id' => $report_user_id,
                'team_name' => $team_name,
                'report_type' => 'work',
                'report_date' => $today,
                'status' => 'submitted',
                'submission_time' => current_time('H:i:s'),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]);
            $report_id = (int)$wpdb->insert_id;
        } else {
            $report_id = (int)$report->id;
            if ($report->status === 'draft') {
                $wpdb->update($reports_table, [
                    'status' => 'submitted',
                    'submission_time' => current_time('H:i:s'),
                    'updated_at' => current_time('mysql')
                ], ['id' => $report_id]);
            }
        }

        if ($report_id <= 0) {
            return ['logged' => false];
        }

        // Compute task milestone completion % for this dedicated order
        $total_t = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tasks_table WHERE project_id = %d", (int)$proj->id));
        $done_t = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tasks_table WHERE project_id = %d AND status = 'done'", (int)$proj->id));
        $task_pct = $total_t > 0 ? (int)min(100, round(($done_t / $total_t) * 100)) : 50;

        $task_line = '✓ [Task Completed] ' . trim((string)$task->title);
        $clean_order = '#' . ltrim((string)$proj->order_id, '#');

        // Find existing daily report item for this dedicated order
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $items_table WHERE report_id = %d AND (project_id = %d OR (order_id != '' AND order_id = %s))",
            $report_id, (int)$proj->id, $proj->order_id
        ));

        if ($item) {
            $existing_text = trim((string)($item->tasks_completed ?? ''));
            if (stripos($existing_text, $task_line) === false) {
                $new_text = $existing_text !== '' ? ($existing_text . "\n" . $task_line) : $task_line;
            } else {
                $new_text = $existing_text;
            }
            $wpdb->update($items_table, [
                'tasks_completed' => sanitize_textarea_field($new_text),
                'progress_percent' => max((int)$item->progress_percent, $task_pct)
            ], ['id' => (int)$item->id]);
        } else {
            $wpdb->insert($items_table, [
                'report_id' => $report_id,
                'project_id' => (int)$proj->id,
                'order_id' => $proj->order_id,
                'client_name' => $proj->client_name,
                'tasks_completed' => sanitize_textarea_field($task_line),
                'work_in_progress' => '',
                'next_day_plan' => '',
                'hours_spent' => 0.00,
                'progress_percent' => $task_pct,
                'created_at' => current_time('mysql')
            ]);
        }

        return ['logged' => true, 'order_id' => $clean_order, 'report_id' => $report_id];
    }

    /**
     * POST /tasks/{id} (Edit task)
     */
    public function update_task($request) {
        global $wpdb;
        $task_id = (int)$request['id'];
        $table = $wpdb->prefix . 'ftm_project_tasks';

        $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $task_id));
        if (!$task) {
            return $this->format_error(__('Task not found.', 'fiverr-team-management'), 404);
        }

        $data = $request->get_json_params() ?: $request->get_params();
        $update = [];

        if (isset($data['title'])) {
            $update['title'] = sanitize_text_field($data['title']);
        }
        if (isset($data['description'])) {
            $update['description'] = sanitize_textarea_field($data['description']);
        }
        if (isset($data['assigned_user_id'])) {
            $update['assigned_user_id'] = (int)$data['assigned_user_id'];
        }
        if (isset($data['priority']) && in_array($data['priority'], ['low', 'medium', 'high', 'urgent'], true)) {
            $update['priority'] = sanitize_text_field($data['priority']);
        }
        if (isset($data['deadline'])) {
            $update['deadline'] = !empty($data['deadline']) ? sanitize_text_field($data['deadline']) : null;
            if ($update['deadline'] && strlen($update['deadline']) === 10) {
                $update['deadline'] .= ' 23:59:59';
            }
        }
        if (isset($data['status']) && in_array($data['status'], ['pending', 'in_progress', 'done'], true)) {
            $update['status'] = sanitize_text_field($data['status']);
            if ($update['status'] === 'done') {
                $update['completed_at'] = current_time('mysql');
                $update['completed_by'] = get_current_user_id();
            } else {
                $update['completed_at'] = null;
                $update['completed_by'] = null;
            }
        }

        $was_done = ($task->status === 'done');
        if (!empty($update)) {
            $wpdb->update($table, $update, ['id' => $task_id]);
        }

        $updated_task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $task_id));

        if (!$was_done && $updated_task && $updated_task->status === 'done') {
            $this->sync_completed_task_to_daily_report($updated_task, get_current_user_id());
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Task updated successfully.', 'fiverr-team-management'),
            'task' => $this->format_task_item($updated_task)
        ]);
    }

    /**
     * DELETE /tasks/{id}
     */
    public function delete_task($request) {
        global $wpdb;
        $task_id = (int)$request['id'];
        $table = $wpdb->prefix . 'ftm_project_tasks';

        $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $task_id));
        if (!$task) {
            return $this->format_error(__('Task not found.', 'fiverr-team-management'), 404);
        }

        $wpdb->delete($table, ['id' => $task_id]);

        return rest_ensure_response([
            'success' => true,
            'message' => __('Task deleted.', 'fiverr-team-management')
        ]);
    }

    /**
     * GET /tasks (Dedicated My Tasks / Team Tasks Board)
     * - Individual Members: ONLY tasks explicitly assigned to them.
     * - Team Leaders: ONLY tasks for their dedicated team/squad (or their own tasks).
     * - Super Admin / Manager / Executive: All teams, organized & filterable by team/specialist/mine.
     */
    public function get_tasks_board($request) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $is_admin = FTM_Permissions::is_super_admin($current_user_id) || (method_exists('FTM_Permissions', 'is_executive') && FTM_Permissions::is_executive($current_user_id));
        $is_leader = FTM_Permissions::is_leader($current_user_id);
        $user_team = FTM_Permissions::get_user_team($current_user_id);

        $tasks_table = $wpdb->prefix . 'ftm_project_tasks';
        $projects_table = $wpdb->prefix . 'ftm_projects';
        $assignees_table = $wpdb->prefix . 'ftm_project_assignees';

        $scope = sanitize_text_field($request->get_param('scope') ?: (($is_admin || $is_leader) ? 'all' : 'mine'));
        $filter_team = sanitize_text_field($request->get_param('team') ?: 'all');
        $filter_user_id = (int)$request->get_param('user_id');
        $filter_status = sanitize_text_field($request->get_param('status') ?: 'all');
        $filter_priority = sanitize_text_field($request->get_param('priority') ?: 'all');
        $search = sanitize_text_field($request->get_param('search') ?: '');

        // Dedicated member clause: matches tasks explicitly assigned to the user (only falls back to project assignee if task has no assignee)
        $dedicated_user_sql = "(t.assigned_user_id = %d OR FIND_IN_SET(%d, COALESCE(t.assigned_user_ids, '')) OR (t.assigned_user_id = 0 AND COALESCE(t.assigned_user_ids, '') = '' AND (p.assigned_user_id = %d OR FIND_IN_SET(%d, p.assigned_user_ids) OR p.id IN (SELECT project_id FROM $assignees_table WHERE user_id = %d))))";

        // Strict Role Isolation
        if (!$is_admin && !$is_leader) {
            $scope = 'mine';
            $filter_team = 'all';
            $filter_user_id = 0;
        } elseif ($is_leader && !$is_admin) {
            // Team Leaders are strictly locked to their own dedicated squad
            $filter_team = !empty($user_team) ? $user_team : 'all';
        }

        $base_where = ["(p.status NOT IN ('cancelled', 'delivery_accepted', 'completed', 'delivered') OR (t.status = 'done' AND t.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)))"];

        // Compute per-team counts for Manager/Leader tabs (before team/status/priority filters)
        $team_counts = [];
        $all_visible_count = 0;
        if ($is_admin || $is_leader) {
            $tc_where = $base_where;
            $tc_params = [];
            if ($is_leader && !$is_admin && !empty($user_team)) {
                $tc_where[] = "p.team_name = %s";
                $tc_params[] = $user_team;
            }
            $tc_sql = "SELECT COALESCE(NULLIF(p.team_name, ''), 'Unassigned') AS team_name, COUNT(*) AS cnt
                       FROM $tasks_table t
                       JOIN $projects_table p ON t.project_id = p.id
                       WHERE " . implode(' AND ', $tc_where) . "
                       GROUP BY p.team_name";
            $tc_rows = !empty($tc_params) ? $wpdb->get_results($wpdb->prepare($tc_sql, $tc_params)) : $wpdb->get_results($tc_sql);
            foreach ($tc_rows ?: [] as $tr) {
                $team_counts[$tr->team_name] = (int)$tr->cnt;
                $all_visible_count += (int)$tr->cnt;
            }
        }

        // Compute current user's own dedicated task count (and pending count for badge)
        $my_sql = "SELECT COUNT(*) AS total_cnt, SUM(CASE WHEN t.status != 'done' THEN 1 ELSE 0 END) AS pending_cnt
                   FROM $tasks_table t
                   JOIN $projects_table p ON t.project_id = p.id
                   WHERE " . implode(' AND ', $base_where) . " AND $dedicated_user_sql";
        $my_stats = $wpdb->get_row($wpdb->prepare($my_sql, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id));
        $my_total_count = (int)($my_stats->total_cnt ?? 0);
        $my_pending_count = (int)($my_stats->pending_cnt ?? 0);

        $where = $base_where;
        $params = [];

        if ($scope === 'mine') {
            $where[] = $dedicated_user_sql;
            $params[] = $current_user_id;
            $params[] = $current_user_id;
            $params[] = $current_user_id;
            $params[] = $current_user_id;
            $params[] = $current_user_id;
        } else {
            if ($filter_team !== 'all' && !empty($filter_team)) {
                $where[] = "p.team_name = %s";
                $params[] = $filter_team;
            }
            if ($filter_user_id > 0) {
                $where[] = $dedicated_user_sql;
                $params[] = $filter_user_id;
                $params[] = $filter_user_id;
                $params[] = $filter_user_id;
                $params[] = $filter_user_id;
                $params[] = $filter_user_id;
            }
        }

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = "(t.title LIKE %s OR t.description LIKE %s OR p.order_id LIKE %s OR p.client_name LIKE %s OR p.team_name LIKE %s)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = implode(' AND ', $where);
        $sql = "SELECT t.*, p.order_id, p.client_name, p.team_name, p.status as project_status, p.delivery_date as project_delivery_date
                FROM $tasks_table t
                JOIN $projects_table p ON t.project_id = p.id
                WHERE $where_sql
                ORDER BY p.team_name ASC,
                         FIELD(t.status, 'pending', 'in_progress', 'done'),
                         CASE WHEN t.deadline IS NULL THEN 1 ELSE 0 END,
                         t.deadline ASC,
                         t.id DESC
                LIMIT 300";

        $rows = !empty($params) ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);
        $all_formatted = array_map([$this, 'format_task_item_with_project'], $rows ?: []);

        // Compute KPI overview counts before applying status/priority pill filters
        $total_cnt = count($all_formatted);
        $pending_cnt = 0;
        $overdue_cnt = 0;
        $completed_cnt = 0;
        foreach ($all_formatted as $item) {
            if ($item['is_done']) {
                $completed_cnt++;
            } else {
                $pending_cnt++;
                if (!empty($item['is_overdue'])) {
                    $overdue_cnt++;
                }
            }
        }

        // Apply status & priority filters to returned list
        $filtered = array_values(array_filter($all_formatted, function($item) use ($filter_status, $filter_priority) {
            if ($filter_status === 'pending' && $item['is_done']) return false;
            if ($filter_status === 'done' && !$item['is_done']) return false;
            if ($filter_status === 'overdue' && ($item['is_done'] || empty($item['is_overdue']))) return false;
            if ($filter_priority !== 'all' && $item['priority'] !== $filter_priority) return false;
            return true;
        }));

        return rest_ensure_response([
            'scope' => $scope,
            'role_scope' => $is_admin ? 'admin' : ($is_leader ? 'leader' : 'member'),
            'user_team' => $user_team ?: '',
            'active_team' => $filter_team,
            'all_visible_count' => $all_visible_count,
            'team_counts' => $team_counts,
            'my_total_count' => $my_total_count,
            'my_pending_count' => $my_pending_count,
            'total_tasks' => $total_cnt,
            'pending_count' => $pending_cnt,
            'overdue_count' => $overdue_cnt,
            'completed_count' => $completed_cnt,
            'tasks' => $filtered
        ]);
    }

    /**
     * GET /members/{id}/tasks (Tasks strictly dedicated to a specific member)
     */
    public function get_member_tasks($request) {
        global $wpdb;
        $member_id = (int)$request['id'];
        $tasks_table = $wpdb->prefix . 'ftm_project_tasks';
        $projects_table = $wpdb->prefix . 'ftm_projects';
        $assignees_table = $wpdb->prefix . 'ftm_project_assignees';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, p.order_id, p.client_name, p.team_name, p.status as project_status, p.delivery_date as project_delivery_date
             FROM $tasks_table t
             JOIN $projects_table p ON t.project_id = p.id
             WHERE (t.assigned_user_id = %d OR FIND_IN_SET(%d, COALESCE(t.assigned_user_ids, '')) OR (t.assigned_user_id = 0 AND COALESCE(t.assigned_user_ids, '') = '' AND (p.assigned_user_id = %d OR FIND_IN_SET(%d, p.assigned_user_ids) OR p.id IN (SELECT project_id FROM $assignees_table WHERE user_id = %d))))
               AND (p.status NOT IN ('cancelled', 'delivery_accepted', 'completed', 'delivered') OR (t.status = 'done' AND t.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)))
             ORDER BY FIELD(t.status, 'pending', 'in_progress', 'done'), t.deadline ASC, t.id ASC
             LIMIT 150",
            $member_id, $member_id, $member_id, $member_id, $member_id
        ));

        $formatted = array_map([$this, 'format_task_item_with_project'], $rows ?: []);

        $pending = array_values(array_filter($formatted, function($x) { return $x['status'] !== 'done'; }));
        $overdue = array_values(array_filter($pending, function($x) { return !empty($x['is_overdue']); }));
        $completed = array_values(array_filter($formatted, function($x) { return $x['status'] === 'done'; }));

        return rest_ensure_response([
            'member_id' => $member_id,
            'total_tasks' => count($formatted),
            'pending_count' => count($pending),
            'overdue_count' => count($overdue),
            'completed_count' => count($completed),
            'pending_tasks' => $pending,
            'completed_tasks' => $completed,
            'tasks' => $formatted
        ]);
    }

    /**
     * GET /tasks/my-pending
     */
    public function get_my_pending_tasks($request) {
        $current_user_id = get_current_user_id();
        $request->set_param('id', $current_user_id);
        return $this->get_member_tasks($request);
    }

    /**
     * Format Task Item
     */
    public function format_task_item($t) {
        $now = current_time('timestamp');
        $deadline_ts = !empty($t->deadline) ? strtotime($t->deadline) : null;
        $is_overdue = ($t->status !== 'done' && $deadline_ts && $deadline_ts < $now);

        $countdown_label = '';
        if ($deadline_ts) {
            $diff = $deadline_ts - $now;
            if ($diff < 0) {
                $countdown_label = 'Overdue';
            } elseif ($diff < 3600) {
                $countdown_label = ceil($diff / 60) . 'm left';
            } elseif ($diff < 86400) {
                $countdown_label = ceil($diff / 3600) . 'h left';
            } else {
                $countdown_label = ceil($diff / 86400) . 'd left';
            }
        }

        $uid_list = [];
        if (!empty($t->assigned_user_ids)) {
            foreach (explode(',', (string)$t->assigned_user_ids) as $raw_uid) {
                $u_int = (int)trim($raw_uid);
                if ($u_int > 0 && !in_array($u_int, $uid_list, true)) {
                    $uid_list[] = $u_int;
                }
            }
        }
        if (!empty($t->assigned_user_id) && !in_array((int)$t->assigned_user_id, $uid_list, true)) {
            array_unshift($uid_list, (int)$t->assigned_user_id);
        }

        $assigned_users = [];
        foreach ($uid_list as $uid_item) {
            $u = get_userdata($uid_item);
            if ($u) {
                $assigned_users[] = [
                    'id' => (int)$u->ID,
                    'name' => $u->display_name,
                    'team' => get_user_meta($u->ID, 'ftm_team', true) ?: '',
                    'avatar' => get_user_meta($u->ID, 'ftm_custom_avatar', true) ?: get_avatar_url($u->ID, ['size' => 64])
                ];
            }
        }
        $assigned_user = !empty($assigned_users) ? $assigned_users[0] : null;

        $completed_by_user = null;
        if (!empty($t->completed_by)) {
            $cu = get_userdata((int)$t->completed_by);
            if ($cu) {
                $c_avatar = get_user_meta($cu->ID, 'ftm_custom_avatar', true);
                $u_team = get_user_meta($cu->ID, 'ftm_team', true) ?: '';
                $u_role = get_user_meta($cu->ID, 'ftm_team_role', true) ?: 'Leader';
                $role_label = ucwords(str_replace('_', ' ', $u_role));
                if (!empty($u_team)) {
                    $role_label = $u_team . ' ' . $role_label;
                }
                if (class_exists('FTM_Permissions') && FTM_Permissions::is_super_admin($cu->ID)) {
                    $role_label = 'Super Admin';
                }
                $completed_by_user = [
                    'id' => (int)$cu->ID,
                    'name' => $cu->display_name,
                    'role' => $role_label,
                    'avatar' => !empty($c_avatar) ? $c_avatar : get_avatar_url($cu->ID, ['size' => 64])
                ];
            }
        }

        $completed_at_formatted = '';
        if (!empty($t->completed_at) && $t->completed_at !== '0000-00-00 00:00:00') {
            $completed_at_formatted = date('M j, Y g:i A', strtotime($t->completed_at));
        }

        return [
            'id' => (int)$t->id,
            'project_id' => (int)$t->project_id,
            'assigned_user_id' => !empty($uid_list) ? (int)$uid_list[0] : (int)$t->assigned_user_id,
            'assigned_user_ids' => $uid_list,
            'assigned_user' => $assigned_user,
            'assigned_users' => $assigned_users,
            'title' => $t->title,
            'description' => $t->description ?: '',
            'task_type' => $t->task_type ?: 'custom',
            'is_leader_gate' => (strpos((string)$t->task_type, 'leader_') === 0),
            'deadline' => $t->deadline,
            'deadline_formatted' => $deadline_ts ? date('M j, g:i A', $deadline_ts) : 'No Deadline',
            'countdown_label' => $countdown_label,
            'is_overdue' => $is_overdue,
            'status' => $t->status,
            'is_done' => ($t->status === 'done'),
            'priority' => $t->priority ?: 'medium',
            'created_by' => (int)$t->created_by,
            'completed_at' => $t->completed_at,
            'completed_at_formatted' => $completed_at_formatted,
            'completed_by' => (int)($t->completed_by ?? 0),
            'completed_by_user' => $completed_by_user,
            'created_at' => $t->created_at
        ];
    }

    public function format_task_item_with_project($t) {
        $base = $this->format_task_item($t);
        $clean_order = '#' . ltrim($t->order_id ?? '', '#');
        $base['project'] = [
            'id' => (int)$t->project_id,
            'order_id' => $clean_order,
            'client_name' => $t->client_name ?? '',
            'team_name' => $t->team_name ?? '',
            'project_status' => $t->project_status ?? '',
            'project_delivery_date' => $t->project_delivery_date ?? ''
        ];
        return $base;
    }

    public function check_leader_or_admin_permission($request = null) {
        $uid = get_current_user_id();
        if (!$uid) return false;
        return FTM_Permissions::is_super_admin($uid) || FTM_Permissions::is_leader($uid) || (class_exists('FTM_Permissions') && method_exists('FTM_Permissions', 'is_executive') && FTM_Permissions::is_executive($uid));
    }
}
