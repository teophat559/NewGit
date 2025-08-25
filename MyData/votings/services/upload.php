<?php
// upload.php - Chuyển đổi từ dịch vụ upload React
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
  $targetDir = '../uploads/';
  if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
  }
  $targetFile = $targetDir . basename($_FILES['image']['name']);
  if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Lỗi upload: ' . $_FILES['image']['error']]);
    exit;
  }
  if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
    echo json_encode(['success' => true, 'message' => 'Tải lên thành công!', 'file' => basename($_FILES['image']['name'])]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Tải lên thất bại!']);
  }
  exit;
}
?>
