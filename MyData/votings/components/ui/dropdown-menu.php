<?php
// dropdown-menu.php - Chuyển đổi từ React sang PHP, giữ nguyên UI và logic
?>
<div class="dropdown relative">
  <button class="dropdown-toggle px-4 py-2 bg-gray-200 rounded">Chọn mục ▼</button>
  <ul class="dropdown-menu absolute hidden bg-white border rounded shadow mt-2 min-w-[120px]">
    <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer">Mục 1</li>
    <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer">Mục 2</li>
    <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer">Mục 3</li>
  </ul>
</div>
<script>
const toggle = document.querySelector('.dropdown-toggle');
const menu = document.querySelector('.dropdown-menu');
toggle.onclick = function() {
  menu.classList.toggle('hidden');
};
document.addEventListener('click', function(e) {
  if (!toggle.contains(e.target) && !menu.contains(e.target)) {
    menu.classList.add('hidden');
  }
});
</script>
