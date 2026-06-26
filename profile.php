<?php
require_once 'config.php';
if(!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user = getCurrentUser();
$orders = $conn->query("SELECT * FROM orders WHERE user_id = " . (int)$_SESSION['user_id'] . " ORDER BY order_date DESC");
$order_count = $orders ? $orders->num_rows : 0;

$total_result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE user_id = " . (int)$_SESSION['user_id'] . " AND status='доставлено'");
$total_spent = $total_result ? ($total_result->fetch_assoc()['total'] ?? 0) : 0;

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = $conn->real_escape_string($_POST['full_name'] ?? '');
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    $avatar = $conn->real_escape_string($_POST['avatar'] ?? '');
    $conn->query("UPDATE users SET full_name='$full_name', phone='$phone', avatar='$avatar' WHERE id=" . (int)$_SESSION['user_id']);
    echo "<script>alert('Профіль оновлено!');window.location.href='profile.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Особистий кабінет - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo"><i class="fas fa-bolt"></i> <?php echo SITE_NAME; ?></div>
            <nav class="nav">
                <a href="index.php">Головна</a>
                <a href="profile.php" class="active">Кабінет</a>
                <?php if(isAdmin()): ?><a href="admin.php">Адмін панель</a><?php endif; ?>
            </nav>
            <div class="header-actions">
                <div class="user-info">
                    <span><?php echo htmlspecialchars($_SESSION['user_login']); ?></span>
                    <a href="logout.php" class="btn-logout">Вихід</a>
                </div>
            </div>
        </div>
    </header>

    <main class="profile-main">
        <div class="container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'https://ui-avatars.com/api/?background=3b82f6&color=fff&name=' . urlencode($user['login']); ?>" id="avatarPreview">
                    <div class="avatar-stats">
                        <div class="stat-item"><i class="fas fa-shopping-bag"></i><span><?php echo $order_count; ?></span><small>Замовлень</small></div>
                        <div class="stat-item"><i class="fas fa-money-bill"></i><span><?php echo number_format($total_spent, 0, '', ' '); ?>₴</span><small>Витрачено</small></div>
                        <div class="stat-item"><i class="fas fa-calendar"></i><span><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></span><small>З нами</small></div>
                    </div>
                </div>
                <div class="profile-title">
                    <h1><?php echo htmlspecialchars(!empty($user['full_name']) ? $user['full_name'] : $user['login']); ?></h1>
                    <p class="profile-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="profile-role"><i class="fas fa-user-tag"></i> <?php echo $user['role'] == 'admin' ? 'Адміністратор' : 'Користувач'; ?></p>
                </div>
            </div>

            <div class="profile-tabs">
                <button class="profile-tab-btn active" data-tab="info">Особисті дані</button>
                <button class="profile-tab-btn" data-tab="orders">Мої замовлення</button>
            </div>

            <div id="infoTab" class="profile-tab-content active">
                <div class="profile-card">
                    <h3><i class="fas fa-user-edit"></i> Редагувати профіль</h3>
                    <form method="POST" class="profile-form">
                        <div class="form-group">
                            <label>ПІБ</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Телефон</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>URL аватара</label>
                            <input type="text" name="avatar" id="avatarInput" value="<?php echo htmlspecialchars($user['avatar'] ?? ''); ?>" onchange="document.getElementById('avatarPreview').src=this.value || 'https://ui-avatars.com/api/?background=3b82f6&color=fff&name=<?php echo urlencode($user['login']); ?>'">
                        </div>
                        <button type="submit" name="update_profile" class="btn-primary">Зберегти зміни</button>
                    </form>
                </div>
            </div>

            <div id="ordersTab" class="profile-tab-content">
                <div class="profile-card">
                    <h3><i class="fas fa-history"></i> Історія замовлень</h3>
                    <?php if($orders && $orders->num_rows > 0): ?>
                        <?php while($order = $orders->fetch_assoc()): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <span class="order-number">Замовлення #<?php echo $order['id']; ?></span>
                                    <span class="order-status status-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span>
                                </div>
                                <div class="order-body">
                                    <p><i class="fas fa-calendar"></i> <?php echo date('d.m.Y H:i', strtotime($order['order_date'])); ?></p>
                                    <p><i class="fas fa-money-bill"></i> Сума: <?php echo number_format($order['total_amount'], 0, '', ' '); ?> ₴</p>
                                    <?php
                                    $items = $conn->query("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = " . (int)$order['id']);
                                    if($items && $items->num_rows > 0):
                                    ?>
                                        <div class="order-items">
                                            <strong>Товари:</strong>
                                            <?php while($item = $items->fetch_assoc()): ?>
                                                <div class="order-item">• <?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?> = <?php echo number_format($item['price'] * $item['quantity'], 0, '', ' '); ?> ₴</div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-orders">
                            <i class="fas fa-shopping-cart" style="font-size: 48px; color: #cbd5e1; margin-bottom: 20px;"></i>
                            <p>У вас ще немає замовлень</p>
                            <a href="index.php" class="btn-primary" style="margin-top: 20px;">Перейти до покупок</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p>© 2025 <?php echo SITE_NAME; ?> — Всі права захищено</p>
        </div>
    </footer>
    
    <script>
        document.querySelectorAll('.profile-tab-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.profile-tab-btn').forEach(function(b) { b.classList.remove('active'); });
                document.querySelectorAll('.profile-tab-content').forEach(function(c) { c.classList.remove('active'); });
                btn.classList.add('active');
                document.getElementById(btn.getAttribute('data-tab') + 'Tab').classList.add('active');
            });
        });
    </script>
</body>
</html>