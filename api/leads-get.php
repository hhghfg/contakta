<?php
require_once 'security.php';
require_once 'roles.php';

header('Content-Type: application/json');

$token = get_bearer_token();
$session_data = get_session_data($token);

if (!$session_data) {
    http_response_code(401);
    die(json_encode(['ok' => false, 'error' => 'Не авторизован']));
}

require_permission($session_data['role'], 'view_leads');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $since = $_GET['since'] ?? 0;
    $limit = $_GET['limit'] ?? 50;
    
    $leads_file = STORAGE_PATH . 'leads.json';
    
    if (!file_exists($leads_file)) {
        http_response_code(200);
        die(json_encode([
            'ok' => true,
            'new_leads' => [],
            'total_new' => 0,
            'timestamp' => time()
        ]));
    }
    
    $leads_data = json_decode(file_get_contents($leads_file), true);
    $leads = $leads_data['leads'];
    
    // Фильтр: только новые заявки (status = 'new')
    $new_leads = array_filter($leads, function($lead) use ($since) {
        return $lead['status'] === 'new' && strtotime($lead['created_at']) > $since;
    });
    
    // Сортировать по дате (новые первыми)
    usort($new_leads, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Ограничить
    $new_leads = array_slice($new_leads, 0, $limit);
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'new_leads' => array_values($new_leads),
        'total_new' => count(array_filter($leads, function($l) { return $l['status'] === 'new'; })),
        'timestamp' => time()
    ]);
    
} else {
    http_response_code(405);
    die(json_encode(['ok' => false, 'error' => 'Method not allowed']));
}
?>
