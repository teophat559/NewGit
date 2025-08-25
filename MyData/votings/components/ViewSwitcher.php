<?php
// ViewSwitcher.php - Chuyển đổi từ ViewSwitcher.jsx
?>
<div id="view-switcher" style="position:fixed;bottom:20px;right:20px;z-index:50;">
  <button id="switch-view-btn" class="rounded-full shadow-lg bg-primary hover:bg-primary/90 text-white glowing-button-cyber" style="padding:16px 32px;font-size:1.1rem;">
    <span id="switch-view-icon">👁️</span> <span id="switch-view-label">Xem trang User</span>
  </button>
</div>
<script>
document.getElementById('switch-view-btn').onclick = function() {
  // Giả lập chuyển đổi giữa admin/user
  var label = document.getElementById('switch-view-label');
  var icon = document.getElementById('switch-view-icon');
  if (label.textContent === 'Xem trang User') {
    label.textContent = 'Về trang Admin';
    icon.textContent = '🛡️';
    window.location.href = '/vote';
  } else {
    label.textContent = 'Xem trang User';
    icon.textContent = '👁️';
    window.location.href = '/dashboard';
  }
};
</script>
