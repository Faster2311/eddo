<?php
require_once 'config.php';
header('Content-Type: application/json');

if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $order_result = $conn->query("SELECT o.*, u.login FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $id");
    
    if($order_result && $order_result->num_rows > 0) {
        $order = $order_result->fetch_assoc();
        
        $items_result = $conn->query("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $id");
        
        $items = array();
        if($items_result) {
            while($item = $items_result->fetch_assoc()) {
                $items[] = array(
                    'name' => $item['name'],
                    'quantity' => (int)$item['quantity'],
                    'price' => (float)$item['price']
                );
            }
        }
        
        echo json_encode(array(
            'id' => $order['id'],
            'user' => $order['login'],
            'total' => (float)$order['total_amount'],
            'status' => $order['status'],
            'date' => date('d.m.Y H:i', strtotime($order['order_date'])),
            'items' => $items
        ));
    } else {
        echo json_encode(array('error' => 'Замовлення не знайдено'));
    }
} else {
    echo json_encode(array('error' => 'ID не вказано'));
}
?>