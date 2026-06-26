// Загрузка корзины
function loadCart() {
    fetch('cart.php?action=get')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if(data.success) {
                var count = 0;
                if(data.items && data.items.length > 0) {
                    for(var i = 0; i < data.items.length; i++) {
                        count += data.items[i].quantity;
                    }
                }
                var cartCountElem = document.getElementById('cartCount');
                if(cartCountElem) cartCountElem.innerText = count;
            }
        })
        .catch(function(error) {
            console.error('Error loading cart:', error);
        });
}

// Добавление в корзину
function addToCart(productId) {
    fetch('cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=add&product_id=' + productId
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if(data.success) {
            loadCart();
            showToast('✅ Товар додано в кошик!');
        }
    })
    .catch(function(error) {
        console.error('Error adding to cart:', error);
        showToast('❌ Помилка при додаванні');
    });
}

// Показать корзину
function showCartModal() {
    fetch('cart.php?action=get')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if(data.success) {
                var itemsHtml = '';
                if(!data.items || data.items.length === 0) {
                    itemsHtml = '<p class="empty-cart">Кошик порожній</p>';
                } else {
                    for(var i = 0; i < data.items.length; i++) {
                        var item = data.items[i];
                        itemsHtml += '<div class="cart-item">' +
                            '<span>' + escapeHtml(item.name) + ' x' + item.quantity + '</span>' +
                            '<span>' + formatNumber(item.total) + ' ₴ ' +
                            '<button onclick="removeFromCart(' + item.id + ')" class="remove-item">✕</button></span>' +
                            '</div>';
                    }
                }
                var cartItemsElem = document.getElementById('cartItemsList');
                var cartTotalElem = document.getElementById('cartTotalPrice');
                if(cartItemsElem) cartItemsElem.innerHTML = itemsHtml;
                if(cartTotalElem) cartTotalElem.innerText = formatNumber(data.total || 0) + ' ₴';
                document.getElementById('cartModal').style.display = 'flex';
            }
        })
        .catch(function(error) {
            console.error('Error showing cart:', error);
        });
}

// Удаление из корзины
function removeFromCart(productId) {
    fetch('cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=remove&product_id=' + productId
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if(data.success) {
            loadCart();
            showCartModal();
        }
    })
    .catch(function(error) {
        console.error('Error removing from cart:', error);
    });
}

// Оформление заказа
function checkout() {
    fetch('cart.php?action=checkout', { method: 'POST' })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if(data.success) {
                showToast('✅ ' + (data.message || 'Замовлення оформлено!'));
                closeModal('cartModal');
                loadCart();
            } else {
                if(data.error === 'Авторизуйтесь') {
                    showToast('Будь ласка, увійдіть в акаунт');
                    closeModal('cartModal');
                    showAuthModal('login');
                } else {
                    showToast('❌ ' + (data.error || 'Помилка оформлення'));
                }
            }
        })
        .catch(function(error) {
            console.error('Error during checkout:', error);
            showToast('❌ Помилка при оформленні');
        });
}

// Показать модалку авторизации
function showAuthModal(type) {
    var container = document.getElementById('authFormContainer');
    if(!container) return;
    
    var html = '';
    if(type === 'login') {
        html = '<h3>Вхід</h3>' +
            '<input type="text" id="loginUsername" placeholder="Логін або Email" class="form-input">' +
            '<input type="password" id="loginPassword" placeholder="Пароль" class="form-input">' +
            '<button onclick="login()" class="btn-primary">Увійти</button>' +
            '<p style="margin-top:15px">Немає акаунту? <a href="#" onclick="showAuthModal(\'register\');return false;">Реєстрація</a></p>';
    } else {
        html = '<h3>Реєстрація</h3>' +
            '<input type="text" id="regLogin" placeholder="Логін" class="form-input">' +
            '<input type="email" id="regEmail" placeholder="Email" class="form-input">' +
            '<input type="password" id="regPassword" placeholder="Пароль (мін. 6 символів)" class="form-input">' +
            '<input type="text" id="regFullName" placeholder="ПІБ" class="form-input">' +
            '<input type="tel" id="regPhone" placeholder="Телефон" class="form-input">' +
            '<button onclick="register()" class="btn-primary">Зареєструватися</button>' +
            '<p style="margin-top:15px">Вже є акаунт? <a href="#" onclick="showAuthModal(\'login\');return false;">Вхід</a></p>';
    }
    container.innerHTML = html;
    document.getElementById('authModal').style.display = 'flex';
}

// Логин
function login() {
    var login = document.getElementById('loginUsername').value;
    var password = document.getElementById('loginPassword').value;
    
    if(!login || !password) {
        showToast('❌ Заповніть всі поля');
        return;
    }
    
    fetch('auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=login&login=' + encodeURIComponent(login) + '&password=' + encodeURIComponent(password)
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if(data.success) {
            showToast('✅ Вхід виконано успішно!');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showToast('❌ ' + (data.error || 'Помилка входу'));
        }
    })
    .catch(function(error) {
        console.error('Error during login:', error);
        showToast('❌ Помилка при вході');
    });
}

// Регистрация
function register() {
    var login = document.getElementById('regLogin').value;
    var email = document.getElementById('regEmail').value;
    var password = document.getElementById('regPassword').value;
    var full_name = document.getElementById('regFullName').value;
    var phone = document.getElementById('regPhone').value;
    
    if(!login || !email || !password) {
        showToast('❌ Заповніть обов\'язкові поля');
        return;
    }
    
    if(password.length < 6) {
        showToast('❌ Пароль повинен бути не менше 6 символів');
        return;
    }
    
    var formData = 'action=register&login=' + encodeURIComponent(login) + 
                   '&email=' + encodeURIComponent(email) + 
                   '&password=' + encodeURIComponent(password) + 
                   '&full_name=' + encodeURIComponent(full_name) + 
                   '&phone=' + encodeURIComponent(phone);
    
    fetch('auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if(data.success) {
            showToast('✅ Реєстрація успішна! Тепер увійдіть');
            closeModal('authModal');
            showAuthModal('login');
        } else {
            showToast('❌ ' + (data.error || 'Помилка реєстрації'));
        }
    })
    .catch(function(error) {
        console.error('Error during registration:', error);
        showToast('❌ Помилка при реєстрації');
    });
}

// Показать уведомление
function showToast(msg) {
    var toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = msg;
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 3000);
}

// Закрыть модалку
function closeModal(id) {
    var modal = document.getElementById(id);
    if(modal) modal.style.display = 'none';
}

// Вспомогательные функции
function escapeHtml(str) {
    if(!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    loadCart();
    
    // Добавление в корзину
    var addButtons = document.querySelectorAll('.add-to-cart');
    for(var i = 0; i < addButtons.length; i++) {
        addButtons[i].addEventListener('click', function(e) {
            var btn = e.currentTarget;
            var productId = btn.getAttribute('data-id');
            if(productId) addToCart(productId);
        });
    }
    
    // Кнопка корзины
    var cartBtn = document.getElementById('cartBtn');
    if(cartBtn) cartBtn.addEventListener('click', showCartModal);
    
    // Мобильное меню
    var mobileBtn = document.getElementById('mobileMenuBtn');
    var mainNav = document.getElementById('mainNav');
    if(mobileBtn && mainNav) {
        mobileBtn.addEventListener('click', function() {
            mainNav.classList.toggle('show');
        });
    }
    
    // Закрытие модалок по клику вне
    window.addEventListener('click', function(e) {
        if(e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
});