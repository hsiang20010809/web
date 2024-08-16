<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

// 處理發布新訊息
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_message'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    
    $stmt = $conn->prepare("INSERT INTO messages (user_id, title, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $title, $content);
    $stmt->execute();
    $stmt->close();
}

// 處理回復訊息
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply'])) {
    $messageId = $_POST['message_id'];
    $content = $_POST['reply_content'];
    
    $stmt = $conn->prepare("INSERT INTO replies (message_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $messageId, $userId, $content);
    $stmt->execute();
    $stmt->close();
}

// 處理刪除訊息
if (isset($_GET['delete_message'])) {
    $messageId = $_GET['delete_message'];
    
    // 開始事務
    $conn->begin_transaction();
    
    try {
        // 首先刪除相關的回覆
        $stmt = $conn->prepare("DELETE FROM replies WHERE message_id = ?");
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        $stmt->close();
        
        // 然後刪除留言
        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $messageId, $userId);
        $stmt->execute();
        $stmt->close();
        
        // 提交事務
        $conn->commit();
    } catch (Exception $e) {
        // 如果出現錯誤，回滾事務
        $conn->rollback();
        echo "刪除失敗：" . $e->getMessage();
    }
}

// 處理刪除回復
if (isset($_GET['delete_reply'])) {
    $replyId = $_GET['delete_reply'];
    $stmt = $conn->prepare("DELETE FROM replies WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $replyId, $userId);
    $stmt->execute();
    $stmt->close();
}

// 處理編輯訊息
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_message'])) {
    $messageId = $_POST['message_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    
    $stmt = $conn->prepare("UPDATE messages SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssii", $title, $content, $messageId, $userId);
    $stmt->execute();
    $stmt->close();
}

// 獲取所有訊息和回復
$stmt = $conn->prepare("
    SELECT m.id, m.user_id, m.title, m.content, m.created_at, m.updated_at, u.username,
    (SELECT COUNT(*) FROM replies WHERE message_id = m.id) as reply_count
    FROM messages m
    JOIN users u ON m.user_id = u.id
    ORDER BY m.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>留言板</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>留言板</h1>
        <a href="dashboard.php">返回儀表板</a>
        
        <h2>發布新訊息</h2>
        <form action="message_board.php" method="post">
            <input type="hidden" name="new_message" value="1">
            <div>
                <label for="title">標題：</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div>
                <label for="content">內容：</label>
                <textarea id="content" name="content" required></textarea>
            </div>
            <div>
                <input type="submit" value="發布">
            </div>
        </form>

        <h2>訊息列表</h2>
        <?php foreach ($messages as $message): ?>
            <div class="message">
                <h3><?php echo htmlspecialchars($message['title']); ?></h3>
                <p>作者：<?php echo htmlspecialchars($message['username']); ?></p>
                <p>發布時間：<?php echo $message['created_at']; ?></p>
                <?php if ($message['updated_at'] != $message['created_at']): ?>
                    <p>最後更新：<?php echo $message['updated_at']; ?></p>
                <?php endif; ?>
                <p><?php echo nl2br(htmlspecialchars($message['content'])); ?></p>
                <?php if ($message['user_id'] == $userId): ?>
                    <a href="edit_message.php?id=<?php echo $message['id']; ?>">編輯</a>
                    <a href="message_board.php?delete_message=<?php echo $message['id']; ?>" onclick="return confirm('確定要刪除這條訊息嗎？');">刪除</a>
                <?php endif; ?>
                
                <h4>回復 (<?php echo $message['reply_count']; ?>)</h4>
                <?php
                $conn = getDBConnection();
                $stmt = $conn->prepare("
                    SELECT r.id, r.user_id, r.content, r.created_at, r.updated_at, u.username
                    FROM replies r
                    JOIN users u ON r.user_id = u.id
                    WHERE r.message_id = ?
                    ORDER BY r.created_at ASC
                ");
                $stmt->bind_param("i", $message['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $replies = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                $conn->close();
                ?>
                
                <?php foreach ($replies as $reply): ?>
                    <div class="reply">
                        <p><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                        <p>回復者：<?php echo htmlspecialchars($reply['username']); ?></p>
                        <p>回復時間：<?php echo $reply['created_at']; ?></p>
                        <?php if ($reply['updated_at'] != $reply['created_at']): ?>
                            <p>最後更新：<?php echo $reply['updated_at']; ?></p>
                        <?php endif; ?>
                        <?php if ($reply['user_id'] == $userId): ?>
                            <a href="message_board.php?delete_reply=<?php echo $reply['id']; ?>" onclick="return confirm('確定要刪除這條回復嗎？');">刪除</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <form action="message_board.php" method="post">
                    <input type="hidden" name="reply" value="1">
                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                    <div>
                        <label for="reply_content">回復：</label>
                        <textarea id="reply_content" name="reply_content" required></textarea>
                    </div>
                    <div>
                        <input type="submit" value="發布回復">
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
