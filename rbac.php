<?php

function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function hasRole($role) {
    $userRole = getCurrentUserRole();
    if (is_array($role)) {
        return in_array($userRole, $role);
    }
    return $userRole === $role;
}

function hasPermission($permission) {
    $userRole = getCurrentUserRole();

    $permissions = [
        'admin' => [
            'view_dashboard',
            'manage_users',
            'manage_students',
            'manage_courses',
            'manage_requirements',
            'manage_settings',
            'view_analytics',
            'manage_permissions'
        ],
        'staff' => [
            'view_dashboard',
            'manage_students',
            'manage_courses',
            'manage_requirements',
            'view_analytics'
        ],
        'teacher' => [
            'view_dashboard',
            'view_students',
            'manage_requirements',
            'grade_requirements',
            'view_analytics'
        ],
        'student' => [
            'view_dashboard',
            'view_profile',
            'view_courses',
            'view_requirements',
            'submit_requirements'
        ]
    ];

    if (!isset($permissions[$userRole])) {
        return false;
    }

    return in_array($permission, $permissions[$userRole]);
}

function requirePermission($permission, $redirectTo = 'dashboard.php') {
    if (!hasPermission($permission)) {
        header("Location: {$redirectTo}");
        exit;
    }
}

function requireRole($role, $redirectTo = 'dashboard.php') {
    if (!hasRole($role)) {
        header("Location: {$redirectTo}");
        exit;
    }
}

function getRoleLabel($role) {
    $labels = [
        'admin' => 'Administrator',
        'staff' => 'Staff',
        'teacher' => 'Teacher',
        'student' => 'Student'
    ];
    return $labels[$role] ?? ucfirst($role);
}

function getRoleColor($role) {
    $colors = [
        'admin' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200',
        'staff' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200',
        'teacher' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200',
        'student' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200'
    ];
    return $colors[$role] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200';
}

function getValidRoles() {
    return ['admin', 'staff', 'teacher', 'student'];
}
?>
