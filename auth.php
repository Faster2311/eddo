<?php
require_once 'config.php';
header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

if($action == 'login') {
    $login = isset($_POST['login']) ? $conn->real_escape_string($_POST['login']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if(empty($login) || empty($password)) {
        echo json_encode(array('success' => false, 'error' => 'Заповніть всі поля'));
        exit;
    }
    
    $result = $conn->query("SELECT * FROM users WHERE login='$login' OR email='$login'");
    
    if($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if(password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['user_role'] = $user['role'];
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'error' => 'Невірний пароль'));
        }
    } else {
        echo json_encode(array('success' => false, 'error' => 'Користувача не знайдено'));
    }
}
elseif($action == 'register') {
    $login = isset($_POST['login']) ? $conn->real_escape_string($_POST['login']) : '';
    $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $full_name = isset($_POST['full_name']) ? $conn->real_escape_string($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? $conn->real_escape_string($_POST['phone']) : '';
    
    if(empty($login) || empty($email) || empty($password)) {
        echo json_encode(array('success' => false, 'error' => 'Заповніть обов\'язкові поля'));
        exit;
    }
    
    if(strlen($password) < 6) {
        echo json_encode(array('success' => false, 'error' => 'Пароль повинен бути не менше 6 символів'));
        exit;
    }
    
    $check = $conn->query("SELECT id FROM users WHERE login='$login' OR email='$email'");
    if($check && $check->num_rows > 0) {
        echo json_encode(array('success' => false, 'error' => 'Користувач з таким логіном або email вже існує'));
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (login, email, password, full_name, phone) VALUES ('$login', '$email', '$hashed_password', '$full_name', '$phone')");
    
    if($conn->affected_rows > 0) {
        echo json_encode(array('success' => true));
    } else {
        echo json_encode(array('success' => false, 'error' => 'Помилка реєстрації'));
    }
}
else {
    echo json_encode(array('success' => false, 'error' => 'Невідома дія'));
}
?>