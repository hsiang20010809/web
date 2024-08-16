<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $messageId = $_GET['id'];
    
    // 獲取留言信息
    $stmt = $conn->prepare("SELECT * FROM messages WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $messageId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "您沒有權限編輯這條留言或留言不存在。";
        exit;
    }
    
    $message = $result->fetch_assoc();
    $stmt->close();
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_message'])) {
    $messageId = $_POST['message_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    
    // 更新留言
    $stmt = $conn->prepare("UPDATE messages SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssii", $title, $content, $messageId, $userId);
    
    if ($stmt->execute()) {
        header("Location: message_board.php");
        exit;
    } else {
        echo "更新失敗，請稍後再試。";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯留言</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>編輯留言</h1>
        <form action="edit_message.php" method="post">
            <input type="hidden" name="edit_message" value="1">
            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
            <div>
                <label for="title">標題：</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($message['title']); ?>" required>
            </div>
            <div>
                <label for="content">內容：</label>
                <textarea id="content" name="content" required><?php echo htmlspecialchars($message['content']); ?></textarea>
            </div>
            <div>
                <input type="submit" value="更新留言">
            </div>
        </form>
        <a href="message_board.php">返回留言板</a>
    </div>
</body>
</html>
