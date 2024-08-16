<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

$conn = getDBConnection();

// 檢查用戶是否已訂閱
$stmt = $conn->prepare("SELECT is_subscribed FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$isSubscribed = $user['is_subscribed'];
$stmt->close();

$stmt = $conn->prepare("SELECT id, username, email, gender, favorite_color FROM users");
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>儀表板</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>歡迎，<?php echo $_SESSION['username']; ?>！</h1>
        <nav>
            <ul>
                <li><a href="file_management.php">檔案管理</a></li>
                <li><a href="edit_profile.php">修改會員資料</a></li>
                <li><a href="message_board.php">留言板</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="admin.php">管理員模式</a></li>
                <?php endif; ?>
                <li><a href="logout.php">登出</a></li>
            </ul>
        </nav>
        
        <!-- 新增訂閱按鈕 -->
        <button id="subscribeBtn" onclick="toggleSubscription()">
            <?php echo $isSubscribed ? '取消訂閱' : '訂閱'; ?>
        </button>

        <h2>會員列表</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用戶名</th>
                    <th>電子郵件</th>
                    <th>性別</th>
                    <th>喜愛的顏色</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo $user['username']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo $user['gender']; ?></td>
                    <td><?php echo $user['favorite_color']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
