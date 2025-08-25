<?php
// SystemStatusWidget.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div class="cyber-card-bg">
  <div class="pb-2">
    <div class="text-lg text-center text-gray-200">Hệ Thống</div>
  </div>
  <div class="flex flex-col space-y-3 pt-2">
    <div class="flex items-center space-x-2">
      <div class="relative flex items-center justify-center h-4 w-4">
        <div class="absolute h-4 w-4 rounded-full bg-green-500 opacity-75 animate-ping"></div>
        <div class="relative h-2.5 w-2.5 rounded-full bg-green-500"></div>
      </div>
      <span class="text-sm text-gray-300">Online</span>
    </div>
    <div class="flex items-center space-x-2">
      <div class="relative flex items-center justify-center h-4 w-4">
        <div class="relative h-2.5 w-2.5 rounded-full bg-blue-500"></div>
      </div>
      <span class="text-sm text-gray-300">Đang chạy</span>
    </div>
    <div class="flex items-center space-x-2">
      <div class="relative flex items-center justify-center h-4 w-4">
        <div class="relative h-2.5 w-2.5 rounded-full bg-yellow-500"></div>
      </div>
      <span class="text-sm text-gray-300">Chờ OTP</span>
    </div>
    <div class="flex items-center space-x-2">
      <div class="relative flex items-center justify-center h-4 w-4">
        <div class="relative h-2.5 w-2.5 rounded-full bg-red-500"></div>
      </div>
      <span class="text-sm text-gray-300">Bị lắc</span>
    </div>
  </div>
</div>
