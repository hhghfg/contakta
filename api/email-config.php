<?php
/**
 * КОНТАНТА - Конфиг отправки писем
 * Настройки для отправки уведомлений на почту админинистратора
 */

// ============ НАСТРОЙКИ ПОЧТЫ ============

// EMAIL АДМИНИСТРАТОРА - ИЗМЕНИТЕ НА СВОЙ!
define('ADMIN_EMAIL', 'admin@example.com');

// Резервная почта (на случай если основная недоступна)
define('BACKUP_EMAIL', 'backup@example.com');

// Email от которого будут отправляться письма
define('FROM_EMAIL', 'noreply@kontanta.ru');
define('FROM_NAME', 'КОНТАНТА - Система заявок');

// ============ СПОСОБЫ ОТПРАВКИ ============

// Метод отправки: 'mail', 'smtp', 'sendgrid', 'mailgun'
define('MAIL_DRIVER', 'mail'); // или 'smtp'

// === Если используется SMTP ===
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password'); // Для Gmail используйте App Password!
define('SMTP_ENCRYPTION', 'tls'); // 'tls' или 'ssl'

// === Если используется SendGrid ===
define('SENDGRID_API_KEY', ''); // Получите ключ на https://sendgrid.com/

// === Если используется Mailgun ===
define('MAILGUN_DOMAIN', ''); // domain.mailgun.org
define('MAILGUN_API_KEY', ''); // API key

// ============ ШАБЛОНЫ ПИСЕМ ============

// Отправлять HTML письма (не текст)
define('SEND_HTML_EMAILS', true);

// Язык писем
define('EMAIL_LANGUAGE', 'ru');

// ============ ЛОГИРОВАНИЕ ============

// Логировать все отправленные письма
define('LOG_EMAILS', true);

// Директория для логов писем
define('EMAIL_LOGS_PATH', dirname(__FILE__) . '/../storage/email-logs/');

// ============ УВЕДОМЛЕНИЯ ============

// Отправлять письмо административору о новой заявке
define('NOTIFY_ADMIN_ON_LEAD', true);

// Отправлять письмо-подтверждение клиенту
define('SEND_CLIENT_CONFIRMATION', true);

// Отправлять письмо каждые N заявок (0 = только первому админу)
define('NOTIFY_BACKUP_AFTER_N_LEADS', 10);

// ============ ФУНКЦИИ ============

/**
 * Отправить письмо
 */
function send_email($to, $subject, $body_html, $body_text = null, $attachments = []) {
    // Если $to пуст, используем админ-почту
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $to = ADMIN_EMAIL;
    }
    
    // Создаем письмо
    $headers = [
        'From' => FROM_EMAIL,
        'From-Name' => FROM_NAME,
        'To' => $to,
        'Subject' => $subject,
        'Reply-To' => ADMIN_EMAIL,
        'X-Mailer' => 'КОНТАНТА/1.0',
        'X-Priority' => '3'
    ];
    
    if (SEND_HTML_EMAILS) {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        $body = $body_html;
    } else {
        $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        $body = $body_text ?? strip_tags($body_html);
    }
    
    // Логируем попытку отправки
    if (LOG_EMAILS) {
        log_email_attempt($to, $subject, 'pending');
    }
    
    // Отправляем в зависимости от метода
    $result = false;
    
    if (MAIL_DRIVER === 'mail') {
        $result = send_via_mail($to, $subject, $body, $headers);
    } else if (MAIL_DRIVER === 'smtp') {
        $result = send_via_smtp($to, $subject, $body, $headers);
    } else if (MAIL_DRIVER === 'sendgrid') {
        $result = send_via_sendgrid($to, $subject, $body, $headers);
    }
    
    // Логируем результат
    if (LOG_EMAILS) {
        log_email_attempt($to, $subject, $result ? 'sent' : 'failed');
    }
    
    return $result;
}

/**
 * Отправка через встроенную функцию mail()
 */
