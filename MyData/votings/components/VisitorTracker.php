<?php
// VisitorTracker.php - Chuyển đổi từ VisitorTracker.jsx
?>
<div class="visitor-tracker">
  <p>Số lượt truy cập: <span id="visitorCount">0</span></p>
</div>
</script>
<script>
// Ghi nhận lượt truy cập mới
function trackVisitor() {
  var notifications = JSON.parse(localStorage.getItem('systemNotifications') || '[]');
  notifications.push({
    type: 'info',
    message: 'Có một lượt truy cập mới trên website.',
    time: new Date().toISOString()
  });
  localStorage.setItem('systemNotifications', JSON.stringify(notifications));
}
trackVisitor();
</script>
</script>
