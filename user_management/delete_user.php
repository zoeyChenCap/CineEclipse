<?php
/*******w******** 
    
    Name:Zoey Chen
    Date:2025/04/24
    Description:This PHP script allows an admin to delete a user account from the database. It verifies the user's session and role, 
                retrieves the user ID from the URL, and deletes the corresponding user record. The script provides feedback and 
                redirects the admin to the user management page upon successful deletion.

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
