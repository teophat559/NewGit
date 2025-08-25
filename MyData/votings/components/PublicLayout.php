<?php
/**
 * PublicLayout Component - Tương tự React PublicLayout
 * Hiển thị header navigation và content area cho public pages
 */
class PublicLayout extends Component {
    private $currentPage;
    private $user;

    public function __construct($props = []) {
        parent::__construct($props);
        $this->currentPage = $_GET['page'] ?? 'home';
        $this->user = $_SESSION['user'] ?? null;
    }

    protected function renderContent() {
        ?>
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo $this->getPageTitle(); ?> - BVOTE</title>
            <link rel="stylesheet" href="/assets/style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
        </head>
        <body class="min-h-screen text-foreground relative overflow-hidden main-gradient-bg">
            <div class="absolute inset-0 wavy-line"></div>
            <div class="relative z-10 flex flex-col min-h-screen">
                <!-- Header -->
                <?php $this->renderHeader(); ?>

                <!-- Main Content -->
                <main class="flex-1">
                    <?php $this->renderPageContent(); ?>
                </main>

                <!-- Footer -->
                <?php $this->renderFooter(); ?>
            </div>

            <!-- Login Modal -->
            <?php $this->renderLoginModal(); ?>

            <script src="/assets/app.js"></script>
        </body>
        </html>
        <?php
    }

    private function renderHeader() {
        ?>
        <header class="bg-background/80 shadow-md backdrop-blur-sm sticky top-0 z-40 border-b border-white/10">
            <nav class="container mx-auto px-4 py-3 flex justify-between items-center">
                <div class="flex items-center gap-6">
                    <a href="/contests" class="flex items-center gap-2 text-2xl font-bold text-white nav-item-glow p-2 rounded-lg">
                        <i class="fas fa-trophy h-8 w-8 text-primary"></i>
                        <span>BVOTE</span>
                    </a>
                </div>

                <div class="flex-1 max-w-xl mx-6 hidden md:block">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                        <input
                            type="text"
                            placeholder="Tìm kiếm cuộc thi hoặc thí sinh..."
                            class="bg-card/80 border-white/10 rounded-full pl-10 w-full px-4 py-2 text-white placeholder-gray-400"
                        />
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <?php if ($this->user): ?>
                        <!-- User Menu -->
                        <div class="relative">
                            <button class="relative h-10 w-10 rounded-full bg-white/10 hover:bg-white/20 transition-colors duration-200" onclick="toggleUserMenu()">
                                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                                    <span class="text-white font-medium"><?php echo substr($this->user['name'] ?? 'U', 0, 1); ?></span>
                                </div>
                            </button>

                            <!-- User Dropdown -->
                            <div id="user-menu" class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 z-50 hidden">
                                <div class="px-4 py-2 border-b border-gray-200">
                                    <p class="text-sm font-medium text-gray-900"><?php echo $this->user['name']; ?></p>
                                    <p class="text-xs text-gray-500">
                                        <?php echo $this->user['platform'] ? "Qua {$this->user['platform']}" : 'Đã đăng nhập'; ?>
                                    </p>
                                </div>
                                <a href="/user/<?php echo $this->user['name']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2 h-4 w-4"></i>Hồ sơ
                                </a>
                                <hr class="my-1">
                                <button onclick="logout()" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2 h-4 w-4"></i>Đăng xuất
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Login Button -->
                        <button onclick="openLoginModal()" class="bg-gradient-to-r from-primary to-purple-500 hover:from-primary/90 hover:to-purple-500/90 text-white font-bold px-6 py-2 rounded-full shadow-lg shadow-primary/30 transition-transform transform hover:scale-105">
                            Đăng Nhập
                        </button>
                    <?php endif; ?>
                </div>
            </nav>
        </header>
        <?php
    }

    private function renderPageContent() {
        // Nội dung trang sẽ được render ở đây
        if (isset($this->props['content'])) {
            echo $this->props['content'];
        }
    }

