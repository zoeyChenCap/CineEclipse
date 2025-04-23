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
    $filename = pathinfo($original_filename, PATHINFO_FILENAME);
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);

    if (!empty($suffix)) {
        $filename .= $suffix;
    }

    $path_segments = [$current_folder, "..", $upload_subfolder_name, $filename . '.' . $extension];
    return join(DIRECTORY_SEPARATOR, $path_segments);
}

// Fetch movie information from TMDb via API
function fetchTMDbData($url) {
    $apiKey = '7d694c4e2a2366e2deeab57aba8c7597';
    preg_match('/movie\/(\d+)/', $url, $matches);
    if (!$matches) return null;

    $movieId = $matches[1];
    $apiUrl = "https://api.themoviedb.org/3/movie/$movieId?api_key=$apiKey&language=en-US";

    $response = file_get_contents($apiUrl);
    return $response ? json_decode($response, true) : null;
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

    if (isset($_POST['autofill']) && !empty($tmdb_link)) {
        // === 自动填充 TMDb 数据 ===
        $movieData = fetchTMDbData($tmdb_link);
        if ($movieData) {
            $title = $movieData['title'] ?? $title;
            $language = $movieData['original_language'] ?? $language;
            $release_year = $movieData['release_year'] ?? $release_year;
            $runtime = $movieData['runtime'] ?? $runtime;
            $country = $movieData['country'] ?? $country;
            $type = $movieData['tmdb_type'] ?? $type;

            foreach ($genres as $genre) {
                foreach ($movieData['genres'] as $g) {
                    if (strtolower($genre['genre_name']) === strtolower($g['name'])) {
                        $genre_id = $genre['genre_id'];
                        break 2;
                    }
                }
            }
    if (!empty($movieData['poster_path'])) {
        $posterPath = "https://image.tmdb.org/t/p/original" . $movieData['poster_path'];
        $imageData = file_get_contents($posterPath);
        $newFileName = uniqid() . '_tmdb.jpg';
        $destPath = file_upload_path($newFileName, 'posters');
        file_put_contents($destPath, $imageData);
        $poster_url = "posters/" . $newFileName;

        try {
            $mediumPath = file_upload_path($newFileName, 'posters', '_medium');
            $imageMedium = new ImageResize($destPath);
            $imageMedium->resizeToWidth(400);
            $imageMedium->save($mediumPath);
            $poster_url_medium = "posters/" . pathinfo($newFileName, PATHINFO_FILENAME) . "_medium.jpg";

            $thumbPath = file_upload_path($newFileName, 'posters', '_thumb');
            $imageThumb = new ImageResize($destPath);
            $imageThumb->resizeToWidth(110);
            $imageThumb->save($thumbPath);
            $poster_url_thumb = "posters/" . pathinfo($newFileName, PATHINFO_FILENAME) . "_thumb.jpg";
        } catch (Exception $e) {
            $error = "Image resize failed: " . $e->getMessage();
            }
        }
    }
} else {
    // Upload poster manually if TMDb link is not provided
    $image_upload_detected = empty($poster_url) && isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK;
    if ($image_upload_detected) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        $fileTmpPath = $_FILES['poster']['tmp_name'];
        $fileName = $_FILES['poster']['name'];
        $fileSize = $_FILES['poster']['size'];
        $fileType = mime_content_type($fileTmpPath);

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
                    $imageMedium = new ImageResize($destPath);
                    $imageMedium->resizeToWidth(400);
                    $imageMedium->save($mediumPath);
                    $poster_url_medium = "posters/" . pathinfo($newFileName, PATHINFO_FILENAME) . "_medium." . pathinfo($newFileName, PATHINFO_EXTENSION);

                    // Thumbnail version
                    $thumbPath = file_upload_path($newFileName, 'posters', '_thumb');
                    $imageThumb = new ImageResize($destPath);
                    $imageThumb->resizeToWidth(110);
                    $imageThumb->save($thumbPath);
                    $poster_url_thumb = "posters/" . pathinfo($newFileName, PATHINFO_FILENAME) . "_thumb." . pathinfo($newFileName, PATHINFO_EXTENSION);
                } catch (Exception $e) {
                    $error = "Image resize failed: " . $e->getMessage();
                }
            } else {
                $error = "Failed to upload image.";
            }
        }
    }
}

    // Validate required fields
    if (empty($title) || empty($type) || empty($runtime) || empty($release_year) || empty($language) || empty($country) || empty($genre_id)) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($release_year) || $release_year < 1888 || $release_year > date("Y")) {
        $error = "Please enter a valid release year.";
    } elseif (!is_numeric($runtime) || $runtime < 1) {
        $error = "Please enter a valid runtime.";
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
<form action="add.php" method="POST" enctype="multipart/form-data">
    <div>
        <label for="tmdb_link">TMDb Link:</label>
        <input type="url" name="tmdb_link" id="tmdb_link" required>
        <button type="button"  name="autofill" id="autofillBtn">Autofill from TMDb</button>
    </div>

    <div>
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" value="<?= htmlspecialchars($title) ?>" required>
    </div>

    <div>
        <label for="type">Type:</label>
        <input type="text" name="type" id="type" value="<?= htmlspecialchars($type ?? '') ?>" required>
    </div>

    <div>
        <label for="runtime">Runtime (minutes):</label>
        <input type="number" name="runtime" id="runtime" required>
    </div>

    <div>
        <label for="release_year">Release Year:</label>
        <input type="number" name="release_year" id="release_year" value="<?= htmlspecialchars($release_year ?? '') ?>" required>
    </div>

    <div>
        <label for="language">Language:</label>
        <input type="text" name="language" id="language" required>
    </div>

    <div>
        <label for="country">Country:</label>
        <input type="text" name="country" id="country" value="<?= htmlspecialchars($country ?? '') ?>" required>
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
    </div>

    <div>
        <button type="submit">Add Movie</button>
    </div>
    <a href="../backstage.php">Return to Backstage</a>
</form>
<script src="autofill.js"></script>

</body>
</html>
