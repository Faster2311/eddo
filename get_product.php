<?php
require_once 'config.php';
header('Content-Type: application/json');
if(isset($_GET['id'])) echo json_encode($conn->query("SELECT * FROM products WHERE id=".intval($_GET['id']))->fetch_assoc());
?>