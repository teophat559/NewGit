/**
 * Main JavaScript file for BVOTE System
 * Handles interactions, animations, and UI functionality
 */

// Global variables
let isSidebarOpen = false;
let currentTheme = 'light';

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 BVOTE System initialized!');

    // Initialize components
    initSidebar();
    initNotifications();
    initThemeToggle();
    initMobileMenu();

    // Add smooth scrolling
    initSmoothScrolling();

    // Add loading animations
    initLoadingAnimations();
});

/**
 * Sidebar functionality
 */
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-menu-overlay');

    if (!sidebar) return;

    // Toggle sidebar
    window.toggleSidebar = function() {
        isSidebarOpen = !isSidebarOpen;

        if (isSidebarOpen) {
            sidebar.classList.remove('-translate-x-full');
            if (overlay) overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            sidebar.classList.add('-translate-x-full');
            if (overlay) overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }
    };

    // Close sidebar on overlay click
    if (overlay) {
        overlay.addEventListener('click', function() {
            toggleSidebar();
        });
    }

    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isSidebarOpen) {
            toggleSidebar();
        }
    });
}

/**
 * Submenu functionality
 */
function initSubmenus() {
    window.toggleSubmenu = function(button) {
        const submenu = button.nextElementSibling;
        const icon = button.querySelector('.fa-chevron-down');

        if (!submenu || !icon) return;

        const isOpen = !submenu.classList.contains('hidden');

        if (isOpen) {
            submenu.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        } else {
            submenu.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        }
    };
}

/**
 * User menu functionality
 */
function initUserMenu() {
    window.toggleUserMenu = function() {
        const userMenu = document.getElementById('user-menu');
        if (!userMenu) return;

        userMenu.classList.toggle('hidden');

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenu.contains(e.target) && !e.target.closest('[onclick*="toggleUserMenu"]')) {
                userMenu.classList.add('hidden');
            }
        });
    };
}

/**
 * Notifications system
 */
function initNotifications() {
    // Create notification container if it doesn't exist
    if (!document.getElementById('notification-container')) {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
    }

    // Global notification function
    window.showNotification = function(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notification-container');
        const notification = document.createElement('div');

        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };

        notification.className = `${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
        notification.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        container.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, duration);
    };

    // Test notifications
    setTimeout(() => {
        showNotification('🎉 Hệ thống đã sẵn sàng!', 'success');
    }, 1000);
}

/**
 * Theme toggle functionality
 */
function initThemeToggle() {
    const themeToggle = document.getElementById('theme-toggle');
    if (!themeToggle) return;

    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    themeToggle.addEventListener('click', function() {
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        setTheme(newTheme);
        localStorage.setItem('theme', newTheme);
    });
}

function setTheme(theme) {
    currentTheme = theme;
    document.documentElement.classList.toggle('dark', theme === 'dark');

    // Update theme toggle icon
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        const icon = themeToggle.querySelector('i');
        if (icon) {
            icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }
    }
}

/**
 * Mobile menu functionality
 */
function initMobileMenu() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    if (!mobileToggle) return;

    mobileToggle.addEventListener('click', function() {
        toggleSidebar();
    });
}

/**
 * Smooth scrolling
 */
function initSmoothScrolling() {
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Loading animations
 */
function initLoadingAnimations() {
    // Add loading animation to buttons
    document.querySelectorAll('button[type="submit"], button.onclick').forEach(button => {
        button.addEventListener('click', function() {
            if (this.classList.contains('loading')) return;

            const originalText = this.innerHTML;
            this.classList.add('loading');
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';
            this.disabled = true;

            // Reset button after 3 seconds (or when action completes)
            setTimeout(() => {
                this.classList.remove('loading');
                this.innerHTML = originalText;
                this.disabled = false;
            }, 3000);
        });
    });
}

/**
 * Form handling
 */
function initForms() {
    // Auto-resize textareas
    document.querySelectorAll('textarea').forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });

    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');

                    // Remove error styling after user starts typing
                    field.addEventListener('input', function() {
                        this.classList.remove('border-red-500');
                    }, { once: true });
                }
            });

            if (!isValid) {
                e.preventDefault();
                showNotification('⚠️ Vui lòng điền đầy đủ thông tin bắt buộc', 'warning');
            }
        });
    });
}

/**
 * Search functionality
 */
function initSearch() {
    const searchInput = document.querySelector('input[placeholder*="tìm kiếm"]');
    if (!searchInput) return;

    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            const query = this.value.trim();
            if (query.length >= 2) {
                performSearch(query);
            }
        }, 300);
    });
}

function performSearch(query) {
    console.log('🔍 Searching for:', query);
    // Implement search logic here
    showNotification(`🔍 Đang tìm kiếm: ${query}`, 'info');
}

/**
 * Utility functions
 */
window.utils = {
    // Format number with commas
    formatNumber: function(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },

    // Format date
    formatDate: function(date) {
        return new Date(date).toLocaleDateString('vi-VN');
    },

    // Copy to clipboard
    copyToClipboard: function(text) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('📋 Đã sao chép vào clipboard!', 'success');
        }).catch(() => {
            showNotification('❌ Không thể sao chép', 'error');
        });
    },

    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

/**
 * Performance monitoring
 */
function initPerformanceMonitoring() {
    // Monitor page load time
    window.addEventListener('load', function() {
        const loadTime = performance.now();
        console.log(`⚡ Page loaded in ${loadTime.toFixed(2)}ms`);

        if (loadTime > 3000) {
            console.warn('⚠️ Page load time is slow');
        }
    });

    // Monitor memory usage
    if ('memory' in performance) {
        setInterval(() => {
            const memory = performance.memory;
            if (memory.usedJSHeapSize > 50 * 1024 * 1024) { // 50MB
                console.warn('⚠️ High memory usage detected');
            }
        }, 30000);
    }
}

// Initialize performance monitoring
initPerformanceMonitoring();

// Export functions for global use
window.BVOTE = {
    showNotification,
    toggleSidebar,
    toggleSubmenu,
    toggleUserMenu,
    utils
};

console.log('🎯 BVOTE System ready! Use window.BVOTE to access global functions.');
