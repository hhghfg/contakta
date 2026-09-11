# КОНТАНТА - Скрытая Админка (Полное руководство)

## 🔐 БЕЗОПАСНОСТЬ И СКРЫТЫЙ ДОСТУП

### 1. Генерация Скрытой Ссылки

Для максимальной безопасности ссылка админки должна быть абсолютно случайной и непредсказуемой.

**Шаг 1: Генерируем ключ**
```
GET /api/generate-admin-key.php
```

Результат (примерный):
```json
{
  "статус": "успешно",
  "скрытая_ссылка": "/контанта/a2F0YW50YV9hZG1pbl9zY2V0X3BhZWw",
  "полная_ссылка": "https://example.com/контанта/a2F0YW50YV9hZG1pbl9zY2V0X3BhZWw",
  "ключ_для_хранения": "f8e9a7c4b2d1f3e5a9c7b5d3f1e9a7c4b2d1f3e5a9c7b5d3f1e9a7c4b2d1",
  "инструкция": "Сохраните в безопасном месте"
}
```

**Шаг 2: Обновляем конфиг**

Отредактируйте `/api/admin-config.php`:

```php
define('ADMIN_SECRET_PATH', 'контанта');  // ваша папка
define('ADMIN_KEY_HASH', 'ваш_хэш_ключа');  // хэш из ответа выше
```

**Шаг 3: Защищаем доступ через .htaccess**

Создайте `/контанта/.htaccess`:
```
# Блокируем прямой доступ
<Files "*.php">
    Deny from all
</Files>

# Разрешаем только основной файл
<Files "админ.html">
    Allow from all
</Files>

# Используем TLS 1.2+
SSLEngine on
SSLProtocol TLSv1.2 TLSv1.3

# HSTS (принудить HTTPS)
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Скрыть от поисковиков
Header set X-Robots-Tag "noindex, nofollow"
Header set X-UA-Compatible "IE=edge"

# Защита от XSS
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "DENY"
Header set X-XSS-Protection "1; mode=block"

# Защита от clickjacking
Header set X-Permitted-Cross-Domain-Policies "none"

# CSP (Content Security Policy)
Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
```

---

## 🔑 Установка Пароля Администратора

По умолчанию: `admin123`

**Для смены пароля:**

1. Сгенерируйте хэш:
```bash
php -r "echo password_hash('ваш_новый_пароль', PASSWORD_BCRYPT);"
```

2. Обновите в `/api/admin-auth.php`:
```php
function get_admin_password_hash() {
    return 'ВАШ_НОВЫЙ_ХЭШ';
}
```

---

## 📊 Четыре Раздела Админки

### 1. 📋 Заявки
- Список всех форм заявок
- Быстрые действия: просмотр, удаление, звонок
- Статистика по статусам
- Фильтрация по статусу

### 2. 📊 Аналитика
- Общая статистика по заявкам
- Графики и диаграммы
- Динамика за время
- Конверсия

### 3. 👥 Клиенты
Максимально подробная информация о каждом клиенте:

- **Браузер:**
  - User-Agent
  - Версия браузера
  - ОС
  - Язык системы

- **Cookies:**
  - Список всех cookies
  - Время жизни
  - Домены

- **Fingerprint:**
  - WebGL информация
  - Canvas fingerprint
  - Список шрифтов
  - Плагины браузера

- **Геолокация:**
  - IP адрес
  - Страна, город, регион
  - Координаты (если разрешил клиент)

- **Поведение:**
  - Время на сайте
  - Количество кликов
  - Глубина прокрутки
  - Посещенные страницы

- **Безопасность:**
  - Обнаружение VPN ✓
  - Обнаружение Proxy ✓
  - Обнаружение TOR ✓
  - WebRTC утечки
  - DNS утечки

### 4. ☎️ Звонки
- Автоматический набор номера
- История всех контактов
- Интеграция с WhatsApp/Telegram
- Логирование всех звонков

---

## 🛡️ Система Безопасности

### Защита от Brute Force
- Максимум 3 попытки входа
- Блокировка на 1 час после превышения
- Логирование всех попыток

### Проверка IP и User-Agent
- IP адрес должен совпадать при каждой операции
- User-Agent сравнивается с исходным
- Обнаружение попыток session hijacking

### Требование HTTPS
- ОБЯЗАТЕЛЬНО использовать HTTPS в продакшене
- Все данные шифруются в пути

