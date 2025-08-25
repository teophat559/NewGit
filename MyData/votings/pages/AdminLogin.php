<?php
// AdminLogin.php - Trang đăng nhập quản trị viên
$pageTitle = 'Đăng nhập Admin';
?>

<div class="content-header">
    <h1><i class="fas fa-user-shield"></i> Đăng nhập Quản trị viên</h1>
    <p>Vui lòng đăng nhập để truy cập hệ thống quản trị</p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Thông tin đăng nhập</h3>
    </div>

    <form class="login-form admin-login" method="POST">
        <div class="form-group">
            <label for="username" class="form-label">
                <i class="fas fa-user"></i> Tên đăng nhập
            </label>
            <input
                type="text"
                id="username"
                name="username"
                class="form-input"
                placeholder="Nhập tên đăng nhập"
                required
            >
        </div>

        <div class="form-group">
            <label for="password" class="form-label">
                <i class="fas fa-lock"></i> Mật khẩu
            </label>
            <input
                type="password"
                id="password"
                name="password"
                class="form-input"
                placeholder="Nhập mật khẩu"
                required
            >
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt"></i> Đăng nhập
            </button>
        </div>
    </form>

    <div class="text-center mt-3">
        <p class="text-muted">
            <i class="fas fa-info-circle"></i>
            Tài khoản mặc định: <strong>admin</strong> / <strong>admin123</strong>
        </p>
    </div>
</div>

<!-- Additional Info Cards -->
<div class="row" style="display: flex; gap: 20px; margin-top: 20px;">
    <div class="card" style="flex: 1;">
        <div class="card-header">
            <h4 class="card-title">
                <i class="fas fa-shield-alt"></i> Bảo mật
            </h4>
        </div>
        <div class="card-body">
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 10px;">
                    <i class="fas fa-check text-success"></i> Xác thực JWT token
                </li>
                <li style="margin-bottom: 10px;">
                    <i class="fas fa-check text-success"></i> Session management
                </li>
                <li style="margin-bottom: 10px;">
                    <i class="fas fa-check text-success"></i> Role-based access control
                </li>
            </ul>
        </div>
    </div>

    <div class="card" style="flex: 1;">
        <div class="card-header">
            <h4 class="card-title">
                <i class="fas fa-cogs"></i> Tính năng
            </h4>
        </div>
        <div class="card-body">
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 10px;">
                    <i class="fas fa-star text-warning"></i> Quản lý cuộc thi
                </li>
                <li style="margin-bottom: 10px;">
                    <i class="fas fa-star text-warning"></i> Quản lý người dùng
                </li>
                <li style="margin-bottom: 10px;">
                    <i class="fas fa-star text-warning"></i> Tự động hóa Chrome
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus username field
    document.getElementById('username').focus();

    // Handle form submission
    const form = document.querySelector('.login-form');
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        // Show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading-spinner"></span> Đang xử lý...';

        try {
            const formData = new FormData(form);
            const data = {
                username: formData.get('username'),
                password: formData.get('password')
            };

            const result = await window.app.login(data.username, data.password, true);

            if (!result.success) {
                window.showNotification(result.message, 'error');
            }
        } catch (error) {
            window.showNotification('Lỗi kết nối server', 'error');
        } finally {
            // Hide loading
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    // Enter key navigation
    document.getElementById('username').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('password').focus();
        }
    });

    document.getElementById('password').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            form.submit();
        }
    });
});
</script>
