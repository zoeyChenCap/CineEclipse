<?php
/*******w******** 
    
    Name:Zoey Chen
    Date:2025/04/24
    Description:This PHP script generates the header for the CineEclipse website. 
                It starts a session, checks the user's login status and role, 
                and displays navigation links accordingly. Logged-out users see 
                options to sign up or log in, while logged-in users see personalized 
                greetings and options based on their role (e.g., admin access to the backstage).

****************/

session_start();
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$fullname = ($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '');
$fullname = trim($fullname);
?>

<header>
    <h1 class="logo">CineEclipse</h1>
    <nav>
        <?php if (!$logged_in): ?>
            <a href="register.php" class="button">Sign Up</a>
            <a href="login.php" class="button">Log in</a>
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <span class="welcome-text">Hello, administartor</span>
            <a href="backstage.php" class="button">Backstage</a>
            <a href="logout.php" class="button">Logout</a>
        <?php else: ?>
            <span class="welcome-text">Enjoy your movie exploration</span>
            <a href="logout.php" class="button">Logout</a>
        <?php endif; ?>
    </nav>
</header>