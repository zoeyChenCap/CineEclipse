<?php
/*******w******** 
    
    Name:Zoey Chen
    Date:2025/04/24
    Description:This PHP script allows an admin to edit movie details in the database. 
                It verifies the user's session and role, fetches the current movie and 
                genre information, and processes form submissions to update the movie's 
                details. It includes validation for inputs, handles poster uploads and 
                resizing, and provides feedback to the user.

****************/

session_start();
require('../connect.php'); // Connect to the database 

require_once '../php-image-resize-master/lib/ImageResize.php';
require_once '../php-image-resize-master/lib/ImageResizeException.php';
use \Gumlet\ImageResize;

// Ensure the user is logged in
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

// Get movie ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Movie ID is missing.");
}

$movie_id = $_GET['id'];

// Fetch current movie information
$query = "SELECT * FROM Movies WHERE movie_id = :movie_id";
$statement = $db->prepare($query);
$statement->bindParam(':movie_id', $movie_id, PDO::PARAM_INT);
$statement->execute();
$movie = $statement->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    die("Error: Movie not found.");
}

// Fetch all genres
$genresQuery = "SELECT * FROM genres";
$genresStatement = $db->prepare($genresQuery);
$genresStatement->execute();
$genres = $genresStatement->fetchAll(PDO::FETCH_ASSOC);

// Generate file upload path function
function file_upload_path($original_filename, $upload_subfolder_name = 'posters') {
    $current_folder = dirname(__FILE__); // Get the current script directory
    $path_segments = [$current_folder, "..", $upload_subfolder_name, basename($original_filename)];
    return join(DIRECTORY_SEPARATOR, $path_segments);
}

$error = "";

