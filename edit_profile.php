<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $favoriteColor = $_POST['favorite_color'];
    
    $stmt = $conn->prepare("UPDATE users SET email = ?, gender = ?, favorite_color = ? WHERE id = ?");
    $stmt->bind_param("sssi", $email, $gender, $favoriteColor, $userId);
    
    if ($stmt->execute()) {
        $success = "會員資料更新成功。";
        logUserAction($userId, "User updated profile");
    } else {
        $error = "更新失敗，請稍後再試。";
    }
    $stmt->close();
}

// 獲取當前用戶資料
$stmt = $conn->prepare("SELECT email, gender, favorite_color FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改會員資料</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>修改會員資料</h1>
        <a href="dashboard.php">返回儀表板</a>
        
        <?php
        if (isset($success)) echo "<p style='color: green;'>$success</p>";
        if (isset($error)) echo "<p style='color: red;'>$error</p>";
        ?>

        <form action="edit_profile.php" method="post">
            <div>
                <label for="email">電子郵件：</label>
                <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
            </div>
            <div>
                <label for="gender">性別：</label>
                <select id="gender" name="gender" required>
                    <option value="male" <?php echo $user['gender'] == 'male' ? 'selected' : ''; ?>>男</option>
                    <option value="female" <?php echo $user['gender'] == 'female' ? 'selected' : ''; ?>>女</option>
                    <option value="other" <?php echo $user['gender'] == 'other' ? 'selected' : ''; ?>>其他</option>
                </select>
            </div>
            <div>
                <label for="favorite_color">喜愛的顏色：</label>
                <input type="text" id="favorite_color" name="favorite_color" value="<?php echo $user['favorite_color']; ?>" required>
            </div>
            <div>
                <input type="submit" value="更新資料">
            </div>
        </form>
    </div>
</body>
</html>
