<?php
// DynamicStatusWidget.php - Chuyển đổi từ DynamicStatusWidget.jsx
?>
<div class="cyber-card-bg h-full flex flex-col justify-between" id="dynamic-status-widget">
  <div>
    <div class="flex flex-col items-center justify-center text-center space-y-2 pb-2">
      <div id="status-title" class="text-2xl font-bold transition-all duration-300 cursor-pointer text-gray-300">Bảng Trạng Thái</div>
    </div>
    <div class="flex space-x-4 my-6 justify-center">
      <div class="h-5 w-5 rounded-full bg-red-500" id="dot-1"></div>
      <div class="h-5 w-5 rounded-full bg-yellow-500" id="dot-2"></div>
      <div class="h-5 w-5 rounded-full bg-green-500" id="dot-3"></div>
    </div>
  </div>
  <div class="mt-auto pt-4 flex items-center justify-center space-x-4">
    <label for="blinking-toggle">Hiệu ứng nhấp nháy</label>
    <input type="checkbox" id="blinking-toggle" checked />
    <button id="add-title-btn" class="bg-blue-600 hover:bg-blue-500 text-white px-2 py-1 rounded">+</button>
    <button id="delete-title-btn" class="bg-red-600 hover:bg-red-500 text-white px-2 py-1 rounded">X</button>
  </div>
</div>
<div id="add-title-dialog" style="display:none;">
  <div class="cyber-card-bg text-white p-4 rounded">
    <h3>Tạo Tiêu Đề Mới</h3>
    <p>Nhập tên tiêu đề bạn muốn hiển thị trên widget.</p>
    <input type="text" id="new-title-input" placeholder="Ví dụ: Cảnh báo hệ thống" class="bg-slate-800/50 border-slate-700 text-white" />
    <button id="save-title-btn">Lưu</button>
    <button id="cancel-title-btn">Hủy</button>
  </div>
</div>
<script>
let titles = JSON.parse(localStorage.getItem('dynamicStatusWidget_titles') || '[{"id":1,"text":"Bảng Trạng Thái"}]');
let currentTitleIndex = 0;
let isBlinkingEnabled = JSON.parse(localStorage.getItem('dynamicStatusWidget_effectEnabled') || 'true');
function updateTitle() {
  document.getElementById('status-title').textContent = titles[currentTitleIndex]?.text || 'Bảng Trạng Thái';
}
document.getElementById('status-title').onclick = function() {
  currentTitleIndex = (currentTitleIndex + 1) % titles.length;
  updateTitle();
};
document.getElementById('blinking-toggle').checked = isBlinkingEnabled;
document.getElementById('blinking-toggle').onchange = function() {
  isBlinkingEnabled = this.checked;
  localStorage.setItem('dynamicStatusWidget_effectEnabled', JSON.stringify(isBlinkingEnabled));
};
document.getElementById('add-title-btn').onclick = function() {
  document.getElementById('add-title-dialog').style.display = 'block';
};
document.getElementById('cancel-title-btn').onclick = function() {
  document.getElementById('add-title-dialog').style.display = 'none';
};
document.getElementById('save-title-btn').onclick = function() {
  const val = document.getElementById('new-title-input').value.trim();
  if (val) {
    titles.push({ id: Date.now(), text: val });
    localStorage.setItem('dynamicStatusWidget_titles', JSON.stringify(titles));
    currentTitleIndex = titles.length - 1;
    updateTitle();
    document.getElementById('add-title-dialog').style.display = 'none';
    document.getElementById('new-title-input').value = '';
    alert('Đã tạo tiêu đề mới: ' + val);
  } else {
    alert('Tiêu đề không được để trống.');
  }
};
document.getElementById('delete-title-btn').onclick = function() {
  if (titles.length <= 1) {
    alert('Phải có ít nhất một tiêu đề.');
    return;
  }
  titles.splice(currentTitleIndex, 1);
  localStorage.setItem('dynamicStatusWidget_titles', JSON.stringify(titles));
  currentTitleIndex = Math.max(0, currentTitleIndex - 1);
  updateTitle();
  alert('Đã xóa tiêu đề.');
};
updateTitle();
function checkBlinking() {
  const now = new Date();
  const hours = now.getUTCHours();
  const minutes = now.getUTCMinutes();
  const seconds = now.getUTCSeconds();
  // Giả lập hiệu ứng nhấp nháy lúc 18:00:00 giờ Mỹ (UTC-4)
  const isBlinking = (hours === 22 && minutes === 0 && seconds < 10) && isBlinkingEnabled;
  ['dot-1','dot-2','dot-3'].forEach(id => {
    document.getElementById(id).style.boxShadow = isBlinking ? '0 0 10px 5px #fff' : '';
  });
  document.getElementById('status-title').style.textShadow = isBlinking ? '0 0 10px #fff' : '';
}
setInterval(checkBlinking, 1000);
</script>
