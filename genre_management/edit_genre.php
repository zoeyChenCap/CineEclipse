<?php
session_start();
require_once('../connect.php');
require_once('../authenticate.php');

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied: only Admin can manage genre data.");
}

$error = '';
$success_message = '';

// 获取 genre_id，确保 URL 中有该参数
if (!isset($_GET['genre_id']) || !is_numeric($_GET['genre_id'])) {
    die("Invalid Genre ID.");
}

$genre_id = $_GET['genre_id'];

// 获取当前 genre_name
$query = "SELECT genre_name FROM Genres WHERE genre_id = :genre_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':genre_id', $genre_id, PDO::PARAM_INT);
$stmt->execute();
$current_genre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_genre) {
    die("Genre not found.");
}

// 检查是否是表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $genre_name = $_POST['genre_name'];

    // 检查genre_name是否已经存在
    if (empty($genre_name)) {
        $error = "Please enter a genre name.";
    } else {
        // 确保忽略大小写进行检查，检查是否有重复的genre
        $query = "SELECT genre_name FROM Genres WHERE LOWER(genre_name) = LOWER(:genre_name) AND genre_id != :genre_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':genre_name', $genre_name);
        $stmt->bindParam(':genre_id', $genre_id, PDO::PARAM_INT);
        $stmt->execute();
        $existing_genre = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_genre) {
            $error = "The genre name already exists.";
        } else {
            // 更新类别数据
            $query = "UPDATE Genres SET genre_name = :genre_name WHERE genre_id = :genre_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':genre_name', $genre_name);
            $stmt->bindParam(':genre_id', $genre_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // 成功更新类别，设置成功消息
                $_SESSION['success_message'] = "Genre '$genre_name' was updated successfully.";
                header('Location: edit_genre.php?genre_id=' . $genre_id); // 重定向回当前页面
                exit();
            } else {
                // 更新失败
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
    <link rel="stylesheet" href="../style.css?v=1.0">
    <title>Edit Genre</title>
</head>
<body>

    <div class="log_form">
        <h2>Edit Genre</h2>

        <!-- 显示错误信息 -->
        <?php if ($error): ?> 
            <div class='error'><?= htmlspecialchars($error) ?></div> 
        <?php endif; ?>

        <!-- 显示成功消息 -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
            <?php unset($_SESSION['success_message']); // 显示一次后清空 ?>
        <?php endif; ?>

        <form method="POST" action="edit_genre.php?genre_id=<?= htmlspecialchars($genre_id) ?>">
            <label for="genre_name">Genre name:</label>
            <input type="text" id="genre_name" name="genre_name" value="<?= htmlspecialchars($current_genre['genre_name']) ?>" autofocus />

            <div class="double_buttons">
                <button type="submit">Update</button>
                <button type="button" onclick="window.location.href='../backstage.php'">Cancel</button>
            </div>
        </form>

        <a href="../backstage.php">Return to Backstage</a>
    </div>

</body>
</html>
