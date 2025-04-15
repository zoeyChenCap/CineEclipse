<?php
require('connect.php');
include('header.php');

// 检查是否传递了电影ID
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

// 获取电影ID并防止SQL注入
$movie_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// 查询电影详细信息
$query = "SELECT m.*, g.genre_name 
          FROM Movies m
          LEFT JOIN Genres g ON m.genre_id = g.genre_id
          WHERE m.movie_id = :movie_id";

$statement = $db->prepare($query);
$statement->bindValue(':movie_id', $movie_id, PDO::PARAM_INT);
$statement->execute();
$movie = $statement->fetch();

// 如果电影不存在则跳转回首页
if (!$movie) {
    header("Location: index.php");
    exit;
}

// 格式化片长
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a href="index.php">← Back to Movie List</a>
    
    <div class="movie_detail_container">
        <div class="movie_details">
            <?php if (!empty($movie['poster_url'])): ?>
            <img src="<?= htmlspecialchars($movie['poster_url']) ?>" alt="<?= htmlspecialchars($movie['title']) ?> Poster" class="movie_poster">
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