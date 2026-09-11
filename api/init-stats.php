<?php
/**
 * /api/init-stats.php
 * Инициализация файлов статистики если они отсутствуют
 * Вызвать один раз при первом запуске
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $storage_dir = dirname(__FILE__) . '/../storage';
    
    if (!is_dir($storage_dir)) {
        mkdir($storage_dir, 0777, true);
    }
    
    // Инициализируем lead_stats.json
    $stats_file = $storage_dir . '/lead_stats.json';
    if (!file_exists($stats_file)) {
        $initial_stats = [
            1 => [
                'admin_id' => 1,
                'admin_name' => 'Тестовый администратор',
                'total_opened' => 0,
                'total_closed' => 0,
                'total_viewed' => 0,
                'total_edited' => 0,
                'last_activity' => date('Y-m-d H:i:s')
            ]
        ];
        
        file_put_contents($stats_file, json_encode($initial_stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $stats_created = true;
    } else {
        $stats_created = false;
    }
    
    // Инициализируем lead_locks.json
    $locks_file = $storage_dir . '/lead_locks.json';
    if (!file_exists($locks_file)) {
        file_put_contents($locks_file, json_encode([], JSON_PRETTY_PRINT));
        $locks_created = true;
    } else {
        $locks_created = false;
    }
    
    // Инициализируем leads_meta.json
    $meta_file = $storage_dir . '/leads_meta.json';
    if (!file_exists($meta_file)) {
        file_put_contents($meta_file, json_encode([], JSON_PRETTY_PRINT));
        $meta_created = true;
    } else {
        $meta_created = false;
    }
    
    // Проверяем что leads.json существует
    $leads_file = $storage_dir . '/leads.json';
    $leads_exists = file_exists($leads_file);
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => 'Инициализация завершена',
        'storage_dir' => $storage_dir,
        'initialized' => [
            'lead_stats.json' => $stats_created,
            'lead_locks.json' => $locks_created,
            'leads_meta.json' => $meta_created,
            'leads.json' => $leads_exists
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
