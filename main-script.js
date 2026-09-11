// Вспомогательная функция для безопасного создания кнопки закрытия
function createCloseButton(modalId) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.style.cssText = 'position: absolute; top: 15px; right: 15px; background: none; border: none; color: var(--gold); font-size: 28px; cursor: pointer; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;';
    btn.textContent = '×';
    btn.addEventListener('click', () => {
        const modal = document.getElementById(modalId);
        if (modal) modal.remove();
    });
    return btn;
}

// МОДАЛЬНОЕ ОКНО КОНТАКТОВ
window.showContactsModal = function() {
    const modal = document.createElement('div');
    modal.id = 'contacts-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        padding: 20px;
    `;
    
    const content = document.createElement('div');
    content.style.cssText = `
        background: linear-gradient(135deg, rgba(13, 31, 23, 0.95) 0%, rgba(26, 71, 42, 0.95) 100%);
        border: 2px solid var(--gold);
        border-radius: 4px;
        padding: 40px;
        max-width: 500px;
        width: 100%;
        box-shadow: 0 12px 48px rgba(212, 175, 55, 0.3);
        position: relative;
    `;
    
    // Кнопка закрытия
    content.appendChild(createCloseButton('contacts-modal'));
    
    // Заголовок
    const title = document.createElement('h2');
    title.style.cssText = 'color: var(--gold); font-size: 24px; text-transform: uppercase; margin-bottom: 25px; letter-spacing: 2px; margin-top: 0;';
    title.textContent = 'Наши контакты';
    content.appendChild(title);
    
    // Телефон
    const phoneDiv = document.createElement('div');
    phoneDiv.style.cssText = 'margin-bottom: 20px;';
    const phoneLabel = document.createElement('p');
    phoneLabel.style.cssText = 'color: var(--gold); font-weight: 700; font-size: 12px; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; margin: 0 0 8px 0;';
    phoneLabel.textContent = '📞 Телефон';
    const phoneLink = document.createElement('a');
    phoneLink.href = 'tel:+74954152564';
    phoneLink.style.cssText = 'color: var(--red); font-weight: 900; font-size: 18px; text-decoration: none;';
    phoneLink.textContent = '+7 (495) 415-25-64';
    phoneDiv.appendChild(phoneLabel);
    phoneDiv.appendChild(phoneLink);
    content.appendChild(phoneDiv);
    
    // Email
    const emailDiv = document.createElement('div');
    emailDiv.style.cssText = 'margin-bottom: 20px;';
    const emailLabel = document.createElement('p');
    emailLabel.style.cssText = 'color: var(--gold); font-weight: 700; font-size: 12px; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; margin: 0 0 8px 0;';
    emailLabel.textContent = '📧 Email';
    const emailLink = document.createElement('a');
    emailLink.href = 'mailto:svo@mos.ru';
    emailLink.style.cssText = 'color: var(--red); font-weight: 900; font-size: 16px; text-decoration: none;';
    emailLink.textContent = 'svo@mos.ru';
    emailDiv.appendChild(emailLabel);
    emailDiv.appendChild(emailLink);
    content.appendChild(emailDiv);
    
    // Адрес
    const addressDiv = document.createElement('div');
    addressDiv.style.cssText = 'margin-bottom: 25px;';
    const addressLabel = document.createElement('p');
    addressLabel.style.cssText = 'color: var(--gold); font-weight: 700; font-size: 12px; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; margin: 0 0 8px 0;';
    addressLabel.textContent = '📍 Адрес';
    const addressText = document.createElement('p');
    addressText.style.cssText = 'color: var(--text-light); font-size: 14px; line-height: 1.6; margin: 0;';
    addressText.textContent = 'Западная ул., 16А/1, Восточный АО, Москва, м. Щёлковская';
    addressDiv.appendChild(addressLabel);
    addressDiv.appendChild(addressText);
    content.appendChild(addressDiv);
    
    // Режим работы
    const hoursDiv = document.createElement('div');
    hoursDiv.style.cssText = 'border-top: 1px dashed rgba(212, 175, 55, 0.3); padding-top: 15px;';
    const hoursLabel = document.createElement('p');
    hoursLabel.style.cssText = 'color: var(--gold); font-weight: 700; font-size: 12px; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; margin: 0 0 8px 0;';
    hoursLabel.textContent = '⏰ Режим работы';
    const hoursText = document.createElement('p');
    hoursText.style.cssText = 'color: var(--text-light); font-size: 13px; line-height: 1.6; margin: 0;';
    hoursText.textContent = 'Пн-Пт: 09:00 - 18:00, Сб-Вс: По согласованию';
    hoursDiv.appendChild(hoursLabel);
    hoursDiv.appendChild(hoursText);
    content.appendChild(hoursDiv);
    
    modal.appendChild(content);
    document.body.appendChild(modal);
    
    // Закрытие по клику на фон
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.remove();
    });
};

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    // ГЛАВНЫЙ ОБРАБОТЧИК ФОРМЫ
    const contactForm = document.getElementById('contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            console.log('Form submitted');
            
            const consentCheckbox = document.getElementById('consent');
            if (!consentCheckbox || !consentCheckbox.checked) {
                alert('❌ Пожалуйста, отметьте согласие на обработку персональных данных');
                return;
            }
            
            const formData = new FormData(this);
            
            let userData = {};
            if (typeof cookiesManager !== 'undefined' && cookiesManager) {
                try {
                    userData = cookiesManager.getCookieData() || {};
                } catch (err) {
                    console.error('Error getting cookie data:', err);
                }
            }
            
            const data = {
                name: formData.get('name'),
                phone: formData.get('phone'),
                email: formData.get('email') || null,
                age: formData.get('age') || null,
                message: formData.get('message') || null,
                userData: userData,
                gdpr_consent: true
            };

            console.log('Sending data:', data);

            try {
                const response = await fetch('/api/lead-email.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                console.log('Response status:', response.status);

                const result = await response.json();
                console.log('Response:', result);
                
                if (result.ok) {
                    alert('✓ Спасибо! Ваша заявка принята. Специалист КОНТАНТА свяжется с вами в течение 30 минут.');
                    contactForm.reset();
                    const charCount = document.getElementById('char-count');
                    if (charCount) charCount.textContent = '0/500';
                } else {
                    alert('❌ ' + (result.error || 'Ошибка при отправке. Позвоните: +7 (495) 415-25-64'));
                }
            } catch (error) {
                console.error('Ошибка формы:', error);
                alert('❌ Ошибка подключения. Позвоните: +7 (495) 415-25-64');
            }
        });
    } else {
        console.error('Contact form not found in DOM');
    }

    // Счетчик символов
    const messageField = document.getElementById('message');
    if (messageField) {
        messageField.addEventListener('input', function() {
            const charCount = document.getElementById('char-count');
            if (charCount) {
                charCount.textContent = this.value.length + '/500';
            }
        });
    }

    // Навигация
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('nav-menu');

    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('show');
        });

        document.querySelectorAll('#nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('show');
            });
        });
    }
});
