<?php
/**
 * КОНТАНТА - Создание администраторов
 */

header('Content-Type: application/json');

$STORAGE_PATH = dirname(dirname(__FILE__)) . '/storage/';

function get_bearer_token() {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/', $auth, $m)) {
        return $m[1];
    }
    return null;
}

function get_session($token, $storage) {
    $file = $storage . 'sessions.json';
    if (!file_exists($file)) return null;
    
    $sessions = json_decode(file_get_contents($file), true) ?: [];
    if (!isset($sessions[$token])) return null;
    
    $s = $sessions[$token];
    if ($s['expires'] < time()) {
        unset($sessions[$token]);
        file_put_contents($file, json_encode($sessions, JSON_PRETTY_PRINT), LOCK_EX);
        return null;
    }
    
    return $s;
}

// === POST: CREATE ADMIN ===

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = get_bearer_token();
    $session = get_session($token, $STORAGE_PATH);
    
    if (!$session) {
        http_response_code(401);
        die(json_encode(['ok' => false, 'error' => 'Not authorized']));
    }
    
    // Только super-admin может создавать
    if ($session['role'] !== 'super-admin') {
        http_response_code(403);
        die(json_encode(['ok' => false, 'error' => 'Forbidden']));
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['username']) || !isset($data['password']) || !isset($data['email']) || !isset($data['role'])) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Missing fields']));
    }
    
    $admins_file = $STORAGE_PATH . 'admins.json';
    $admins_data = json_decode(file_get_contents($admins_file), true);
    
    // Проверить существование
    foreach ($admins_data['admins'] as $a) {
        if ($a['username'] === $data['username']) {
            http_response_code(400);
            die(json_encode(['ok' => false, 'error' => 'Username exists']));
        }
    }
    
    $new_admin = [
        'id' => 'adm_' . substr(md5(uniqid()), 0, 8),
        'username' => $data['username'],
        'email' => $data['email'],
        'password' => $data['password'],
        'role' => $data['role'],
        'status' => 'active',
        'created_at' => date('c'),
        'last_login' => null,
        'failed_attempts' => 0,
        'blocked_until' => null,
        'created_by' => $session['admin_id']
    ];
    
    $admins_data['admins'][] = $new_admin;
    file_put_contents($admins_file, json_encode($admins_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    
    http_response_code(201);
    echo json_encode([
        'ok' => true,
        'admin' => [
            'id' => $new_admin['id'],
            'username' => $new_admin['username'],
            'role' => $new_admin['role']
        ]
    ]);
    exit;
}

// === GET: LIST ADMINS ===

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = get_bearer_token();
    $session = get_session($token, $STORAGE_PATH);
    
    if (!$session) {
        http_response_code(401);
        die(json_encode(['ok' => false, 'error' => 'Not authorized']));
    }
    
    $admins_file = $STORAGE_PATH . 'admins.json';
    $admins_data = json_decode(file_get_contents($admins_file), true);
    
    $list = [];
    foreach ($admins_data['admins'] as $a) {
        // Super-admin видит всех
        if ($session['role'] === 'super-admin' || $session['role'] === 'admin') {
            $list[] = [
                'id' => $a['id'],
                'username' => $a['username'],
                'email' => $a['email'],
                'role' => $a['role'],
                'status' => $a['status'],
                'last_login' => $a['last_login']
            ];
        }
    }
    
    http_response_code(200);
    echo json_encode(['ok' => true, 'admins' => $list, 'total' => count($list)]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
?>
