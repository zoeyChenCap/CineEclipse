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

    // 获取用户信息
    $query = "SELECT * FROM Users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 如果用户不存在，则显示错误信息
    if (!$user) {
        die("User not found.");
    }
} else {
    die("Invalid user ID.");
}

// 更新用户信息
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // Validate the email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    }
    else {
        // Check if the email already exists
        $query = "SELECT email FROM users WHERE email = :email AND user_id != :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_user) {
            $error = "The email address is already registered, please change to a new email.";
        } else {
            // Update user information
            $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, role = :role WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                header('Location: ../backstage.php');  // 更新成功后跳转到用户管理页面
                exit();
            } else {
                $error = "Failed to update user.";
            }
        }
    }
}
?>

<h2>Edit User</h2>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
<form method="POST">
    <label for="first_name">First Name:</label>
    <input type="text" name="first_name" id="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required><br>

    <label for="last_name">Last Name:</label>
    <input type="text" name="last_name" id="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required><br>

    <label for="last_name">Email:</label>
    <input type="text" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required><br>

    <label for="role">Role:</label>
    <select name="role" id="role">
        <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>User</option>
        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
    </select><br>

    <button type="submit">Update User</button>
    <a href="../backstage.php">Return to Backstage</a>
</form>
