<?php
/**
 * API обработки заявок с отправкой на почту администратора
 * Заявки сохраняются в админку и отправляются письмом
 */

header('Content-Type: application/json; charset=utf-8');

// Определяем константы прямо здесь (без зависимостей)
define('STORAGE_PATH', dirname(__FILE__) . '/../storage/');

// Убеждаемся что папка storage существует
if (!is_dir(STORAGE_PATH)) {
    @mkdir(STORAGE_PATH, 0755, true);
}

// ============ КОНФИГ ПОЧТЫ ============
$ADMIN_EMAIL = 'admin@example.com';  // ⚠️ ОТРЕДАКТИРУЙТЕ ЗДЕСЬ - ВАША РЕАЛЬНАЯ ПОЧТА!
$EMAIL_METHOD = 'mail';

// ============ ОСНОВНОЙ КОД ============

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Валидация обязательных полей
        if (!isset($input['name']) || !isset($input['phone'])) {
            http_response_code(400);
            die(json_encode(['ok' => false, 'error' => 'Отсутствуют обязательные поля'], JSON_UNESCAPED_UNICODE));
        }
        
        // Санитизация
        $name = htmlspecialchars(trim($input['name']), ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars(trim($input['phone']), ENT_QUOTES, 'UTF-8');
        $email = isset($input['email']) && !empty($input['email']) ? htmlspecialchars(trim($input['email']), ENT_QUOTES, 'UTF-8') : '';
        $age = isset($input['age']) && !empty($input['age']) ? htmlspecialchars(trim($input['age']), ENT_QUOTES, 'UTF-8') : '';
        $message = isset($input['message']) && !empty($input['message']) ? htmlspecialchars(trim($input['message']), ENT_QUOTES, 'UTF-8') : '';
        
        // Валидация длины
        if (strlen($name) < 3 || strlen($name) > 100) {
            http_response_code(400);
            die(json_encode(['ok' => false, 'error' => 'Неверное имя (3-100 символов)'], JSON_UNESCAPED_UNICODE));
        }
        
        if (strlen($phone) < 10 || strlen($phone) > 20) {
            http_response_code(400);
            die(json_encode(['ok' => false, 'error' => 'Неверный телефон'], JSON_UNESCAPED_UNICODE));
        }
        
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            die(json_encode(['ok' => false, 'error' => 'Неверный email'], JSON_UNESCAPED_UNICODE));
        }
        
        // Проверка GDPR согласия
        if (!isset($input['gdpr_consent']) || !$input['gdpr_consent']) {
            http_response_code(400);
            die(json_encode(['ok' => false, 'error' => 'Требуется согласие на обработку данных'], JSON_UNESCAPED_UNICODE));
        }
        
        // Создание заявки
        $leads_file = STORAGE_PATH . 'leads.json';
        $leads = file_exists($leads_file) ? json_decode(file_get_contents($leads_file), true) : [];
        if (!is_array($leads)) $leads = [];
        
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
            'gdpr_consent' => true
        ];
        
        // Сохраняем заявку
        $leads[] = $lead;
        @file_put_contents($leads_file, json_encode($leads, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        
        // Отправляем письма
        $admin_notified = false;
        $client_notified = false;
        
        // Письмо администратору
        if (!empty($ADMIN_EMAIL) && filter_var($ADMIN_EMAIL, FILTER_VALIDATE_EMAIL)) {
            $subject = '[КОНТАНТА] Новая заявка от ' . $name;
            $body = "Имя: $name\nТелефон: $phone\nEmail: $email\nВозраст: $age\nСообщение: $message\n\nIP: " . $_SERVER['REMOTE_ADDR'];
            $headers = "From: noreply@contanta.ru\r\nContent-Type: text/plain; charset=UTF-8\r\n";
            $admin_notified = @mail($ADMIN_EMAIL, $subject, $body, $headers);
        }
        
        // Письмо клиенту (если есть email)
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $subject = 'Спасибо за вашу заявку - КОНТАНТА';
            $body = "Спасибо, что вы оставили заявку! Наш специалист свяжется с вами в течение 30 минут.";
            $headers = "From: noreply@contanta.ru\r\nContent-Type: text/plain; charset=UTF-8\r\n";
            $client_notified = @mail($email, $subject, $body, $headers);
        }
        
        http_response_code(201);
        echo json_encode([
            'ok' => true,
            'lead_id' => $lead_id,
            'message' => 'Заявка успешно принята',
            'admin_notified' => $admin_notified,
            'client_notified' => $client_notified
        ], JSON_UNESCAPED_UNICODE);
        exit;
        
    } else {
        http_response_code(405);
        die(json_encode(['ok' => false, 'error' => 'Метод не разрешен'], JSON_UNESCAPED_UNICODE));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Ошибка сервера',
        'detail' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

?>
