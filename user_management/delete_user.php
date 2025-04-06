<?php
session_start();
require_once('../connect.php');
require_once('../authenticate.php');

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied: only Admin can manage user data.");
}

// 获取用户 ID
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // 删除用户数据
    $query = "DELETE FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header('Location: ../backstage.php');  // 删除成功后跳转回用户管理页面
        exit();
    } else {
        die("Failed to delete user.");
    }
} else {
    die("Invalid user ID.");
}
