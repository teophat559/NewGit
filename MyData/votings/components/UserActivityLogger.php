<?php
// UserActivityLogger.php - Chuyển đổi từ UserActivityLogger.jsx
?>
<div class="user-activity-logger">
  <button onclick="logActivity()">Ghi lại hoạt động</button>
</div>
<script>
function logActivity() {
  fetch('/php/services/log.php', {
    method: 'POST',
    body: new URLSearchParams({ activity: 'Người dùng vừa nhấn nút ghi lại hoạt động' })
  })
  .then(response => response.text())
  .then(data => {
    alert('Đã ghi lại hoạt động!');
  });
}
</script>
