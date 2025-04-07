<?php
require('connect.php');
include('header.php');

// Check whether user is loged in
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Obtain all movies
$query = "SELECT m.*, g.genre_name 
          FROM Movies m
          LEFT JOIN Genres g ON m.genre_id = g.genre_id
          ORDER BY m.release_year DESC";
$statement = $db->prepare($query);
$statement->execute();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css?v=1.0">
    <title>Home page</title>
</head>
<body>

    <!--<h1>Here you can recommend movies you like!</h1>-->
    
    <div class="movies_container">

            <?php while($row = $statement->fetch()): ?>
                <div class="movie_card">
                    <div class="movie_info">
                        
                        <div class="info_with_poster">
                            <div class="text_info">
                                <h2><?= htmlspecialchars_decode($row['title']) ?></h2>
                                <p><strong>Type:</strong> <?= htmlspecialchars($row['type']); ?></p>

                                <?php
                                $runtime = $row['runtime'];
                                        if ($runtime >= 60) {
                                            $hours = floor($runtime / 60);
                                            $minutes = $runtime % 60;
                                            $formatted_runtime = "{$hours}h {$minutes}m";
                                        } else {
                                            $formatted_runtime = "{$runtime}m";
                                        }
                                ?>
                                <p><strong>Runtime:</strong> <?= htmlspecialchars($formatted_runtime); ?></p>

                                <p><strong>Release Year:</strong> <?= htmlspecialchars($row['release_year']); ?></p>
                                <p><strong>Language:</strong> <?= htmlspecialchars_decode($row['language']); ?></p>
                                <p><strong>Genre:</strong> <?= htmlspecialchars($row['genre_name'] ?? 'Unkown'); ?></p>
                            </div>
                            <div class="poster_container">
                                <?php if (!empty(trim($row['poster_url']))): ?>
                                <img src="<?= htmlspecialchars($row['poster_url']) ?>" alt="Movie Poster">
                                <?php endif; ?>
                            </div>
                        </div>
                        <p><strong>TMDb Link:</strong> <a href="<?= htmlspecialchars($row['tmdb_link']); ?>" target="_blank"><?= htmlspecialchars_decode($row['tmdb_link']); ?></a></p>
                    </div>
                </div>
            <?php endwhile ?>

    </div>

</body>
</html>
