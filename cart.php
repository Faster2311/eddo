<?php
require_once 'config.php';
header('Content-Type: application/json');

if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if($action == 'add') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    if($product_id > 0) {
        if(isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]++;
        } else {
            $_SESSION['cart'][$product_id] = 1;
        }
        echo json_encode(array('success' => true));
    } else {
        echo json_encode(array('success' => false, 'error' => 'Invalid product ID'));
    }
}
elseif($action == 'remove') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    if($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        echo json_encode(array('success' => true));
    } else {
        echo json_encode(array('success' => false, 'error' => 'Product not in cart'));
    }
}
elseif($action == 'get') {
    $items = array();
    $total = 0;
    
    if(!empty($_SESSION['cart'])) {
        foreach($_SESSION['cart'] as $id => $qty) {
            $id = (int)$id;
            $result = $conn->query("SELECT id, name, price, image_url FROM products WHERE id = $id");
            if($result && $product = $result->fetch_assoc()) {
                $items[] = array(
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => (float)$product['price'],
                    'quantity' => (int)$qty,
                    'total' => (float)$product['price'] * (int)$qty,
                    'image' => $product['image_url']
                );
                $total += $product['price'] * $qty;
            }
        }
    }
    
    echo json_encode(array('success' => true, 'items' => $items, 'total' => $total));
}
elseif($action == 'checkout') {
    if(!isLoggedIn()) {
        echo json_encode(array('success' => false, 'error' => 'Авторизуйтесь'));
        exit;
    }
    
    if(empty($_SESSION['cart'])) {
        echo json_encode(array('success' => false, 'error' => 'Кошик порожній'));
        exit;
    }
    
    $total = 0;
    $items_data = array();
    
    foreach($_SESSION['cart'] as $id => $qty) {
        $id = (int)$id;
        $result = $conn->query("SELECT price, stock FROM products WHERE id = $id");
        if($result && $product = $result->fetch_assoc()) {
            if($product['stock'] < $qty) {
                echo json_encode(array('success' => false, 'error' => 'Недостатньо товару на складі'));
                exit;
            }
            $total += $product['price'] * $qty;
            $items_data[] = array('id' => $id, 'qty' => $qty, 'price' => $product['price']);
        }
    }
    
    $user_id = (int)$_SESSION['user_id'];
    $conn->query("INSERT INTO orders (user_id, total_amount) VALUES ($user_id, $total)");
    $order_id = $conn->insert_id;
    
    foreach($items_data as $item) {
        $conn->query("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES ($order_id, {$item['id']}, {$item['qty']}, {$item['price']})");
        $conn->query("UPDATE products SET stock = stock - {$item['qty']} WHERE id = {$item['id']}");
    }
    
    $_SESSION['cart'] = array();
    echo json_encode(array('success' => true, 'message' => 'Замовлення оформлено!'));
}
else {
    echo json_encode(array('success' => false, 'error' => 'Невідома дія'));
}
?>