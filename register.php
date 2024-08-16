<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $favorite_color = $_POST['favorite_color'];

    $conn = getDBConnection();
    
    // 檢查用戶名是否已存在
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = "用戶名已存在，請選擇另一個用戶名。";
    } else {
        // 檢查是否為第一個用戶
        $sql = "SELECT COUNT(*) as count FROM users";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        
        $is_admin = ($row['count'] == 2) ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO users (username, password, email, gender, favorite_color, is_admin) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $username, $password, $email, $gender, $favorite_color, $is_admin);
        
        if ($stmt->execute()) {
            if ($is_admin) {
                $success = "您已成功註冊為管理員帳號！";
            } else {
                $success = "註冊成功！";
            }
            header("Location: login.php?message=" . urlencode($success));
            exit();
        } else {
            $error = "註冊失敗，請稍後再試。";
        }
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
    <title>註冊</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>註冊</h1>
        <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
        <form action="register.php" method="post">
            <div>
                <label for="username">用戶名：</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">密碼：</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <label for="email">電子郵件：</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="gender">性別：</label>
                <select id="gender" name="gender" required>
                    <option value="male">男</option>
                    <option value="female">女</option>
                    <option value="other">其他</option>
                </select>
            </div>
            <div>
                <label for="favorite_color">喜愛的顏色：</label>
                <input type="text" id="favorite_color" name="favorite_color" required>
            </div>
            <div>
                <input type="submit" value="註冊">
            </div>
        </form>
        <p>已有帳號？<a href="login.php">登入</a></p>
    </div>
</body>
</html>
