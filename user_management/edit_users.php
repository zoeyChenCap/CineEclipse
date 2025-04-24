<?php
/*******w******** 
    
    Name:Zoey Chen
    Date:2025/04/24
    Description:This PHP script allows an admin to edit user details in the database. 
                It verifies the user's session and role, retrieves the user information 
                based on the user ID, and processes form submissions to update the user's 
                first name, last name, email, and role. The script includes validation for 
                email format and checks for duplicate email addresses, providing feedback 
                with error or success messages.

****************/

session_start();
require_once('../connect.php');
require_once('../authenticate.php');

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied: only Admin can manage user data.");
}

// Get the user ID
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Fetch user information
    $query = "SELECT * FROM Users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the user does not exist, show an error message
    if (!$user) {
        die("User not found.");
    }
} else {
    die("Invalid user ID.");
}

// Update user information
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
                header('Location: ../backstage.php');  // Redirect to the user management page after successful update
                exit();
            } else {
                $error = "Failed to update user.";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style.css?v=1.0">
    <title>Edit User</title>
</head>
<body>
    <div class="container mt-4">
        <!-- Top navigation button -->
        <div class="d-flex justify-content-end mb-3">
            <a href="../backstage.php" class="btn btn-secondary me-2">Return to Backstage</a>
        </div>

        <h2 class="text-center mb-4">Edit User</h2>
        <p class="text-center text-muted">
            Update the user details below and click "Update" to save changes.
        </p>

        <!-- Display error message -->
        <?php if (isset($error)): ?> 
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> 
        <?php endif; ?>

        <form method="POST" action="edit_users.php?user_id=<?= htmlspecialchars($user_id) ?>" class="p-4 border rounded shadow-sm bg-light form-container">
            <!-- First Name and Last Name -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label">First Name:</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" autofocus required>
                </div>
                <div class="col-md-6">
                    <label for="last_name" class="form-label">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                </div>
            </div>

            <!-- Email -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
            </div>

            <!-- Role -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="role" class="form-label">Role:</label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary" style="width: 100px;">Update</button>
                <button type="button" class="btn btn-secondary" style="width: 100px;" onclick="window.location.href='../backstage.php'">Cancel</button>
            </div>
        </form>
    </div>
</body>
</html>
