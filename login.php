<?php
session_start();
require 'connect.php'; // database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Search the user
    $stmt = $db->prepare("SELECT user_id, first_name, last_name, password, role FROM Users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check whether the user exist and the password is right
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['fname'] = $user['first_name'];
        $_SESSION['lname'] = $user['last_name'];
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        header("Location: index.php"); // Log in successfully and turn to the main page
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css?v=0.1">
</head>
<body>
    <div class="log_form">
    <h2>Login</h2>

    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="post">
        <label for="email">Email:</label>
        <input type="email" name="email" required>

        <label for="password">Password:</label>
        <input type="password" name="password" required>

        <div class="double_buttons">
            <button type="submit">Login</button>
            <button onclick="location.href='index.php?'">Cancel</button>
        </div>
    </form>

    <p>Don't have an account? <a href="register.php">Register here</a></p>
</div>
</body>
</html>
