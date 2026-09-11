<?php
/**
 * КОНТАНТА - Simplified Admin Authentication
 * No roles, no permissions - just login
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'security.php';

// ADMIN CREDENTIALS (замените на свои)
define('ADMIN_LOGIN', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('admin123', PASSWORD_BCRYPT)); // Change this!

$storageDir = dirname(__FILE__) . '/../storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$sessionsFile = $storageDir . '/sessions.json';

// Initialize sessions file if it doesn't exist
if (!file_exists($sessionsFile)) {
    file_put_contents($sessionsFile, json_encode([], JSON_PRETTY_PRINT), LOCK_EX);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? 'login';
    
    if ($action === 'login') {
        $input = json_decode(file_get_contents('php://input'), true);
        $login = trim($input['login'] ?? '');
        $password = trim($input['password'] ?? '');
        
        if (empty($login) || empty($password)) {
            http_response_code(400);
            die(json_encode(['ok' => false, 'error' => 'Login and password required']));
        }
        
        // Check credentials
        if ($login !== ADMIN_LOGIN || !password_verify($password, ADMIN_PASSWORD_HASH)) {
            http_response_code(401);
            die(json_encode(['ok' => false, 'error' => 'Invalid credentials']));
        }
        
        // Create session token
        $token = bin2hex(random_bytes(32));
        $sessions = json_decode(file_get_contents($sessionsFile), true) ?: [];
        
        $sessions[$token] = [
            'admin_id' => 1,
            'username' => ADMIN_LOGIN,
            'login_time' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', time() + 86400 * 7), // 7 days
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        file_put_contents($sessionsFile, json_encode($sessions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'token' => $token,
            'admin' => [
                'id' => 1,
                'username' => ADMIN_LOGIN
            ]
        ]);
    } 
    else if ($action === 'logout') {
        $token = trim($_GET['token'] ?? '');
        
        if (empty($token)) {
            http_response_code(400);
            die(json_encode(['ok' => false, 'error' => 'Token required']));
        }
        
        $sessions = json_decode(file_get_contents($sessionsFile), true) ?: [];
        unset($sessions[$token]);
        file_put_contents($sessionsFile, json_encode($sessions, JSON_PRETTY_PRINT), LOCK_EX);
        
        http_response_code(200);
        echo json_encode(['ok' => true, 'message' => 'Logged out']);
    }
} 
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'verify';
    $token = trim($_GET['token'] ?? '');
    
    if ($action === 'verify') {
        if (empty($token)) {
            http_response_code(401);
            die(json_encode(['ok' => false, 'error' => 'No token']));
        }
        
        $sessions = json_decode(file_get_contents($sessionsFile), true) ?: [];
        
        if (!isset($sessions[$token])) {
            http_response_code(401);
            die(json_encode(['ok' => false, 'error' => 'Invalid token']));
        }
        
        $session = $sessions[$token];
        
        // Check expiration
        if (strtotime($session['expires_at']) < time()) {
            unset($sessions[$token]);
            file_put_contents($sessionsFile, json_encode($sessions, JSON_PRETTY_PRINT), LOCK_EX);
            http_response_code(401);
            die(json_encode(['ok' => false, 'error' => 'Session expired']));
        }
        
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'admin' => [
                'id' => $session['admin_id'],
                'username' => $session['username']
            ]
        ]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
}
?>
