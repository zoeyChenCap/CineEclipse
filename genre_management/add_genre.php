<?php
session_start();
require_once('../connect.php');
require_once('../authenticate.php');

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied: only Admin can manage genre data.");
}

$error = '';
$success_message = '';

// 检查是否是表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $genre_name = $_POST['genre_name'];

    // 检查genre_name是否已经存在
    if (empty($genre_name)) {
        $error = "Please enter a genre name.";
    } else {
        // 确保忽略大小写进行检查
        $query = "SELECT genre_name FROM Genres WHERE LOWER(genre_name) = LOWER(:genre_name)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':genre_name', $genre_name);
        $stmt->execute();
        $existing_genre = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_genre) {
            $error = "The genre name already exists.";
        } else {
            // 插入新类别数据
            $query = "INSERT INTO Genres (genre_name) VALUES (:genre_name)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':genre_name', $genre_name);

            if ($stmt->execute()) {
                // 成功创建新类别，设置成功消息
                $_SESSION['success_message'] = "Genre '$genre_name' was added successfully.";
                header('Location: add_genre.php'); // 重定向回当前页面
                exit();
            } else {
                // 插入失败
                $error = "Error: Could not create genre.";
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
    <title>Add New Genre</title>
</head>
<body>
    <div class="container mt-4">
        <!-- 顶部导航按钮 -->
        <div class="d-flex justify-content-end mb-3">
            <a href="../backstage.php" class="btn btn-secondary me-2">Return to Backstage</a>
        </div>

        <h2 class="text-center mb-4">Add New Genre</h2>
        <p class="text-center text-muted">
            Add a new genre to the database by entering its name below.
        </p>

        <!-- 显示错误信息 -->
        <?php if ($error): ?> 
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> 
        <?php endif; ?>

        <!-- 显示成功消息 -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
            <?php unset($_SESSION['success_message']); // 显示一次后清空 ?>
        <?php endif; ?>

        <form method="POST" action="add_genre.php" class="p-4 border rounded shadow-sm bg-light form-container">
            <!-- Genre Name -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="genre_name" class="form-label">Genre Name:</label>
                    <input type="text" id="genre_name" name="genre_name" class="form-control" value="" autofocus required>
                </div>
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary" style="width: 100px;">Add</button>
                <button type="button" class="btn btn-secondary" style="width: 100px;" onclick="window.location.href='../backstage.php'">Cancel</button>
            </div>
        </form>
    </div>
</body>
</html>
