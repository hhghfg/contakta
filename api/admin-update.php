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

$admin_role = $session_data['role'];
$admin_id = $session_data['admin_id'];
$admins_file = STORAGE_PATH . 'admins.json';

if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обновление администратора
    require_permission($admin_role, 'edit_admin');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $target_admin_id = $input['admin_id'] ?? null;
    
    if (!$target_admin_id) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Не указан ID администратора']));
    }
    
    if ($target_admin_id === $admin_id && isset($input['new_role'])) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Вы не можете изменить свою роль']));
    }
    
    $admins_data = json_decode(file_get_contents($admins_file), true);
    
    $target_admin = null;
    $target_index = -1;
    
    foreach ($admins_data['admins'] as $i => $a) {
        if ($a['id'] === $target_admin_id) {
            $target_admin = $a;
            $target_index = $i;
            break;
        }
    }
    
    if (!$target_admin) {
        http_response_code(404);
        die(json_encode(['ok' => false, 'error' => 'Администратор не найден']));
    }
    
    // Проверка прав на редактирование
    if ($admin_role === 'admin' && !in_array($target_admin['role'], ['manager', 'viewer'])) {
        http_response_code(403);
        die(json_encode(['ok' => false, 'error' => 'Вы можете редактировать только Manager и Viewer']));
    }
    
    // Обновление полей
    if (isset($input['new_username']) && strlen($input['new_username']) >= 3) {
        // Проверка уникальности
        foreach ($admins_data['admins'] as $a) {
            if ($a['id'] !== $target_admin_id && $a['username'] === $input['new_username']) {
                http_response_code(400);
                die(json_encode(['ok' => false, 'error' => 'Этот логин уже занят']));
            }
        }
        $target_admin['username'] = $input['new_username'];
    }
    
    if (isset($input['new_email']) && filter_var($input['new_email'], FILTER_VALIDATE_EMAIL)) {
        // Проверка уникальности
        foreach ($admins_data['admins'] as $a) {
            if ($a['id'] !== $target_admin_id && $a['email'] === $input['new_email']) {
                http_response_code(400);
                die(json_encode(['ok' => false, 'error' => 'Этот email уже зарегистрирован']));
            }
        }
        $target_admin['email'] = $input['new_email'];
    }
    
    if (isset($input['new_password']) && strlen($input['new_password']) >= 8) {
        $target_admin['password_hash'] = password_hash($input['new_password'], PASSWORD_BCRYPT);
    }
    
    if (isset($input['new_role']) && in_array($input['new_role'], get_all_roles())) {
        require_permission($admin_role, 'change_admin_role');
        
        // Admin не может назначать роли выше Manager
        if ($admin_role === 'admin' && in_array($input['new_role'], ['super-admin', 'admin'])) {
            http_response_code(403);
            die(json_encode(['ok' => false, 'error' => 'Вы можете назначать только Manager и Viewer']));
        }
        
        if ($target_admin['role'] !== $input['new_role']) {
            $admins_data['role_rotation_log'][] = [
                'admin_id' => $target_admin_id,
                'old_role' => $target_admin['role'],
                'new_role' => $input['new_role'],
                'changed_by' => $admin_id,
                'changed_at' => date('c'),
                'reason' => $input['reason'] ?? 'Изменение роли'
            ];
            
            $target_admin['role'] = $input['new_role'];
            
            log_security_event('ADMIN_ROLE_CHANGED', [
                'changed_by' => $admin_id,
                'target_admin' => $target_admin_id,
                'old_role' => $admins_data['role_rotation_log'][count($admins_data['role_rotation_log']) - 1]['old_role'],
                'new_role' => $input['new_role']
            ]);
        }
    }
    
    $admins_data['admins'][$target_index] = $target_admin;
    file_put_contents($admins_file, json_encode($admins_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'admin' => [
            'id' => $target_admin['id'],
            'username' => $target_admin['username'],
            'email' => $target_admin['email'],
            'role' => $target_admin['role'],
            'status' => $target_admin['status']
        ]
    ]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Удаление администратора
    require_permission($admin_role, 'delete_admin');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $target_admin_id = $input['admin_id'] ?? null;
    
    if (!$target_admin_id) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Не указан ID администратора']));
    }
    
    if ($target_admin_id === $admin_id) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Вы не можете удалить сам себя']));
    }
    
    $admins_data = json_decode(file_get_contents($admins_file), true);
    
    $target_admin = null;
    $target_index = -1;
    
    foreach ($admins_data['admins'] as $i => $a) {
        if ($a['id'] === $target_admin_id) {
            $target_admin = $a;
            $target_index = $i;
            break;
        }
    }
    
    if (!$target_admin) {
        http_response_code(404);
        die(json_encode(['ok' => false, 'error' => 'Администратор не найден']));
    }
    
    // Проверка прав
    if ($admin_role === 'admin' && !in_array($target_admin['role'], ['manager', 'viewer'])) {
        http_response_code(403);
        die(json_encode(['ok' => false, 'error' => 'Вы можете удалять только Manager и Viewer']));
    }
    
    // Проверка - нельзя удалить последнего Super-Admin
    if ($target_admin['role'] === 'super-admin') {
        $super_admin_count = 0;
        foreach ($admins_data['admins'] as $a) {
            if ($a['role'] === 'super-admin' && $a['status'] === 'active') {
                $super_admin_count++;
            }
        }
        
        if ($super_admin_count <= 1) {
            http_response_code(400);
            die(json_encode(['ok' => false, 'error' => 'Невозможно удалить последнего Super-Admin']));
        }
    }
    
    // Удалить
    array_splice($admins_data['admins'], $target_index, 1);
    file_put_contents($admins_file, json_encode($admins_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
    
    log_security_event('ADMIN_DELETED', [
        'deleted_by' => $admin_id,
        'deleted_admin_id' => $target_admin_id,
        'deleted_admin_role' => $target_admin['role']
    ]);
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => 'Администратор удален'
    ]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Получение одного администратора
    $admin_id_get = $_GET['admin_id'] ?? null;
    
    if (!$admin_id_get) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Не указан ID администратора']));
    }
    
    $admins_data = json_decode(file_get_contents($admins_file), true);
    
    foreach ($admins_data['admins'] as $a) {
        if ($a['id'] === $admin_id_get) {
            // Проверка прав на просмотр
            if ($admin_role === 'admin' && !in_array($a['role'], ['manager', 'viewer'])) {
                http_response_code(403);
                die(json_encode(['ok' => false, 'error' => 'Недостаточно прав']));
            }
            
            http_response_code(200);
            echo json_encode([
                'ok' => true,
                'admin' => [
                    'id' => $a['id'],
                    'username' => $a['username'],
                    'email' => $a['email'],
                    'role' => $a['role'],
                    'status' => $a['status'],
                    'created_at' => $a['created_at'],
                    'last_login' => $a['last_login']
                ]
            ]);
            exit;
        }
    }
    
    http_response_code(404);
    die(json_encode(['ok' => false, 'error' => 'Администратор не найден']));
    
} else {
    http_response_code(405);
    die(json_encode(['ok' => false, 'error' => 'Method not allowed']));
}
?>
