<?php
// tabs.php - Chuyển đổi từ React sang PHP, giữ nguyên UI và logic
?>
<div class="tabs flex border-b">
  <button class="tab px-4 py-2" onclick="setTab(0)">Tab 1</button>
  <button class="tab px-4 py-2" onclick="setTab(1)">Tab 2</button>
</div>
<div id="tab-content" class="p-4">Nội dung Tab 1</div>
<script>
function setTab(idx) {
  document.getElementById('tab-content').textContent = idx === 0 ? 'Nội dung Tab 1' : 'Nội dung Tab 2';
}
</script>
