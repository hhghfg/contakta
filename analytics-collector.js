/**
 * КОНТАНТА - Сборщик аналитики клиентов
 * Собирает максимум информации: cookies, браузер, fingerprint и т.д.
 * 
 * Используется на главной странице и в формах
 */

class ClientAnalytics {
    constructor() {
        this.data = {};
        this.initCollector();
    }

    // Инициализируем сборщик
    initCollector() {
        window.addEventListener('load', () => {
            setTimeout(() => this.collectAllData(), 1000);
        });

        // Отправляем данные при выходе со страницы
        window.addEventListener('beforeunload', () => {
            this.sendAnalytics();
        });

        // Отправляем каждые 30 секунд
        setInterval(() => {
            this.sendAnalytics();
        }, 30000);
    }

    // ============ ОСНОВНОЙ СБОРЩИК ДАННЫХ ============

    async collectAllData() {
        this.data = {
            // Браузер и ОС
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            languages: navigator.languages,
            
            // Экран и разрешение
            screenWidth: window.screen.width,
            screenHeight: window.screen.height,
            screenColorDepth: window.screen.colorDepth,
            screenPixelDepth: window.screen.pixelDepth,
            screenDevicePixelRatio: window.devicePixelRatio,
            
            // Timezone и время
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            timezoneOffset: new Date().getTimezoneOffset(),
            
            // Cookies
            cookies: this.getCookies(),
            
            // Браузер fingerprint
            webgl: await this.getWebGLInfo(),
            canvas: this.getCanvasFingerprint(),
            fonts: this.detectFonts(),
            plugins: this.getPlugins(),
            
            // Браузерные возможности
            doNotTrack: navigator.doNotTrack,
            localStorageEnabled: this.checkLocalStorage(),
            sessionStorageEnabled: this.checkSessionStorage(),
            indexedDBEnabled: this.checkIndexedDB(),
            notificationsEnabled: this.checkNotifications(),
            geolocationEnabled: this.checkGeolocation(),
            
            // Производительность
            connectionSpeed: this.getConnectionSpeed(),
            connectionType: this.getConnectionType(),
            deviceMemory: navigator.deviceMemory,
            
            // Поведение на сайте
            pagesVisited: this.getPagesVisited(),
            timeOnSite: Math.floor((Date.now() - window.pageLoadTime) / 1000),
            clickCount: window.clickCount || 0,
            scrollDepth: this.getScrollDepth(),
            formInteractions: window.formInteractions || [],
            
            // Геолокация (если есть разрешение)
            latitude: window.clientLatitude || null,
            longitude: window.clientLongitude || null,
            accuracy: window.clientAccuracy || null,
            country: window.clientCountry || '',
            city: window.clientCity || '',
            region: window.clientRegion || '',
            
            // Обнаружение утечек и защиты
            webrtcLeak: await this.detectWebRTCLeak(),
            dnsLeak: false, // требует сервера
        };

        return this.data;
    }

    // ============ COOKIES ============

    getCookies() {
        const cookies = {};
        const cookieString = document.cookie;
        
        if (cookieString) {
            cookieString.split(';').forEach(cookie => {
                const [name, value] = cookie.trim().split('=');
                if (name && !this.isSensitiveCookie(name)) {
                    cookies[name] = decodeURIComponent(value || '');
                }
            });
        }
        
        return cookies;
    }

    isSensitiveCookie(name) {
        const sensitive = ['password', 'token', 'session', 'auth', 'secret', 'api_key'];
        return sensitive.some(s => name.toLowerCase().includes(s));
    }

    // ============ WEBGL FINGERPRINT ============

    async getWebGLInfo() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (!gl) return null;
            
            const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
            
