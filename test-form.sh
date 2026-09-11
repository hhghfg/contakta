#!/bin/bash
# Скрипт проверки работоспособности формы обратной связи

echo "========================================"
echo "🧪 ТЕСТИРОВАНИЕ ФОРМЫ ОБРАТНОЙ СВЯЗИ"
echo "========================================"
echo

# 1. Проверяем существование файлов
echo "1️⃣ Проверка файлов..."
files=(
    "api/lead-email.php"
    "api/email-config.php"
    "main-script.js"
    "index.html"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ $file (НЕ НАЙДЕН)"
    fi
done

echo

# 2. Проверяем конфиг
echo "2️⃣ Проверка конфига..."
ADMIN_EMAIL=$(grep -oP '\$ADMIN_EMAIL\s*=\s*[\'\"]\K[^\'"]*' api/lead-email.php | head -1)
if [ -z "$ADMIN_EMAIL" ] || [ "$ADMIN_EMAIL" = "admin@example.com" ]; then
    echo "⚠️  Email администратора не настроен или имеет значение по умолчанию"
    echo "   Отредактируйте: api/lead-email.php"
    echo "   Строка: \$ADMIN_EMAIL = 'your-email@gmail.com';"
else
    echo "✅ Email администратора: $ADMIN_EMAIL"
fi

echo

# 3. Проверяем что можно отправить тестовое письмо
echo "3️⃣ Проверка функции mail()..."
php -r "
\$to = '$ADMIN_EMAIL';
\$subject = 'Test from КОНТАНТА Form';
\$message = 'Это тестовое письмо от системы КОНТАНТА';
\$headers = 'From: noreply@kontanta.ru' . \"\\r\\n\";
\$headers .= 'Content-Type: text/html; charset=UTF-8' . \"\\r\\n\";

if (@mail(\$to, \$subject, \$message, \$headers)) {
    echo \"✅ Письмо может быть отправлено на $to\\n\";
} else {
    echo \"⚠️  mail() функция может быть недоступна\\n\";
}
" 2>/dev/null

echo

# 4. Проверяем хранилище
echo "4️⃣ Проверка хранилища..."
if [ -d "storage" ]; then
    echo "✅ Директория storage существует"
    
    if [ -f "storage/leads.json" ]; then
        COUNT=$(jq 'length' storage/leads.json 2>/dev/null || echo "?")
        echo "✅ storage/leads.json: $COUNT заявок"
    else
        echo "📝 storage/leads.json: будет создан при первой заявке"
    fi
else
    echo "⚠️  Директория storage не найдена (создаст себя при необходимости)"
fi

echo

# 5. Инструкция
echo "========================================"
echo "📋 ИНСТРУКЦИЯ ТЕСТИРОВАНИЯ"
echo "========================================"
echo
echo "1️⃣ Откройте сайт:"
echo "   https://your-domain.com"
echo
echo "2️⃣ Прокрутите до раздела 'Получить консультацию'"
echo
echo "3️⃣ Заполните форму:"
echo "   - Имя: Иван Тестов"
echo "   - Телефон: +7 (999) 123-45-67"
echo "   - Email: your-test@gmail.com"
echo "   - Сообщение: Тестовая заявка"
echo
echo "4️⃣ Отметьте чекбокс согласия"
echo
echo "5️⃣ Нажмите 'Отправить заявку'"
echo
echo "6️⃣ Проверьте:"
echo "   ✅ Появилось ли сообщение об успехе"
echo "   ✅ Пришло ли письмо администратору ($ADMIN_EMAIL)"
echo "   ✅ Пришло ли письмо клиенту (your-test@gmail.com)"
echo
echo "7️⃣ Проверьте логи:"
echo "   tail -f storage/email-log.jsonl"
echo
echo "8️⃣ Проверьте админку:"
echo "   /контанта/админ.html → Раздел 'Заявки'"
echo

echo "========================================"
echo "🔍 ЧТОБЫ ОТСЛЕДИТЬ ПРОБЛЕМЫ"
echo "========================================"
echo
echo "1. Откройте DevTools браузера (F12)"
echo "2. Перейдите на Console"
echo "3. Заполните форму заново"
echo "4. Посмотрите ошибки в консоли"
echo
echo "5. Проверьте логи сервера:"
echo "   tail -f /var/log/mail.log"
echo
echo "6. Проверьте логи PHP:"
echo "   tail -f /var/log/php-error.log"
echo

echo "========================================"
echo "✅ ГОТОВО К ТЕСТИРОВАНИЮ!"
echo "========================================"
