<?php
/**
 * BVOTE Main Router
 * Điều hướng chính cho ứng dụng
 */

require_once __DIR__ . '/bootstrap.php';

use BVOTE\Core\Router;
use BVOTE\Core\Middleware\AuthMiddleware;
use BVOTE\Core\Middleware\RateLimitMiddleware;

// Khởi tạo router
$router = new Router();

// Middleware toàn cục
$router->use(new RateLimitMiddleware());

// Health check route
$router->get('/health', function() {
    include __DIR__ . '/pages/HealthCheckPage.php';
});

// Routes công khai
$router->get('/', function() {
    include __DIR__ . '/pages/HomePage.php';
});

$router->get('/login', function() {
    include __DIR__ . '/pages/UserLoginPage.php';
});

$router->get('/register', function() {
    include __DIR__ . '/pages/UserRegisterPage.php';
});

// API Routes
$router->group('/api', function($router) {
    $router->post('/auth/login', 'BVOTE\Controllers\AuthController@login');
    $router->post('/auth/register', 'BVOTE\Controllers\AuthController@register');
    $router->post('/auth/logout', 'BVOTE\Controllers\AuthController@logout');

    $router->group('/v1', function($router) {
        $router->get('/contests', 'BVOTE\Controllers\ContestController@index');
        $router->get('/contests/{id}', 'BVOTE\Controllers\ContestController@show');
        $router->post('/contests/{id}/vote', 'BVOTE\Controllers\VoteController@store');
    });
});

// Admin Routes
$router->group('/admin', function($router) {
    $router->get('/login', function() {
        include __DIR__ . '/pages/admin/AdminLoginPage.php';
    });

    $router->post('/login', 'BVOTE\Controllers\Admin\AuthController@login');

    // Admin routes cần authentication
    $router->group('', function($router) {
        $router->get('/dashboard', function() {
            include __DIR__ . '/pages/admin/DashboardPage.php';
        });

        $router->get('/logout', 'BVOTE\Controllers\Admin\AuthController@logout');

        // Contest Management
        $router->group('/contests', function($router) {
            $router->get('/', function() {
                include __DIR__ . '/pages/admin/contest/ContestsPage.php';
            });
            $router->get('/create', function() {
                include __DIR__ . '/pages/admin/contest/CreateContestPage.php';
            });
            $router->get('/{id}/edit', function($id) {
                include __DIR__ . '/pages/admin/contest/EditContestPage.php';
            });
        });

        // User Management
        $router->group('/users', function($router) {
            $router->get('/', function() {
                include __DIR__ . '/pages/admin/user/UserManagementPage.php';
            });
        });

        // Auto Login Management
        $router->group('/auto-login', function($router) {
            $router->get('/', function() {
                include __DIR__ . '/pages/admin/auto-login/AutoLoginManagementPage.php';
            });
        });

        // Settings
        $router->group('/settings', function($router) {
            $router->get('/', function() {
                include __DIR__ . '/pages/admin/settings/SystemSettingsPage.php';
            });
        });
    }, [AuthMiddleware::class]);
});

// User Routes (cần authentication)
$router->group('/user', function($router) {
    $router->get('/dashboard', function() {
        include __DIR__ . '/pages/UserHomePage.php';
    });

    $router->get('/profile', function() {
        include __DIR__ . '/pages/UserProfilePage.php';
    });

    $router->get('/logout', function() {
        include __DIR__ . '/pages/UserLogoutPage.php';
    });
}, [AuthMiddleware::class]);

// Voting Routes
$router->group('/voting', function($router) {
    $router->get('/', function() {
        include __DIR__ . '/pages/voting/ContestsListPage.php';
    });

    $router->get('/contest/{id}', function($id) {
        include __DIR__ . '/pages/voting/ContestDetailPage.php';
    });

    $router->post('/contest/{id}/vote', 'BVOTE\Controllers\VoteController@store');
});

// Error handling
$router->setNotFoundHandler(function() {
    http_response_code(404);
    include __DIR__ . '/templates/404.php';
});

$router->setErrorHandler(function($error) {
    http_response_code(500);
    include __DIR__ . '/templates/error.php';
});

// Dispatch request
$router->dispatch();
