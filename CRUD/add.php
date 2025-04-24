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
                    // Medium size version and thumb size version
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
                        // Delete the original file if resizing fails
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container mt-4">
        <!-- 顶部导航按钮 -->
        <div class="d-flex justify-content-end mb-3">
            <a href="../backstage.php" class="btn btn-secondary me-2">Return to Backstage</a>
            <a href="../index.php" class="btn btn-secondary">Back to Movie List</a>
        </div>

<h2 class="text-center mb-4">Add New Movie</h2>
        <p class="text-center text-muted">
            Add a new movie by autofill via TMDb link or manually enter the information.
        </p>
        <form action="add.php" method="POST" enctype="multipart/form-data" class="p-4 border rounded shadow-sm bg-light form-container">
<!-- TMDb Link 和 Autofill Button -->
<div class="row mb-3">
    <div class="col-md-12 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center" style="width: 75%;">
            <label for="tmdb_link" class="form-label me-3">TMDb Link:</label>
            <input type="url" name="tmdb_link" id="tmdb_link" class="form-control" required>
        </div>
        <button type="button" name="autofill" id="autofillBtn" class="btn btn-primary w-auto ms-3">Autofill from TMDb</button>
    </div>
</div>

<!-- Title, Type 和 Release Year -->
    <div class="row mb-3">
<div class="col-md-4">
        <label for="title" class="form-label">Title:</label>
        <input type="text" name="title" id="title" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label for="type" class="form-label">Type:</label>
        <input type="text" name="type" id="type" class="form-control" required>
    </div>
<div class="col-md-4">
        <label for="release_year" class="form-label">Release Year:</label>
        <input type="number" name="release_year" id="release_year" class="form-control" required>
    </div>
</div>

<!-- Runtime, Language 和 Country -->
    <div class="row mb-3">
<div class="col-md-4">
                    <label for="runtime" class="form-label">Runtime (minutes):</label>
                    <input type="number" name="runtime" id="runtime" class="form-control" required>
                </div>
                <div class="col-md-4">
        <label for="language" class="form-label">Language:</label>
        <input type="text" name="language" id="language" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label for="country" class="form-label">Country:</label>
        <input type="text" name="country" id="country" class="form-control" required>
    </div>
</div>

<!-- TMDb Suggested Genres 和 Genre -->
    <div class="row mb-3">
                        <div class="col-md-6">
            <label>TMDb Suggested Genres:</label>
            <strong><span id="tmdb_genres_label"></span></strong>
        </div>
        <div class="col-md-6">
                <label for="genre_id" class="form-label d-inline">Genre:</label>
<p class="text-muted d-inline ms-2">Choose a genre based on TMDb suggestions.</p>
                <select name="genre_id" id="genre_id" class="form-select mt-2" required>
                    <option value="">-- Select Genre --</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?= $genre['genre_id'] ?>"><?= htmlspecialchars($genre['genre_name']) ?></option>
            <?php endforeach; ?>
        </select>
                </div>
    </div>

<!-- Poster Upload 和 Add Movie 按钮 -->
<div class="row mb-3">
    <div class="col-md-12 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center" style="width: 75%;">
            <label for="poster" class="form-label me-3">Poster Image:</label>
            <input type="file" name="poster" id="poster" class="form-control" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary w-auto ms-3">Add Movie</button>
    </div>
</div>

<!-- Poster Preview -->
<div class="row mb-3" id="poster_preview_container" style="display: none;">
    <div class="col-md-12 text-center">
        <label class="form-label">Poster Preview:</label>
        <img id="poster_preview" src="" alt="Poster Preview" class="img-thumbnail" style="max-width: 200px;">
    </div>
</div>

<!-- Hidden input for TMDb poster URL -->
<input type="hidden" name="poster_from_tmdb" id="poster_from_tmdb">
        </form>
    </div>
    <script src="autofill.js"></script>
</body>
</html>
