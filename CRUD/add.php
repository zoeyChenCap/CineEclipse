<?php
session_start();
require('../connect.php');

require_once '../php-image-resize-master/lib/ImageResize.php';
require_once '../php-image-resize-master/lib/ImageResizeException.php';
use \Gumlet\ImageResize;

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

// Only admin can add movie information
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
if ($role !== 'admin') {
    echo "<script>alert('Access denied. Only admins can add movies.');</script>";
    echo "<script>window.location.href = '../index.php';</script>";
    exit();
}

// Get all genres
$genresQuery = "SELECT * FROM genres";
$genresStatement = $db->prepare($genresQuery);
$genresStatement->execute();
$genres = $genresStatement->fetchAll(PDO::FETCH_ASSOC);

// Generate upload path
function file_upload_path($original_filename, $upload_subfolder_name = 'posters', $suffix = '') {
    $current_folder = dirname(__FILE__);
    $filename = basename(pathinfo($original_filename, PATHINFO_FILENAME)); // 使用 basename 防止目录遍历
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);

    if (!empty($suffix)) {
        $filename .= $suffix;
    }

    $path_segments = [$current_folder, "..", $upload_subfolder_name, $filename . '.' . $extension];
    $path = join(DIRECTORY_SEPARATOR, $path_segments);

    // 确保目录存在
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    return $path;
}

$error = "";
// Image paths
$poster_url = "";
$poster_url_medium = "";
$poster_url_thumb = "";

$title = $type = $language = $country = $tmdb_link = "";
$runtime = $release_year = $genre_id = null;
$tmdbGenres = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize form input
    $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $type = trim(filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $runtime = trim(filter_input(INPUT_POST, 'runtime', FILTER_VALIDATE_INT));
    $release_year = trim(filter_input(INPUT_POST, 'release_year', FILTER_VALIDATE_INT));
    $language = trim(filter_input(INPUT_POST, 'language', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $country = trim(filter_input(INPUT_POST, 'country', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $genre_id = trim(filter_input(INPUT_POST, 'genre_id', FILTER_VALIDATE_INT));
    $tmdb_link = trim(filter_input(INPUT_POST, 'tmdb_link', FILTER_SANITIZE_URL));

    // Upload poster manually if TMDb link is not provided
    $image_upload_detected = empty($poster_url) && isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK;

    $poster_from_tmdb = isset($_POST['poster_from_tmdb']) ? trim($_POST['poster_from_tmdb']) : "";
$downloaded_from_tmdb = false;

if (empty($poster_url) && !$image_upload_detected && !empty($poster_from_tmdb)) {
    // 从 TMDb 下载图片
    $image_data = file_get_contents($poster_from_tmdb);
    if ($image_data === false) {
        $error = "Failed to download image from TMDb. Please check the URL.";
    }

    if ($image_data !== false) {
        $ext = pathinfo(parse_url($poster_from_tmdb, PHP_URL_PATH), PATHINFO_EXTENSION);
        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array(strtolower($ext), $allowed_types)) {
            $error = "TMDb image type not supported.";
        } else {
            $newFileName = uniqid() . '_tmdb.' . $ext;
            $destPath = file_upload_path($newFileName, 'posters');

            // 保存图片到本地
            file_put_contents($destPath, $image_data);
            $poster_url = "posters/" . $newFileName;

            try {
                // 中图
                $mediumPath = file_upload_path($newFileName, 'posters', '_medium');
                $imageMedium = new ImageResize($destPath);
                $imageMedium->resizeToWidth(400);
                $imageMedium->save($mediumPath);
                $poster_url_medium = "posters/" . pathinfo($newFileName, PATHINFO_FILENAME) . "_medium." . $ext;

                // 缩略图
                $thumbPath = file_upload_path($newFileName, 'posters', '_thumb');
                $imageThumb = new ImageResize($destPath);
                $imageThumb->resizeToWidth(110);
                $imageThumb->save($thumbPath);
                $poster_url_thumb = "posters/" . pathinfo($newFileName, PATHINFO_FILENAME) . "_thumb." . $ext;

                $downloaded_from_tmdb = true;
            } catch (Exception $e) {
                $error = "TMDb image resize failed: " . $e->getMessage();
            }
        }
    } else {
        $error = "Failed to download image from TMDb.";
    }
}

    if ($image_upload_detected) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        $fileTmpPath = $_FILES['poster']['tmp_name'];
        $fileName = $_FILES['poster']['name'];
        $fileSize = $_FILES['poster']['size'];
        $fileType = mime_content_type($fileTmpPath);
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($fileType, $allowed_types)) {
            $error = "Invalid file type. Only JPG, PNG, and WEBP are allowed.";
        }

        if (!in_array($fileType, $allowed_types)) {
            $error = "Invalid file type. Only JPG, PNG, and WEBP are allowed.";
        } elseif ($fileSize > $max_size) {
            $error = "File size exceeds 2MB.";
        } else {
            $newFileName = uniqid() . '_' . basename($fileName);
            $destPath = file_upload_path($newFileName, 'posters');

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $poster_url = "posters/" . $newFileName;

                try {
                    // Medium version
                    $mediumPath = file_upload_path($newFileName, 'posters', '_medium');
                    $thumbPath = file_upload_path($newFileName, 'posters', '_thumb');
                    try {
                        $imageMedium = new ImageResize($destPath);
                        $imageMedium->resizeToWidth(400);
                        $imageMedium->save($mediumPath);

                        $imageThumb = new ImageResize($destPath);
                        $imageThumb->resizeToWidth(110);
                        $imageThumb->save($thumbPath);
                    } catch (Exception $e) {
                        // 删除已生成的文件
                        if (file_exists($destPath)) unlink($destPath);
                        if (file_exists($mediumPath)) unlink($mediumPath);
                        if (file_exists($thumbPath)) unlink($thumbPath);

                        $error = "Image resize failed: " . $e->getMessage();
                    }
                    $poster_url_medium = "posters/" . pathinfo($newFileName, PATHINFO_FILENAME) . "_medium." . pathinfo($newFileName, PATHINFO_EXTENSION);

                    // Thumbnail version
                    $poster_url_thumb = "posters/" . pathinfo($newFileName, PATHINFO_FILENAME) . "_thumb." . pathinfo($newFileName, PATHINFO_EXTENSION);
                } catch (Exception $e) {
                    $error = "Image resize failed: " . $e->getMessage();
                }
            } else {
                $error = "Failed to upload image.";
            }
        }
    }

    // Validate required fields
    if (empty($title) || empty($type) || empty($runtime) || empty($release_year) || empty($language) || empty($country) || empty($genre_id)) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($release_year) || $release_year < 1888 || $release_year > intval(date("Y"))) {
        $error = "Please enter a valid release year between 1888 and " . date("Y") . ".";
    } elseif (!is_numeric($runtime) || $runtime <= 0) {
        $error = "Please enter a valid runtime greater than 0.";
    }

    // Insert into DB if valid
    if (!empty($error)) {
        echo "<script>alert('$error');</script>";
    } else {
        try {
            $insertQuery = "INSERT INTO movies (title, type, runtime, release_year, language, country, genre_id, tmdb_link, poster_url, poster_url_medium, poster_url_thumb, user_id)
                            VALUES (:title, :type, :runtime, :release_year, :language, :country, :genre_id, :tmdb_link, :poster_url, :poster_url_medium, :poster_url_thumb, :user_id)";
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
                ':poster_url_medium' => $poster_url_medium,
                ':poster_url_thumb' => $poster_url_thumb,
                ':user_id' => $user_id,
            ]);

            echo "<script>alert('Movie added successfully!'); window.location.href = '../backstage.php';</script>";
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
<title>Add Movie</title>
</head>
<body>
<h2>Add New Movie</h2>
<p class="description">
    Use the form below to add a new movie to the database. You can either autofill the movie details using a TMDb link or manually enter the information. 
