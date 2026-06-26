<?php
require_once 'config.php';
if(!isAdmin()) {
    header('Location: index.php');
    exit;
}

// Обработка действий
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_product'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $price = (float)$_POST['price'];
        $category = $conn->real_escape_string($_POST['category']);
        $image_url = $conn->real_escape_string($_POST['image_url']);
        $desc = $conn->real_escape_string($_POST['description']);
        $stock = (int)$_POST['stock'];
        $conn->query("INSERT INTO products (name, price, category, image_url, description, stock) VALUES ('$name', $price, '$category', '$image_url', '$desc', $stock)");
        echo "<script>alert('Товар додано!');window.location.href='admin.php';</script>";
    }
    
    if(isset($_POST['delete_product'])) {
        $id = (int)$_POST['product_id'];
        $conn->query("DELETE FROM products WHERE id=$id");
        echo "<script>alert('Товар видалено!');window.location.href='admin.php';</script>";
    }
    
    if(isset($_POST['update_order_status'])) {
        $order_id = (int)$_POST['order_id'];
        $status = $conn->real_escape_string($_POST['status']);
        $conn->query("UPDATE orders SET status='$status' WHERE id=$order_id");
        echo "<script>alert('Статус оновлено!');window.location.href='admin.php';</script>";
    }
    
    if(isset($_POST['delete_user'])) {
        $id = (int)$_POST['user_id'];
        $conn->query("DELETE FROM users WHERE id=$id AND role='user'");
        echo "<script>alert('Користувача видалено!');window.location.href='admin.php';</script>";
    }
}

