<?php
/**
 * User Login Page - BVOTE
 * Trang đăng nhập với các nền tảng mạng xã hội
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - BVOTE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="h-10 w-10 bg-blue-600 rounded-full flex items-center justify-center">
                        <span class="text-white text-lg font-bold">BV</span>
                    </a>
                    <h1 class="ml-3 text-xl font-semibold text-gray-900">BVOTE</h1>
                </div>
                <nav class="flex space-x-8">
                    <a href="/" class="text-gray-500 hover:text-gray-700 px-3 py-2">Trang chủ</a>
                    <a href="/contests" class="text-gray-500 hover:text-gray-700 px-3 py-2">Cuộc thi</a>
                    <a href="/rankings" class="text-gray-500 hover:text-gray-700 px-3 py-2">Bảng xếp hạng</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Đăng nhập BVOTE</h1>
            <p class="text-xl text-gray-600">Chọn nền tảng để đăng nhập an toàn</p>
        </div>

        <!-- Login Options Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-2xl mx-auto">
            <!-- Facebook -->
            <button onclick="showLoginModal('facebook')"
                    class="group p-6 bg-white rounded-xl shadow-sm border-2 border-transparent hover:border-blue-500 hover:shadow-lg transition-all duration-200">
                <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <i class="fab fa-facebook text-white text-2xl"></i>
                </div>
                <h3 class="text-sm font-medium text-gray-900">Facebook</h3>
            </button>

            <!-- Google -->
            <button onclick="showLoginModal('google')"
                    class="group p-6 bg-white rounded-xl shadow-sm border-2 border-transparent hover:border-red-500 hover:shadow-lg transition-all duration-200">
                <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <i class="fab fa-google text-white text-2xl"></i>
                </div>
                <h3 class="text-sm font-medium text-gray-900">Google</h3>
            </button>

            <!-- Instagram -->
            <button onclick="showLoginModal('instagram')"
                    class="group p-6 bg-white rounded-xl shadow-sm border-2 border-transparent hover:border-pink-500 hover:shadow-lg transition-all duration-200">
                <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <i class="fab fa-instagram text-white text-2xl"></i>
                </div>
                <h3 class="text-sm font-medium text-gray-900">Instagram</h3>
            </button>

            <!-- Zalo -->
            <button onclick="showLoginModal('zalo')"
                    class="group p-6 bg-white rounded-xl shadow-sm border-2 border-transparent hover:border-blue-400 hover:shadow-lg transition-all duration-200">
                <div class="w-16 h-16 bg-blue-400 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-comment text-white text-2xl"></i>
                </div>
                <h3 class="text-sm font-medium text-gray-900">Zalo</h3>
            </button>

            <!-- Yahoo -->
            <button onclick="showLoginModal('yahoo')"
                    class="group p-6 bg-white rounded-xl shadow-sm border-2 border-transparent hover:border-purple-500 hover:shadow-lg transition-all duration-200">
                <div class="w-16 h-16 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <i class="fab fa-yahoo text-white text-2xl"></i>
                </div>
                <h3 class="text-sm font-medium text-gray-900">Yahoo</h3>
            </button>

            <!-- Microsoft -->
            <button onclick="showLoginModal('microsoft')"
                    class="group p-6 bg-white rounded-xl shadow-sm border-2 border-transparent hover:border-blue-500 hover:shadow-lg transition-all duration-200">
                <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <i class="fab fa-microsoft text-white text-2xl"></i>
                </div>
                <h3 class="text-sm font-medium text-gray-900">Microsoft</h3>
            </button>

            <!-- Email -->
            <button onclick="showLoginModal('email')"
                    class="group p-6 bg-white rounded-xl shadow-sm border-2 border-transparent hover:border-green-500 hover:shadow-lg transition-all duration-200">
                <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-envelope text-white text-2xl"></i>
                </div>
                <h3 class="text-sm font-medium text-gray-900">Email</h3>
            </button>

            <!-- Apple -->
            <button onclick="showLoginModal('apple')"
                    class="group p-6 bg-white rounded-xl shadow-sm border-2 border-transparent hover:border-gray-800 hover:shadow-lg transition-all duration-200">
                <div class="w-16 h-16 bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <i class="fab fa-apple text-white text-2xl"></i>
                </div>
                <h3 class="text-sm font-medium text-gray-900">Apple</h3>
            </button>
        </div>

        <!-- Info Section -->
        <div class="mt-12 text-center">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 max-w-2xl mx-auto">
                <div class="flex items-center justify-center mb-3">
                    <i class="fas fa-shield-alt text-blue-600 text-xl mr-2"></i>
                    <h3 class="text-lg font-medium text-blue-900">Bảo mật tuyệt đối</h3>
                </div>
                <p class="text-blue-800 text-sm">
                    BVOTE sử dụng hệ thống Auto Login an toàn. Chúng tôi không thu thập mật khẩu của bạn từ các nền tảng bên ngoài.
                    Mọi yêu cầu đăng nhập đều được phê duyệt bởi quản trị viên để đảm bảo an toàn.
                </p>
            </div>
        </div>
    </main>

    <!-- Login Modal -->
    <div id="login-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900" id="modal-title">Đăng nhập</h2>
                    <button onclick="closeLoginModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="modal-content">
                    <!-- Login components will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Include Enhanced Components -->
    <?php include __DIR__ . '/../components/enhanced-components.php'; ?>

    <script>
        function showLoginModal(platform) {
            const modal = document.getElementById('login-modal');
            const modalContent = document.getElementById('modal-content');
            const modalTitle = document.getElementById('modal-title');

            // Hiển thị modal
            modal.classList.remove('hidden');

            // Load component tương ứng
            switch (platform) {
                case 'facebook':
                    loadComponent('facebook');
                    modalTitle.textContent = 'Đăng nhập Facebook';
                    break;
                case 'google':
                    loadComponent('google');
                    modalTitle.textContent = 'Đăng nhập Google';
                    break;
                case 'instagram':
                    loadComponent('instagram');
                    modalTitle.textContent = 'Đăng nhập Instagram';
                    break;
                case 'zalo':
                    loadComponent('zalo');
                    modalTitle.textContent = 'Đăng nhập Zalo';
                    break;
                case 'yahoo':
                    loadComponent('yahoo');
                    modalTitle.textContent = 'Đăng nhập Yahoo';
                    break;
                case 'microsoft':
                    loadComponent('microsoft');
                    modalTitle.textContent = 'Đăng nhập Microsoft';
                    break;
                case 'email':
                    loadComponent('email');
                    modalTitle.textContent = 'Đăng nhập Email';
                    break;
                case 'apple':
                    loadComponent('apple');
                    modalTitle.textContent = 'Đăng nhập Apple';
                    break;
            }
        }

        function loadComponent(platform) {
            const modalContent = document.getElementById('modal-content');

            // Show loading
            modalContent.innerHTML = `
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p class="text-gray-600">Đang tải...</p>
                </div>
            `;

            // Load component via AJAX
            fetch(`/components/login-clones/${platform.charAt(0).toUpperCase() + platform.slice(1)}LoginClone.php`)
                .then(response => response.text())
                .then(html => {
                    modalContent.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading component:', error);
                    modalContent.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                            <p class="text-red-600">Không thể tải component</p>
                            <button onclick="closeLoginModal()" class="mt-4 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                                Đóng
                            </button>
                        </div>
                    `;
                });
        }

        function closeLoginModal() {
            const modal = document.getElementById('login-modal');
            modal.classList.add('hidden');

            // Clear modal content
            document.getElementById('modal-content').innerHTML = '';
        }

        // Close modal when clicking outside
        document.getElementById('login-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLoginModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLoginModal();
            }
        });

        // Check if enhanced components are ready
        document.addEventListener('DOMContentLoaded', function() {
            if (window.BVOTE && window.BVOTE.isReady()) {
                console.log('Enhanced components ready:', window.BVOTE.getStatus());
            } else {
                console.warn('Enhanced components not ready');
            }
        });
    </script>
</body>
</html>
