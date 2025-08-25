<?php
// NotificationItem.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div id="notification-item" class="flex items-start space-x-4 p-4 rounded-lg border bg-blue-900/30 border-blue-500/50">
  <div class="flex-1">
    <div class="flex items-center justify-between">
      <span id="notification-type" class="badge">INFO</span>
      <span id="notification-time" class="text-xs text-gray-400">--:--</span>
    </div>
    <p id="notification-message" class="mt-2 text-white">Nội dung thông báo</p>
  </div>
  <div class="flex flex-col space-y-2">
    <button id="mark-as-read" class="text-gray-400 hover:text-white">Đã đọc</button>
    <button id="delete-notification" class="text-red-500 hover:text-red-400">🗑️</button>
  </div>
</div>
<script>
function setNotificationItem(notification) {
  var type = notification.type || 'info';
  document.getElementById('notification-type').textContent = type.toUpperCase();
  document.getElementById('notification-time').textContent = notification.time || '--:--';
  document.getElementById('notification-message').textContent = notification.message || '';
  if (notification.read) {
    document.getElementById('notification-item').classList.add('bg-gray-800/30','border-gray-700/50');
    document.getElementById('notification-item').classList.remove('bg-blue-900/30','border-blue-500/50');
    document.getElementById('notification-message').classList.add('text-gray-400');
    document.getElementById('notification-message').classList.remove('text-white');
    document.getElementById('notification-time').classList.add('text-gray-500');
    document.getElementById('notification-time').classList.remove('text-gray-400');
    document.getElementById('mark-as-read').style.display = 'none';
  } else {
    document.getElementById('notification-item').classList.add('bg-blue-900/30','border-blue-500/50');
    document.getElementById('notification-item').classList.remove('bg-gray-800/30','border-gray-700/50');
    document.getElementById('notification-message').classList.add('text-white');
    document.getElementById('notification-message').classList.remove('text-gray-400');
    document.getElementById('notification-time').classList.add('text-gray-400');
    document.getElementById('notification-time').classList.remove('text-gray-500');
    document.getElementById('mark-as-read').style.display = '';
  }
}
document.getElementById('mark-as-read').onclick = function(){ /* logic đánh dấu đã đọc */ };
document.getElementById('delete-notification').onclick = function(){ /* logic xóa thông báo */ };
</script>
