<?php
session_start();
require_once('../connect.php');
require_once('../authenticate.php');

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied: only Admin can manage user data.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理表单提交
    $first_name = $_POST['fname'];
    $last_name = $_POST['lname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = trim($_POST['confirm_password']);
    $role = $_POST['role'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match. Please keep the password consistent.";
    } else {
        // 检查邮箱是否已经存在
        $query = "SELECT email FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_user) {
            $error = "The email address is already registered.";
        } else {
        // 密码加密
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // 插入新用户数据
        $query = "INSERT INTO users (first_name, last_name, email, password, role) 
                  VALUES (:first_name, :last_name, :email, :password, :role)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            // 成功创建新用户
            header('Location: ../backstage.php');
            exit();
        } else {
            // 插入失败
            $error = "Error: Could not create user.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css?v=1.0">
    <title>Create New User</title>
</head>
<body>

    <div class="log_form">
        <h2>Create New User</h2>
        <?php if ($error): ?> 
            <div class='error'><?= htmlspecialchars($error) ?></div> 
        <?php endif; ?>

        <form method="POST" action="create_user.php">
            <label for="fname">First Name:</label>
            <input type="text" id="fname" name="fname" value="" autofocus />

            <label for="lname">Last Name:</label>
            <input type="text" id="lname" name="lname">

            <label for="email">Email:</label>
            <input type="email" name="email">

            <label for="password">Password:</label>
            <input type="password" name="password">

            <label for="confirm_password">Confirm the password:</label>
            <input type="password" name="confirm_password">

            <label for="role">Role:</label>
            <select id="role" name="role">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select><br><br>

            <div class="double_buttons">
                <button type="submit">Create User</button>
                <button type="button" onclick="window.location.href='../backstage.php'">Cancel</button>
            </div>
        </form>
    </div>
    
</body>
</html>
