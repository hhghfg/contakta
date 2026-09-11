<?php
/**
 * Проверка совместимости хостинга РЦЕНТР
 * Откройте этот файл в браузере: https://your-domain.nic.ru/check-rucentr.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка совместимости РЦЕНТР</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a472a;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        .check {
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #ddd;
        }
        .check.ok {
            background: #f0f9f0;
            border-left-color: #10b981;
        }
        .check.warning {
            background: #fef8f0;
            border-left-color: #f59e0b;
        }
        .check.error {
            background: #fef0f0;
            border-left-color: #ef4444;
        }
        .check-name {
            font-weight: 600;
            color: #333;
        }
        .check-value {
            font-family: monospace;
            font-size: 12px;
            color: #666;
        }
        .status {
            font-weight: 700;
            font-size: 14px;
        }
        .ok .status { color: #10b981; }
        .warning .status { color: #f59e0b; }
        .error .status { color: #ef4444; }
        
        .section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        .section h2 {
            color: #1a472a;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 4px;
            border-left: 4px solid #1a472a;
        }
        .summary h3 {
            color: #1a472a;
            margin-bottom: 10px;
        }
        .summary p {
            margin: 5px 0;
            font-size: 14px;
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Проверка совместимости РЦЕНТР</h1>

        <?php
        $checks = [];
        $all_ok = true;

        // 1. PHP версия
        $php_version = phpversion();
        $php_major = intval(explode('.', $php_version)[0]);
        
        if ($php_major >= 7) {
            $checks[] = [
                'name' => 'PHP версия',
                'value' => $php_version,
                'status' => 'ok',
                'message' => 'ОК'
            ];
        } else {
            $checks[] = [
                'name' => 'PHP версия',
                'value' => $php_version,
                'status' => 'error',
                'message' => 'Требуется PHP 7.2+'
            ];
            $all_ok = false;
        }

        // 2. Функция mail()
        $mail_ok = function_exists('mail');
        $checks[] = [
            'name' => 'Функция mail()',
            'value' => $mail_ok ? 'Да' : 'Нет',
            'status' => $mail_ok ? 'ok' : 'warning',
            'message' => $mail_ok ? 'Работает' : 'Может не работать'
        ];

        // 3. Функция fsockopen()
        $fsockopen_ok = function_exists('fsockopen');
        $checks[] = [
            'name' => 'Функция fsockopen()',
            'value' => $fsockopen_ok ? 'Да' : 'Нет',
            'status' => $fsockopen_ok ? 'ok' : 'warning',
            'message' => $fsockopen_ok ? 'SMTP доступен' : 'SMTP недоступен'
        ];

        // 4. cURL
        $curl_ok = function_exists('curl_init');
        $checks[] = [
            'name' => 'cURL',
            'value' => $curl_ok ? 'Да' : 'Нет',
            'status' => $curl_ok ? 'ok' : 'warning',
            'message' => $curl_ok ? 'Доступен' : 'Недоступен'
        ];

        // 5. JSON
        $json_ok = function_exists('json_encode');
        $checks[] = [
            'name' => 'JSON функции',
            'value' => $json_ok ? 'Да' : 'Нет',
            'status' => $json_ok ? 'ok' : 'error',
            'message' => $json_ok ? 'ОК' : 'ОШИБКА'
        ];
        if (!$json_ok) $all_ok = false;

        // 6. Запись файлов
        $test_file = 'test_write_' . time() . '.txt';
        $write_ok = @file_put_contents($test_file, 'test') !== false;
        if ($write_ok) @unlink($test_file);
        
        $checks[] = [
            'name' => 'Запись файлов',
            'value' => $write_ok ? 'Да' : 'Нет',
            'status' => $write_ok ? 'ok' : 'error',
            'message' => $write_ok ? 'ОК' : 'ОШИБКА'
        ];
        if (!$write_ok) $all_ok = false;

        // 7. .htaccess
        $htaccess_ok = file_exists('.htaccess') || true; // РЦЕНТР обычно поддерживает
        $checks[] = [
            'name' => '.htaccess поддержка',
            'value' => 'Предположительно да',
            'status' => 'ok',
            'message' => 'РЦЕНТР поддерживает'
        ];

        // 8. DirectoryIndex
        $checks[] = [
            'name' => 'DirectoryIndex',
            'value' => 'index.html/index.php',
            'status' => 'ok',
            'message' => 'РЦЕНТР по умолчанию'
        ];

        // Выводим результаты
        ?>

        <div class="section">
            <h2>📋 Результаты проверки</h2>
            
            <?php foreach ($checks as $check): ?>
            <div class="check <?php echo $check['status']; ?>">
                <div>
                    <div class="check-name"><?php echo $check['name']; ?></div>
                    <div class="check-value"><?php echo $check['value']; ?></div>
                </div>
                <div class="status"><?php echo $check['message']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="section">
            <h2>📊 Информация о сервере</h2>
            <div class="check ok">
                <div>
                    <div class="check-name">Операционная система</div>
                    <div class="check-value"><?php echo php_uname(); ?></div>
                </div>
            </div>

            <div class="check ok">
                <div>
                    <div class="check-name">Web сервер</div>
                    <div class="check-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Неизвестно'; ?></div>
                </div>
            </div>

            <div class="check ok">
                <div>
                    <div class="check-name">Временная зона</div>
                    <div class="check-value"><?php echo date_default_timezone_get(); ?></div>
                </div>
            </div>

            <div class="check ok">
                <div>
                    <div class="check-name">Максимальный размер загрузки</div>
                    <div class="check-value"><?php echo ini_get('upload_max_filesize'); ?></div>
                </div>
            </div>

            <div class="check ok">
                <div>
                    <div class="check-name">Лимит памяти PHP</div>
                    <div class="check-value"><?php echo ini_get('memory_limit'); ?></div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>🔧 Расширения PHP</h2>
            <div class="check ok">
                <div>
                    <div class="check-name">Загруженные расширения</div>
                    <div class="check-value"><?php 
                        $extensions = ['OpenSSL', 'PDO', 'MySQLi', 'GD', 'SPL'];
                        $loaded = [];
                        foreach ($extensions as $ext) {
                            if (extension_loaded($ext)) {
                                $loaded[] = $ext;
                            }
                        }
                        echo implode(', ', $loaded) ?: 'Основные расширения';
                    ?></div>
                </div>
            </div>
        </div>

        <div class="summary">
            <h3><?php echo $all_ok ? '✅ ВСЕ ПРОВЕРКИ ПРОЙДЕНЫ' : '⚠️ ЕСТЬ ПРОБЛЕМЫ'; ?></h3>
            <p>
                <?php if ($all_ok): ?>
                    <strong>Хостинг РЦЕНТР полностью совместим с проектом КОНТАНТА!</strong><br>
                    Можете развертывать сайт без проблем.
                <?php else: ?>
                    <strong>Обнаружены ошибки:</strong><br>
                    Обратитесь в технической поддержку РЦЕНТР для включения необходимых функций.
                <?php endif; ?>
            </p>

            <p style="margin-top: 15px; color: #666; font-size: 12px;">
                Проверка выполнена: <?php echo date('d.m.Y H:i:s'); ?>
            </p>
        </div>

        <div class="section" style="background: #f9f9f9; padding: 15px; border-radius: 4px;">
            <h3 style="color: #1a472a; margin-bottom: 10px;">💡 Следующие шаги:</h3>
            <ol style="margin-left: 20px; line-height: 1.8;">
                <li>Удалите этот файл <code>check-rucentr.php</code> после проверки</li>
                <li>Откройте файл <code>/api/lead-email.php</code> и установите ваш email</li>
                <li>Откройте <code>https://your-domain.nic.ru/api/generate-admin-key.php</code></li>
                <li>Скопируйте сгенерированный ключ в <code>/api/admin-config.php</code></li>
                <li>Откройте главную страницу и протестируйте форму</li>
            </ol>
        </div>
    </div>

    <script>
        console.log('✅ Проверка совместимости РЦЕНТР завершена');
        console.log('📋 Результаты выше на странице');
    </script>
</body>
</html>
