<?php
// Диагностика системы

header('Content-Type: application/json');

$storage_path = dirname(dirname(__FILE__)) . '/storage/';

$diagnostics = [
    'storage_path' => $storage_path,
    'storage_exists' => is_dir($storage_path),
    'storage_writable' => is_writable($storage_path),
    'admins_file' => [
        'path' => $storage_path . 'admins.json',
        'exists' => file_exists($storage_path . 'admins.json'),
        'readable' => is_readable($storage_path . 'admins.json'),
        'size' => file_exists($storage_path . 'admins.json') ? filesize($storage_path . 'admins.json') : 0,
    ]
];

// Попытаться прочитать admins.json
if (file_exists($storage_path . 'admins.json')) {
    $content = file_get_contents($storage_path . 'admins.json');
    $data = json_decode($content, true);
    
    $diagnostics['admins_data'] = [
        'json_valid' => $data !== null,
        'admins_count' => $data ? count($data['admins'] ?? []) : 0,
        'first_admin' => $data && isset($data['admins'][0]) ? [
            'id' => $data['admins'][0]['id'] ?? null,
            'username' => $data['admins'][0]['username'] ?? null,
            'password' => $data['admins'][0]['password'] ?? '[HIDDEN]',
            'password_hash' => isset($data['admins'][0]['password_hash']) ? '[PRESENT]' : '[ABSENT]',
            'role' => $data['admins'][0]['role'] ?? null,
            'status' => $data['admins'][0]['status'] ?? null,
        ] : null
    ];
}

// Протестировать login
$_SERVER['REQUEST_METHOD'] = 'POST';
$test_data = [
    'username' => 'test_admin',
    'password' => 'admin123'
];

$admins_data = json_decode(file_get_contents($storage_path . 'admins.json'), true);
$found = false;
$password_match = false;

foreach ($admins_data['admins'] as $admin) {
    if ($admin['username'] === 'test_admin') {
        $found = true;
        if (isset($admin['password'])) {
            $password_match = ($admin['password'] === 'admin123');
        }
        break;
    }
}

$diagnostics['login_test'] = [
    'username_found' => $found,
    'password_matches' => $password_match,
    'test_login' => 'test_admin / admin123'
];

echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
