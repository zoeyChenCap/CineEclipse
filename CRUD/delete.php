<?php
/*******w******** 
    
    Name:Zoey Chen
    Date:2025/04/24
    Description:This PHP script allows an admin to delete a movie from the database. It verifies the user's session and role, 
                checks if the movie ID exists, fetches the movie details, and deletes the movie if it exists. It also provides 
                feedback to the user and redirects them back to the admin dashboard.

****************/

session_start();
require('../connect.php'); // Connect to the database

// Ensure the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

// Only admin can delete movie information
$role = $_SESSION['role'];
if ($role !== 'admin') {
    echo "<script>alert('Access denied. Only admin can delete movies.');</script>";
    echo "<script>window.location.href = '../index.php';</script>";
    exit();
}

// Get the movie ID from the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Movie ID is missing.");
}

$movie_id = $_GET['id'];

// Fetch the movie information to check if it exists
$query = "SELECT * FROM Movies WHERE movie_id = :movie_id";
$statement = $db->prepare($query);
$statement->bindParam(':movie_id', $movie_id, PDO::PARAM_INT);
$statement->execute();
$movie = $statement->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    die("Error: Movie not found.");
}

// Delete the movie from the database
$deleteQuery = "DELETE FROM Movies WHERE movie_id = :movie_id";
$deleteStmt = $db->prepare($deleteQuery);
$deleteStmt->execute([':movie_id' => $movie_id]);

$_SESSION['message'] = "Movie deleted successfully!";
echo "<script>
    alert('Movie deleted successfully!');
    window.location.href = '../backstage.php';
</script>";
exit;
