<?php
// LoginModal.php - Chuyển đổi từ LoginModal.jsx
?>
<div id="login-modal" style="display:none;">
  <div class="dialog-content">
    <form id="login-form">
      <div class="form-group">
        <label for="account">Tài khoản</label>
        <input type="text" id="account" name="account" placeholder="Nhập tài khoản..." />
      </div>
      <div class="form-group">
        <label for="password">Mật khẩu</label>
        <input type="password" id="password" name="password" placeholder="Nhập mật khẩu..." />
      </div>
      <div class="form-group">
        <button type="submit" class="glowing-button-cyber">Đăng nhập</button>
      </div>
    </form>
  </div>
</div>
<script>
document.getElementById('login-form').onsubmit = function(e) {
  e.preventDefault();
  var account = document.getElementById('account').value;
  var password = document.getElementById('password').value;
  if (!account || !password) {
    alert('Vui lòng nhập đầy đủ thông tin.');
    return;
  }
  // Xử lý đăng nhập, ví dụ gửi lên server hoặc lưu trạng thái
  alert('Đăng nhập thành công!');
  document.getElementById('login-modal').style.display = 'none';
  document.getElementById('account').value = '';
  document.getElementById('password').value = '';
};
</script>