$products = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
$orders = $conn->query("SELECT o.*, u.login FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC");
$users = $conn->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC");
$stats = array(
    'products' => $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'],
    'orders' => $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'],
    'users' => $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'],
    'revenue' => ($conn->query("SELECT SUM(total_amount) as t FROM orders WHERE status='доставлено'")->fetch_assoc()['t'] ?? 0),
    'low_stock' => $conn->query("SELECT COUNT(*) as c FROM products WHERE stock < 5")->fetch_assoc()['c']
);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Адмін панель - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 16px; text-align: center; }
        .stat-card i { font-size: 2rem; }
        .stat-card h3 { font-size: 1.8rem; margin: 10px 0; }
        .admin-tabs { display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap; }
        .tab-btn { padding: 12px 24px; border: none; border-radius: 40px; cursor: pointer; font-weight: 600; background: #e2e8f0; transition: 0.2s; }
        .tab-btn.active { background: #3b82f6; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .admin-card { background: white; border-radius: 24px; padding: 24px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th, .admin-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .admin-table th { background: #f8fafc; font-weight: 600; }
        .table-responsive { overflow-x: auto; }
        .btn-primary { background: #3b82f6; color: white; padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; }
        .btn-danger { background: #ef4444; color: white; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; }
        .btn-warning { background: #f59e0b; color: white; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 30px; border-radius: 16px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo"><i class="fas fa-shield-alt"></i> Адмін панель</div>
            <nav><a href="index.php" target="_blank"><i class="fas fa-store"></i> Магазин</a> <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Вихід</a></nav>
        </div>
    </header>

    <main class="admin-main">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card"><i class="fas fa-boxes"></i><h3><?php echo $stats['products']; ?></h3><p>Товарів</p></div>
                <div class="stat-card"><i class="fas fa-shopping-cart"></i><h3><?php echo $stats['orders']; ?></h3><p>Замовлень</p></div>
                <div class="stat-card"><i class="fas fa-users"></i><h3><?php echo $stats['users']; ?></h3><p>Користувачів</p></div>
                <div class="stat-card"><i class="fas fa-money-bill"></i><h3><?php echo number_format($stats['revenue'], 0, '', ' '); ?>₴</h3><p>Дохід</p></div>
                <div class="stat-card"><i class="fas fa-exclamation-triangle"></i><h3><?php echo $stats['low_stock']; ?></h3><p>Товарів <5 шт</p></div>
            </div>

            <div class="admin-tabs">
                <button class="tab-btn active" data-tab="products">📦 Товари</button>
                <button class="tab-btn" data-tab="orders">📋 Замовлення</button>
                <button class="tab-btn" data-tab="users">👥 Користувачі</button>
            </div>

            <div id="productsTab" class="tab-content active">
                <div class="admin-card">
                    <div class="card-header">
                        <h3>Управління товарами</h3>
                        <button class="btn-primary" onclick="openProductModal()">+ Додати товар</button>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead><tr><th>ID</th><th>Назва</th><th>Ціна</th><th>Наявність</th><th>Дії</th></tr></thead>
                            <tbody>
                                <?php while($p = $products->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $p['id']; ?></td>
                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><?php echo number_format($p['price'], 0, '', ' '); ?>₴</td>
                                    <td><?php echo $p['stock']; ?> шт</td>
                                    <td>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Видалити товар?')">
                                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" name="delete_product" class="btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="ordersTab" class="tab-content">
                <div class="admin-card">
                    <h3>Замовлення</h3>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead><tr><th>ID</th><th>Користувач</th><th>Сума</th><th>Статус</th><th>Дія</th></tr></thead>
                            <tbody>
                                <?php while($o = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $o['id']; ?></td>
                                    <td><?php echo htmlspecialchars($o['login']); ?></td>
                                    <td><?php echo number_format($o['total_amount'], 0, '', ' '); ?>₴</td>
                                    <td>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                            <select name="status" onchange="this.form.submit()">
                                                <?php $statuses = array('новий', 'обробляється', 'відправлено', 'доставлено', 'скасовано'); ?>
                                                <?php foreach($statuses as $s): ?>
                                                    <option <?php echo $o['status'] == $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="submit" name="update_order_status" value="Оновити" style="display:none">
                                        </form>
                                    </td>
                                    <td>
                                        <button class="btn-warning" onclick="viewOrder(<?php echo $o['id']; ?>)"><i class="fas fa-eye"></i></button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="usersTab" class="tab-content">
                <div class="admin-card">
                    <h3>Користувачі</h3>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead><tr><th>ID</th><th>Логін</th><th>Email</th><th>ПІБ</th><th>Телефон</th><th>Дія</th></tr></thead>
                            <tbody>
                                <?php while($u = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td><?php echo htmlspecialchars($u['login']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo htmlspecialchars($u['full_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($u['phone'] ?? '-'); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Видалити користувача?')">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <h3>Додати товар</h3>
            <form method="POST">
                <div class="form-group"><label>Назва</label><input type="text" name="name" required></div>
                <div class="form-group"><label>Ціна (₴)</label><input type="number" name="price" required></div>
                <div class="form-group">
                    <label>Категорія</label>
                    <select name="category">
                        <option value="phones">Телефони</option>
                        <option value="tablets">Планшети</option>
                        <option value="laptops">Ноутбуки</option>
                    </select>
                </div>
                <div class="form-group"><label>URL зображення</label><input type="text" name="image_url" value="https://via.placeholder.com/200"></div>
                <div class="form-group"><label>Опис</label><textarea name="description" rows="3"></textarea></div>
                                <div class="form-group"><label>Кількість</label><input type="number" name="stock" value="10"></div>
                <button type="submit" name="add_product" class="btn-primary">Додати</button>
                <button type="button" onclick="closeProductModal()" class="btn-danger">Скасувати</button>
            </form>
        </div>
    </div>

    <script>
        function openProductModal() {
            document.getElementById('productModal').style.display = 'flex';
        }
        
        function closeProductModal() {
            document.getElementById('productModal').style.display = 'none';
        }
        
        function viewOrder(orderId) {
            fetch('get_order.php?id=' + orderId)
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if(data && data.id) {
                        var itemsList = '';
                        if(data.items && data.items.length > 0) {
                            for(var i = 0; i < data.items.length; i++) {
                                itemsList += data.items[i].name + ' x ' + data.items[i].quantity + ' = ' + 
                                    formatNumber(data.items[i].price * data.items[i].quantity) + ' ₴\n';
                            }
                        }
                        alert('Замовлення #' + data.id + '\n' +
                              'Користувач: ' + data.user + '\n' +
                              'Сума: ' + formatNumber(data.total) + ' ₴\n' +
                              'Статус: ' + data.status + '\n' +
                              'Дата: ' + data.date + '\n\n' +
                              'Товари:\n' + itemsList);
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    alert('Помилка завантаження деталей замовлення');
                });
        }
        
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        }
        
        // Табы
        var tabBtns = document.querySelectorAll('.tab-btn');
        for(var i = 0; i < tabBtns.length; i++) {
            tabBtns[i].addEventListener('click', function() {
                var tabName = this.getAttribute('data-tab');
                var allTabs = document.querySelectorAll('.tab-content');
                for(var j = 0; j < allTabs.length; j++) {
                    allTabs[j].classList.remove('active');
                }
                var allBtns = document.querySelectorAll('.tab-btn');
                for(var j = 0; j < allBtns.length; j++) {
                    allBtns[j].classList.remove('active');
                }
                document.getElementById(tabName + 'Tab').classList.add('active');
                this.classList.add('active');
            });
        }
        
        // Закрытие модалки по клику вне
        window.onclick = function(e) {
            var modal = document.getElementById('productModal');
            if(e.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>