### Логирование
Все события логируются в `/storage/admin/logs/`:
```
logs/
├── 2024-01-15.jsonl        (все входы)
├── alerts_2024-01-15.jsonl  (подозрительная активность)
└── brute_force_*.txt        (попытки взлома)
```

---

## 🚀 Использование Админки

### Вход в систему

1. Откройте: `https://example.com/контанта/ВАШ_СГЕНЕРИРОВАННЫЙ_КЛЮЧ`
2. Введите пароль
3. ✅ Вы вошли

### Безопасность при использовании

⚠️ **Обязательные требования:**

1. **Используйте TOR браузер**
   - Скачайте с: https://www.torproject.org/
   - Это скрывает ваш IP и запросы

2. **Очищайте кэш после каждого входа**
   - История браузера
   - Cookies
   - Cache

3. **Не сохраняйте пароль в браузере**
   - Используйте KeePass, 1Password, LastPass
   - Генерируйте случайные пароли

4. **Не сообщайте ссылку никому**
   - Даже сотрудникам
   - Даже "временно"

5. **Используйте разные IP адреса**
   - VPN (ExpressVPN, NordVPN)
   - TOR
   - Мобильный интернет

6. **Отключайте JavaScript если не требуется**
   - Settings → Privacy & Security → Disable JavaScript

---

## 📝 Примеры API

### Сбор аналитики клиента

```javascript
// На клиенте (form.html)
fetch('/api/collect-analytics.php?action=collect', {
    method: 'POST',
    body: JSON.stringify({
        lead_id: 123,
        userAgent: navigator.userAgent,
        platform: navigator.platform,
        language: navigator.language,
        screenWidth: window.screen.width,
        screenHeight: window.screen.height,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        cookies: getCookieData(),
        webgl: getWebGLData(),
        canvas: getCanvasFingerprint()
    })
})
```

### Вход в админку

```javascript
fetch('/api/admin-auth.php?action=login', {
    method: 'POST',
    body: JSON.stringify({
        path_token: 'a2F0YW50YV9hZG1pbl9zY2V0X3BhZWw',
        password: 'your_password'
    })
})
```

---

## 📁 Структура Файлов

```
/
├── api/
│   ├── admin-auth.php           # Авторизация
│   ├── admin-config.php         # Конфиг безопасности
│   ├── generate-admin-key.php   # Генератор ключей
│   └── collect-analytics.php    # Сбор данных
│
├── контанта/
│   ├── админ.html               # Скрытая админка
│   └── .htaccess                # Защита через htaccess
│
└── storage/
    ├── admin/
    │   ├── analytics/           # Данные клиентов
    │   ├── sessions.json        # Сессии
    │   └── logs/
    │       ├── 2024-01-15.jsonl
    │       └── alerts_2024-01-15.jsonl
    └── leads.json              # Заявки
```

---

## ⚡ Особенности Системы

✅ **Что собирается:**
- Все cookies клиента
- Полный User-Agent
- Canvas fingerprint
- WebGL информация
- Список установленных шрифтов
- Плагины браузера
- Timezone и язык
- Разрешение экрана
- IP адрес
- Геолокация (если разрешили)

🛡️ **Система защиты:**
- 512+ бит энтропии в URL
- Salt по серверным данным
- Защита от brute force (3 попытки)
- Session hijacking detection
- IP binding
- User-Agent verification
- HTTPS enforcement
- CSP headers

📊 **Аналитика:**
- Поведение клиента (клики, прокрутка)
- История посещений
- Время на сайте
- VPN/Proxy/TOR detection
- WebRTC leak detection

---

## 🚨 Troubleshooting

### "Доступ запрещен"
- Проверьте IP адрес (должен совпадать)
- Убедитесь, что используете HTTPS
- Очистите cookies и cache

### "Сессия недействительна"
- Сессия истекла (30 минут)
- Войдите заново

### "Слишком много попыток"
- Подождите 1 час
- Или измените IP адрес

### Logs не записываются
- Проверьте права на `/storage/admin/logs/`
- `chmod 755 storage/admin/logs/`

---

## 📞 Поддержка

Все данные хранятся локально в JSON файлах.
Для миграции на БД (MySQL/PostgreSQL) - свяжитесь с разработчиком.

**Версия:** 1.0.0  
**Дата:** 2024  
**Безопасность:** ВЫСОКАЯ 🔐
