<?php
/*******w******** 
    
    Name:Zoey Chen
    Date:2025/04/24
    Description:This PHP script serves as the admin dashboard for managing users, movies, 
                and genres. It verifies the admin's session and role, handles AJAX requests 
                for sorting movies, and retrieves data for users, movies, and genres from 
                the database. The script provides tab-based navigation for managing user 
                accounts, movie records, and genres, with options to create, edit, or delete entries.

****************/

session_start();
require_once('connect.php');
require_once('authenticate.php');

// Set the active tab by default
$active_tab = $_GET['tab'] ?? '#users';

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied: only Admin can manage user data.");
}

// Handle AJAX request for sorting movies (before querying user data)
if (isset($_GET['sort_column']) && isset($_GET['sort_order'])) {
    header('Content-Type: application/json');

    // Handle sorting parameters for AJAX
    $allowed_columns = ['title', 'release_year', 'runtime', 'created_at'];
    $allowed_orders = ['ASC', 'DESC'];

    $sort_column = in_array($_GET['sort_column'], $allowed_columns) 
        ? $_GET['sort_column'] 
        : 'release_year';
        
    $sort_order = in_array(strtoupper($_GET['sort_order']), $allowed_orders) 
        ? strtoupper($_GET['sort_order']) 
        : 'DESC';

    // Obtain all movies
    $query = "SELECT m.*, g.genre_name 
              FROM Movies m
              LEFT JOIN Genres g ON m.genre_id = g.genre_id
              ORDER BY $sort_column $sort_order";
    // Prepare and execute the statement
    $statement = $db->prepare($query);
    $statement->execute();
    $movies = $statement->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($movies);
    exit; // Terminate the script, do not output subsequent HTML
    }

// Normal page load
$sort_column = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';

// Obtain all users (not include admin) information (only executed for normal page requests)
$query = "SELECT * FROM Users WHERE role = 'user' "; // Only search user accounts
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtain all genres from genre table (only executed for normal page requests)
$query = "SELECT * FROM Genres ORDER BY genre_id ASC"; 
$stmt = $db->prepare($query);
$stmt->execute();
$genres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtain movie data (used for initial page load)
$query = "SELECT m.*, g.genre_name 
          FROM Movies m
          LEFT JOIN Genres g ON m.genre_id = g.genre_id
          ORDER BY $sort_column $sort_order";
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
                <a class="nav-link <?= $active_tab == '#users' ? 'active' : '' ?>" data-bs-toggle="tab" href="#users">User Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab == '#content' ? 'active' : '' ?>" data-bs-toggle="tab" href="#content">Content Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab == '#genres' ? 'active' : '' ?>" data-bs-toggle="tab" href="#genres">Genre Management</a>
            </li>
        </ul>
        
        <!-- Tab Content -->
        <div class="tab-content mt-3">

            <!-- User Management -->
            <div id="users" class="tab-pane fade show active">
                <div class="tab_header">
                    <h3>User List</h3>
                    <a href="user_management/create_user.php" class="btn btn-primary">Create New User</a>
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
                    <a href="CRUD/add.php" class="btn btn-primary">Add Movie</a>
                </div>

                <form class="sort_movies" id="sortMoviesForm">
                    <label for="sort_column"><strong>Sort by:</strong></label>
                    <select name="sort_column" id="sort_column">
                        <option value="title">Title</option>
                        <option value="release_year">Release Year</option>
                        <option value="runtime">Runtime</option>
                        <option value="created_at">Created Time</option>
                    </select>
                    <label for="sort_order"><strong>Order:</strong></label>
                    <select name="sort_order" id="sort_order">
                        <option value="ASC">Ascending</option>
                        <option value="DESC">Descending</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Sort</button>
                </form>

                <!-- Movie list will be rendered here -->
                <div id="moviesContainer" class="movies_container"></div>
            </div>
                
                

            <!-- Genre Management -->
            <div id="genres" class="tab-pane fade">
                <div class="tab_header">
                    <h3>Genre Management</h3>
                    <a href="genre_management/add_genre.php " class="btn btn-primary">Add Genre</a>
                </div>
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Genre ID</th>
                            <th>Genre Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($genres as $genre): ?>
                            <tr>
                                <td><?= $genre['genre_id'] ?></td>
                                <td><?= htmlspecialchars($genre['genre_name']) ?></td>
                                <td>
                                    <a href="genre_management/edit_genre.php?genre_id=<?= $genre['genre_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>


<script src="script.js" defer></script>      
</body>
</html>
