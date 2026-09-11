<?php
/**
 * Simplified administrator authentication.
 * Configure ADMIN_LOGIN and ADMIN_PASSWORD_HASH in the server environment.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$storageDir = dirname(__FILE__) . '/../storage';
$sessionsFile = $storageDir . '/sessions.json';

if (!is_dir($storageDir) && !mkdir($storageDir, 0700, true) && !is_dir($storageDir)) {
    http_response_code(500);
    exit(json_encode(['ok' => false, 'error' => 'Storage is unavailable']));
}
if (!file_exists($sessionsFile)) {
    file_put_contents($sessionsFile, "{}\n", LOCK_EX);
    @chmod($sessionsFile, 0600);
}

function respond(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_sessions(string $file): array {
    $decoded = json_decode((string) @file_get_contents($file), true);
    return is_array($decoded) ? $decoded : [];
}

function write_sessions(string $file, array $sessions): void {
    $json = json_encode($sessions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($file, $json . "\n", LOCK_EX) === false) {
        respond(500, ['ok' => false, 'error' => 'Could not save session']);
    }
    @chmod($file, 0600);
}

function bearer_token(): string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+([A-Fa-f0-9]{64})$/', trim($header), $matches) !== 1) {
        return '';
    }
    return $matches[1];
}

function valid_session(array $session): bool {
    $expires = $session['expires_at'] ?? 0;
    $timestamp = is_numeric($expires) ? (int) $expires : (int) strtotime((string) $expires);
    return $timestamp > time();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($method === 'POST' ? 'login' : 'verify');

if ($method === 'POST' && $action === 'login') {
    $adminLogin = (string) getenv('ADMIN_LOGIN');
    $adminPasswordHash = (string) getenv('ADMIN_PASSWORD_HASH');
    if ($adminLogin === '' || $adminPasswordHash === '') {
        respond(503, ['ok' => false, 'error' => 'Administrator credentials are not configured']);
    }

    $input = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($input)) {
        respond(400, ['ok' => false, 'error' => 'Invalid JSON body']);
    }

    $login = trim((string) ($input['login'] ?? ''));
    $password = (string) ($input['password'] ?? '');
    if ($login === '' || $password === '') {
        respond(400, ['ok' => false, 'error' => 'Login and password are required']);
    }
    if (!hash_equals($adminLogin, $login) || !password_verify($password, $adminPasswordHash)) {
        usleep(250000);
        respond(401, ['ok' => false, 'error' => 'Invalid credentials']);
    }

    $token = bin2hex(random_bytes(32));
    $sessions = read_sessions($sessionsFile);
    $now = time();
    foreach ($sessions as $key => $session) {
        if (!is_array($session) || !valid_session($session)) unset($sessions[$key]);
    }
    $sessions[$token] = [
        'admin_id' => 1,
        'username' => $adminLogin,
        'role' => 'admin',
        'login_time' => date(DATE_ATOM, $now),
        'last_activity' => date(DATE_ATOM, $now),
        'expires_at' => $now + 1800,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    ];
    write_sessions($sessionsFile, $sessions);
    respond(200, ['ok' => true, 'token' => $token, 'admin' => ['id' => 1, 'username' => $adminLogin]]);
}

if ($method === 'POST' && $action === 'logout') {
    $token = bearer_token();
    if ($token === '') respond(401, ['ok' => false, 'error' => 'Unauthorized']);
    $sessions = read_sessions($sessionsFile);
    unset($sessions[$token]);
    write_sessions($sessionsFile, $sessions);
    respond(200, ['ok' => true]);
}

if ($method === 'GET' && $action === 'verify') {
    $token = bearer_token();
    $sessions = read_sessions($sessionsFile);
    if ($token === '' || !isset($sessions[$token]) || !is_array($sessions[$token]) || !valid_session($sessions[$token])) {
        if ($token !== '' && isset($sessions[$token])) {
            unset($sessions[$token]);
            write_sessions($sessionsFile, $sessions);
        }
        respond(401, ['ok' => false, 'error' => 'Unauthorized']);
    }
    $session = $sessions[$token];
    respond(200, ['ok' => true, 'admin' => ['id' => $session['admin_id'], 'username' => $session['username']]]);
}

respond(405, ['ok' => false, 'error' => 'Method not allowed']);
