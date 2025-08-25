<?php
// toaster.php - Chuyển đổi từ React sang PHP, giữ nguyên UI và logic
?>
<div id="toaster" class="fixed bottom-4 right-4 space-y-2"></div>
<script>
function showToaster(messages, duration = 2000) {
  var toaster = document.getElementById('toaster');
  toaster.innerHTML = '';
  messages.forEach(function(msg) {
    var div = document.createElement('div');
    div.className = 'bg-black text-white px-4 py-2 rounded shadow-lg';
    div.textContent = msg;
    toaster.appendChild(div);
    setTimeout(function(){ div.remove(); }, duration);
  });
}
// Ví dụ sử dụng: showToaster(['Thông báo 1', 'Thông báo 2']);
</script>
