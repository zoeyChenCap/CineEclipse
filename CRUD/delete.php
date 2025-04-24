<?php
session_start();
require('../connect.php'); // 连接数据库

// 确保用户已登录
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

// Only admin can delete movie information
$role = $_SESSION['role'];
if ($role !== 'admin') {
    echo "<script>alert('Access denied. Only admin can delete movies.');</script>";
    echo "<script>window.location.href = '../index.php';</script>";
    exit();
}

// 获取电影 ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Movie ID is missing.");
}

$movie_id = $_GET['id'];

// 先获取电影信息，检查是否存在
$query = "SELECT * FROM Movies WHERE movie_id = :movie_id";
$statement = $db->prepare($query);
$statement->bindParam(':movie_id', $movie_id, PDO::PARAM_INT);
$statement->execute();
$movie = $statement->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    die("Error: Movie not found.");
}

// 删除电影记录
$deleteQuery = "DELETE FROM Movies WHERE movie_id = :movie_id";
$deleteStmt = $db->prepare($deleteQuery);
$deleteStmt->execute([':movie_id' => $movie_id]);

$_SESSION['message'] = "Movie deleted successfully!";
echo "<script>
    alert('Movie deleted successfully!');
    window.location.href = '../backstage.php';
</script>";
exit;
