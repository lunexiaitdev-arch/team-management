<?php
/**
 * FTM Base REST Controller
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class FTM_REST_Controller extends WP_REST_Controller {

    protected $namespace = 'ftm/v1';

    public function check_auth_permission($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', __('You must be logged in to access this resource.', 'fiverr-team-management'), ['status' => 401]);
        }
        return true;
    }

    public function check_admin_permission($request) {
        if (!FTM_Permissions::is_super_admin()) {
            return new WP_Error('rest_forbidden', __('Only Super Admin / Manager can access this resource.', 'fiverr-team-management'), ['status' => 403]);
        }
        return true;
    }

    public function check_executive_or_admin_permission($request = null) {
        $uid = get_current_user_id();
        if (!FTM_Permissions::is_super_admin($uid) && !FTM_Permissions::is_executive($uid)) {
            return new WP_Error('rest_forbidden', __('Only Management, Executives, and Super Admin can access this resource.', 'fiverr-team-management'), ['status' => 403]);
        }
        return true;
    }

    public function check_leader_permission($request) {
        if (!FTM_Permissions::is_leader()) {
            return new WP_Error('rest_forbidden', __('Only Team Leaders and Admins can access this resource.', 'fiverr-team-management'), ['status' => 403]);
        }
        return true;
    }

    public function check_leader_or_admin_permission($request = null) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', __('You must be logged in to access this resource.', 'fiverr-team-management'), ['status' => 401]);
        }
        $uid = get_current_user_id();
        if (!FTM_Permissions::is_super_admin($uid) && !FTM_Permissions::is_leader($uid) && !FTM_Permissions::is_executive($uid)) {
            return new WP_Error('rest_forbidden', __('Only Squad Leaders, Executives, and Operations Managers can perform this action.', 'fiverr-team-management'), ['status' => 403]);
        }
        return true;
    }

    protected function format_success($data, $message = '') {
        $response = ['success' => true, 'data' => $data];
        if (!empty($message)) {
            $response['message'] = $message;
        }
        return new WP_REST_Response($response, 200);
    }

    protected function format_error($message, $status = 400, $code = 'ftm_error') {
        return new WP_Error($code, $message, ['status' => $status]);
    }
}