            return {
                vendor: debugInfo ? gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) : 'unknown',
                renderer: debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : 'unknown',
                version: gl.getParameter(gl.VERSION),
                shadingLanguageVersion: gl.getParameter(gl.SHADING_LANGUAGE_VERSION),
            };
        } catch (e) {
            return null;
        }
    }

    // ============ CANVAS FINGERPRINT ============

    getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            canvas.width = 280;
            canvas.height = 60;
            
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.textBaseline = 'alphabetic';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText('КОНТАНТА Canvas', 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('КОНТАНТА Canvas', 4, 17);
            
            return canvas.toDataURL().substring(0, 100); // Только начало
        } catch (e) {
            return null;
        }
    }

    // ============ ШРИФТЫ ============

    detectFonts() {
        const baseFonts = ['monospace', 'sans-serif', 'serif'];
        const testFonts = [
            'Arial', 'Verdana', 'Times New Roman', 'Courier New',
            'Georgia', 'Palatino', 'Garamond', 'Bookman', 'Comic Sans MS',
            'Trebuchet MS', 'Impact', 'Lucida Sans', 'Tahoma', 'Lucida Console',
            'MS Sans Serif', 'MS Serif', 'Consolas', 'Segoe UI'
        ];
        
        const detectedFonts = [];
        
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        testFonts.forEach(font => {
            try {
                ctx.font = `14px "${font}"`;
                const testText = 'mmmmmmmmmmlli';
                const baseWidth = ctx.measureText(testText).width;
                
                for (let i = 0; i < baseFonts.length; i++) {
                    ctx.font = `14px "${font}", ${baseFonts[i]}`;
                    const width = ctx.measureText(testText).width;
                    
                    if (width !== baseWidth) {
                        detectedFonts.push(font);
                        break;
                    }
                }
            } catch (e) {
                // Игнорируем ошибки
            }
        });
        
        return detectedFonts;
    }

    // ============ ПЛАГИНЫ ============

    getPlugins() {
        const plugins = [];
        
        for (let i = 0; i < navigator.plugins.length; i++) {
            const plugin = navigator.plugins[i];
            plugins.push({
                name: plugin.name,
                version: plugin.version,
                description: plugin.description
            });
        }
        
        return plugins;
    }

    // ============ БРАУЗЕРНЫЕ ВОЗМОЖНОСТИ ============

    checkLocalStorage() {
        try {
            const test = '__localStorage_test__';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);
            return true;
        } catch (e) {
            return false;
        }
    }

    checkSessionStorage() {
        try {
            const test = '__sessionStorage_test__';
            sessionStorage.setItem(test, test);
            sessionStorage.removeItem(test);
            return true;
        } catch (e) {
            return false;
        }
    }

    checkIndexedDB() {
        try {
            return !!window.indexedDB;
        } catch (e) {
            return false;
        }
    }

    checkNotifications() {
        try {
            return !!window.Notification;
        } catch (e) {
            return false;
        }
    }

    checkGeolocation() {
        try {
            return !!navigator.geolocation;
        } catch (e) {
            return false;
        }
    }

    // ============ СЕТЕВЫЕ ДАННЫЕ ============

    getConnectionSpeed() {
        const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        return conn ? conn.effectiveType : 'unknown';
    }

    getConnectionType() {
        const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        return conn ? conn.type : 'unknown';
    }

    // ============ ПОВЕДЕНИЕ НА САЙТЕ ============

    getPagesVisited() {
        return window.pagesVisited || [window.location.href];
    }

    getScrollDepth() {
        const scrolled = window.scrollY || document.documentElement.scrollTop;
        const docHeight = document.documentElement.scrollHeight;
        const windowHeight = window.innerHeight;
        
        return Math.round((scrolled + windowHeight) / docHeight * 100);
    }

    // ============ WEBRTC LEAK DETECTION ============

    async detectWebRTCLeak() {
        return new Promise((resolve) => {
            const pc = new (window.RTCPeerConnection || window.webkitRTCPeerConnection)({
                iceServers: []
            });
            
            let hasLeak = false;
            
            const candidates = [];
            
            pc.onicecandidate = (e) => {
                if (e.candidate) {
                    candidates.push(e.candidate.candidate);
                    if (e.candidate.candidate.includes('srflx') || 
                        e.candidate.candidate.includes('prflx') ||
                        e.candidate.candidate.includes('relay')) {
                        hasLeak = true;
                    }
                }
            };
            
            try {
                pc.createDataChannel('test');
                pc.createOffer().then(offer => {
                    pc.setLocalDescription(offer).catch(() => {
                        resolve(hasLeak);
                        pc.close();
                    });
                }).catch(() => {
                    resolve(hasLeak);
                    pc.close();
                });
            } catch (e) {
                resolve(false);
            }
            
            setTimeout(() => {
                resolve(hasLeak);
                pc.close();
            }, 3000);
        });
    }

    // ============ ОТПРАВКА ДАННЫХ ============

    async sendAnalytics() {
        if (!window.leadId) return;

        try {
            const response = await fetch('/api/collect-analytics.php?action=collect', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    lead_id: window.leadId,
                    ...this.data
                })
            });

            const result = await response.json();
            
            if (result.статус === 'успешно') {
                window.clientFingerprint = result.client_id;
                console.log('[Analytics] Данные отправлены');
            }
        } catch (e) {
            console.error('[Analytics Error]', e);
        }
    }
}

// ============ ИНИЦИАЛИЗАЦИЯ ============

// Отслеживаем загрузку страницы
window.pageLoadTime = Date.now();
window.clickCount = 0;
window.pagesVisited = [window.location.href];
window.formInteractions = [];

// Считаем клики
document.addEventListener('click', () => {
    window.clickCount++;
});

// Отслеживаем смену страниц
window.addEventListener('popstate', () => {
    window.pagesVisited.push(window.location.href);
});

// Отслеживаем отправку форм
document.addEventListener('submit', (e) => {
    if (e.target.name) {
        window.formInteractions.push({
            form: e.target.name,
            timestamp: new Date().toISOString()
        });
    }
});

// Запускаем сборщик аналитики
const clientAnalytics = new ClientAnalytics();

// Экспортируем для использования
window.ClientAnalytics = ClientAnalytics;
window.clientAnalytics = clientAnalytics;
