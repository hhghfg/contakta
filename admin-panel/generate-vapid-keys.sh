#!/bin/bash

# Скрипт для генерации VAPID ключей для Web Push

echo "🔑 Generating VAPID keys for Web Push..."
echo ""

if command -v npx &> /dev/null; then
    npx web-push generate-vapid-keys
    echo ""
    echo "📋 Скопируйте ключи в .env файл:"
    echo ""
    echo "VAPID_PUBLIC_KEY=<public_key>"
    echo "VAPID_PRIVATE_KEY=<private_key>"
    echo "VAPID_SUBJECT=mailto:your-email@example.com"
else
    echo "❌ Node.js не установлен"
    echo "Установите Node.js: https://nodejs.org/"
fi
