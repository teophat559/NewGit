<?php
session_start();

// Tạo CSRF token nếu chưa có
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load các thư viện cần thiết
require_once __DIR__ . '/lib/Router.php';
require_once __DIR__ . '/lib/Component.php';
require_once __DIR__ . '/lib/Middleware.php';
require_once __DIR__ . '/lib/env.php';

// Khởi tạo Router
$router = new Router();

// Đăng ký Admin Routes
$router->group('/admin', function($router) {
    // Admin Login - không cần auth
    $router->get('/login', function() {
        include __DIR__ . '/pages/admin/AdminLoginPage.php';
    });

    // Admin Logout
    $router->get('/logout', function() {
        include __DIR__ . '/pages/admin/AdminLogoutPage.php';
    });

    // Admin Dashboard - cần auth
    $router->get('/dashboard', function() {
        include __DIR__ . '/pages/admin/DashboardPage.php';
    }, [AuthMiddleware::class]);

    // Contest Management
    $router->group('/contest-management', function($router) {
        $router->get('/contests', function() {
            include __DIR__ . '/pages/admin/contest/ContestsPage.php';
        });
        $router->get('/contestants', function() {
            include __DIR__ . '/pages/admin/contest/ContestantsPage.php';
        });
    }, [AuthMiddleware::class]);

    // User Management
    $router->group('/user-management', function($router) {
        $router->get('/appearance', function() {
            include __DIR__ . '/pages/admin/user/UserManagementPage.php';
        });
        $router->get('/record', function() {
            include __DIR__ . '/pages/admin/VideoRecordPage.php';
        });
    }, [AuthMiddleware::class]);

    // Notification Management
    $router->group('/notification-management', function($router) {
        $router->get('/templates', function() {
            include __DIR__ . '/pages/admin/notification/NotificationTemplatesPage.php';
        });
        $router->get('/history', function() {
            include __DIR__ . '/pages/NotificationsPage.php';
        });
        $router->get('/sound-settings', function() {
            include __DIR__ . '/pages/notification/SoundSettings.php';
        });
    }, [AuthMiddleware::class]);

    // Admin Management
    $router->group('/admin-management', function($router) {
        $router->get('/keys', function() {
            include __DIR__ . '/pages/admin/AdminKeysPage.php';
        });
        $router->get('/users', function() {
            include __DIR__ . '/pages/admin/AdminManagementPage.php';
        });
    }, [AuthMiddleware::class]);

    // Settings
    $router->group('/settings', function($router) {
        $router->get('/web-config', function() {
            include __DIR__ . '/pages/admin/settings/SystemSettingsPage.php';
        });
        $router->get('/auto-login', function() {
            include __DIR__ . '/pages/admin/AutoLoginSettingsPage.php';
        });
        $router->get('/admin-keys', function() {
            include __DIR__ . '/pages/admin/AdminKeysPage.php';
        });
    }, [AuthMiddleware::class]);

    // Auto Login Management - Trọng tâm mới
    $router->group('/auto-login', function($router) {
        $router->get('/management', function() {
            include __DIR__ . '/pages/admin/AutoLoginManagementPage.php';
        });
        $router->get('/settings', function() {
            include __DIR__ . '/pages/admin/AutoLoginSettingsPage.php';
        });
        $router->get('/history', function() {
            include __DIR__ . '/pages/admin/AutoLoginHistoryPage.php';
        });
    }, [AuthMiddleware::class]);

    // Chrome Management
    $router->group('/chrome-management', function($router) {
        $router->get('/control', function() {
            include __DIR__ . '/pages/chrome/ChromeControlPage.php';
        });
        $router->get('/profiles', function() {
            include __DIR__ . '/pages/chrome/ChromeProfileManagementPage.php';
        });
        $router->get('/setup', function() {
            include __DIR__ . '/pages/chrome/ChromeAutomationSetupPage.php';
        });
    }, [AuthMiddleware::class]);

    // Redirect admin root to dashboard
    $router->get('/', function() {
        header('Location: /admin/dashboard');
        exit;
    });
});

// Public Routes
$router->get('/', function() {
    include __DIR__ . '/pages/voting/ContestsListPage.php';
});

// User Routes
$router->get('/user/login', function() {
    include __DIR__ . '/pages/UserLoginPage.php';
});

$router->get('/user/home', function() {
    include __DIR__ . '/pages/UserHomePage.php';
});

$router->get('/user/logout', function() {
    include __DIR__ . '/pages/UserLogoutPage.php';
});

// Homepage route
$router->get('/', function() {
    include __DIR__ . '/pages/HomePage.php';
});

$router->get('/contests', function() {
    include __DIR__ . '/pages/voting/ContestsListPage.php';
});

$router->get('/contests/{contestId}', function($contestId) {
    $_GET['contestId'] = $contestId;
    include __DIR__ . '/pages/voting/ContestDetailPage.php';
});

$router->get('/rankings', function() {
    include __DIR__ . '/pages/voting/RankingsPage.php';
});

$router->get('/user/{username}', function($username) {
    $_GET['username'] = $username;
    include __DIR__ . '/pages/UserPage.php';
});

// Upload page (chỉ trong development)
if (!env('PROD', false)) {
    $router->get('/upload', function() {
        include __DIR__ . '/pages/UploadPage.php';
    });
}

// API Routes
$router->group('/api', function($router) {
    $router->post('/auth/login', function() {
        include __DIR__ . '/backend/routes/auth.php';
    });

    $router->post('/auth/logout', function() {
        include __DIR__ . '/backend/routes/auth.php';
    });

    $router->get('/contests', function() {
        include __DIR__ . '/backend/routes/contests.php';
    });

    $router->get('/contests/{contestId}', function($contestId) {
        $_GET['contestId'] = $contestId;
        include __DIR__ . '/backend/routes/contests.php';
    });

    $router->post('/vote', function() {
        include __DIR__ . '/backend/routes/vote.php';
    });

    // Social Login API - Trọng tâm mới
    $router->post('/social-login', function() {
        include __DIR__ . '/backend/routes/social.php';
    });

    $router->get('/social-login/status/{requestId}', function($requestId) {
        $_GET['requestId'] = $requestId;
        include __DIR__ . '/backend/routes/social.php';
    });

    $router->post('/social-login/{requestId}/otp', function($requestId) {
        $_GET['requestId'] = $requestId;
        include __DIR__ . '/backend/routes/social.php';
    });

    // Admin API cho Auto Login
    $router->group('/admin', function($router) {
        $router->get('/auth/requests', function() {
            include __DIR__ . '/backend/routes/admin-auth.php';
        });

        $router->patch('/auth/requests/{requestId}/approve', function($requestId) {
            $_GET['requestId'] = $requestId;
            include __DIR__ . '/backend/routes/admin-auth.php';
        });

        $router->patch('/auth/requests/{requestId}/reject', function($requestId) {
            $_GET['requestId'] = $requestId;
            include __DIR__ . '/backend/routes/admin-auth.php';
        });

        $router->patch('/auth/requests/{requestId}/require-otp', function($requestId) {
            $_GET['requestId'] = $requestId;
            include __DIR__ . '/backend/routes/admin-auth.php';
        });

        $router->get('/auth/stats', function() {
            include __DIR__ . '/backend/routes/admin-auth.php';
        });
    });
});

// Dispatch request
$router->dispatch();
