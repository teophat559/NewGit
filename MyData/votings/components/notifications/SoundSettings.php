<?php
// SoundSettings.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div class="cyber-card-bg">
  <div class="header">
    <div class="title text-white">Cài đặt âm thanh</div>
    <div class="description text-slate-400">Tùy chỉnh âm thanh cho các loại thông báo.</div>
  </div>
  <div class="content space-y-6">
    <div class="flex items-center justify-between">
      <label for="admin-sound-enabled" class="text-white">Âm thanh Admin</label>
      <input type="checkbox" id="admin-sound-enabled" checked>
    </div>
    <div class="space-y-2">
      <label class="text-white/70 text-sm">Chuông báo Admin Login</label>
      <div class="flex items-center space-x-2">
        <select id="admin-sound-select" class="w-full bg-slate-800/50 border-slate-700"></select>
        <button id="edit-admin-sound" class="text-yellow-400 hover:text-yellow-300">Sửa</button>
        <button id="delete-admin-sound" class="text-red-500 hover:text-red-400">Xóa</button>
      </div>
    </div>
    <div class="flex items-center justify-between border-t border-slate-700 pt-6">
      <label for="user-sound-enabled" class="text-white">Âm thanh User</label>
      <input type="checkbox" id="user-sound-enabled" checked>
    </div>
    <div class="space-y-2">
      <label class="text-white/70 text-sm">Chuông báo User Login</label>
      <div class="flex items-center space-x-2">
        <select id="user-sound-select" class="w-full bg-slate-800/50 border-slate-700"></select>
        <button id="edit-user-sound" class="text-yellow-400 hover:text-yellow-300">Sửa</button>
        <button id="delete-user-sound" class="text-red-500 hover:text-red-400">Xóa</button>
      </div>
    </div>
    <div class="space-y-4 border-t border-slate-700 pt-6">
      <label class="text-white">Thêm Âm Thanh Mới</label>
      <div class="space-y-2">
        <label for="custom-sound-name" class="text-sm text-slate-400">Tên âm thanh</label>
        <input id="custom-sound-name" placeholder="VD: Chuông báo thành công" class="bg-slate-800/50 border-slate-700" />
      </div>
      <div class="space-y-2">
        <label for="custom-sound-url" class="text-sm text-slate-400">Link âm thanh</label>
        <input id="custom-sound-url" placeholder="Dán link file âm thanh (.mp3, .wav)..." class="bg-slate-800/50 border-slate-700" />
      </div>
      <div class="flex justify-end space-x-2 pt-2">
        <button id="save-custom-sound" class="glowing-button-cyber">Lưu</button>
      </div>
    </div>
  </div>
</div>
<script>
// ...logic xử lý giống React: thêm, sửa, xóa, chọn âm thanh, lưu settings...
</script>
