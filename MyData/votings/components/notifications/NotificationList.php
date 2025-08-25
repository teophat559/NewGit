<?php
// NotificationList.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div class="cyber-card-bg">
  <div class="p-4">
    <div class="flex justify-between items-center mb-4">
      <button id="send-notification" class="glowing-button-cyber">Gửi Thông Báo</button>
      <div class="flex items-center space-x-2">
        <button id="mark-all-read" class="border-blue-500 text-blue-400 hover:bg-blue-500/20 hover:text-blue-300 px-2 py-1">Đánh dấu đã đọc</button>
        <button id="delete-all" class="bg-red-800/50 hover:bg-red-700/50 border border-red-600 text-red-300 px-2 py-1">Xóa tất cả</button>
      </div>
    </div>
    <div id="notification-list" class="space-y-3 max-h-[65vh] overflow-y-auto pr-2">
      <!-- Danh sách thông báo sẽ được render bằng JS -->
    </div>
    <div id="no-notification" class="text-center py-12 text-gray-500" style="display:none;">
      <span style="font-size:3em;">🔔</span>
      <p>Không có thông báo nào.</p>
    </div>
  </div>
</div>
<script>
function renderNotificationList(notifications) {
  var list = document.getElementById('notification-list');
  list.innerHTML = '';
  if (!notifications || notifications.length === 0) {
    document.getElementById('no-notification').style.display = '';
    return;
  }
  document.getElementById('no-notification').style.display = 'none';
  notifications.forEach(function(n){
    // Giả sử có hàm setNotificationItem(n) đã chuyển đổi ở NotificationItem.php
    var div = document.createElement('div');
    div.className = 'flex items-center p-2 rounded hover:bg-gray-100';
    div.innerHTML = '<span class="font-medium">' + n.title + '</span><span class="ml-auto text-xs text-gray-500">' + n.time + '</span>';
    list.appendChild(div);
  });
}
// Ví dụ dữ liệu mẫu
renderNotificationList([
  { title: 'Bạn có thông báo mới', time: '1 phút trước' },
  { title: 'Cập nhật hệ thống', time: '10 phút trước' }
]);
document.getElementById('send-notification').onclick = function(){ /* logic gửi thông báo */ };
document.getElementById('mark-all-read').onclick = function(){ /* logic đánh dấu tất cả đã đọc */ };
document.getElementById('delete-all').onclick = function(){ /* logic xóa tất cả */ };
</script>
