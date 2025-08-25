<?php
// toast.php - Chuyển đổi từ React sang PHP, giữ nguyên UI và logic
?>
<div id="toast" class="fixed bottom-4 right-4 bg-black text-white px-4 py-2 rounded shadow-lg hidden">Thông báo</div>
<script>
function showToast(message, duration = 2000) {
  var toast = document.getElementById('toast');
  toast.textContent = message;
  toast.classList.remove('hidden');
  setTimeout(function(){ toast.classList.add('hidden'); }, duration);
}
// Ví dụ sử dụng: showToast('Đã lưu thành công!');
</script>
