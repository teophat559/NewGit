<?php
session_start();

// Kết nối database
require_once 'database.php';

// Xử lý đăng ký
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $errors = [];

        // Validation
        if (empty($name)) $errors[] = 'Vui lòng nhập họ tên';
        if (empty($email)) $errors[] = 'Vui lòng nhập email';
        if (empty($phone)) $errors[] = 'Vui lòng nhập số điện thoại';
        if (empty($password)) $errors[] = 'Vui lòng nhập mật khẩu';
        if ($password !== $confirm_password) $errors[] = 'Mật khẩu xác nhận không khớp';
        if (strlen($password) < 6) $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        }

        if (!preg_match('/^[0-9]{10,11}$/', $phone)) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }

        if (empty($errors)) {
            try {
                // Kiểm tra email đã tồn tại
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = 'Email đã được sử dụng';
                }

                // Kiểm tra số điện thoại đã tồn tại
                $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
                $stmt->execute([$phone]);
                if ($stmt->fetch()) {
                    $errors[] = 'Số điện thoại đã được sử dụng';
                }

                if (empty($errors)) {
                    // Tạo tài khoản mới
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, 'user', NOW())");

                    if ($stmt->execute([$name, $email, $phone, $hashed_password])) {
                        $user_id = $pdo->lastInsertId();

                        // Tự động đăng nhập
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_role'] = 'user';

                        $success = 'Đăng ký thành công! Bạn sẽ được chuyển hướng...';
                        echo "<script>setTimeout(() => window.location.href = 'index.html', 2000);</script>";
                    } else {
                        $errors[] = 'Có lỗi xảy ra khi tạo tài khoản';
                    }
                }
            } catch (PDOException $e) {
                $errors[] = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
                error_log('Register error: ' . $e->getMessage());
            }
        }
    }
}

