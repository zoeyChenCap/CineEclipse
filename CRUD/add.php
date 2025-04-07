<?php
session_start();
require('../connect.php');

// 确保用户已登录
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

// Only admin can edit movie information
$user_id = $_SESSION['user_id']; 
$role = $_SESSION['role'];
if ($role !== 'admin') {
    echo "<script>alert('Access denied. You do not have permission to add a movie.');</script>";
    echo "<script>window.location.href = '../index.php';</script>";
    exit();
}

// 获取所有类型（Genres）
$genresQuery = "SELECT * FROM genres";
$genresStatement = $db->prepare($genresQuery);
$genresStatement->execute();
$genres = $genresStatement->fetchAll(PDO::FETCH_ASSOC);

// 生成文件上传路径的函数
function file_upload_path($original_filename, $upload_subfolder_name = 'posters') {
    $current_folder = dirname(__FILE__);
    $path_segments = [$current_folder, "..", $upload_subfolder_name, basename($original_filename)];
    return join(DIRECTORY_SEPARATOR, $path_segments);
}

$error = "";

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取并清理用户输入
    $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $type = trim(filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $runtime = trim(filter_input(INPUT_POST, 'runtime', FILTER_VALIDATE_INT));
    $release_year = trim(filter_input(INPUT_POST, 'release_year', FILTER_VALIDATE_INT));
    $language = trim(filter_input(INPUT_POST, 'language', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $country = trim(filter_input(INPUT_POST, 'country', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $genre_id = trim(filter_input(INPUT_POST, 'genre_id', FILTER_VALIDATE_INT));
    $tmdb_link = trim(filter_input(INPUT_POST, 'tmdb_link', FILTER_SANITIZE_URL));

    // 处理海报上传
    $poster_url = "";
    $image_upload_detected = isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK;

    if ($image_upload_detected) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        $fileTmpPath = $_FILES['poster']['tmp_name'];
        $fileName = $_FILES['poster']['name'];
        $fileSize = $_FILES['poster']['size'];
        $fileType = mime_content_type($fileTmpPath); // 使用 mime_content_type() 获取真实的文件 MIME 类型 

        if (!in_array($fileType, $allowed_types)) {
            $error = "Invalid file type, only JPG, PNG, and WEBP files are allowed.";
        } elseif ($fileSize > $max_size) {
            $error = "File size exceeds the limit which is 2MB.";
        } else {
            $newFileName = uniqid() . '_' . basename($fileName);
            $destPath = file_upload_path($newFileName, 'posters');

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $poster_url = "posters/" . $newFileName;
            } else {
                $error = "Error: File upload failed.";
            }
        }
    }

    // Validate the required information
    if(empty($title) || empty($type) || empty($runtime) || empty($release_year) || empty($language) || empty($country) || empty($genre_id)){
            $error = "Error: Incomplete movie information.";
        } elseif (!is_numeric($release_year) || $release_year < 1888 || $release_year > date("Y")){
            $error = "Error: Please enter a valid release year.";
        } elseif (!is_numeric($runtime) || $runtime < 1) {
            $error = "Error: Please enter a valid runtime.";
        }
    // Only when the error is empty, insert the movie information into database
    if(!empty($error)){
        echo "<script>alert('$error');</script>";
    } else {
            try {
                // 插入电影信息
                $insertQuery = "INSERT INTO movies (title, type, runtime, release_year, language, country, genre_id, tmdb_link, poster_url, user_id) 
                                VALUES (:title, :type, :runtime, :release_year, :language, :country, :genre_id, :tmdb_link, :poster_url, :user_id)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([
                    ':title' => $title,
                    ':type' => $type,
                    ':runtime' => $runtime,
                    ':release_year' => $release_year,
                    ':language' => $language,
                    ':country' => $country,
                    ':genre_id' => $genre_id,
                    ':tmdb_link' => $tmdb_link,
                    ':poster_url' => $poster_url,
                    ':user_id' => $user_id,
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Movie</title>
</head>
<body>
    <h1>Add New Movie</h1>

    <!-- 在页面上显示错误信息 -->
    <?php if (!empty($error)): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <form action="add.php" method="post" enctype="multipart/form-data">
        <label for="title">Movie Title:</label>
        <input type="text" name="title" required><br>

        <label for="type">Type:</label>
        <select name="type" id="type" required>
            <option value="movie">Movie</option>
            <option value="series">TV Show</option>
        </select><br>

        <label for="runtime">Runtime (in minutes):</label>
        <input type="number" name="runtime" required><br>

        <label for="release_year">Release Year:</label>
        <input type="number" name="release_year" min="1888" max="<?= date('Y') ?>" required><br>

        <label for="language">Language:</label>
        <input type="text" name="language" required><br>

        <label for="country">Country:</label>
        <input type="text" name="country" required><br>

        <label for="genre_id">Genre:</label>
        <select name="genre_id" required>
            <option value="">-- Select Genre --</option>
            <?php foreach ($genres as $genre): ?>
                <option value="<?= $genre['genre_id'] ?>"><?= $genre['genre_name'] ?></option>
            <?php endforeach; ?>
        </select><br>

        <label for="tmdb_link">TMDb Link:</label>
        <input type="text" name="tmdb_link"><br>

        <label for="poster">Upload Poster:</label>
        <input type="file" name="poster" ><br>

        <button type="submit">Add Movie</button>
    </form>
    <a href="../index.php">Back to Home Page</a>
</body>
</html>
