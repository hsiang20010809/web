<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

if (isset($_GET['id']) && isset($_GET['new_name'])) {
  $fileId = $_GET['id'];
  $newName = $_GET['new_name'];
  
  $conn = getDBConnection();
  
  // 獲取當前文件信息
  $stmt = $conn->prepare("SELECT filename FROM files WHERE id = ? AND user_id = ?");
  $stmt->bind_param("ii", $fileId, $_SESSION['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($row = $result->fetch_assoc()) {
    $oldName = $row['filename'];
    $fileExtension = pathinfo($oldName, PATHINFO_EXTENSION);
    $newNameWithExt = $newName . '.' . $fileExtension;
    
    // 更新數據庫
    $updateStmt = $conn->prepare("UPDATE files SET filename = ? WHERE id = ? AND user_id = ?");
    $updateStmt->bind_param("sii", $newNameWithExt, $fileId, $_SESSION['user_id']);
    
    if ($updateStmt->execute()) {
      // 更新實際文件系統中的文件名
      $oldPath = "uploads/" . $oldName;
      $newPath = "uploads/" . $newNameWithExt;
      if (rename($oldPath, $newPath)) {
        header("Location: file_management.php?message=文件名稱已成功更新");
      } else {
        header("Location: file_management.php?error=文件系統更新失敗");
      }
    } else {
      header("Location: file_management.php?error=數據庫更新失敗");
    }
    
    $updateStmt->close();
  } else {
    header("Location: file_management.php?error=文件不存在或無權訪問");
  }
  
  $stmt->close();
  $conn->close();
} else {
  header("Location: file_management.php?error=參數錯誤");
}
?>
