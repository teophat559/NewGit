<?php
// LiveVideoFeed.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div class="cyber-card-bg overflow-hidden">
  <div class="flex flex-row items-center justify-between space-y-0 pb-2">
    <div class="text-lg font-bold text-white">Khung hình Video Trực tiếp</div>
    <div class="flex items-center space-x-2">
      <span id="live-indicator" style="display:none;">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
      </span>
      <span id="live-status" class="font-mono text-sm text-gray-400">OFFLINE</span>
    </div>
  </div>
  <div>
    <div class="aspect-video bg-black rounded-md flex items-center justify-center border border-slate-700">
      <div class="text-center text-gray-500">
        <svg class="mx-auto h-12 w-12" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#888" /></svg>
        <p class="mt-2 text-sm" id="video-message">Chưa có tín hiệu</p>
      </div>
    </div>
  </div>
  <div class="flex justify-end gap-2 pt-2">
    <button id="start-record" class="border-green-500 text-green-400 hover:bg-green-500/10 hover:text-green-300 px-4 py-2" onclick="handleStartRecording()">Bắt đầu Quay</button>
    <button id="stop-record" class="px-4 py-2 text-red-400" onclick="handleStopRecording()" disabled>Dừng Quay</button>
  </div>
</div>
<script>
var isRecording = false;
function handleStartRecording() {
  isRecording = true;
  document.getElementById('live-indicator').style.display = '';
  document.getElementById('live-status').textContent = 'LIVE';
  document.getElementById('live-status').classList.add('text-red-400');
  document.getElementById('start-record').disabled = true;
  document.getElementById('stop-record').disabled = false;
  document.getElementById('video-message').textContent = 'Đang nhận tín hiệu từ user...';
  alert('Đã gửi yêu cầu bắt đầu quay video!');
}
function handleStopRecording() {
  isRecording = false;
  document.getElementById('live-indicator').style.display = 'none';
  document.getElementById('live-status').textContent = 'OFFLINE';
  document.getElementById('live-status').classList.remove('text-red-400');
  document.getElementById('start-record').disabled = false;
  document.getElementById('stop-record').disabled = true;
  document.getElementById('video-message').textContent = 'Chưa có tín hiệu';
  alert('Đã gửi yêu cầu dừng quay video.');
}
</script>
