<?php
// log.php - Chuyển đổi từ dịch vụ ghi log React
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $activity = $_POST['activity'] ?? '';
  file_put_contents('../logs/activity.log', $activity . "\n", FILE_APPEND);
  echo 'Đã ghi lại hoạt động!';
  exit;
}
?>