function send_via_mail($to, $subject, $body, $headers) {
    $headers_str = '';
    foreach ($headers as $key => $value) {
        if ($key !== 'To' && $key !== 'Subject') {
            $headers_str .= $key . ': ' . $value . "\r\n";
        }
    }
    
    return mail($to, $subject, $body, $headers_str);
}

/**
 * Отправка через SMTP (PHPMailer или простой сокет)
 */
function send_via_smtp($to, $subject, $body, $headers) {
    // Простая реализация через fsockopen
    // Для production используйте PHPMailer!
    
    $smtp_host = SMTP_HOST;
    $smtp_port = SMTP_PORT;
    $smtp_user = SMTP_USER;
    $smtp_pass = SMTP_PASS;
    
    $smtp = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 5);
    
    if (!$smtp) {
        error_log('SMTP Connection Error: ' . $errstr);
        return false;
    }
    
    // Начальное приветствие
    fgets($smtp, 1024);
    
    // Команды SMTP
    fputs($smtp, "EHLO localhost\r\n");
    fgets($smtp, 1024);
    
    // STARTTLS
    if (SMTP_ENCRYPTION === 'tls') {
        fputs($smtp, "STARTTLS\r\n");
        fgets($smtp, 1024);
        stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        fputs($smtp, "EHLO localhost\r\n");
        fgets($smtp, 1024);
    }
    
    // Аутентификация
    fputs($smtp, "AUTH LOGIN\r\n");
    fgets($smtp, 1024);
    fputs($smtp, base64_encode($smtp_user) . "\r\n");
    fgets($smtp, 1024);
    fputs($smtp, base64_encode($smtp_pass) . "\r\n");
    $response = fgets($smtp, 1024);
    
    if (strpos($response, '235') === false) {
        error_log('SMTP Auth Failed');
        fclose($smtp);
        return false;
    }
    
    // От кого
    fputs($smtp, "MAIL FROM: <" . FROM_EMAIL . ">\r\n");
    fgets($smtp, 1024);
    
    // Кому
    fputs($smtp, "RCPT TO: <" . $to . ">\r\n");
    fgets($smtp, 1024);
    
    // Данные письма
    fputs($smtp, "DATA\r\n");
    fgets($smtp, 1024);
    
    $message = "Subject: " . $subject . "\r\n";
    foreach ($headers as $key => $value) {
        if ($key !== 'To' && $key !== 'Subject') {
            $message .= $key . ": " . $value . "\r\n";
        }
    }
    $message .= "\r\n" . $body . "\r\n";
    
    fputs($smtp, $message . "\r\n.\r\n");
    fgets($smtp, 1024);
    
    // Закрытие
    fputs($smtp, "QUIT\r\n");
    fclose($smtp);
    
    return true;
}

/**
 * Отправка через SendGrid API
 */
function send_via_sendgrid($to, $subject, $body, $headers) {
    if (empty(SENDGRID_API_KEY)) {
        error_log('SendGrid API Key not configured');
        return false;
    }
    
    $data = [
        'personalizations' => [
            [
                'to' => [['email' => $to]],
                'subject' => $subject
            ]
        ],
        'from' => [
            'email' => FROM_EMAIL,
            'name' => FROM_NAME
        ],
        'content' => [
            [
                'type' => 'text/html',
                'value' => $body
            ]
        ],
        'reply_to' => [
            'email' => ADMIN_EMAIL
        ]
    ];
    
    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . SENDGRID_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code === 202;
}

/**
 * Логирование попыток отправки
 */
function log_email_attempt($to, $subject, $status) {
    if (!LOG_EMAILS) return;
    
    if (!is_dir(EMAIL_LOGS_PATH)) {
        mkdir(EMAIL_LOGS_PATH, 0755, true);
    }
    
    $log_file = EMAIL_LOGS_PATH . date('Y-m-d') . '.jsonl';
    
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $to,
        'subject' => $subject,
        'status' => $status,
        'from' => FROM_EMAIL
    ];
    
    file_put_contents($log_file, json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

// Создаем директории при первом включении
if (!is_dir(EMAIL_LOGS_PATH)) {
    mkdir(EMAIL_LOGS_PATH, 0755, true);
}

?>
