<?php
/**
 * КОНТАНТА - Система управления ролями и правами
 * 4-уровневая система ротации прав
 */

// ============== РОЛИ И ПРАВА ==============

define('ROLE_SUPER_ADMIN', 'super-admin');    // Полный доступ
define('ROLE_ADMIN', 'admin');                 // Управление заявками + админы
define('ROLE_MANAGER', 'manager');             // Только просмотр + захват заявок
define('ROLE_VIEWER', 'viewer');              // Только чтение логов

// Матрица прав по ролям
$ROLES_PERMISSIONS = [
    'super-admin' => [
        'view_dashboard' => true,
        'view_leads' => true,
        'capture_leads' => true,
        'export_leads' => true,
        'delete_leads' => true,
        
        'create_admin' => true,
        'edit_admin' => true,
        'delete_admin' => true,
        'change_admin_role' => true,
        'reset_admin_password' => true,
        'block_admin' => true,
        
        'view_logs' => true,
        'export_logs' => true,
        'clear_logs' => true,
        
        'manage_roles' => true,
        'manage_permissions' => true,
        'system_settings' => true,
    ],
    
    'admin' => [
        'view_dashboard' => true,
        'view_leads' => true,
        'capture_leads' => true,
        'export_leads' => true,
        'delete_leads' => false,
        
        'create_admin' => true,
        'edit_admin' => true,
        'delete_admin' => false,
        'change_admin_role' => false,
        'reset_admin_password' => true,
        'block_admin' => false,
        
        'view_logs' => true,
        'export_logs' => false,
        'clear_logs' => false,
        
        'manage_roles' => false,
        'manage_permissions' => false,
        'system_settings' => false,
    ],
    
    'manager' => [
        'view_dashboard' => true,
        'view_leads' => true,
        'capture_leads' => true,
        'export_leads' => false,
        'delete_leads' => false,
        
        'create_admin' => false,
        'edit_admin' => false,
        'delete_admin' => false,
        'change_admin_role' => false,
        'reset_admin_password' => false,
        'block_admin' => false,
        
        'view_logs' => false,
        'export_logs' => false,
        'clear_logs' => false,
        
        'manage_roles' => false,
        'manage_permissions' => false,
        'system_settings' => false,
    ],
    
    'viewer' => [
        'view_dashboard' => true,
        'view_leads' => false,
        'capture_leads' => false,
        'export_leads' => false,
        'delete_leads' => false,
        
        'create_admin' => false,
        'edit_admin' => false,
        'delete_admin' => false,
        'change_admin_role' => false,
        'reset_admin_password' => false,
        'block_admin' => false,
        
        'view_logs' => true,
        'export_logs' => false,
        'clear_logs' => false,
        
        'manage_roles' => false,
        'manage_permissions' => false,
        'system_settings' => false,
    ]
];

// Описание ролей
$ROLES_DESCRIPTIONS = [
    'super-admin' => 'Полный доступ ко всему. Может управлять всеми администраторами и настройками.',
    'admin' => 'Управление заявками и администраторами. Может создавать и редактировать админов.',
    'manager' => 'Работа с заявками. Может просматривать, захватывать и экспортировать заявки.',
    'viewer' => 'Только просмотр. Может видеть статистику и логи, но не может редактировать.'
];

// ============== ФУНКЦИИ ==============

/**
 * Проверка права доступа
 */
function has_permission($admin_role, $permission) {
    global $ROLES_PERMISSIONS;
    
    if (!isset($ROLES_PERMISSIONS[$admin_role])) {
        return false;
    }
    
    return $ROLES_PERMISSIONS[$admin_role][$permission] ?? false;
}

/**
 * Проверка права с возвратом ошибки
 */
function require_permission($admin_role, $permission) {
    if (!has_permission($admin_role, $permission)) {
        http_response_code(403);
        die(json_encode([
            'ok' => false,
            'error' => 'У вас нет прав для этого действия'
        ]));
    }
}

/**
 * Получить все доступные права для роли
 */
function get_role_permissions($role) {
    global $ROLES_PERMISSIONS;
    return $ROLES_PERMISSIONS[$role] ?? [];
}

/**
 * Получить описание роли
 */
function get_role_description($role) {
    global $ROLES_DESCRIPTIONS;
    return $ROLES_DESCRIPTIONS[$role] ?? 'Неизвестная роль';
}

/**
 * Получить все доступные роли
 */
function get_all_roles() {
    return array_keys($ROLES_DESCRIPTIONS);
}

/**
 * Список ролей с описаниями
 */
function get_roles_with_descriptions() {
    global $ROLES_DESCRIPTIONS;
    return $ROLES_DESCRIPTIONS;
}
?>