    private function renderFooter() {
        ?>
        <footer class="bg-background/80 border-t border-white/10 py-8 mt-auto">
            <div class="container mx-auto px-4 text-center">
                <div class="flex justify-center items-center space-x-6 mb-4">
                    <a href="/contests" class="text-white/70 hover:text-white transition-colors duration-200">Cuộc thi</a>
                    <a href="/rankings" class="text-white/70 hover:text-white transition-colors duration-200">Bảng xếp hạng</a>
                    <a href="/about" class="text-white/70 hover:text-white transition-colors duration-200">Giới thiệu</a>
                    <a href="/contact" class="text-white/70 hover:text-white transition-colors duration-200">Liên hệ</a>
                </div>
                <div class="text-white/50 text-sm">
                    © 2024 BVOTE. Tất cả quyền được bảo lưu.
                </div>
            </div>
        </footer>
        <?php
    }

    private function renderLoginModal() {
        ?>
        <!-- Login Modal -->
        <div id="login-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Đăng nhập</h2>
                    <button onclick="closeLoginModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <button onclick="loginWithGoogle()" class="w-full flex items-center justify-center space-x-3 bg-white border border-gray-300 rounded-lg px-4 py-3 text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                        <img src="/assets/social/google.svg" alt="Google" class="w-5 h-5">
                        <span>Tiếp tục với Google</span>
                    </button>

                    <button onclick="loginWithFacebook()" class="w-full flex items-center justify-center space-x-3 bg-blue-600 text-white rounded-lg px-4 py-3 hover:bg-blue-700 transition-colors duration-200">
                        <img src="/assets/social/facebook.svg" alt="Facebook" class="w-5 h-5">
                        <span>Tiếp tục với Facebook</span>
                    </button>

                    <button onclick="loginWithZalo()" class="w-full flex items-center justify-center space-x-3 bg-blue-500 text-white rounded-lg px-4 py-3 hover:bg-blue-600 transition-colors duration-200">
                        <img src="/assets/social/zalo.svg" alt="Zalo" class="w-5 h-5">
                        <span>Tiếp tục với Zalo</span>
                    </button>
                </div>

                <div class="mt-6 text-center text-sm text-gray-500">
                    Bằng cách đăng nhập, bạn đồng ý với <a href="/terms" class="text-primary hover:underline">Điều khoản sử dụng</a> và <a href="/privacy" class="text-primary hover:underline">Chính sách bảo mật</a>
                </div>
            </div>
        </div>
        <?php
    }

    private function getPageTitle() {
        $titles = [
            'home' => 'Trang chủ',
            'contests' => 'Cuộc thi',
            'contest-detail' => 'Chi tiết cuộc thi',
            'rankings' => 'Bảng xếp hạng',
            'user-profile' => 'Hồ sơ người dùng'
        ];

        return $titles[$this->currentPage] ?? 'BVOTE';
    }
}

// Helper functions
function openLoginModal() {
    echo "<script>
        document.getElementById('login-modal').classList.remove('hidden');
    </script>";
}

function closeLoginModal() {
    echo "<script>
        document.getElementById('login-modal').classList.add('hidden');
    </script>";
}

function toggleUserMenu() {
    echo "<script>
        const userMenu = document.getElementById('user-menu');
        userMenu.classList.toggle('hidden');
    </script>";
}

function loginWithGoogle() {
    echo "<script>
        window.location.href = '/api/auth/google';
    </script>";
}

function loginWithFacebook() {
    echo "<script>
        window.location.href = '/api/auth/facebook';
    </script>";
}

function loginWithZalo() {
    echo "<script>
        window.location.href = '/api/auth/zalo';
    </script>";
}

function logout() {
    echo "<script>
        fetch('/api/auth/logout', {
            method: 'POST',
            credentials: 'same-origin'
        }).then(() => {
            window.location.href = '/contests';
        });
    </script>";
}
?>
