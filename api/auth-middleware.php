<?php
/**
 * Middleware для проверки токенов и защиты от несанкционированного доступа
 * FEATURES: Token validation, Session verification, CSRF protection, IP checking
 */

class AuthMiddleware {
    private $storageDir;
    private $sessionTimeout = 1800; // 30 минут

    public function __construct() {
        $this->storageDir = dirname(__FILE__) . '/../storage';
    }

    /**
     * Проверяет токен доступа
     * @return array|false Данные сессии или false если невалиден
     */
    public function validateToken($token) {
        if (empty($token)) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Token required']);
            exit;
        }

        $sessionsFile = $this->storageDir . '/sessions.json';
        if (!file_exists($sessionsFile)) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Invalid token']);
            exit;
        }

        $sessions = json_decode(@file_get_contents($sessionsFile), true) ?: [];
        
        if (!isset($sessions[$token])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Session expired']);
            exit;
        }

        $session = $sessions[$token];

        // Проверяем timeout
        $lastActivity = strtotime($session['last_activity']);
        if (time() - $lastActivity > $this->sessionTimeout) {
            unset($sessions[$token]);
            @file_put_contents($sessionsFile, json_encode($sessions));
            
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Session expired']);
            exit;
        }

        // Проверяем IP (защита от session hijacking)
        if ($session['ip'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
            // Логируем попытку
            $this->logSecurityEvent('ip_mismatch', $session['admin_id'], $token);
            
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Forbidden']);
            exit;
        }

        // Проверяем User Agent (защита от session hijacking)
        $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        if ($session['user_agent'] !== $currentUserAgent) {
            // Логируем попытку
            $this->logSecurityEvent('user_agent_mismatch', $session['admin_id'], $token);
            
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Forbidden']);
            exit;
        }

        // Обновляем last_activity
        $session['last_activity'] = date('Y-m-d H:i:s');
        $sessions[$token] = $session;
        @file_put_contents($sessionsFile, json_encode($sessions));

        return $session;
    }

    /**
     * Проверяет CSRF токен
     */
    public function validateCSRF($csrfToken) {
        if (empty($csrfToken)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'CSRF token required']);
            exit;
        }

        $csrfFile = $this->storageDir . '/csrf_tokens.json';
        if (!file_exists($csrfFile)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }

        $tokens = json_decode(@file_get_contents($csrfFile), true) ?: [];
        
        if (!isset($tokens[$csrfToken])) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }

        // Проверяем истечение (1 час)
        $tokenData = $tokens[$csrfToken];
        if (time() - strtotime($tokenData['created_at']) > 3600) {
            unset($tokens[$csrfToken]);
            @file_put_contents($csrfFile, json_encode($tokens));
            
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'CSRF token expired']);
            exit;
        }

        // Удаляем токен (one-time use)
        unset($tokens[$csrfToken]);
        @file_put_contents($csrfFile, json_encode($tokens));

        return true;
    }

    /**
     * Генерирует CSRF токен
     */
    public function generateCSRFToken() {
        $token = bin2hex(random_bytes(32));
        
        $csrfFile = $this->storageDir . '/csrf_tokens.json';
        $tokens = [];
        
        if (file_exists($csrfFile)) {
            $tokens = json_decode(@file_get_contents($csrfFile), true) ?: [];
        }

        $tokens[$token] = [
            'created_at' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        @file_put_contents($csrfFile, json_encode($tokens));

        return $token;
    }

    /**
     * Требует роль
     */
    public function requireRole($session, $requiredRole) {
        if ($session['role'] !== $requiredRole && $session['role'] !== 'super-admin') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Insufficient permissions']);
            exit;
        }
    }

    /**
     * Логирует события безопасности
     */
    private function logSecurityEvent($eventType, $adminId, $token) {
        $logFile = $this->storageDir . '/security_log.jsonl';
        
        $event = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $eventType,
            'admin_id' => $adminId,
            'token_hash' => hash('sha256', $token),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        @file_put_contents($logFile, json_encode($event, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Проверяет rate limit для API
     */
    public function checkRateLimit($endpoint) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimitFile = $this->storageDir . '/api_rate_limit.json';
        
        $rateLimit = [];
        if (file_exists($rateLimitFile)) {
            $rateLimit = json_decode(@file_get_contents($rateLimitFile), true) ?: [];
        }

        $now = time();
        $minute = date('Y-m-d H:i', $now);
        $key = $ip . '_' . $endpoint . '_' . $minute;

        // Максимум 60 запросов в минуту с одного IP
        if (!isset($rateLimit[$key])) {
            $rateLimit[$key] = 0;
        }

        $rateLimit[$key]++;

        if ($rateLimit[$key] > 60) {
            http_response_code(429);
            echo json_encode(['ok' => false, 'error' => 'Too many requests']);
            exit;
        }

        // Очищаем старые записи
        foreach ($rateLimit as $k => $v) {
            if (strpos($k, $ip . '_') === 0) {
                $oldTime = strtotime(substr($k, strlen($ip) + 1, 16));
                if ($now - $oldTime > 3600) { // Удаляем старше часа
                    unset($rateLimit[$k]);
                }
            }
        }

        @file_put_contents($rateLimitFile, json_encode($rateLimit));
    }

    /**
     * Блокирует прямой доступ к файлам (для защиты админки)
     */
    public function blockDirectAccess() {
        if (php_sapi_name() === 'cli') {
            return; // Разрешаем CLI
        }

        // Если это GET запрос к API - блокируем
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
            exit;
        }
    }
}

// Используется в каждом API файле так:
// $auth = new AuthMiddleware();
// $auth->blockDirectAccess();
// $auth->checkRateLimit('/api/leads');
// $session = $auth->validateToken($_GET['token'] ?? $_POST['token'] ?? '');
// $auth->requireRole($session, 'admin');
?>
