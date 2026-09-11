<?php
/**
 * ФИНАЛЬНЫЙ АУДИТ - Проверка всех учетных данных
 */

header('Content-Type: application/json; charset=utf-8');

$STORAGE_PATH = dirname(dirname(__FILE__)) . '/storage/';
$admins_file = $STORAGE_PATH . 'admins.json';

$audit = [
    'timestamp' => date('c'),
    'status' => 'AUDIT_COMPLETE',
    'files' => [
        'admins.json' => [
            'exists' => file_exists($admins_file),
            'readable' => is_readable($admins_file),
            'size' => file_exists($admins_file) ? filesize($admins_file) : 0
        ],
        'auth.php' => [
            'exists' => file_exists(dirname(__FILE__) . '/auth.php'),
            'size' => file_exists(dirname(__FILE__) . '/auth.php') ? filesize(dirname(__FILE__) . '/auth.php') : 0
        ],
        'admin-create.php' => [
            'exists' => file_exists(dirname(__FILE__) . '/admin-create.php'),
            'size' => file_exists(dirname(__FILE__) . '/admin-create.php') ? filesize(dirname(__FILE__) . '/admin-create.php') : 0
        ]
    ],
    'admins' => []
];

if (file_exists($admins_file)) {
    $data = json_decode(file_get_contents($admins_file), true);
    
    foreach ($data['admins'] as $admin) {
        $audit['admins'][] = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'role' => $admin['role'],
            'status' => $admin['status'],
            'email' => $admin['email'],
            'password_configured' => !empty($admin['password'])
        ];
    }
}

echo json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
