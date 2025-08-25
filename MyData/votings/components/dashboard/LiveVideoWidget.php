<?php
// LiveVideoWidget.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div class="cyber-card-bg h-full flex flex-col">
  <div class="flex flex-row items-center justify-between space-y-0 pb-2">
    <div class="text-lg font-bold text-white">Quay Video Trực Tiếp</div>
    <span id="video-status-icon" class="h-6 w-6 text-gray-500"></span>
  </div>
  <div class="flex-grow p-2">
    <div class="bg-black w-full h-full rounded-md overflow-hidden flex items-center justify-center" id="video-content">
      <!-- Nội dung video sẽ được render bằng JS -->
    </div>
  </div>
</div>
<script>
var permissionState = 'idle';
function requestCamera() {
  permissionState = 'pending';
  renderVideoContent();
  if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
      .then(function(mediaStream) {
        permissionState = 'granted';
        renderVideoContent(mediaStream);
      })
      .catch(function(err) {
        console.error('Lỗi khi truy cập camera:', err);
        permissionState = 'denied';
        renderVideoContent();
      });
  } else {
    permissionState = 'denied';
    renderVideoContent();
  }
}
function renderVideoContent(stream) {
  var container = document.getElementById('video-content');
  container.innerHTML = '';
  var icon = document.getElementById('video-status-icon');
  if (permissionState === 'granted') {
    var video = document.createElement('video');
    video.autoplay = true;
    video.playsInline = true;
    video.muted = true;
    video.className = 'w-full h-full object-cover rounded-md';
    if (stream) video.srcObject = stream;
    container.appendChild(video);
    icon.className = 'h-6 w-6 text-green-400';
    icon.innerHTML = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="6" fill="#10b981"/></svg>';
  } else if (permissionState === 'pending') {
    container.innerHTML = '<div class="text-center flex flex-col items-center justify-center h-full"><svg class="w-12 h-12 text-blue-400 animate-pulse mb-2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#3b82f6"/></svg><p class="text-sm font-semibold text-gray-300">Đang chờ kết nối...</p></div>';
    icon.className = 'h-6 w-6 text-gray-500';
    icon.innerHTML = '';
  } else {
    container.innerHTML = '<div class="flex flex-col items-center justify-center text-center h-full"><svg class="w-12 h-12 text-red-500 mb-2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#ef4444"/></svg><p class="text-sm font-semibold text-red-400">Không có tín hiệu</p><p class="text-xs text-gray-400 mt-1">Không thể truy cập camera người dùng.</p></div>';
    icon.className = 'h-6 w-6 text-gray-500';
    icon.innerHTML = '';
  }
}
document.addEventListener('DOMContentLoaded', function() {
  requestCamera();
});
</script>
