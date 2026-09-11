<?php
require_once 'security.php';

header('Content-Type: application/json');

// API Rate limiting
if (!check_api_rate_limit()) {
    http_response_code(429);
    die(json_encode(['ok' => false, 'error' => 'Слишком много запросов']));
}

// Проверка авторизации
$token = null;
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
    if (strpos($auth_header, 'Bearer ') === 0) {
        $token = substr($auth_header, 7);
    }
}

if (!$token) {
    http_response_code(401);
    die(json_encode(['ok' => false, 'error' => 'Отсутствует токен авторизации']));
}

$session = validate_session($token);
if (!$session) {
    http_response_code(401);
    die(json_encode(['ok' => false, 'error' => 'Недействительная сессия']));
}

// Проверка роли - только super-admin может удалять админов
if ($session['role'] !== 'super-admin') {
    log_security_event('UNAUTHORIZED_ADMIN_DELETE_ATTEMPT', ['admin_id' => $session['admin_id'], 'role' => $session['role']]);
    http_response_code(403);
    die(json_encode(['ok' => false, 'error' => 'Недостаточно прав']));
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Валидация входных данных
    if (!isset($input['admin_id'])) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Отсутствует admin_id']));
    }
    
    $admin_id = htmlspecialchars(trim($input['admin_id']), ENT_QUOTES, 'UTF-8');
    
    // Защита: нельзя удалить самого себя
    if ($admin_id === $session['admin_id']) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Нельзя удалить собственный аккаунт']));
    }
    
    $admins_file = STORAGE_PATH . 'admins.json';
    $admins_data = json_decode(file_get_contents($admins_file), true);
    $admins = $admins_data['admins'];
    
    // Найти админа для удаления
    $admin_index = -1;
    $admin_to_delete = null;
    foreach ($admins as $index => $a) {
        if ($a['id'] === $admin_id) {
            $admin_index = $index;
            $admin_to_delete = $a;
            break;
        }
    }
    
    if ($admin_index === -1) {
        http_response_code(404);
        die(json_encode(['ok' => false, 'error' => 'Администратор не найден']));
    }
    
    // Удалить админа
    array_splice($admins, $admin_index, 1);
    $admins_data['admins'] = $admins;
    file_put_contents($admins_file, json_encode($admins_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
    
    log_security_event('ADMIN_DELETED', [
        'deleted_by' => $session['admin_id'],
        'deleted_admin_id' => $admin_id,
        'deleted_admin_username' => $admin_to_delete['username'],
        'deleted_admin_role' => $admin_to_delete['role']
    ]);
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => 'Администратор успешно удален'
    ]);
    
} else {
    http_response_code(405);
    die(json_encode(['ok' => false, 'error' => 'Method not allowed']));
}
