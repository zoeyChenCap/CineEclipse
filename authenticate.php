<?php
/*******w******** 
    
    Name:Zoey Chen
    Date:2025/04/24
    Description:This PHP script handles user authentication. It includes a login function to verify user credentials, 
                set session variables upon successful login, and retrieve user details from the database. It also 
                provides utility functions like isAdmin to check if the logged-in user is an admin and isLoggedIn 
                to verify if a user is logged in.

****************/

require('connect.php');  

function login($email, $password) {
    global $db;  

    // Retrieve user information from the database
    $query = "SELECT user_id, first_name, last_name, email, password, role FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Login successful, set session variables
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
