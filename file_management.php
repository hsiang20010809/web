<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

// 處理文件上傳
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $fileName = $_FILES["file"]["name"];
    $fileSize = $_FILES["file"]["size"];
    $fileTmpName = $_FILES["file"]["tmp_name"];
    $fileType = $_FILES["file"]["type"];
    $uploadDir = "uploads/";
    $uniqueFileName = uniqid() . "_" . $fileName;
    $uploadPath = $uploadDir . $uniqueFileName;

    // 檢查文件是否已存在
    $stmt = $conn->prepare("SELECT id FROM files WHERE user_id = ? AND original_filename = ?");
    $stmt->bind_param("is", $userId, $fileName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('文件已存在，上傳取消。');</script>";
    } else {
        if (move_uploaded_file($fileTmpName, $uploadPath)) {
            $stmt = $conn->prepare("INSERT INTO files (user_id, filename, original_filename, file_size) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $userId, $uniqueFileName, $fileName, $fileSize);
            $stmt->execute();
            echo "<script>alert('文件上傳成功。');</script>";
        } else {
            echo "<script>alert('文件上傳失敗。');</script>";
        }
    }
}


// 處理文件重命名
if (isset($_POST['rename'])) {
    $fileId = $_POST['file_id'];
    $newName = $_POST['new_name'];
    
    $stmt = $conn->prepare("SELECT filename, original_filename FROM files WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $fileId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $file = $result->fetch_assoc();
        $oldName = $file['original_filename'];
        $fileExtension = pathinfo($oldName, PATHINFO_EXTENSION);
        $newNameWithExt = $newName . '.' . $fileExtension;
        
        $stmt = $conn->prepare("UPDATE files SET original_filename = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $newNameWithExt, $fileId, $userId);
        
        if ($stmt->execute()) {
            echo "<script>alert('文件名稱已成功更新。');</script>";
        } else {
            echo "<script>alert('文件名稱更新失敗。');</script>";
        }
    }
}


// 處理文件刪除
if (isset($_GET['delete'])) {
    $fileId = $_GET['delete'];
    $stmt = $conn->prepare("SELECT filename FROM files WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $fileId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $file = $result->fetch_assoc();
        $filePath = "uploads/" . $file['filename'];
        if (unlink($filePath)) {
            $stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
            $stmt->bind_param("i", $fileId);
            $stmt->execute();
            echo "<script>alert('文件刪除成功。');</script>";
        } else {
            echo "<script>alert('文件刪除失敗。');</script>";
        }
    }
}

// 獲取用戶的文件列表
$stmt = $conn->prepare("SELECT id, filename, original_filename, file_size, upload_time FROM files WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$files = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件管理</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
    function showRenameForm(fileId, currentName) {
        var newName = prompt("請輸入新的文件名稱（不包含副檔名）:", currentName.split('.').slice(0, -1).join('.'));
        if (newName != null && newName !== "") {
            document.getElementById('rename_file_id').value = fileId;
            document.getElementById('new_file_name').value = newName;
            document.getElementById('rename_form').submit();
        }
    }
    </script>
</head>
<body>
    <div class="container">
        <h1>文件管理</h1>
        <a href="dashboard.php">返回儀表板</a>
        
        <h2>上傳文件</h2>
        <form action="file_management.php" method="post" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <input type="submit" value="上傳">
        </form>

        <h2>我的文件</h2>
        <table>
            <thead>
                <tr>
                    <th>文件名</th>
                    <th>大小</th>
                    <th>上傳時間</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $file): ?>
                <tr>
                    <td><?php echo htmlspecialchars($file['original_filename']); ?></td>
                    <td><?php echo number_format($file['file_size'] / 1024, 2) . ' KB'; ?></td>
                    <td><?php echo $file['upload_time']; ?></td>
                    <td>
                        <a href="uploads/<?php echo $file['filename']; ?>" download="<?php echo $file['original_filename']; ?>">下載</a>
                        <a href="file_management.php?delete=<?php echo $file['id']; ?>" onclick="return confirm('確定要刪除這個文件嗎？');">刪除</a>
                        <button onclick="showRenameForm(<?php echo $file['id']; ?>, '<?php echo htmlspecialchars($file['original_filename']); ?>')">修改名稱</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- 隱藏的重命名表單 -->
        <form id="rename_form" action="file_management.php" method="post" style="display: none;">
            <input type="hidden" name="rename" value="1">
            <input type="hidden" id="rename_file_id" name="file_id">
            <input type="hidden" id="new_file_name" name="new_name">
        </form>
    </div>
</body>
</html>
