
<?php
/**
 * MainLayout - Layout chính cho Admin Panel
 * Giống 100% với React gốc: cyber theme, gradient backgrounds, animations
 */
require_once __DIR__ . '/Component.php';

class MainLayout extends Component {
    private $currentPage;
    private $user;

    public function __construct($props = []) {
        parent::__construct($props);
        $this->currentPage = $_GET['page'] ?? 'dashboard';
        $this->user = $_SESSION['admin_user'] ?? null;
    }

    protected function renderContent() {
        ?>
        <div class="flex h-screen bg-background text-foreground">
            <!-- Mobile Menu Button -->
            <button class="lg:hidden fixed top-4 left-4 z-50 text-white bg-transparent border-none p-2" onclick="toggleSidebar()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>

            <!-- Sidebar -->
            <aside id="sidebar" class="fixed lg:relative z-40 h-full flex flex-col p-4 cyber-sidebar-bg transition-all duration-300 ease-in-out" style="width: 280px;">
                <!-- Logo Section -->
                <div class="flex items-center justify-center mb-8 relative">
                    <h1 class="text-2xl font-bold text-white animate-text-glow">Hello Boong!!</h1>
                    <button class="hidden lg:inline-flex text-white absolute right-0 bg-transparent border-none p-2" onclick="toggleSidebar()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-grow">
                    <?php $this->renderNavItems(); ?>
                </nav>

                <!-- Bottom Actions -->
                <div class="mt-auto flex flex-col items-center space-y-2">
                    <!-- View User Page -->
                    <div class="w-full">
                        <a href="/" target="_blank" rel="noopener noreferrer" class="flex items-center p-3 rounded-md transition-all duration-200 border border-blue-500/50 text-blue-400 hover:bg-blue-500/10 hover:text-white">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            <span>Xem trang User</span>
                        </a>
                    </div>

                    <!-- Logout -->
                    <div class="w-full">
                        <button onclick="logout()" class="w-full flex items-center p-3 rounded-md transition-all duration-200 border border-red-500/50 bg-transparent text-red-400 hover:bg-red-500/10 hover:text-white">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            <span>Đăng xuất</span>
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <main class="flex-1 overflow-x-hidden overflow-y-auto main-gradient-bg p-4">
                    <?php echo $this->props['content'] ?? ''; ?>
                </main>
            </div>
        </div>

        <!-- Mobile Overlay -->
        <div id="mobile-menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden" onclick="toggleSidebar()"></div>

        <script>
        let isSidebarExpanded = true;

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-menu-overlay');

            if (window.innerWidth < 1024) {
                // Mobile mode
                sidebar.classList.toggle('translate-x-0');
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            } else {
                // Desktop mode
                isSidebarExpanded = !isSidebarExpanded;
                if (isSidebarExpanded) {
                    sidebar.style.width = '280px';
                    sidebar.querySelectorAll('.nav-text').forEach(el => el.style.display = 'block');
                } else {
                    sidebar.style.width = '80px';
                    sidebar.querySelectorAll('.nav-text').forEach(el => el.style.display = 'none');
                }
            }
        }

        function toggleSubmenu(button) {
            const submenu = button.nextElementSibling;
            const icon = button.querySelector('.submenu-icon');

            if (!submenu || !icon) return;

            const isOpen = !submenu.classList.contains('hidden');

            if (isOpen) {
                submenu.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            } else {
                submenu.classList.remove('hidden');
                icon.style.transform = 'rotate(90deg)';
            }
        }

