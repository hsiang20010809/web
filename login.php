<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

//session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin']; // 設置 is_admin
            logUserAction($user['id'], "User logged in");
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "密碼錯誤";
        }
    } else {
        $error = "用戶名不存在";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>登入</h1>
        <?php
        // 添加顯示管理員成功消息的代碼
        if (isset($_GET['message'])) {
            echo "<p style='color: green;'>" . htmlspecialchars($_GET['message']) . "</p>";
        }
        ?>
        <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
        <form action="login.php" method="post">
            <div>
                <label for="username">用戶名：</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">密碼：</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <input type="submit" value="登入">
            </div>
        </form>
        <p>還沒有帳號？<a href="register.php">註冊</a></p>
    </div>
</body>
</html>