</p>
<form action="add.php" method="POST" enctype="multipart/form-data">
    <div>
        <label for="tmdb_link">TMDb Link:</label>
        <input type="url" name="tmdb_link" id="tmdb_link" required>
        <button type="button"  name="autofill" id="autofillBtn">Autofill from TMDb</button>
    </div>

    <div>
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" required>
    </div>

    <div>
        <label for="type">Type:</label>
        <input type="text" name="type" id="type" required>
    </div>

    <div>
        <label for="runtime">Runtime (minutes):</label>
        <input type="number" name="runtime" id="runtime" required>
    </div>

    <div>
        <label for="release_year">Release Year:</label>
        <input type="number" name="release_year" id="release_year" required>
    </div>

    <div>
        <label for="language">Language:</label>
        <input type="text" name="language" id="language" required>
    </div>

    <div>
        <label for="country">Country:</label>
        <input type="text" name="country" id="country" required>
    </div>

    <div>
        <label for="genre_id">Genre:</label>
        <select name="genre_id" id="genre_id" required>
            <option value="">-- Select Genre --</option>
            <?php foreach ($genres as $genre): ?>
                <option value="<?= $genre['genre_id'] ?>"><?= htmlspecialchars($genre['genre_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <div style="margin-top: 5px;">
            <label>TMDb Suggested Genres: </label>
            <span id="tmdb_genres_label" style="font-weight: bold;"></span>
        </div>
    </div>

    <div>
        <label for="poster">Poster Image:</label>
        <input type="file" name="poster" id="poster" accept="image/*">
        <input type="hidden" name="poster_from_tmdb" id="poster_from_tmdb">
        <div id="poster_preview_container" style="display: none;">
            <img id="poster_preview" src="" alt="Poster Preview">
        <img id="poster_preview" src="" alt="" >
    </div>

    <div>
        <button type="submit">Add Movie</button>
    </div>
    <a href="../backstage.php">Return to Backstage</a>
</form>
<script src="autofill.js"></script>

</body>
</html>
