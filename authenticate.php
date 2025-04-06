<?php
require('connect.php');  

function login($email, $password) {
    global $db;  

    // 获取用户数据
    $query = "SELECT user_id, first_name, last_name, email, password, role FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // 登录成功，设置 session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['fname'] = $user['first_name'];
        $_SESSION['lname'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
