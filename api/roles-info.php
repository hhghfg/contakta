<?php
require_once 'security.php';
require_once 'roles.php';

header('Content-Type: application/json');

$token = get_bearer_token();
$session_data = get_session_data($token);

if (!$session_data) {
    http_response_code(401);
    die(json_encode(['ok' => false, 'error' => 'Не авторизован']));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? null;
    
    if ($action === 'all') {
        // Получить все доступные роли
        $roles = [];
        foreach (get_all_roles() as $role) {
            $roles[] = [
                'name' => $role,
                'description' => get_role_description($role),
                'permissions' => get_role_permissions($role)
            ];
        }
        
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'roles' => $roles
        ]);
        
    } elseif ($action === 'my') {
        // Получить мои права
        $role = $session_data['role'];
        
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'role' => $role,
            'description' => get_role_description($role),
            'permissions' => get_role_permissions($role)
        ]);
        
    } elseif ($action === 'rotation-log') {
        // Получить историю изменения ролей (только для Super-Admin)
        if ($session_data['role'] !== 'super-admin') {
            http_response_code(403);
            die(json_encode(['ok' => false, 'error' => 'Только Super-Admin может просматривать историю']));
        }
        
        $admins_file = STORAGE_PATH . 'admins.json';
        $admins_data = json_decode(file_get_contents($admins_file), true);
        
        $log = $admins_data['role_rotation_log'] ?? [];
        
        // Сортировать по дате (новые первыми)
        usort($log, function($a, $b) {
            return strtotime($b['changed_at']) - strtotime($a['changed_at']);
        });
        
        // Ограничить последние 100 записей
        $log = array_slice($log, 0, 100);
        
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'rotation_log' => $log,
            'total' => count($log)
        ]);
        
    } else {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Неизвестное действие']));
    }
    
} else {
    http_response_code(405);
    die(json_encode(['ok' => false, 'error' => 'Method not allowed']));
}
?>
