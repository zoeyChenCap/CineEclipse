<?php
require('connect.php');
include('header.php');

// Check whether user is logged in
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Initialize search parameters
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$release_year = isset($_GET['release_year']) ? $_GET['release_year'] : '';
$genre_id = isset($_GET['genre_id']) ? $_GET['genre_id'] : '';

// Prepare the SQL query based on the search query and filters
$query = "SELECT m.*, g.genre_name 
          FROM Movies m
          LEFT JOIN Genres g ON m.genre_id = g.genre_id
          WHERE 1=1";

$params = [];

// Apply filters if available
if (!empty($search_query)) {
    $query .= " AND m.title LIKE :search_query";
    $params[':search_query'] = '%' . $search_query . '%';
}

if (!empty($release_year)) {
    $query .= " AND m.release_year = :release_year";
    $params[':release_year'] = $release_year;
}

if (!empty($genre_id)) {
    $query .= " AND m.genre_id = :genre_id";
    $params[':genre_id'] = $genre_id;
}

$query .= " ORDER BY m.release_year DESC";

// Prepare and execute the query
$statement = $db->prepare($query);
$statement->execute($params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css?v=1.0">
    <title>Home Page</title>
</head>
<body>

    <!-- Search form -->
    <form method="GET" action="index.php" class="search_form" id="searchForm">
        <input type="text" name="search" placeholder="Enter movie title..." value="<?= htmlspecialchars($search_query) ?>">
        <input type="text" name="release_year" placeholder="Release Year" value="<?= htmlspecialchars($release_year) ?>">
        <select name="genre_id">
            <option value="">Select Genre</option>
            <?php
            $genres = $db->query("SELECT * FROM Genres")->fetchAll();
            foreach ($genres as $genre) {
                $selected = ($genre['genre_id'] == $genre_id) ? 'selected' : '';
                echo "<option value=\"{$genre['genre_id']}\" $selected>{$genre['genre_name']}</option>";
            }
            ?>
        </select>
        <button type="submit">Search</button>
        <button type="button" id="resetBtn">Reset</button>
    </form>

    <?php if (!empty($search_query) || !empty($release_year) || !empty($genre_id)): ?>
        <h2>Search results</h2>
    <?php endif; ?>

    <div class="movies_container">
        <?php if ($statement->rowCount() > 0): ?>
            <?php while ($row = $statement->fetch()): ?>
                <div class="movie_card">
                    <div class="movie_info">
                        <div class="info_with_poster">
                            <div class="text_info">
                                <h2><?= htmlspecialchars_decode($row['title']) ?></h2>
                                <p><strong>Type:</strong> <?= htmlspecialchars($row['type']) ?></p>
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
                                <p><strong>Runtime:</strong> <?= $formatted_runtime ?></p>
                                <p><strong>Release Year:</strong> <?= htmlspecialchars($row['release_year']) ?></p>
                                <p><strong>Language:</strong> <?= htmlspecialchars_decode($row['language']) ?></p>
                                <p><strong>Genre:</strong> <?= htmlspecialchars($row['genre_name']) ?></p>
                            </div>
                            <?php if (!empty($row['poster_url'])): ?>
                            <div class="poster_container">
                                <img src="<?= htmlspecialchars($row['poster_url']) ?>" alt="Movie Poster">
                            </div>
                            <?php endif; ?>
                        </div>
                        <p><strong>TMDb Link:</strong> <a href="<?= htmlspecialchars($row['tmdb_link']) ?>" target="_blank"><?= htmlspecialchars_decode($row['tmdb_link']) ?></a></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No movies found.</p>
        <?php endif; ?>
    </div>

    <script src="script.js"></script>      

</body>
</html>

