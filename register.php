<?php
require 'connect.php'; // Datebase connection

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['fname']);
    $last_name = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = "user";

    // Check whether the password equals the confirmed password
    if ($password !== $confirm_password) {
        $error = "Passwords do not match. Please keep the password consistent.";
    } else {
        // Password hashing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check whether the email was registered
        $checkEmail = $db->prepare("SELECT COUNT(*) FROM Users WHERE email = :email");
        $checkEmail->execute([':email' => $email]);
        if ($checkEmail->fetchColumn() > 0) {
            $error = "Error: Email is already registered.";
        } else {
            // Insert the new user
            $sql = "INSERT INTO Users (first_name, last_name, email, password, role) 
                    VALUES (:first_name, :last_name, :email, :password, :role)";
            $stmt = $db->prepare($sql);

            try {
                $stmt->execute([
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':email' => $email,
                    ':password' => $hashed_password,
                    ':role' => $role  
                ]);
                $success = "Registration successful! <a href='login.php'>Login here</a>";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
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
    <title>Register</title>
    <link rel="stylesheet" href="style.css?v=1.0">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="log_form">
        <h2>Register Page</h2>

        <?php if (!empty($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <form method="post">
        <div id="registerInfo">
            <label for="fname">First Name:</label>
            <input type="text" id="fname" name="fname" value="" autofocus />

            <label for="lname">Last Name:</label>
            <input type="text" id="lname" name="lname">

            <label for="email">Email:</label>
            <input type="email" name="email">

            <label for="password">Password:</label>
            <input type="password" name="password">

            <label for="confirm_password">Confirm your password:</label>
            <input type="password" name="confirm_password">

            <div class="double_buttons">
                <button type="submit">Register</button>
                <button type="button" onclick="window.location.href='index.php'">Cancel</button>
            </div>
        </div>
    </div>
</form>


</body>
</html>
