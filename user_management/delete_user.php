<?php
session_start();
require_once('../connect.php');
require_once('../authenticate.php');

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied: only Admin can manage user data.");
}

// Get the user ID
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Delete user information
    $query = "DELETE FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header('Location: ../backstage.php');  // Redirect to the user management page after successful deletion
        exit();
    } else {
        die("Failed to delete user.");
    }
} else {
    die("Invalid user ID.");
}
