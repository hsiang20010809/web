<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// 檢查用戶是否為管理員
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user['is_admin']) {
    die("您沒有權限訪問此頁面。");
}

// 處理搜索
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT u.id, u.username, u.email, l.action, l.timestamp 
          FROM users u 
          LEFT JOIN user_logs l ON u.id = l.user_id
          WHERE u.username LIKE ? OR u.email LIKE ?
          ORDER BY l.timestamp DESC";
$searchTerm = "%$search%";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理員模式</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>管理員模式</h1>
        <a href="dashboard.php">返回儀表板</a>
        
        <h2>會員日誌搜索</h2>
        <form action="admin.php" method="get">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="搜索用戶名或郵箱">
            <input type="submit" value="搜索">
        </form>

        <table>
            <thead>
                <tr>
                    <th>用戶ID</th>
                    <th>用戶名</th>
                    <th>郵箱</th>
                    <th>操作</th>
                    <th>時間</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo $log['id']; ?></td>
                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                    <td><?php echo htmlspecialchars($log['email']); ?></td>
                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                    <td><?php echo $log['timestamp']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
