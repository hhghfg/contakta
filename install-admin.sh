#!/bin/bash
# КОНТАНТА - Скрипт автоматической установки новой админки

echo "=================================="
echo "🔐 КОНТАНТА - Установка админки"
echo "=================================="
echo

# 1. Проверяем структуру
echo "1️⃣  Проверяем структуру файлов..."
files=(
    "api/admin-config.php"
    "api/admin-auth.php"
    "api/generate-admin-key.php"
    "api/collect-analytics.php"
    "контанта/админ.html"
    "контанта/.htaccess"
    "analytics-collector.js"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ $file (ОТСУТСТВУЕТ)"
    fi
done

echo

# 2. Создаем директории для хранения
echo "2️⃣  Создаем директории для хранения..."
mkdir -p storage/admin/analytics
mkdir -p storage/admin/logs
chmod 755 storage/admin/analytics
chmod 755 storage/admin/logs
echo "✅ Директории созданы"

echo

# 3. Генерируем ключ
echo "3️⃣  Генерируем скрытую ссылку..."
KEY=$(php api/generate-admin-key.php 2>/dev/null | grep -oP '"полная_ссылка":\s*"\K[^"]+' | head -1)
HASH=$(php api/generate-admin-key.php 2>/dev/null | grep -oP '"ключ_для_хранения":\s*"\K[^"]+' | head -1)

if [ -z "$KEY" ]; then
    echo "⚠️  Не удалось сгенерировать ключ автоматически"
    echo "📝 Выполните вручную: curl http://localhost/api/generate-admin-key.php"
else
    echo "✅ Ключ сгенерирован:"
    echo "   URL: $KEY"
    echo "   ХЭШЬ: $HASH"
fi

echo

# 4. Инструкция
echo "4️⃣  ДАЛЕЕ НУЖНО СДЕЛАТЬ ВРУЧНУЮ:"
echo

echo "📝 Шаг 1 - Отредактируйте /api/admin-config.php"
echo "   Строка 23: define('ADMIN_KEY_HASH', 'ВСТАВЬТЕ_ХЭШ');"
echo

echo "🔑 Шаг 2 - Установите пароль"
echo "   Команда: php -r \"echo password_hash('your_password', PASSWORD_BCRYPT);\""
echo "   Вставьте результат в /api/admin-auth.php функция get_admin_password_hash()"
echo

echo "📊 Шаг 3 - Подключите аналитику"
echo "   На главной странице (index.html) перед </body> добавьте:"
echo '   <script src="/analytics-collector.js"></script>'
echo '   <script>window.leadId = {{ LEAD_ID }};</script>'
echo

echo "✅ Вход в админку:"
echo "   URL: $KEY"
echo "   Пароль: admin123 (или ваш установленный)"
echo

echo "=================================="
echo "🎉 Установка завершена!"
echo "=================================="
