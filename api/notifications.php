<?php
/**
 * КОНТАНТА - Система WebSocket уведомлений для админки
 * Обеспечивает real-time push-уведомления о новых заявках
 */

require_once 'security.php';
require_once 'roles.php';

header('Content-Type: application/json');

$token = get_bearer_token();
$session_data = get_session_data($token);

if (!$session_data) {
    http_response_code(401);
    die(json_encode(['ok' => false, 'error' => 'Не авторизован']));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? null;
    
    if ($action === 'check-new') {
        // Проверка новых заявок и уведомлений
        require_permission($session_data['role'], 'view_leads');
        
        $last_check = $_GET['last_check'] ?? 0;
        $leads_file = STORAGE_PATH . 'leads.json';
        
        if (!file_exists($leads_file)) {
            http_response_code(200);
            die(json_encode([
                'ok' => true,
                'new_leads' => [],
                'total_new' => 0,
                'unread_notifications' => 0,
                'timestamp' => time()
            ]));
        }
        
        $leads_data = json_decode(file_get_contents($leads_file), true);
        $leads = $leads_data['leads'] ?? [];
        
        // Фильтр: только новые заявки после last_check
        $new_leads = array_filter($leads, function($lead) use ($last_check) {
            return $lead['status'] === 'new' && strtotime($lead['created_at']) > $last_check;
        });
        
        // Сортировать по дате (новые первыми)
        usort($new_leads, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Подсчет всех новых
        $total_new = count(array_filter($leads, function($l) { 
            return $l['status'] === 'new'; 
        }));
        
        // Уведомления
        $notifications = [];
        foreach ($new_leads as $lead) {
            $notifications[] = [
                'id' => $lead['id'],
                'type' => 'new_lead',
                'title' => 'Новая заявка',
                'message' => $lead['name'] . ' - ' . $lead['phone'],
                'timestamp' => $lead['created_at'],
                'data' => [
                    'lead_id' => $lead['id'],
                    'lead_name' => $lead['name'],
                    'lead_phone' => $lead['phone']
                ]
            ];
        }
        
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'new_leads' => array_values($new_leads),
            'total_new' => $total_new,
            'unread_notifications' => count($notifications),
            'notifications' => $notifications,
            'timestamp' => time()
        ]);
        
    } elseif ($action === 'get-all') {
        // Получить все уведомления для текущего администратора
        $notifications_file = STORAGE_PATH . 'notifications.json';
        $admin_id = $session_data['admin_id'];
        
        if (!file_exists($notifications_file)) {
            http_response_code(200);
            die(json_encode([
                'ok' => true,
                'notifications' => [],
                'total' => 0
            ]));
        }
        
        $notifications_data = json_decode(file_get_contents($notifications_file), true);
        
        // Фильтр: только для текущего администратора
        $user_notifications = array_filter($notifications_data['notifications'] ?? [], function($n) use ($admin_id) {
            return $n['for_admin_id'] === $admin_id || $n['for_admin_id'] === 'all';
        });
        
        // Сортировать по дате (новые первыми)
        usort($user_notifications, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Ограничить последние 50
        $user_notifications = array_slice($user_notifications, 0, 50);
        
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'notifications' => array_values($user_notifications),
            'total' => count($user_notifications)
        ]);
        
    } else {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Неизвестное действие']));
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Создание уведомления (только для super-admin)
    require_permission($session_data['role'], 'manage_permissions');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $notification = [
        'id' => 'notif_' . uniqid(),
        'for_admin_id' => $input['for_admin_id'] ?? 'all',
        'type' => $input['type'] ?? 'info',
        'title' => $input['title'] ?? 'Уведомление',
        'message' => $input['message'] ?? '',
        'timestamp' => date('c'),
        'created_by' => $session_data['admin_id'],
        'read' => false
    ];
    
    $notifications_file = STORAGE_PATH . 'notifications.json';
    
    if (!file_exists($notifications_file)) {
        $notifications_data = ['notifications' => []];
    } else {
        $notifications_data = json_decode(file_get_contents($notifications_file), true);
    }
    
    $notifications_data['notifications'][] = $notification;
    
    // Ограничить до 1000 последних
    if (count($notifications_data['notifications']) > 1000) {
        $notifications_data['notifications'] = array_slice($notifications_data['notifications'], -1000);
    }
    
    file_put_contents($notifications_file, json_encode($notifications_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
    
    http_response_code(201);
    echo json_encode([
        'ok' => true,
        'notification' => $notification
    ]);
    
} else {
    http_response_code(405);
    die(json_encode(['ok' => false, 'error' => 'Method not allowed']));
}
?>
