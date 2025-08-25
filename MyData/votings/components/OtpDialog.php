<?php
// OtpDialog.php - Chuyển đổi từ OtpDialog.jsx
?>
<div id="otp-dialog" style="display:none;">
  <div class="dialog-content sm:max-w-[425px] bg-[#0f0c29] border-purple-800 text-slate-50">
    <div class="dialog-header">
      <div class="dialog-title flex items-center text-xl text-white" style="text-shadow: 0 0 5px #8e2de2;">
        <span class="mr-2 h-6 w-6 text-yellow-400">✅</span>
        Duyệt Mã OTP
      </div>
      <div class="dialog-description text-slate-400">
        Nhập mã OTP gồm 6 chữ số để hoàn tất đăng nhập.
      </div>
    </div>
    <form id="otp-form" class="grid gap-4 py-4">
      <div class="grid grid-cols-4 items-center gap-4">
        <label for="otp" class="text-right text-slate-400">Mã OTP</label>
        <input type="text" id="otp" name="otp" class="col-span-3 bg-slate-800/50 border-slate-700 focus:ring-purple-500" placeholder="123456" maxlength="6" />
      </div>
      <div class="dialog-footer">
        <button type="submit" class="glowing-button-cyber bg-yellow-500 hover:bg-yellow-400">Xác nhận</button>
      </div>
    </form>
  </div>
</div>
<script>
document.getElementById('otp-form').onsubmit = function(e) {
  e.preventDefault();
  var otp = document.getElementById('otp').value;
  if (otp.length === 6 && /^\d+$/.test(otp)) {
    alert('Đã xác nhận OTP: ' + otp);
    document.getElementById('otp-dialog').style.display = 'none';
    document.getElementById('otp').value = '';
  } else {
    alert('Vui lòng nhập đúng mã OTP gồm 6 chữ số.');
  }
};
</script>
