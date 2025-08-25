<?php
// Pagination.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div class="flex justify-end items-center space-x-2 mt-4">
  <button id="prev-page" class="border-gray-700 bg-gray-800 hover:bg-gray-700 disabled:opacity-50 px-2 py-1" onclick="changePage(-1)">←</button>
  <span id="page-info" class="text-sm text-gray-400">Trang 1 / 1</span>
  <button id="next-page" class="border-gray-700 bg-gray-800 hover:bg-gray-700 disabled:opacity-50 px-2 py-1" onclick="changePage(1)">→</button>
</div>
<script>
var currentPage = 1;
var totalPages = 1;
function updatePagination() {
  document.getElementById('page-info').textContent = 'Trang ' + currentPage + ' / ' + totalPages;
  document.getElementById('prev-page').disabled = currentPage === 1;
  document.getElementById('next-page').disabled = currentPage === totalPages;
}
function changePage(delta) {
  currentPage = Math.max(1, Math.min(totalPages, currentPage + delta));
  updatePagination();
  // ...logic chuyển trang dữ liệu...
}
document.addEventListener('DOMContentLoaded', updatePagination);
</script>
