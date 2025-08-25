<?php
// NotificationsWidget.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div class="cyber-card-bg p-4 text-slate-200 shadow-lg flex flex-col h-full">
  <h2 class="text-center font-bold text-xl mb-3 text-white" style="text-shadow: 0 0 5px #4a00e0;">Thông Báo</h2>
  <div class="flex-grow space-y-3 text-sm text-slate-300 overflow-y-auto pr-2" id="notifications-list">
    <!-- Danh sách thông báo sẽ được render bằng JS -->
  </div>
  <button class="mt-3 w-full text-cyan-400 hover:bg-cyan-500/10 hover:text-cyan-300 text-sm" onclick="viewAllNotifications()">
    Xem tất cả <span class="ml-2">→</span>
  </button>
</div>
<script>
function getNotificationIcon(type) {
  if (type === 'success') return '✔️';
  if (type === 'warning') return '⚠️';
  if (type === 'error') return '❌';
  return 'ℹ️';
}
function renderNotificationsWidget(notifications) {
  var list = document.getElementById('notifications-list');
  list.innerHTML = '';
  if (!notifications || notifications.length === 0) {
    list.innerHTML = '<div class="flex flex-col items-center justify-center h-full text-slate-500"><span style="font-size:2em;">🔔</span><p>Không có thông báo mới.</p></div>';
    return;
  }
  notifications.slice(0, 3).forEach(function(n) {
    var div = document.createElement('div');
    div.className = 'flex items-start space-x-3 p-1 rounded hover:bg-slate-700/50 transition-colors';
    div.innerHTML = `<span class="mt-0.5">${getNotificationIcon(n.type)}</span><p class="truncate flex-1" title="${n.message}">${n.message}</p>`;
    list.appendChild(div);
  });
}
function viewAllNotifications() {
  window.location.href = '/admin/notification-management/history';
}
</script>
