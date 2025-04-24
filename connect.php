<?php
/*******w******** 
    
    Name:Zoey Chen
    Date:2025/04/24
    Description:This PHP script establishes a connection to the MySQL database using PDO. 
                It defines constants for the database credentials, sets the timezone to 
                Winnipeg, and configures the PDO error mode to throw exceptions. 
                If the connection fails, it displays an error message.

****************/

// Set the timezone of PHP to Winnnipeg local time
date_default_timezone_set('America/Winnipeg');

// Use defined() to check if constants are already defined to prevent redefinition
if (!defined('DB_DSN')) {
    define('DB_DSN', 'mysql:host=localhost;dbname=movie_cms');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // add error mode
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
