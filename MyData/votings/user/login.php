<?php
session_start();

// Kết nối database
require_once 'database.php';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } else {
            try {
                // Kiểm tra user trong database
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
                $stmt->execute([$email, $email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'] ?? 'user';

                    // Redirect dựa trên role
                    if ($_SESSION['user_role'] === 'admin') {
                        header('Location: ../admin/dashboard.php');
                    } else {
                        header('Location: index.html');
                    }
                    exit;
                } else {
                    $error = 'Email/số điện thoại hoặc mật khẩu không chính xác';
                }
            } catch (PDOException $e) {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
                error_log('Login error: ' . $e->getMessage());
            }
        }
    }

    // Xử lý social login
    if ($action === 'social_login') {
        $platform = $_POST['platform'] ?? '';

        // Mock social login - trong thực tế sẽ tích hợp với API của từng platform
        $social_user = [
            'facebook' => ['name' => 'Facebook User', 'email' => 'facebook@user.com'],
            'google' => ['name' => 'Google User', 'email' => 'google@user.com'],
            'instagram' => ['name' => 'Instagram User', 'email' => 'instagram@user.com'],
            'zalo' => ['name' => 'Zalo User', 'email' => 'zalo@user.com'],
        ];

        if (isset($social_user[$platform])) {
            $_SESSION['user_id'] = rand(1000, 9999);
            $_SESSION['user_name'] = $social_user[$platform]['name'];
            $_SESSION['user_email'] = $social_user[$platform]['email'];
            $_SESSION['user_role'] = 'user';

            header('Location: index.html');
            exit;
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
    <title>Đăng nhập - BVOTE Platform</title>
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

        .login-gradient {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(168, 85, 247, 0.05) 50%, rgba(192, 132, 252, 0.1) 100%);
        }

        .social-btn {
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            transform: scale(1.05);
        }

        .pattern-dots {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='1'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
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

    <!-- Main Login Container -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Login Card -->
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
                    <h1 class="text-2xl font-bold text-white mb-2">Đăng nhập</h1>
                    <p class="text-gray-400">Chào mừng bạn quay trở lại!</p>
                </div>

                <!-- Error Message -->
                <?php if (isset($error)): ?>
                    <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg mb-6">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="login">

                    <div>
                        <label for="email" class="block text-sm font-medium text-white mb-2">
                            Email hoặc số điện thoại
                        </label>
                        <input
                            type="text"
                            id="email"
                            name="email"
                            required
                            class="w-full px-4 py-3 bg-white/10 border border-purple-500/30 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all"
                            placeholder="Nhập email hoặc số điện thoại"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
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
                            placeholder="Nhập mật khẩu"
                        >
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-purple-600 bg-white/10 border-purple-500/30 rounded focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-300">Ghi nhớ đăng nhập</span>
                        </label>
                        <a href="#" class="text-sm text-purple-400 hover:text-purple-300 transition-colors">Quên mật khẩu?</a>
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-gray-900"
                    >
                        Đăng nhập
                    </button>
                </form>

                <!-- Divider -->
                <div class="my-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-purple-500/20"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="bg-gray-900 px-4 text-gray-400">Hoặc đăng nhập bằng</span>
                        </div>
                    </div>
                </div>

                <!-- Social Login -->
                <div class="grid grid-cols-3 gap-3 mb-6">
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="social_login">
                        <input type="hidden" name="platform" value="facebook">
                        <button type="submit" class="social-btn w-full h-12 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center justify-center font-bold">
                            f
                        </button>
                    </form>

                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="social_login">
                        <input type="hidden" name="platform" value="google">
                        <button type="submit" class="social-btn w-full h-12 bg-white hover:bg-gray-100 text-blue-600 rounded-lg flex items-center justify-center font-bold">
                            G
                        </button>
                    </form>

                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="social_login">
                        <input type="hidden" name="platform" value="zalo">
                        <button type="submit" class="social-btn w-full h-12 bg-blue-500 hover:bg-blue-600 text-white rounded-lg flex items-center justify-center text-xs font-bold">
                            Zalo
                        </button>
                    </form>
                </div>

                <!-- Demo Accounts -->
                <div class="bg-purple-500/10 border border-purple-500/20 rounded-lg p-4 mb-6">
                    <h4 class="text-white font-medium mb-2">Tài khoản demo:</h4>
                    <div class="text-xs text-gray-300 space-y-1">
                        <p><strong>Admin:</strong> admin@bvote.com / admin123</p>
                        <p><strong>User:</strong> user@bvote.com / user123</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center">
                    <p class="text-sm text-gray-400">
                        Chưa có tài khoản?
                        <a href="register.php" class="text-purple-400 hover:text-purple-300 transition-colors">Đăng ký ngay</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-fill demo accounts
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers for demo account info
            const demoAdmin = document.querySelector('[data-demo="admin"]');
            const demoUser = document.querySelector('[data-demo="user"]');

            // Quick fill functions
            window.fillAdmin = function() {
                document.getElementById('email').value = 'admin@bvote.com';
                document.getElementById('password').value = 'admin123';
            };

            window.fillUser = function() {
                document.getElementById('email').value = 'user@bvote.com';
                document.getElementById('password').value = 'user123';
            };

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    document.querySelector('form').submit();
                }
            });
        });
    </script>
</body>
</html>
