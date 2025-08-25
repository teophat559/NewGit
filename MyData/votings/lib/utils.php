<?php
// utils.php - Chuyển đổi từ utils.js
function formatDate($date) {
  return date('d/m/Y', strtotime($date));
}
?>
