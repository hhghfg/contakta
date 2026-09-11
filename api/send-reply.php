<?php
/**
 * POST /api/send-reply.php
 * Отправка ответа клиенту по Email
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE));
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['lead_id'], $input['email'], $input['subject'], $input['message'])) {
    http_response_code(400);
    exit(json_encode(['ok' => false, 'error' => 'Missing required fields'], JSON_UNESCAPED_UNICODE));
}

$lead_id = (int)$input['lead_id'];
$email = trim($input['email']);
$subject = trim($input['subject']);
$message = trim($input['message']);

// Валидация email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit(json_encode(['ok' => false, 'error' => 'Invalid email address'], JSON_UNESCAPED_UNICODE));
}

// Отправляем письмо
$admin_email = 'svo@bez.mos.ru';

$subject_encoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';
$headers = "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "From: " . $admin_email . "\r\n";
$headers .= "Reply-To: " . $admin_email . "\r\n";

$full_message = $message . "\n\n---\nКОНТАНТА\nТелефон: +7 (495) 415-25-64\nEmail: svo@bez.mos.ru";

$mail_result = @mail($email, $subject_encoded, $full_message, $headers);

$email_status = $mail_result ? 'sent' : 'failed';

// Логируем отправку
$storage_dir = __DIR__ . '/../storage';
@mkdir($storage_dir, 0755, true);

$logs_file = $storage_dir . '/admin_logs.jsonl';
$log_entry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'action' => 'email_sent',
    'lead_id' => $lead_id,
    'to_email' => $email,
    'status' => $email_status,
    'admin' => $input['admin_name'] ?? 'Unknown'
];

@file_put_contents($logs_file, json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

http_response_code(200);
echo json_encode([
    'ok' => $mail_result,
    'message' => $mail_result ? 'Email sent successfully' : 'Failed to send email',
    'status' => $email_status,
    'lead_id' => $lead_id
], JSON_UNESCAPED_UNICODE);
?>