        function logout() {
            if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
                window.location.href = '/admin/logout';
            }
        }

        // Close sidebar on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const overlay = document.getElementById('mobile-menu-overlay');
                if (!overlay.classList.contains('hidden')) {
                    toggleSidebar();
                }
            }
        });
        </script>
        <?php
    }

    private function renderNavItems() {
        $navItems = [
            [
                'name' => 'Bảng Điều Khiển',
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'path' => '/admin/dashboard'
            ],
            [
                'name' => 'Quản lý Cuộc thi',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'subItems' => [
                    [
                        'name' => 'Danh sách Cuộc thi',
                        'path' => '/admin/contest-management/contests',
                        'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
                    ],
                    [
                        'name' => 'Danh sách Thí sinh',
                        'path' => '/admin/contest-management/contestants',
                        'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z'
                    ]
                ]
            ],
            [
                'name' => 'Quản lý Người dùng',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',
                'subItems' => [
                    [
                        'name' => 'Giao diện',
                        'path' => '/admin/user-management/appearance',
                        'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z'
                    ],
                    [
                        'name' => 'Lịch sử Video',
                        'path' => '/admin/user-management/record',
                        'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'
                    ]
                ]
            ],
            [
                'name' => 'Quản lý Thông báo',
                'icon' => 'M15 17h5l-5 5v-5zM4.5 19.5L9 15m0 0h6m-6 0v6',
                'subItems' => [
                    [
                        'name' => 'Mẫu thông báo',
                        'path' => '/admin/notification-management/templates',
                        'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
                    ],
                    [
                        'name' => 'Lịch sử thông báo',
                        'path' => '/admin/notification-management/history',
                        'icon' => 'M15 17h5l-5 5v-5zM4.5 19.5L9 15m0 0h6m-6 0v6'
                    ],
                    [
                        'name' => 'Cài đặt chuông',
                        'path' => '/admin/notification-management/sound-settings',
                        'icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3'
                    ]
                ]
            ],
            [
                'name' => 'Tự động hóa Chrome',
                'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
                'subItems' => [
                    [
                        'name' => 'Bảng điều khiển',
                        'path' => '/admin/chrome-management/control',
                        'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'
                    ],
                    [
                        'name' => 'Quản lý Profiles',
                        'path' => '/admin/chrome-management/profiles',
                        'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z'
                    ],
                    [
                        'name' => 'Cài đặt Agent',
                        'path' => '/admin/chrome-management/setup',
                        'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01'
                    ]
                ]
            ],
            [
                'name' => 'Cài đặt chung',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'subItems' => [
                    [
                        'name' => 'Cấu hình Web',
                        'path' => '/admin/settings/web-config',
                        'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'
                    ],
                    [
                        'name' => 'Cấu hình Auto Login',
                        'path' => '/admin/settings/auto-login',
                        'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'
                    ],
                    [
                        'name' => 'Mã bảo mật',
                        'path' => '/admin/settings/admin-keys',
                        'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'
                    ]
                ]
            ]
        ];

        foreach ($navItems as $item) {
            $this->renderNavItem($item);
        }
    }

    private function renderNavItem($item) {
        $isActive = $this->isCurrentPage($item['path']);
        $hasSubItems = isset($item['subItems']);

        if ($hasSubItems) {
            $this->renderNavGroup($item);
        } else {
            $this->renderNavLink($item);
        }
    }

    private function renderNavGroup($item) {
        $hasActiveSubItem = $this->hasActiveSubItem($item['subItems']);
        ?>
        <div class="my-1">
            <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between p-3 rounded-md text-slate-300 hover:bg-white/5 hover:text-white transition-all duration-200">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $item['icon']; ?>"></path>
                    </svg>
                    <span class="nav-text"><?php echo $item['name']; ?></span>
                </div>
                <div class="submenu-icon transform transition-transform duration-200" style="transform: rotate(<?php echo $hasActiveSubItem ? '90deg' : '0deg'; ?>deg);">
                    &gt;
                </div>
            </button>
            <div class="submenu <?php echo $hasActiveSubItem ? '' : 'hidden'; ?> pt-2 pl-8">
                <?php foreach ($item['subItems'] as $subItem): ?>
                    <?php $this->renderSubNavLink($subItem); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function renderNavLink($item) {
        $isActive = $this->isCurrentPage($item['path']);
        ?>
        <div class="my-1">
            <a href="<?php echo $item['path']; ?>" class="flex items-center p-3 rounded-md transition-all duration-200 nav-item-glow <?php echo $isActive ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-slate-300 hover:bg-white/5 hover:text-white'; ?>">
                <svg class="w-6 h-6 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $item['icon']; ?>"></path>
                </svg>
                <span class="nav-text"><?php echo $item['name']; ?></span>
            </a>
        </div>
        <?php
    }

    private function renderSubNavLink($subItem) {
        $isActive = $this->isCurrentPage($subItem['path']);
        ?>
        <a href="<?php echo $subItem['path']; ?>" class="flex items-center p-3 my-1 rounded-md transition-all duration-200 <?php echo $isActive ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-slate-400 hover:bg-white/5 hover:text-white'; ?>">
            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $subItem['icon']; ?>"></path>
            </svg>
            <span class="text-sm"><?php echo $subItem['name']; ?></span>
        </a>
        <?php
    }

    private function isCurrentPage($path) {
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($currentPath, $path) !== false;
    }

    private function hasActiveSubItem($subItems) {
        foreach ($subItems as $subItem) {
            if ($this->isCurrentPage($subItem['path'])) {
                return true;
            }
        }
        return false;
    }
}
?>
