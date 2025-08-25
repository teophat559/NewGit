<?php
// switch.php - Chuyển đổi từ React sang PHP, giữ nguyên UI và logic
?>
<label class="switch flex items-center cursor-pointer">
  <input type="checkbox" class="hidden" onchange="document.getElementById('switch-indicator').textContent = this.checked ? 'Bật' : 'Tắt';" />
  <span class="ml-2" id="switch-indicator">Tắt</span>
</label>
