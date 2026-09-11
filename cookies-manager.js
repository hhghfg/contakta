/**
 * Cookies Manager - GDPR & РКН COMPLIANT
 * Управление cookie согласиями и их использованием
 */

class CookiesManager {
    constructor() {
        this.storagePrefix = 'kontanta_';
        this.consentKey = this.storagePrefix + 'cookies_consent';
        this.analyticsKey = this.storagePrefix + 'analytics_enabled';
        this.functionalKey = this.storagePrefix + 'functional_enabled';
        this.marketingKey = this.storagePrefix + 'marketing_enabled';
        this.initConsent();
        this.showBannerIfNeeded();
    }

    /**
     * Инициализирует согласие на cookies
     */
    initConsent() {
        const consent = this.getConsent();
        if (!consent) {
            // Если нет согласия - устанавливаем дефолтные необходимые cookies
            this.setNecessaryCookies();
        }
    }

    /**
     * Показывает баннер согласия если пользователь не согласился
     */
    showBannerIfNeeded() {
        const consent = this.getConsent();
        if (!consent) {
            this.showCookiesBanner();
        }
    }

    /**
     * Показывает баннер согласия на cookies (GDPR & РКН)
     */
    showCookiesBanner() {
        const existingBanner = document.getElementById('cookies-banner');
        if (existingBanner) return;

        const banner = document.createElement('div');
        banner.id = 'cookies-banner';
        banner.innerHTML = `
            <div class="cookies-banner-container">
                <div class="cookies-banner-content">
                    <div class="cookies-banner-title">🍪 Управление Cookies</div>
                    <div class="cookies-banner-text">
                        Сайт КОНТАНТА использует cookies для улучшения качества обслуживания, 
                        аналитики и безопасности. Некоторые cookies необходимы для работы сайта.
                    </div>
                    
                    <div class="cookies-types">
                        <div class="cookie-type">
                            <input type="checkbox" id="necessary-cookies" checked disabled>
                            <label for="necessary-cookies">
                                <strong>Необходимые cookies</strong> (обязательные)
                                <small>Требуются для работы сайта и безопасности</small>
                            </label>
                        </div>
                        
                        <div class="cookie-type">
                            <input type="checkbox" id="functional-cookies" checked>
                            <label for="functional-cookies">
                                <strong>Функциональные cookies</strong>
                                <small>Сохраняют ваши предпочтения и язык</small>
                            </label>
                        </div>
                        
                        <div class="cookie-type">
                            <input type="checkbox" id="analytics-cookies">
                            <label for="analytics-cookies">
                                <strong>Аналитические cookies</strong>
                                <small>Помогают нам понять как пользователи взаимодействуют с сайтом</small>
                            </label>
                        </div>
                        
                        <div class="cookie-type">
                            <input type="checkbox" id="marketing-cookies">
                            <label for="marketing-cookies">
                                <strong>Маркетинговые cookies</strong>
                                <small>Для показа релевантной рекламы и отслеживания рекламных кампаний</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="cookies-info">
                        <a href="/privacy.html" target="_blank">Политика конфиденциальности</a> • 
                        <a href="/terms.html" target="_blank">Условия использования</a>
                    </div>
                </div>
                
                <div class="cookies-actions">
                    <button class="btn-reject" onclick="cookiesManager.rejectAll()">Только необходимые</button>
                    <button class="btn-settings" onclick="cookiesManager.showSettings()">Настройки</button>
                    <button class="btn-accept" onclick="cookiesManager.acceptAll()">Принять все</button>
                </div>
            </div>
        `;

        // Добавляем стили
        const style = document.createElement('style');
        style.textContent = `
            #cookies-banner {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(180deg, rgba(13, 31, 23, 0.98) 0%, rgba(26, 71, 42, 0.98) 100%);
                border-top: 3px solid #d4af37;
                padding: 20px;
                z-index: 9999;
                box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.5);
                animation: slideUp 0.3s ease-out;
                font-family: 'Segoe UI', Arial, sans-serif;
                color: #e8e8e8;
            }

            @keyframes slideUp {
                from {
                    transform: translateY(100%);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }

            .cookies-banner-container {
                max-width: 1200px;
                margin: 0 auto;
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 30px;
                flex-wrap: wrap;
            }

            .cookies-banner-content {
                flex: 1;
                min-width: 300px;
            }

            .cookies-banner-title {
                font-size: 18px;
                font-weight: 900;
                color: #d4af37;
                text-transform: uppercase;
                letter-spacing: 1px;
                margin-bottom: 12px;
            }

            .cookies-banner-text {
                font-size: 13px;
                line-height: 1.6;
                margin-bottom: 15px;
                color: #e8e8e8;
            }

            .cookies-types {
                background: rgba(13, 31, 23, 0.5);
                border: 1px solid rgba(212, 175, 55, 0.2);
                border-radius: 4px;
                padding: 15px;
                margin-bottom: 15px;
            }

            .cookie-type {
                display: flex;
                gap: 10px;
                margin-bottom: 12px;
                align-items: flex-start;
            }

            .cookie-type:last-child {
                margin-bottom: 0;
            }

            .cookie-type input[type="checkbox"] {
                width: 18px;
                height: 18px;
                margin-top: 2px;
                cursor: pointer;
                accent-color: #dc143c;
            }

            .cookie-type input[type="checkbox"]:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            .cookie-type label {
                cursor: pointer;
                flex: 1;
            }

            .cookie-type label strong {
                color: #d4af37;
                display: block;
                margin-bottom: 3px;
            }

            .cookie-type label small {
                color: #a8a8a8;
                font-size: 11px;
                display: block;
            }

            .cookies-info {
                font-size: 11px;
                color: #a8a8a8;
            }

            .cookies-info a {
                color: #d4af37;
                text-decoration: none;
                border-bottom: 1px solid rgba(212, 175, 55, 0.3);
                transition: all 0.3s;
            }

            .cookies-info a:hover {
                border-bottom-color: #d4af37;
            }

            .cookies-actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                min-width: 300px;
            }

            .cookies-actions button {
                padding: 10px 16px;
                border: none;
                border-radius: 4px;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 11px;
                letter-spacing: 0.5px;
                cursor: pointer;
                transition: all 0.3s;
                flex: 1;
                min-width: 130px;
            }

            .btn-reject {
                background: rgba(212, 175, 55, 0.2);
                color: #d4af37;
                border: 2px solid #d4af37;
            }

            .btn-reject:hover {
                background: rgba(212, 175, 55, 0.3);
            }

            .btn-settings {
                background: rgba(212, 175, 55, 0.1);
                color: #d4af37;
                border: 2px solid #d4af37;
            }

            .btn-settings:hover {
                background: rgba(212, 175, 55, 0.2);
            }

            .btn-accept {
                background: linear-gradient(135deg, #dc143c 0%, #b01030 100%);
                color: #ffffff;
                border: 2px solid #d4af37;
                font-weight: 700;
            }

            .btn-accept:hover {
                transform: translateY(-2px);
            }

            @media (max-width: 768px) {
                .cookies-banner-container {
                    flex-direction: column;
                    gap: 15px;
                }

                .cookies-actions {
                    width: 100%;
                }

                .cookies-actions button {
                    flex: 1 1 48%;
                    min-width: unset;
                }
            }
        `;

        document.head.appendChild(style);
        document.body.appendChild(banner);
    }

