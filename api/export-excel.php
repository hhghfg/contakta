<?php
/**
 * GET /api/export-excel.php?token={token}
 * Экспорт заявок в Excel/CSV
 */

header('Access-Control-Allow-Origin: *');

$token = $_GET['token'] ?? null;
if (!$token || empty($token)) {
    http_response_code(401);
    exit('Unauthorized');
}

$storage_dir = __DIR__ . '/../storage';
$leads_file = $storage_dir . '/leads.json';
$meta_file = $storage_dir . '/leads_meta.json';

// Читаем заявки
$leads = [];
if (file_exists($leads_file)) {
    $file_content = @file_get_contents($leads_file);
    if ($file_content && !empty($file_content)) {
        $leads = json_decode($file_content, true) ?? [];
    }
}

// Читаем метаданные
$meta = [];
if (file_exists($meta_file)) {
    $meta_content = @file_get_contents($meta_file);
    if ($meta_content && !empty($meta_content)) {
        $meta = json_decode($meta_content, true) ?? [];
    }
}

// Объединяем
foreach ($leads as &$lead) {
    if (isset($meta[$lead['id']])) {
        $lead['status'] = $meta[$lead['id']]['status'] ?? 'new';
        $lead['assigned_to'] = $meta[$lead['id']]['assigned_to'] ?? '-';
    } else {
        $lead['status'] = 'new';
        $lead['assigned_to'] = '-';
    }
}

// CSV заголовки
$filename = 'kontanta-leads-' . date('Y-m-d-H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF"); // BOM для Excel

// Заголовки столбцов
fputcsv($output, [
    'ID',
    'Имя',
    'Телефон',
    'Email',
    'Город',
    'Возраст',
    'Вопрос/пожелание',
    'Статус',
    'Назначено',
    'Дата подачи'
], ';');

// Данные
foreach ($leads as $lead) {
    fputcsv($output, [
        $lead['id'],
        $lead['name'] ?? '-',
        $lead['phone'] ?? '-',
        $lead['email'] ?? '-',
        $lead['city'] ?? '-',
        $lead['age'] ?? '-',
        $lead['message'] ?? '-',
        $lead['status'],
        $lead['assigned_to'],
        $lead['created_at'] ?? '-'
    ], ';');
}

fclose($output);
?>
