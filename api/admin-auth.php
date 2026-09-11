<?php
/**
 * API авторизации администратора
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $action = $_GET['action'] ?? 'verify';
    
    if ($action === 'verify') {
        // Просто возвращаем что API работает
        http_response_code(401);
        echo json_encode([
            'статус' => 'требуется_авторизация',
            'сообщение' => 'Требуется авторизация',
            'действие' => 'login'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['password'])) {
            http_response_code(400);
            die(json_encode([
                'статус' => 'ошибка',
                'сообщение' => 'Требуется пароль'
            ], JSON_UNESCAPED_UNICODE));
        }
        
        // Простая проверка пароля (для теста)
        if ($input['password'] === 'admin123') {
            http_response_code(200);
            echo json_encode([
                'статус' => 'успешно',
                'сообщение' => 'Вход успешен',
                'токен' => bin2hex(random_bytes(32))
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(401);
            echo json_encode([
                'статус' => 'ошибка',
                'сообщение' => 'Неверный пароль'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    http_response_code(405);
    echo json_encode([
        'статус' => 'ошибка',
        'сообщение' => 'Метод не разрешен'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'статус' => 'ошибка',
        'сообщение' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

exit;
?>
