<?php
session_start();
require('../connect.php');

if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in.");
}
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $type = $_POST['type'];
    $release_year = $_POST['release_year'];
    $language = $_POST['language'];
    $country = $_POST['country'];
    $genre_id = $_POST['genre_id'];
    $tmdb_link = $_POST['tmdb_link'];

    // 处理上传的电影海报
    /*$poster_path = NULL;
    if (!empty($_FILES['poster']['name'])) {
        $upload_dir = "uploads/";
        $poster_path = $upload_dir . basename($_FILES["poster"]["name"]);
        move_uploaded_file($_FILES["poster"]["tmp_name"], $poster_path);
    }*/

    // 插入数据到 movies 表
    $query = "INSERT INTO movies (title, type, release_year, language, country, genre_id, user_id, tmdb_link) 
              VALUES (:title, :type, :release_year, :language, :country, :genre_id, :user_id, :tmdb_link)";
    $statement = $db->prepare($query);
    
    $statement->execute([
        ':title' => $title,
        ':type' => $type,
        ':release_year' => $release_year,
        ':language' => $language,
        ':country' => $country,
        ':genre_id' => $genre_id,
        ':user_id' => $user_id,
        ':tmdb_link' => $tmdb_link,
    ]);

    header("Location: ../mainpage.php"); // 跳转到主页
    exit();
}
?>
