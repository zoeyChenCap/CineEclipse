<?php
/*******w******** 
    
    Name:Zoey Chen
    Date:2025/04/24
    Description:This PHP script handles the logout functionality for the CineEclipse website. 
                It clears all session variables, destroys the session, and redirects the user 
                to the login page.

****************/

session_start();
session_unset(); // Clear all session variables
session_destroy(); // Destroy the session
header("Location: login.php");
exit();
?>