// Process the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $type = trim(filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $runtime = trim(filter_input(INPUT_POST, 'runtime', FILTER_VALIDATE_INT));
    $release_year = trim(filter_input(INPUT_POST, 'release_year', FILTER_VALIDATE_INT));
    $language = trim(filter_input(INPUT_POST, 'language', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $country = trim(filter_input(INPUT_POST, 'country', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $genre_id = trim(filter_input(INPUT_POST, 'genre_id', FILTER_VALIDATE_INT));
    $tmdb_link = trim(filter_input(INPUT_POST, 'tmdb_link', FILTER_SANITIZE_URL));
    $remove_poster = isset($_POST['remove_poster']); // Check if the remove poster checkbox is checked

    // Process the poster submissiong or deletion
    $poster_url = $movie['poster_url']; // Using the original poster URL by default
    
    // If the admin checked the remove poster checkbox
    if ($remove_poster && !empty($movie['poster_url'])) {
        // Delete the original poster and the two thumbnails (if they exist)
        $file_paths = [
            '../' . $movie['poster_url'],  // Original poster
            '../' . $movie['poster_url_medium'],   // Medium-sized poster
            '../' . $movie['poster_url_thumb']     // Thumbnail poster
        ];
        foreach ($file_paths as $file_path) {
            if (file_exists($file_path)) {
                unlink($file_path); // Delete the file
            }
        }
        $poster_url = null; // Set to null to remove from the database
        $poster_url_medium = null; // Set medium image path to null
        $poster_url_thumb = null; // Set thumbnail image path to null
    }

    // If the admin uploaded a new poster manually
    elseif (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // Upload poster limitation is 2MB

        $fileTmpPath = $_FILES['poster']['tmp_name'];
        $fileName = $_FILES['poster']['name'];
        $fileSize = $_FILES['poster']['size'];
        $fileType = mime_content_type($fileTmpPath);

        if (!in_array($fileType, $allowed_types)) {
            $error = "Invalid file type, only JPG, PNG, and WEBP files are allowed.";
        } elseif ($fileSize > $max_size) {
            $error = "File size exceeds the limit which is 2MB.";
        } else {
            // Generate a unique filename
            $newFileName = uniqid() . '_' . basename($fileName);
            $destPath = file_upload_path($newFileName, 'posters');

            if (move_uploaded_file($fileTmpPath, $destPath)) {

                try {
                    // Medium version
                    $mediumPath = file_upload_path(pathinfo($newFileName, PATHINFO_FILENAME) . '_medium.' . pathinfo($newFileName, PATHINFO_EXTENSION), 'posters');
                    $imageMedium = new ImageResize($destPath);
                    $imageMedium->resizeToWidth(400);
                    $imageMedium->save($mediumPath);

                    // Thumbnail version
                    $thumbPath = file_upload_path(pathinfo($newFileName, PATHINFO_FILENAME) . '_thumb.' . pathinfo($newFileName, PATHINFO_EXTENSION), 'posters');
                    $imageThumb = new ImageResize($destPath);
                    $imageThumb->resizeToWidth(110);
                    $imageThumb->save($thumbPath);

                    // Delete the old poster (if any)
                    if (!empty($movie['poster_url'])) {
                        $old_file_path = '../' . $movie['poster_url'];
                        if (file_exists($old_file_path)) {
                            unlink($old_file_path);
                        }
                    }
                    // Save paths of all three images to the database
                    $poster_url = "posters/" . $newFileName; // Store the original poster path
                    $poster_url_medium = "posters/" . pathinfo($newFileName, PATHINFO_FILENAME) . "_medium." . pathinfo($newFileName, PATHINFO_EXTENSION);
                    $poster_url_thumb = "posters/" . pathinfo($newFileName, PATHINFO_FILENAME) . "_thumb." . pathinfo($newFileName, PATHINFO_EXTENSION);

                } catch (Exception $e) {
                    $error = "Image resize failed: " . $e->getMessage();
                }
            } else {
                $error = "Error: Failed to upload image.";
            }
        }
    }

    // Validate the required information
    if(empty($title) || empty($type) || empty($runtime) ||empty($release_year) || empty($language) || empty($country) || empty($genre_id)){
        $error = "Error: Incomplete movie information.";
    } elseif (!is_numeric($release_year) || $release_year < 1888 || $release_year > date("Y")){
        $error = "Error: Please enter a valid release year.";
    } elseif (!is_numeric($runtime) || $runtime <= 0) {
        $error = "Error: Please enter a valid runtime.";
    }

    // Only when the error is empty, update the movie information
    if(!empty($error)){
        echo "<script>alert('$error');</script>";
    } else {
        try {
            // Update movie information with poster URLs
            $updateQuery = "UPDATE movies 
                SET title = :title, type = :type, runtime = :runtime, release_year = :release_year, 
                    language = :language, country = :country, genre_id = :genre_id, 
                    tmdb_link = :tmdb_link, poster_url = :poster_url, poster_url_medium = :poster_url_medium, 
                    poster_url_thumb = :poster_url_thumb
                WHERE movie_id = :movie_id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([
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
                ':movie_id' => $movie_id,
            ]);

            echo "<script>alert('Movie updated successfully!'); window.location.href = '../backstage.php';</script>";
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container mt-4">
    <!-- Top navigation button -->
    <div class="d-flex justify-content-end mb-3">
        <a href="../backstage.php" class="btn btn-secondary me-2">Return to Backstage</a>
        <a href="../index.php" class="btn btn-secondary">Back to Movie List</a>
    </div>

    <h2 class="text-center mb-4">Edit Movie</h2>
    <p class="text-center text-muted">
            Edit the movie information below. You can update the details or replace the poster.
        </p>

    <!-- Display error message -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="p-4 border rounded shadow-sm bg-light form-container">
        <!-- Title, Type and Release Year -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="title" class="form-label">Title:</label>
        <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($movie['title']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="type" class="form-label">Type:</label>
        <select name="type" id="type" class="form-select" required>
            <option value="movie" <?= ($movie['type'] == 'movie') ? 'selected' : '' ?>>Movie</option>
            <option value="series" <?= ($movie['type'] == 'series') ? 'selected' : '' ?>>TV Show</option>
        </select>
                </div>
                <div class="col-md-4">
        <label for="release_year" class="form-label">Release Year:</label>
        <input type="number" name="release_year" id="release_year" class="form-control" value="<?= $movie['release_year'] ?>" required min="1888" max="<?= date('Y') ?>">
                </div>
            </div>

        <!-- Runtime, Language and Country -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="runtime" class="form-label">Runtime (minutes):</label>
        <input type="number" name="runtime" id="runtime" class="form-control" value="<?= htmlspecialchars($movie['runtime']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="language" class="form-label">Language:</label>
        <input type="text" name="language" id="language" class="form-control" value="<?= htmlspecialchars($movie['language']) ?>" required>
                </div>
                <div class="col-md-4">
        <label for="country" class="form-label">Country:</label>
        <input type="text" name="country" id="country" class="form-control" value="<?= htmlspecialchars($movie['country']) ?>" required>
                </div>
            </div>

            <!-- Genre and TMDb Link -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="genre_id" class="form-label">Genre:</label>
        <select name="genre_id" id="genre_id" class="form-select" required>
            <?php foreach ($genres as $genre): ?>
                <option value="<?= $genre['genre_id'] ?>" <?= ($genre['genre_id'] == $movie['genre_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($genre['genre_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
                </div>
                <div class="col-md-6">
        <label for="tmdb_link" class="form-label">TMDb Link:</label>
        <input type="url" name="tmdb_link" id="tmdb_link" class="form-control" value="<?= htmlspecialchars($movie['tmdb_link']) ?>">
                </div>
            </div>

            <!-- Poster Upload and Remove Poster -->
            <div class="row mb-3">
                <div class="col-md-9 d-flex align-items-center">
                    <label for="poster" class="form-label me-3">Poster Image:</label>
        <input type="file" name="poster" id="poster" class="form-control w-75" accept="image/*">
                </div>
                <div class="col-md-3 text-end">
                    <button type="submit" class="btn btn-primary w-auto">Update Movie</button>
                </div>
            </div>

            <!-- Current Poster Preview and Remove Option -->
        <?php if (!empty($movie['poster_url_thumb'])): ?>
        <div class="row mb-3">
                    <div class="col-md-12 text-center">
            <img src="../<?= htmlspecialchars($movie['poster_url_thumb']) ?>" alt="Movie Poster" class="img-thumbnail mb-2">
                        <div class="form-check d-flex align-items-center justify-content-center">
            <input type="checkbox" name="remove_poster" id="remove_poster" class="form-check-input me-2">
            <label for="remove_poster" class="form-check-label">Remove current poster</label>
                        </div>
                    </div>
                </div>
        <?php endif; ?>
    </form>
    </div>
</body>
</html>