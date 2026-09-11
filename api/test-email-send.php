<?php
/**
 * КОНТАНТА - Тестовая отправка письма
 * Используйте для проверки работы почты
 * 
 * Откройте в браузере: https://ваш-домен.nic.ru/api/test-email-send.php
 */

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Тест отправки писем - КОНТАНТА</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        button:active {
            transform: translateY(0);
        }
        .info-box {
            background: #f0f7ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 30px;
            font-size: 13px;
            color: #333;
            line-height: 1.6;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        .success.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        .error.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
            vertical-align: middle;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .hint {
            font-size: 12px;
            color: #999;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Тест отправки писем</h1>
        <p class="subtitle">КОНТАНТА - Проверка почты</p>
        
        <div class="info-box">
            ℹ️ <strong>Как это работает:</strong><br>
            Заполните форму и нажмите "Отправить". Вам придет тестовое письмо.<br>
            <strong>Проверьте спам-папку!</strong> Письмо может туда попасть.
        </div>
        
        <div class="success" id="successMessage">
            ✅ <strong>Успешно!</strong> Письмо отправлено. Проверьте почту (включая спам-папку).
        </div>
        
        <div class="error" id="errorMessage">
            ❌ <strong>Ошибка:</strong> <span id="errorText"></span>
        </div>
        
        <form id="testForm">
            <div class="form-group">
                <label for="test_email">📧 Ваш email (куда отправить письмо)</label>
                <input 
                    type="email" 
                    id="test_email" 
                    name="email" 
                    placeholder="example@gmail.com"
                    required
                >
                <div class="hint">Используйте тот же email, что и в конфиге (или любой другой)</div>
            </div>
            
            <div class="form-group">
                <label for="test_name">👤 Ваше имя</label>
                <input 
                    type="text" 
                    id="test_name" 
                    name="name" 
                    placeholder="Иван Петров"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="test_message">💬 Тестовое сообщение</label>
                <textarea 
                    id="test_message" 
                    name="message" 
                    placeholder="Напишите что угодно для теста..."
                ></textarea>
                <div class="hint">Это сообщение попадет в письмо</div>
            </div>
            
            <button type="submit">
                <span id="buttonText">📤 Отправить тестовое письмо</span>
            </button>
        </form>
    </div>

    <script>
        const form = document.getElementById('testForm');
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        const buttonText = document.getElementById('buttonText');
        const button = form.querySelector('button');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Скрываем сообщения
            successMessage.classList.remove('show');
            errorMessage.classList.remove('show');
            
            // Отключаем кнопку и показываем загрузку
            button.disabled = true;
            buttonText.innerHTML = '<span class="loading"></span>Отправляю...';
            
            const formData = {
                email: document.getElementById('test_email').value,
                name: document.getElementById('test_name').value,
                message: document.getElementById('test_message').value,
                phone: '+7 (999) 999-99-99',  // Фиктивный номер для теста
                age: '30',
                gdpr_consent: true
            };
            
            try {
                const response = await fetch('/api/lead-email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.ok) {
                    successMessage.classList.add('show');
                    form.reset();
                    console.log('Письмо отправлено:', data);
                } else {
                    errorText.textContent = data.error || 'Неизвестная ошибка';
                    errorMessage.classList.add('show');
                    console.error('Ошибка:', data);
                }
            } catch (error) {
                errorText.textContent = 'Ошибка сети: ' + error.message;
                errorMessage.classList.add('show');
                console.error('Ошибка сети:', error);
            } finally {
                button.disabled = false;
                buttonText.innerHTML = '📤 Отправить тестовое письмо';
            }
        });
    </script>
</body>
</html>
