<?php
/*******w******** 
    
    Name:Zoey Chen
    Date:2025/04/24
    Description:This PHP script generates a detailed page for a specific movie on the CineEclipse website. 
                It retrieves movie details from the database based on the provided movie ID, validates the ID, 
                and displays information such as title, runtime, release year, language, genre, and poster. 
                It also includes a link to the movie's TMDb page and a back button to return to the movie list.

****************/

require('connect.php');
include('header.php');

// Check if the movie ID is passed
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

// Get the movie ID and sanitize it to prevent SQL injection
$movie_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
// Validate the movie ID
if ($movie_id === false || $movie_id <= 0) {
    header("Location: index.php"); // Redirect if the ID is invalid
    exit;
}

// Query movie details
$query = "SELECT m.*, g.genre_name 
          FROM Movies m
          LEFT JOIN Genres g ON m.genre_id = g.genre_id
          WHERE m.movie_id = :movie_id";

$statement = $db->prepare($query);
$statement->bindValue(':movie_id', $movie_id, PDO::PARAM_INT);
$statement->execute();
$movie = $statement->fetch();

// Redirect to the homepage if the movie does not exist
if (!$movie) {
    header("Location: index.php");
    exit;
}

// Format the runtime
$runtime = $movie['runtime'];
if ($runtime >= 60) {
    $hours = floor($runtime / 60);
    $minutes = $runtime % 60;
    $formatted_runtime = "{$hours}h {$minutes}m";
} else {
    $formatted_runtime = "{$runtime}m";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($movie['title']) ?> - Movie Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a href="index.php" class="btn btn-secondary btn-lg my-3">← Back to Movie List</a>
    
    <div class="movie_detail_container">
        <div class="movie_details">
            <?php if (!empty($movie['poster_url_medium'])): ?>
            <img src="<?= htmlspecialchars($movie['poster_url_medium']) ?>" alt="<?= htmlspecialchars($movie['title']) ?> Poster" class="movie_poster">
            <?php endif; ?>
            
            <div class="movie_details_text">
                <h1><?= htmlspecialchars_decode($movie['title']) ?></h1>
                <p><strong>Type:</strong> <?= htmlspecialchars($movie['type']) ?></p>
                <p><strong>Runtime:</strong> <?= $formatted_runtime ?></p>
                <p><strong>Release Year:</strong> <?= htmlspecialchars($movie['release_year']) ?></p>
                <p><strong>Language:</strong> <?= htmlspecialchars_decode($movie['language']) ?></p>
                <p><strong>Genre:</strong> <?= htmlspecialchars($movie['genre_name']) ?></p>
                <?php if (!empty($movie['tmdb_link'])): ?>
                <p><strong>TMDb Link:</strong> <a href="<?= htmlspecialchars($movie['tmdb_link']) ?>" target="_blank"><?= htmlspecialchars_decode($movie['tmdb_link']) ?></a></p>
                <?php endif; ?>
            </div>
        </div>
        
        
        
    </div>
</body>
</html>