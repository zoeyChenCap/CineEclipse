<?php
session_start();
require_once('connect.php');
require_once('authenticate.php');

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied: only Admin can manage user data.");
}

// Obtain all users (not include admin) information 
$query = "SELECT * FROM Users WHERE role = 'user' "; // Only search user accounts
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtain all movies
$query = "SELECT m.*, g.genre_name 
          FROM Movies m
          LEFT JOIN Genres g ON m.genre_id = g.genre_id
          ORDER BY m.release_year DESC";
$statement = $db->prepare($query);
$statement->execute();
$movies = $statement->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css?v=1.0">
</head>
<body>
    <div class="container mt-4">
        <div id="backstage_header">
            <h2 class="text-center">Backstage</h2>
            <a href="index.php">Back to Home Page</a>
        </div>
        
        <!-- Navigation -->
        <ul class="nav nav-tabs" id="adminTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#users">User Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#content">Content Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#categories">Category Management</a>
            </li>
        </ul>
        
        <!-- Tab 内容 -->
        <div class="tab-content mt-3">

            <!-- User Management -->
            <div id="users" class="tab-pane fade show active">
                <div class="tab_header">
                    <h3>User List</h3>
                    <a href="user_management/create_user.php" class="btn btn-create-user">Create New User</a>
                </div>

                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>User ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['user_id'] ?></td>
                                <td><?= htmlspecialchars($user['first_name']) ?></td>
                                <td><?= htmlspecialchars($user['last_name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['role']) ?></td>
                                <td>
                                    <a href="user_management/edit_users.php?user_id=<?= $user['user_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="user_management/delete_user.php?user_id=<?= $user['user_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Movie Management -->
            <div id="content" class="tab-pane fade">
                <div class="tab_header">
                    <h3>Movie Management</h3>
                    <a href="CRUD/add.php" class="btn btn-add-movie">Add Movie</a>
                </div>
                <div class="movies_container">
                    <?php foreach ($movies as $row): ?>
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
                                        <p><strong>Genre:</strong> <?= htmlspecialchars($row['genre_name'] ?? 'Unknown'); ?></p>
                                    </div>
                                    <div class="poster_container">
                                        <?php if (!empty(trim($row['poster_url']))): ?>
                                            <img src="<?= htmlspecialchars($row['poster_url']) ?>" alt="Movie Poster">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p><strong>TMDb Link:</strong> <a href="<?= htmlspecialchars($row['tmdb_link']); ?>" target="_blank"><?= htmlspecialchars_decode($row['tmdb_link']); ?></a></p>
                                <div class="double_buttons">
                                    <button class="edit-btn" onclick="location.href='CRUD/edit.php?id=<?= $row['movie_id'] ?>'">Edit</button>
                                    <button class="delete-btn" onclick="if(confirm('Are you sure you want to delete this movie?')) location.href='CRUD/delete.php?id=<?= $row['movie_id'] ?>'">Delete</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Category Management -->
            <div id="categories" class="tab-pane fade">
                <div class="tab_header">
                    <h3>Category Management</h3>
                    <a href=" " class="btn btn-edit-category">Add Category</a>
                </div>
            </div>
        </div>

</body>
</html>