    /**
     * Устанавливает необходимые cookies (всегда, без согласия)
     */
    setNecessaryCookies() {
        // Session ID для отслеживания безопасности
        this.setCookie('kontanta_session_id', this.generateSessionId(), 1);
        // Fingerprint браузера для защиты
        this.setCookie('kontanta_fingerprint', this.generateFingerprint(), 365);
    }

    /**
     * Принимает все cookies
     */
    acceptAll() {
        localStorage.setItem(this.consentKey, JSON.stringify({
            necessary: true,
            functional: true,
            analytics: true,
            marketing: true,
            timestamp: new Date().toISOString()
        }));

        document.getElementById('necessary-cookies').checked = true;
        document.getElementById('functional-cookies').checked = true;
        document.getElementById('analytics-cookies').checked = true;
        document.getElementById('marketing-cookies').checked = true;

        this.setAllCookies();
        this.hideBanner();
    }

    /**
     * Отклоняет все, кроме необходимых
     */
    rejectAll() {
        localStorage.setItem(this.consentKey, JSON.stringify({
            necessary: true,
            functional: false,
            analytics: false,
            marketing: false,
            timestamp: new Date().toISOString()
        }));

        document.getElementById('necessary-cookies').checked = true;
        document.getElementById('functional-cookies').checked = false;
        document.getElementById('analytics-cookies').checked = false;
        document.getElementById('marketing-cookies').checked = false;

        this.setNecessaryCookies();
        this.hideBanner();
    }

