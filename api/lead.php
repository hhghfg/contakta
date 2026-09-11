<?php
require_once 'security.php';

header('Content-Type: application/json');

// API Rate limiting
if (!check_api_rate_limit()) {
    http_response_code(429);
    die(json_encode(['ok' => false, 'error' => 'Слишком много запросов. Попробуйте позже.']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Валидация обязательных полей
    if (!isset($input['name']) || !isset($input['phone'])) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Отсутствуют обязательные поля']));
    }
    
    // Санитизация input
    $name = htmlspecialchars(trim($input['name']), ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars(trim($input['phone']), ENT_QUOTES, 'UTF-8');
    $email = isset($input['email']) && !empty($input['email']) ? htmlspecialchars(trim($input['email']), ENT_QUOTES, 'UTF-8') : null;
    $age = isset($input['age']) && !empty($input['age']) ? htmlspecialchars(trim($input['age']), ENT_QUOTES, 'UTF-8') : null;
    $message = isset($input['message']) && !empty($input['message']) ? htmlspecialchars(trim($input['message']), ENT_QUOTES, 'UTF-8') : null;
    
    // Валидация
    if (strlen($name) < 3 || strlen($name) > 100) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Неверное имя (3-100 символов)']));
    }
    
    if (strlen($phone) < 11 || strlen($phone) > 18) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Неверный телефон']));
    }
    
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Неверный email']));
    }
    
    // Проверка GDPR согласия
    if (!isset($input['gdpr_consent']) || !$input['gdpr_consent']) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Требуется согласие на обработку данных']));
    }
    
    // Создание заявки
    $leads_file = STORAGE_PATH . 'leads.json';
    $leads = json_decode(file_get_contents($leads_file), true) ?? [];
    
    $lead_id = 'lead_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
    
    $lead = [
        'id' => $lead_id,
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'age' => $age,
        'message' => $message,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'status' => 'new',
        'created_at' => date('c'),
        'gdpr_consent' => true,
        'gdpr_consent_time' => date('c'),
        'gdpr_retention_until' => date('c', time() + 31536000), // 1 год
        'captured_by' => null,
        'captured_at' => null,
        'notes' => []
    ];
    
    $leads[] = $lead;
    file_put_contents($leads_file, json_encode($leads, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
    
    // Логирование в activity лог
    $activity_file = STORAGE_PATH . 'lead_activity.jsonl';
    $activity = [
        'timestamp' => date('c'),
        'lead_id' => $lead_id,
        'action' => 'LEAD_CREATED',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'details' => ['name' => $name, 'phone' => $phone]
    ];
    
    file_put_contents($activity_file, json_encode($activity) . "\n", FILE_APPEND | LOCK_EX);
    
    log_security_event('LEAD_CREATED', ['lead_id' => $lead_id, 'ip' => $_SERVER['REMOTE_ADDR']]);
    
    http_response_code(201);
    echo json_encode([
        'ok' => true,
        'lead_id' => $lead_id,
        'message' => 'Заявка успешно принята'
    ]);
    
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Получение своих заявок (публичный метод без аутентификации)
    $leads_file = STORAGE_PATH . 'leads.json';
    $leads = json_decode(file_get_contents($leads_file), true) ?? [];
    
    // Возвращаем только последние 10 заявок (без приватной информации)
    $public_leads = array_slice($leads, -10);
    foreach ($public_leads as &$lead) {
        unset($lead['ip'], $lead['user_agent'], $lead['gdpr_retention_until']);
    }
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'total' => count($leads),
        'recent' => $public_leads
    ]);
    
} else {
    http_response_code(405);
    die(json_encode(['ok' => false, 'error' => 'Method not allowed']));
}
