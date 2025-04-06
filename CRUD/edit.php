<?php
session_start();
require('../connect.php'); // 连接数据库

// 确保用户已登录
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}
// Only admin can edit movie information
$role = $_SESSION['role'];
if ($role !== 'admin') {
    echo "<script>alert('Access denied. You do not have permission to edit movies.');</script>";
    echo "<script>window.location.href = '../index.php';</script>";
    exit();
}

// 获取电影 ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Movie ID is missing.");
}

$movie_id = $_GET['id'];

// 获取当前电影信息
$query = "SELECT * FROM Movies WHERE movie_id = :movie_id";
$statement = $db->prepare($query);
$statement->bindParam(':movie_id', $movie_id, PDO::PARAM_INT);
$statement->execute();
$movie = $statement->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    die("Error: Movie not found.");
}

// 获取所有类型（Genres）
$genresQuery = "SELECT * FROM genres";
$genresStatement = $db->prepare($genresQuery);
$genresStatement->execute();
$genres = $genresStatement->fetchAll(PDO::FETCH_ASSOC);

// 生成文件上传路径的函数
function file_upload_path($original_filename, $upload_subfolder_name = 'posters') {
    $current_folder = dirname(__FILE__); // 获取当前脚本目录
    $path_segments = [$current_folder, "..", $upload_subfolder_name, basename($original_filename)];
    return join(DIRECTORY_SEPARATOR, $path_segments);
}

$error = "";

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $type = trim(filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $release_year = trim(filter_input(INPUT_POST, 'release_year', FILTER_VALIDATE_INT));
    $language = trim(filter_input(INPUT_POST, 'language', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $country = trim(filter_input(INPUT_POST, 'country', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $genre_id = trim(filter_input(INPUT_POST, 'genre_id', FILTER_VALIDATE_INT));
    $tmdb_link = trim(filter_input(INPUT_POST, 'tmdb_link', FILTER_SANITIZE_URL));

    // 处理海报上传
    $poster_url = $movie['poster_url']; // 默认使用原有海报
    $image_upload_detected = isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK;

    if ($image_upload_detected) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        $fileTmpPath = $_FILES['poster']['tmp_name'];
        $fileName = $_FILES['poster']['name'];
        $fileSize = $_FILES['poster']['size'];
        $fileType = mime_content_type($fileTmpPath);

        if (!in_array($fileType, $allowed_types)) {
            $error = "Invalid file type, only JPG, PNG, and WEBP files are allowed.";
        } elseif ($fileSize > $max_size) {
            $error = "File size exceeds the limit which is 2MB.";
        } else {
            // 生成唯一文件名
            $newFileName = uniqid() . '_' . basename($fileName);
            $destPath = file_upload_path($newFileName, 'posters');

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $poster_url = "posters/" . $newFileName; // 存入数据库的路径
            } else {
                $error = "Error: File upload failed.";
            }
        }
    }

    // Validate the required information
    if(empty($title) || empty($type) ||empty($release_year) || empty($language) || empty($country) || empty($genre_id)){
        $error = "Error: Incomplete movie information.";
    } elseif (!is_numeric($release_year) || $release_year < 1888 || $release_year > date("Y")){
        $error = "Error: Please enter a valid release year.";
    } 

    // Only when the error is empty, insert the movie information into database
    if(!empty($error)){
        echo "<script>alert('$error');</script>";
    } else {
        try {
            // 更新电影信息
            $updateQuery = "UPDATE movies 
                SET title = :title, type = :type, release_year = :release_year, 
                    language = :language, country = :country, genre_id = :genre_id, 
                    tmdb_link = :tmdb_link, poster_url = :poster_url
                WHERE movie_id = :movie_id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([
                ':title' => $title,
                ':type' => $type,
                ':release_year' => $release_year,
                ':language' => $language,
                ':country' => $country,
                ':genre_id' => $genre_id,
                ':tmdb_link' => $tmdb_link,
                ':poster_url' => $poster_url,
                ':movie_id' => $movie_id,
            ]);

            echo "<script>alert('Movie added successfully!'); window.location.href = 'add.php';</script>";
        } catch (PDOException $e) {
            echo "<script>alert('Database error: " . addslashes($e->getMessage()) . "');</script>";
            }
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Movie</title>
</head>
<body>
    <h2>Edit Movie</h2>

    <!-- 显示错误信息 -->
    <?php if (!empty($error)): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <label>Title:</label>
        <input type="text" name="title" value="<?= htmlspecialchars($movie['title']) ?>" required><br>

        <p>Created At: <?= htmlspecialchars($movie['created_at']) ?></p>

        <label for="type">Type:</label>
        <select name="type" id="type" required>
            <option value="movie" <?= ($movie['type'] == 'movie') ? 'selected' : '' ?>>Movie</option>
            <option value="series" <?= ($movie['type'] == 'series') ? 'selected' : '' ?>>TV Show</option>
        </select><br>


        <label>Release Year:</label>
        <input type="number" name="release_year" value="<?= $movie['release_year'] ?>" required min="1888" max="<?= date('Y') ?>"><br>

        <label>Language:</label>
        <input type="text" name="language" value="<?= htmlspecialchars($movie['language']) ?>" required><br>

        <label>Country:</label>
        <input type="text" name="country" value="<?= htmlspecialchars($movie['country']) ?>" required><br>

        <label>Genre:</label>
        <select name="genre_id" required>
            <?php foreach ($genres as $genre): ?>
                <option value="<?= $genre['genre_id'] ?>" <?= ($genre['genre_id'] == $movie['genre_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($genre['genre_name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label>TMDb Link:</label>
        <input type="url" name="tmdb_link" value="<?= htmlspecialchars($movie['tmdb_link']) ?>"><br>

        <label>Poster:</label>
        <input type="file" name="poster"><br>
        <?php if (!empty($movie['poster_url'])): ?>
            <img src="../<?= htmlspecialchars($movie['poster_url']) ?>" width="150" alt="Movie Poster"><br>
        <?php endif; ?>

        <button type="submit">Update Movie</button>
    </form>
    <a href="../index.php">Back to Home Page</a>
</body>
</html>
