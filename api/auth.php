<?php
/**
 * КОНТАНТА - Аутентификация
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$STORAGE_PATH = dirname(dirname(__FILE__)) . '/storage/';

// === ФУНКЦИИ ===

function get_bearer_token() {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/', $auth, $m)) {
        return $m[1];
    }
    return null;
}

function create_session($admin_id, $admin, $storage) {
    $file = $storage . 'sessions.json';
    $sessions = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    
    $token = bin2hex(random_bytes(32));
    $sessions[$token] = [
        'admin_id' => $admin_id,
        'role' => $admin['role'],
        'username' => $admin['username'],
        'email' => $admin['email'],
        'created' => time(),
        'expires' => time() + 86400
    ];
    
    file_put_contents($file, json_encode($sessions, JSON_PRETTY_PRINT), LOCK_EX);
    return $token;
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

// === POST: LOGIN ===

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Missing credentials']));
    }
    
    $u = $data['username'];
    $p = $data['password'];
    
    $admins_file = $STORAGE_PATH . 'admins.json';
    if (!file_exists($admins_file)) {
        http_response_code(500);
        die(json_encode(['ok' => false, 'error' => 'Config error']));
    }
    
    $admins_data = json_decode(file_get_contents($admins_file), true);
    $admin = null;
    
    foreach ($admins_data['admins'] as $a) {
        if ($a['username'] === $u && $a['status'] === 'active') {
            $admin = $a;
            break;
        }
    }
    
    if (!$admin) {
        http_response_code(401);
        die(json_encode(['ok' => false, 'error' => 'Invalid credentials']));
    }
    
    $ok = false;
    if (isset($admin['password_hash'])) {
        $ok = password_verify($p, $admin['password_hash']);
    } elseif (isset($admin['password'])) {
        $ok = ($admin['password'] === $p);
    }
    
    if (!$ok) {
        http_response_code(401);
        die(json_encode(['ok' => false, 'error' => 'Invalid credentials']));
    }
    
    $token = create_session($admin['id'], $admin, $STORAGE_PATH);
    
    // Обновить last_login
    foreach ($admins_data['admins'] as &$a) {
        if ($a['id'] === $admin['id']) {
            $a['last_login'] = date('c');
            break;
        }
    }
    file_put_contents($admins_file, json_encode($admins_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'token' => $token,
        'role' => $admin['role'],
        'admin_id' => $admin['id'],
        'username' => $admin['username']
    ]);
    exit;
}

// === GET: CHECK SESSION ===

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = get_bearer_token();
    if (!$token) {
        http_response_code(401);
        die(json_encode(['ok' => false, 'error' => 'No token']));
    }
    
    $session = get_session($token, $STORAGE_PATH);
    if (!$session) {
        http_response_code(401);
        die(json_encode(['ok' => false, 'error' => 'Invalid token']));
    }
    
    http_response_code(200);
    echo json_encode(['ok' => true, 'admin' => $session]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
?>
