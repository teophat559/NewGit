<?php
// VideoRecordWidget.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div class="cyber-card-bg h-full flex flex-col">
  <div class="flex flex-row items-center justify-between space-y-0 pb-2">
    <div id="video-record-title" class="text-lg font-bold transition-all duration-300 text-gray-300">Quay Video</div>
    <span id="video-record-icon" class="h-6 w-6 text-gray-500">📹</span>
  </div>
  <div class="flex-grow flex flex-col justify-between">
    <div>
      <div class="flex space-x-2 my-4">
        <div id="dot-1-record" class="h-3 w-3 rounded-full bg-red-500"></div>
        <div id="dot-2-record" class="h-3 w-3 rounded-full bg-yellow-500"></div>
        <div id="dot-3-record" class="h-3 w-3 rounded-full bg-green-500"></div>
      </div>
      <p id="video-record-message" class="text-xs text-gray-400">Không có hoạt động</p>
    </div>
    <div class="mt-auto pt-4 space-y-4">
      <div class="flex items-center space-x-2">
        <input type="checkbox" id="blinking-toggle-record" checked onclick="toggleBlinkingRecord()">
        <label for="blinking-toggle-record" class="text-sm text-gray-300"><span id="power-icon-record" class="h-4 w-4 inline-block mr-1 text-green-400">🔌</span>Bật/Tắt hiệu ứng</label>
      </div>
      <div class="flex space-x-2">
        <button onclick="handleCreateRecord()" class="flex-1">➕ Tạo</button>
        <button onclick="handleDeleteRecord()" class="flex-1 text-red-400">🗑️ Xóa</button>
      </div>
    </div>
  </div>
</div>
<script>
var isBlinkingRecord = true;
function toggleBlinkingRecord() {
  isBlinkingRecord = !isBlinkingRecord;
  document.getElementById('power-icon-record').textContent = isBlinkingRecord ? '🔌' : '🔌';
}
function handleCreateRecord() {
  alert('🚧 Tính năng đang được phát triển. Chức năng tạo bản ghi video sẽ sớm được ra mắt!');
}
function handleDeleteRecord() {
  alert('🚧 Tính năng đang được phát triển. Chức năng xóa bản ghi video sẽ sớm được ra mắt!');
}
</script>