// Kiểm tra nếu user đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - BVOTE Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .glass-effect {
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .pattern-dots {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='1'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .password-strength {
            height: 4px;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-30 pattern-dots"></div>

    <!-- Back to Home -->
    <div class="absolute top-6 left-6 z-20">
        <a href="index.html" class="flex items-center space-x-2 text-white/80 hover:text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m12 19-7-7 7-7"></path>
                <path d="M19 12H5"></path>
            </svg>
            <span>Quay về trang chủ</span>
        </a>
    </div>

    <!-- Main Register Container -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Register Card -->
            <div class="bg-black/20 glass-effect border border-purple-500/30 rounded-2xl p-8 shadow-2xl">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="flex items-center justify-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                                <path d="M20 6 9 17l-5-5"></path>
                            </svg>
                        </div>
                        <span class="text-white font-bold text-2xl">BVOTE</span>
                    </div>
                    <h1 class="text-2xl font-bold text-white mb-2">Đăng ký</h1>
                    <p class="text-gray-400">Tạo tài khoản mới để bắt đầu!</p>
                </div>

                <!-- Success Message -->
                <?php if (isset($success)): ?>
                    <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg mb-6">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg mb-6">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Register Form -->
                <form method="POST" class="space-y-5" id="registerForm">
                    <input type="hidden" name="action" value="register">

                    <div>
                        <label for="name" class="block text-sm font-medium text-white mb-2">
                            Họ và tên
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            required
                            class="w-full px-4 py-3 bg-white/10 border border-purple-500/30 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all"
                            placeholder="Nhập họ và tên"
                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                        >
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-white mb-2">
                            Email
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            class="w-full px-4 py-3 bg-white/10 border border-purple-500/30 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all"
                            placeholder="Nhập địa chỉ email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        >
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-white mb-2">
                            Số điện thoại
                        </label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            required
                            class="w-full px-4 py-3 bg-white/10 border border-purple-500/30 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all"
                            placeholder="Nhập số điện thoại"
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-white mb-2">
                            Mật khẩu
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            class="w-full px-4 py-3 bg-white/10 border border-purple-500/30 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all"
                            placeholder="Nhập mật khẩu (ít nhất 6 ký tự)"
                            minlength="6"
                        >
                        <div class="mt-2">
                            <div class="password-strength bg-gray-600 rounded-full" id="passwordStrength"></div>
                            <div class="text-xs text-gray-400 mt-1" id="passwordHint">Độ mạnh mật khẩu</div>
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-white mb-2">
                            Xác nhận mật khẩu
                        </label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            required
                            class="w-full px-4 py-3 bg-white/10 border border-purple-500/30 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all"
                            placeholder="Nhập lại mật khẩu"
                        >
                        <div class="text-xs mt-1" id="passwordMatch"></div>
                    </div>

                    <div class="flex items-start">
                        <input
                            type="checkbox"
                            id="terms"
                            name="terms"
                            required
                            class="w-4 h-4 text-purple-600 bg-white/10 border-purple-500/30 rounded focus:ring-purple-500 mt-1"
                        >
                        <label for="terms" class="ml-2 text-sm text-gray-300">
                            Tôi đồng ý với
                            <a href="#" class="text-purple-400 hover:text-purple-300 transition-colors">Điều khoản dịch vụ</a>
                            và
                            <a href="#" class="text-purple-400 hover:text-purple-300 transition-colors">Chính sách bảo mật</a>
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
                        id="submitBtn"
                    >
                        Đăng ký
                    </button>
                </form>

                <!-- Footer -->
                <div class="text-center mt-6">
                    <p class="text-sm text-gray-400">
                        Đã có tài khoản?
                        <a href="login.php" class="text-purple-400 hover:text-purple-300 transition-colors">Đăng nhập ngay</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrength = document.getElementById('passwordStrength');
            const passwordHint = document.getElementById('passwordHint');
            const passwordMatch = document.getElementById('passwordMatch');
            const submitBtn = document.getElementById('submitBtn');

            // Password strength indicator
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let hint = '';

                if (password.length >= 6) strength += 25;
                if (password.match(/[a-z]+/)) strength += 25;
                if (password.match(/[A-Z]+/)) strength += 25;
                if (password.match(/[0-9]+/)) strength += 25;

                if (strength < 25) {
                    passwordStrength.className = 'password-strength bg-red-500 rounded-full';
                    hint = 'Yếu';
                } else if (strength < 50) {
                    passwordStrength.className = 'password-strength bg-yellow-500 rounded-full';
                    hint = 'Trung bình';
                } else if (strength < 75) {
                    passwordStrength.className = 'password-strength bg-blue-500 rounded-full';
                    hint = 'Mạnh';
                } else {
                    passwordStrength.className = 'password-strength bg-green-500 rounded-full';
                    hint = 'Rất mạnh';
                }

                passwordStrength.style.width = strength + '%';
                passwordHint.textContent = hint;

                checkPasswordMatch();
            });

            // Password match checker
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);

            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (confirmPassword.length > 0) {
                    if (password === confirmPassword) {
                        passwordMatch.textContent = '✓ Mật khẩu khớp';
                        passwordMatch.className = 'text-xs mt-1 text-green-400';
                        confirmPasswordInput.className = confirmPasswordInput.className.replace('border-red-500/30', 'border-purple-500/30');
                    } else {
                        passwordMatch.textContent = '✗ Mật khẩu không khớp';
                        passwordMatch.className = 'text-xs mt-1 text-red-400';
                        confirmPasswordInput.className = confirmPasswordInput.className.replace('border-purple-500/30', 'border-red-500/30');
                    }
                } else {
                    passwordMatch.textContent = '';
                }
            }

            // Phone number formatting
            document.getElementById('phone').addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // Form validation
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Mật khẩu xác nhận không khớp!');
                    return false;
                }

                if (password.length < 6) {
                    e.preventDefault();
                    alert('Mật khẩu phải có ít nhất 6 ký tự!');
                    return false;
                }

                // Disable submit button to prevent double submission
                submitBtn.disabled = true;
                submitBtn.textContent = 'Đang xử lý...';
            });
        });
    </script>
</body>
</html>
