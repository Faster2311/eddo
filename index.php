<?php
require_once 'config.php';

// Обновляем роль пользователя в сессии
updateUserRole();

$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 1000000;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$where = "WHERE stock > 0";
if($category != 'all') {
    $where .= " AND category = '" . $conn->real_escape_string($category) . "'";
}
if($search != '') {
    $where .= " AND name LIKE '%$search%'";
}
$where .= " AND price BETWEEN $min_price AND $max_price";

switch($sort) {
    case 'price_asc': $order = "ORDER BY price ASC"; break;
    case 'price_desc': $order = "ORDER BY price DESC"; break;
    case 'rating': $order = "ORDER BY rating DESC"; break;
    case 'name_asc': $order = "ORDER BY name ASC"; break;
    default: $order = "ORDER BY created_at DESC";
}

$products_result = $conn->query("SELECT * FROM products $where $order");
$categories = $conn->query("SELECT category, COUNT(*) as count FROM products WHERE stock > 0 GROUP BY category");
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo SITE_NAME; ?> - Магазин електроніки</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo"><i class="fas fa-bolt"></i> <?php echo SITE_NAME; ?></div>
            <button class="mobile-menu-btn" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
            <nav class="nav" id="mainNav">
                <a href="index.php" class="active">Головна</a>
                <?php if(isLoggedIn()): ?>
                    <a href="profile.php">Особистий кабінет</a>
                <?php endif; ?>
                <?php if(isAdmin()): ?>
                    <a href="admin.php" class="admin-link"><i class="fas fa-shield-alt"></i> Адмін панель</a>
                <?php endif; ?>
            </nav>
            <div class="header-actions">
                <button class="cart-btn" id="cartBtn">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </button>
                <?php if(isLoggedIn()): 
                    $current_user = getCurrentUser();
                ?>
                    <div class="user-info">
                        <img src="<?php echo !empty($current_user['avatar']) ? htmlspecialchars($current_user['avatar']) : 'https://ui-avatars.com/api/?background=3b82f6&color=fff&name=' . urlencode($_SESSION['user_login']); ?>" class="user-avatar-mini">
                        <span><?php echo htmlspecialchars($_SESSION['user_login']); ?></span>
                        <?php if(isAdmin()): ?>
                            <span class="admin-badge"><i class="fas fa-crown"></i> ADMIN</span>
                        <?php endif; ?>
                        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <button class="btn-outline" onclick="showAuthModal('login')">Вхід</button>
                        <button class="btn-primary" onclick="showAuthModal('register')">Реєстрація</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Техніка майбутнього <span class="glow">вже тут</span></h1>
                <p>Смартфони, планшети, ноутбуки — найкращі ціни в Україні</p>
                <button class="btn-large" onclick="document.getElementById('catalog').scrollIntoView({behavior:'smooth'})">В магазин <i class="fas fa-arrow-right"></i></button>
            </div>
            <div class="hero-stats">
                <div class="stat"><span>5000+</span> товарів</div>
                <div class="stat"><span>98%</span> задоволених</div>
                <div class="stat"><span>24/7</span> підтримка</div>
            </div>
        </div>
    </section>

    <section class="catalog" id="catalog">
        <div class="container">
            <h2 class="section-title">🔥 Хіти продажів</h2>
            
            <div class="filter-section">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Пошук товарів..." value="<?php echo htmlspecialchars($search); ?>">
                    <button onclick="applyFilters()"><i class="fas fa-search"></i></button>
                </div>
                <div class="filter-row">
                    <div class="price-filter">
                        <input type="number" id="minPrice" placeholder="від" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                        <span>-</span>
                        <input type="number" id="maxPrice" placeholder="до" value="<?php echo $max_price < 1000000 ? $max_price : ''; ?>">
                        <button onclick="applyFilters()" class="price-btn">OK</button>
                    </div>
                    <select id="sortSelect">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Новинки</option>
                        <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Ціна (спочатку дешевші)</option>
                        <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Ціна (спочатку дорожчі)</option>
                        <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>За рейтингом</option>
                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>За назвою (А-Я)</option>
                    </select>
                    <button class="reset-filters" onclick="resetFilters()"><i class="fas fa-redo"></i> Скинути</button>
                </div>
                <div class="category-tabs">
                    <button class="cat-btn <?php echo $category == 'all' ? 'active' : ''; ?>" data-cat="all">Всі</button>
                    <?php if($categories && $categories->num_rows > 0): ?>
                        <?php while($cat = $categories->fetch_assoc()): ?>
                            <button class="cat-btn <?php echo $category == $cat['category'] ? 'active' : ''; ?>" data-cat="<?php echo $cat['category']; ?>">
                                <?php 
                                $icons = ['phones' => '📱', 'tablets' => '📟', 'laptops' => '💻'];
                                $names = ['phones' => 'Телефони', 'tablets' => 'Планшети', 'laptops' => 'Ноутбуки'];
                                echo ($icons[$cat['category']] ?? '📦') . ' ' . ($names[$cat['category']] ?? $cat['category']) . ' (' . $cat['count'] . ')';
                                ?>
                            </button>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="products-grid" id="productsGrid">
                <?php if($products_result && $products_result->num_rows > 0): ?>
                    <?php while($product = $products_result->fetch_assoc()): ?>
                        <div class="product-card">
                            <?php if($product['old_price'] && $product['old_price'] > $product['price']): ?>
                                <div class="sale-badge">-<?php echo round((1 - $product['price']/$product['old_price'])*100); ?>%</div>
                            <?php endif; ?>
                            <div class="product-badge">В наявності</div>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="product-img" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='https://via.placeholder.com/200?text=No+Image'">
                            <div class="product-info">
                                <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= round($product['rating']) ? 'filled' : ''; ?>"></i>
                                    <?php endfor; ?>
                                    <span><?php echo number_format($product['rating'], 1); ?></span>
                                </div>
                                <div class="product-price">
                                    <?php if($product['old_price'] && $product['old_price'] > $product['price']): ?>
                                        <span class="old-price"><?php echo number_format($product['old_price'], 0, '', ' '); ?> ₴</span>
                                    <?php endif; ?>
                                    <span class="current-price"><?php echo number_format($product['price'], 0, '', ' '); ?> <?php echo CURRENCY; ?></span>
                                </div>
                                <button class="add-to-cart" data-id="<?php echo $product['id']; ?>">В кошик <i class="fas fa-cart-plus"></i></button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fas fa-search" style="font-size: 48px; color: #cbd5e1; margin-bottom: 20px;"></i>
                        <p>Товарів не знайдено</p>
                        <button onclick="resetFilters()" class="btn-primary" style="margin-top: 20px;">Скинути фільтри</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><?php echo SITE_NAME; ?></h4>
                    <p>Найкращий магазин електроніки в Україні</p>
                </div>
                <div class="footer-section">
                    <h4>Контакти</h4>
                    <p><i class="fas fa-phone"></i> +380 (44) 123-45-67</p>
                    <p><i class="fas fa-envelope"></i> info@electroshop.ua</p>
                </div>
                <div class="footer-section">
                    <h4>Ми в соцмережах</h4>
                    <div class="socials">
                        <i class="fab fa-telegram"></i>
                        <i class="fab fa-whatsapp"></i>
                        <i class="fab fa-instagram"></i>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 <?php echo SITE_NAME; ?> — Всі права захищено</p>
            </div>
        </div>
    </footer>

    <div id="cartModal" class="modal">
        <div class="modal-content cart-modal">
            <span class="close-modal" onclick="closeModal('cartModal')">&times;</span>
            <h2><i class="fas fa-shopping-bag"></i> Мій кошик</h2>
            <div id="cartItemsList" class="cart-items"></div>
            <div class="cart-total">
                <span>Разом:</span>
                <strong id="cartTotalPrice">0 ₴</strong>
            </div>
            <button class="btn-checkout" onclick="checkout()">Оформити замовлення</button>
        </div>
    </div>

    <div id="authModal" class="modal">
        <div class="modal-content auth-modal">
            <span class="close-modal" onclick="closeModal('authModal')">&times;</span>
            <div id="authFormContainer"></div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        function applyFilters() {
            var search = document.getElementById('searchInput').value;
            var minPrice = document.getElementById('minPrice').value;
            var maxPrice = document.getElementById('maxPrice').value;
            var sort = document.getElementById('sortSelect').value;
            var category = document.querySelector('.cat-btn.active') ? document.querySelector('.cat-btn.active').getAttribute('data-cat') : 'all';
            var url = 'index.php?category=' + category + '&sort=' + sort;
            if(minPrice && minPrice > 0) url += '&min_price=' + minPrice;
            if(maxPrice && maxPrice > 0) url += '&max_price=' + maxPrice;
            if(search) url += '&search=' + encodeURIComponent(search);
            window.location.href = url;
        }
        
        function resetFilters() {
            window.location.href = 'index.php';
        }
        
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.cat-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.cat-btn').forEach(function(b) {
                        b.classList.remove('active');
                    });
                    btn.classList.add('active');
                    applyFilters();
                });
            });
            
            var sortSelect = document.getElementById('sortSelect');
            if(sortSelect) {
                sortSelect.addEventListener('change', applyFilters);
            }
        });
    </script>
</body>
</html>