<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '用戶未登錄']);
    exit;
}

$conn = getDBConnection();

try {
    $userId = $_SESSION['user_id'];
    
    // 獲取當前訂閱狀態
    $stmt = $conn->prepare("SELECT is_subscribed FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // 切換訂閱狀態
    $newStatus = $user['is_subscribed'] ? 0 : 1;
    
    $stmt = $conn->prepare("UPDATE users SET is_subscribed = ? WHERE id = ?");
    $stmt->bind_param("ii", $newStatus, $userId);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true, 
            'message' => $newStatus ? '成功完成作業' : '成功取消訂閱',
            'isSubscribed' => (bool)$newStatus
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '更新失敗']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '發生錯誤：' . $e->getMessage()]);
} finally {
    $conn->close();
}
