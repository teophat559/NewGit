<?php
// ChromeProfileDialog.php - Chuyển đổi từ ChromeProfileDialog.jsx
?>
<div class="chrome-profile-dialog">
<div id="chrome-profile-dialog" style="display:none;">
  <div class="dialog-content sm:max-w-[425px] bg-[#0f0c29] border-purple-800 text-slate-50">
    <div class="dialog-header">
      <div class="dialog-title flex items-center text-xl text-white" style="text-shadow: 0 0 5px #8e2de2;">
        <span class="mr-2 h-6 w-6 text-blue-400">🌐</span>
        Gán Profile Chrome
      </div>
      <div class="dialog-description text-slate-400">
        Nhập tên profile Chrome để gán cho các mục đã chọn.
      </div>
    </div>
    <form id="chrome-profile-form" class="grid gap-4 py-4">
      <div class="grid grid-cols-4 items-center gap-4">
        <label for="profile-name" class="text-right text-slate-400">Tên Profile</label>
        <input type="text" id="profile-name" name="profile-name" class="col-span-3 bg-slate-800/50 border-slate-700 focus:ring-purple-500" placeholder="Profile 1" />
      </div>
      <div class="dialog-footer">
        <button type="submit" class="glowing-button-cyber">Gán Profile</button>
      </div>
    </form>
  </div>
</div>
<script>
document.getElementById('chrome-profile-form').onsubmit = function(e) {
  e.preventDefault();
  var profileName = document.getElementById('profile-name').value;
  if (profileName) {
    // Xử lý gán profile, ví dụ lưu vào localStorage hoặc gửi lên server
    localStorage.setItem('chromeProfile', profileName);
    document.getElementById('chrome-profile-dialog').style.display = 'none';
    document.getElementById('profile-name').value = '';
    alert('Đã gán profile Chrome thành công!');
  }
};
</script>
</div>
