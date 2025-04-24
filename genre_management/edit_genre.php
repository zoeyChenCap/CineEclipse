<?php
/*******w******** 
    
    Name:Zoey Chen
    Date:2025/04/24
    Description:This PHP script allows an admin to edit an existing genre in the database. 
                It verifies the user's session and role, fetches the current genre details, 
                and processes form submissions to update the genre name. The script includes 
                validation to prevent duplicate genre names (case-insensitive) and provides 
                feedback to the user with error or success messages.

****************/

session_start();
require_once('../connect.php');
require_once('../authenticate.php');

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied: only Admin can manage genre data.");
}

$error = '';
$success_message = '';

// Get genre_id, ensure the parameter exists in the URL
if (!isset($_GET['genre_id']) || !is_numeric($_GET['genre_id'])) {
    die("Invalid Genre ID.");
}

$genre_id = $_GET['genre_id'];

// Fetch the current genre_name
$query = "SELECT genre_name FROM Genres WHERE genre_id = :genre_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':genre_id', $genre_id, PDO::PARAM_INT);
$stmt->execute();
$current_genre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_genre) {
    die("Genre not found.");
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $genre_name = $_POST['genre_name'];

    // Check if the genre name already exists
    if (empty($genre_name)) {
        $error = "Please enter a genre name.";
    } else {
        // Ensure case-insensitive check for duplicate genres
        $query = "SELECT genre_name FROM Genres WHERE LOWER(genre_name) = LOWER(:genre_name) AND genre_id != :genre_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':genre_name', $genre_name);
        $stmt->bindParam(':genre_id', $genre_id, PDO::PARAM_INT);
        $stmt->execute();
        $existing_genre = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_genre) {
            $error = "The genre name already exists.";
        } else {
            // Update genre data
            $query = "UPDATE Genres SET genre_name = :genre_name WHERE genre_id = :genre_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':genre_name', $genre_name);
            $stmt->bindParam(':genre_id', $genre_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Successfully updated the genre, set success message
                $_SESSION['success_message'] = "Genre '$genre_name' was updated successfully.";
                header('Location: edit_genre.php?genre_id=' . $genre_id); // 重定向回当前页面
                exit();
            } else {
                // Update failed
                $error = "Error: Could not update genre.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style.css?v=1.0">
    <title>Edit Genre</title>
</head>
<body>
    <div class="container mt-4">
        <!-- Top navigation button -->
        <div class="d-flex justify-content-end mb-3">
            <a href="../backstage.php" class="btn btn-secondary me-2">Return to Backstage</a>
        </div>

        <h2 class="text-center mb-4">Edit Genre</h2>
        <p class="text-center text-muted">
            Update the genre name below and click "Update" to save changes.
        </p>

        <!-- Display error message -->
        <?php if ($error): ?> 
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> 
        <?php endif; ?>

        <!-- Display success message -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
            <?php unset($_SESSION['success_message']); // Clear after displaying once ?>
        <?php endif; ?>

        <form method="POST" action="edit_genre.php?genre_id=<?= htmlspecialchars($genre_id) ?>" class="p-4 border rounded shadow-sm bg-light form-container">
            <!-- Genre Name -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="genre_name" class="form-label">Genre Name:</label>
                    <input type="text" id="genre_name" name="genre_name" class="form-control" value="<?= htmlspecialchars($current_genre['genre_name']) ?>" autofocus required>
                </div>
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary" style="width: 100px;">Update</button>
                <button type="button" class="btn btn-secondary" style="width: 100px;" onclick="window.location.href='../backstage.php'">Cancel</button>
            </div>
        </form>
    </div>
</body>
</html>
