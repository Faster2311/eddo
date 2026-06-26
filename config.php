<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'elshop');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Помилка підключення: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    // Проверяем роль пользователя в сессии
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

function getCurrentUser() {
    global $conn;
    if(isLoggedIn()) {
        $user_id = (int)$_SESSION['user_id'];
        $result = $conn->query("SELECT * FROM users WHERE id = $user_id");
        if($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    }
    return null;
}

// Функция для обновления роли в сессии
function updateUserRole() {
    global $conn;
    if(isLoggedIn()) {
        $user_id = (int)$_SESSION['user_id'];
        $result = $conn->query("SELECT role FROM users WHERE id = $user_id");
        if($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_role'] = $user['role'];
            return $user['role'];
        }
    }
    return null;
}

define('CURRENCY', '₴');
define('SITE_NAME', 'ElectroShop');
?>