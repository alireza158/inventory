<?php

return [
    'sync_enabled' => (bool) env('CRM_SYNC_ENABLED', true),
    'base_url' => env('CRM_BASE_URL'),
    'users_endpoint' => env('CRM_USERS_ENDPOINT', '/external/users'),
    'api_token' => env('CRM_API_TOKEN'),
    'timeout' => (int) env('CRM_SYNC_TIMEOUT', 30),
    'verify_ssl' => (bool) env('CRM_SYNC_VERIFY_SSL', true),
    'sync_interval_minutes' => (int) env('CRM_SYNC_INTERVAL_MINUTES', 15),
    'sync_missing_users_strategy' => env('CRM_SYNC_MISSING_USERS_STRATEGY', 'deactivate'),
    'response' => [
        'users_path_candidates' => [
            'data',
            'users',
            'items',
        ],
        'field_map' => [
            'id' => ['crm_user_id', 'id', 'user_id'],
            'name' => ['name', 'full_name', 'display_name'],
            'mobile' => ['mobile', 'phone', 'mobile_number', 'cellphone'],
            'email' => ['email', 'mail'],
            'username' => ['username', 'user_name', 'login'],
            'status' => ['status', 'is_active', 'active'],
            'roles' => ['roles', 'role', 'user_roles'],
            'created_at' => ['created_at', 'createdAt'],
            'updated_at' => ['updated_at', 'updatedAt'],
            'avatar' => ['avatar', 'avatar_url', 'profile_image'],
            'department' => ['department', 'department_name'],
            'position' => ['position', 'job_title'],
            'personnel_code' => ['personnel_code', 'employee_code'],
            'branch' => ['branch', 'branch_name'],
            'manager_id' => ['manager_id', 'parent_id'],
        ],
    ],
];