    /**
     * Показывает расширенные настройки
     */
    showSettings() {
        const consent = this.getConsent() || {};
        alert('Настройки Cookies:\n\n' +
            '✓ Необходимые: ВСЕГДА (для безопасности)\n' +
            '○ Функциональные: ' + (consent.functional ? 'ВКЛ' : 'ВЫКЛ') + '\n' +
            '○ Аналитические: ' + (consent.analytics ? 'ВКЛ' : 'ВЫКЛ') + '\n' +
            '○ Маркетинговые: ' + (consent.marketing ? 'ВКЛ' : 'ВЫКЛ') + '\n\n' +
            'Нажмите "Только необходимые" или "Принять все"');
    }

    /**
     * Устанавливает все cookies (если согласны)
     */
    setAllCookies() {
        this.setNecessaryCookies();
        
        const consent = this.getConsent();
        if (consent.functional) {
            this.setCookie('kontanta_preferences', JSON.stringify({
                language: 'ru',
                theme: 'dark'
            }), 365);
        }
        if (consent.analytics) {
            this.setCookie('kontanta_analytics', this.generateAnalyticsId(), 365);
        }
        if (consent.marketing) {
            this.setCookie('kontanta_marketing', this.generateMarketingId(), 365);
        }
    }

    /**
     * Скрывает баннер
     */
    hideBanner() {
        const banner = document.getElementById('cookies-banner');
        if (banner) {
            banner.style.animation = 'slideUp 0.3s ease-out reverse';
            setTimeout(() => banner.remove(), 300);
        }
    }

    /**
     * Получает согласие на cookies
     */
    getConsent() {
        try {
            const consent = localStorage.getItem(this.consentKey);
            return consent ? JSON.parse(consent) : null;
        } catch (e) {
            return null;
        }
    }

    /**
     * Установить cookie
     */
    setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = `${name}=${value}; expires=${expires.toUTCString()}; path=/; secure; samesite=strict`;
    }

    /**
     * Получить cookie
     */
    getCookie(name) {
        const nameEQ = name + '=';
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            cookie = cookie.trim();
            if (cookie.indexOf(nameEQ) === 0) {
                return cookie.substring(nameEQ.length);
            }
        }
        return null;
    }

    /**
     * Генерирует Session ID
     */
    generateSessionId() {
        return 'sess_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    }

    /**
     * Генерирует отпечаток браузера
     */
    generateFingerprint() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.textBaseline = 'alphabetic';
        ctx.fillStyle = '#f60';
        ctx.fillRect(125, 1, 62, 20);
        ctx.fillStyle = '#069';
        ctx.fillText('Browser Fingerprint', 2, 15);
        return canvas.toDataURL();
    }

    /**
     * Генерирует ID для аналитики
     */
    generateAnalyticsId() {
        return 'analytics_' + Math.random().toString(36).substr(2, 16) + '_' + Date.now();
    }

    /**
     * Генерирует ID для маркетинга
     */
    generateMarketingId() {
        return 'marketing_' + Math.random().toString(36).substr(2, 16) + '_' + Date.now();
    }

    /**
     * Получает данные для отправки на сервер
     */
    getCookieData() {
        const consent = this.getConsent() || {};
        return {
            sessionId: this.getCookie('kontanta_session_id'),
            fingerprint: this.getCookie('kontanta_fingerprint'),
            consent: {
                necessary: consent.necessary || true,
                functional: consent.functional || false,
                analytics: consent.analytics || false,
                marketing: consent.marketing || false
            },
            timestamp: new Date().toISOString()
        };
    }

    /**
     * Удаляет все cookies
     */
    clearAll() {
        localStorage.removeItem(this.consentKey);
        document.cookie = 'kontanta_session_id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        document.cookie = 'kontanta_fingerprint=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        document.cookie = 'kontanta_preferences=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        document.cookie = 'kontanta_analytics=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        document.cookie = 'kontanta_marketing=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    }
}

// Инициализируем при загрузке страницы
let cookiesManager;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        cookiesManager = new CookiesManager();
    });
} else {
    cookiesManager = new CookiesManager();
}
