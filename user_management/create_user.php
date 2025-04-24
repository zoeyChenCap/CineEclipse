<?php
session_start();
require_once('../connect.php');
require_once('../authenticate.php');

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied: only Admin can manage user data.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form submission
    $first_name = $_POST['fname'];
    $last_name = $_POST['lname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = trim($_POST['confirm_password']);
    $role = $_POST['role'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match. Please keep the password consistent.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
        $error = "Invalid email format."; // Validate email format
    } else {}
        // Check if the email already exists
        $query = "SELECT email FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_user) {
            $error = "The email address is already registered.";
        } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert the new user into the database
        $query = "INSERT INTO users (first_name, last_name, email, password, role) 
                  VALUES (:first_name, :last_name, :email, :password, :role)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            // User created successfully
            header('Location: ../backstage.php');
            exit();
        } else {
            // Failed to create user
            $error = "Error: Could not create user.";
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
    <title>Create New User</title>
</head>
<body>
    <div class="container mt-4">
        <!-- Top navigation button -->
        <div class="d-flex justify-content-end mb-3">
            <a href="../backstage.php" class="btn btn-secondary me-2">Return to Backstage</a>
        </div>

        <h2 class="text-center mb-4">Create New User</h2>
        <p class="text-center text-muted">
            Fill in the details below to create a new user account.
        </p>

        <!-- Display error message -->
        <?php if ($error): ?> 
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> 
        <?php endif; ?>

        <form method="POST" action="create_user.php" class="p-4 border rounded shadow-sm bg-light form-container">
            <!-- First Name and Last Name -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="fname" class="form-label">First Name:</label>
                    <input type="text" id="fname" name="fname" class="form-control" value="" autofocus required>
                </div>
                <div class="col-md-6">
                    <label for="lname" class="form-label">Last Name:</label>
                    <input type="text" id="lname" name="lname" class="form-control" required>
                </div>
            </div>

            <!-- Email -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
            </div>

            <!-- Password and Confirm Password -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
            </div>

            <!-- Role -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="role" class="form-label">Role:</label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary" style="width: 100px;">Create</button>
                <button type="button" class="btn btn-secondary" style="width: 100px;" onclick="window.location.href='../backstage.php'">Cancel</button>
            </div>
        </form>
    </div>
</body>
</html>
