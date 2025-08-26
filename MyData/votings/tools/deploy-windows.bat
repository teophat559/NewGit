@echo off
REM BVOTE Windows Deployment Script
REM Triển khai hệ thống trên Windows

echo 🚀 BVOTE Windows Deployment Starting...
echo ======================================
echo.

set PROJECT_DIR=%~dp0..
set BACKUP_DIR=%PROJECT_DIR%\storage\backups
set LOG_FILE=%PROJECT_DIR%\storage\logs\deployment.log

REM Tạo thư mục logs nếu chưa có
if not exist "%PROJECT_DIR%\storage\logs" mkdir "%PROJECT_DIR%\storage\logs"

echo 📋 Step 1: Checking prerequisites...
echo ------------------------------------

REM Kiểm tra PHP
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP is not available. Please install PHP first.
    pause
    exit /b 1
)
echo ✅ PHP is available

REM Kiểm tra Composer
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Composer is not available. Please install Composer first.
    pause
    exit /b 1
)
echo ✅ Composer is available

REM Kiểm tra Node.js
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ⚠️  Node.js is not available. Frontend build will be skipped.
    set SKIP_NODE=1
) else (
    echo ✅ Node.js is available
    set SKIP_NODE=0
)

echo.
echo 📋 Step 2: Preparing system...
echo ------------------------------

REM Tạo thư mục cần thiết
if not exist "%PROJECT_DIR%\storage\backups" mkdir "%PROJECT_DIR%\storage\backups"
if not exist "%PROJECT_DIR%\storage\cache" mkdir "%PROJECT_DIR%\storage\cache"
if not exist "%PROJECT_DIR%\storage\sessions" mkdir "%PROJECT_DIR%\storage\sessions"
if not exist "%PROJECT_DIR%\uploads" mkdir "%PROJECT_DIR%\uploads"

echo ✅ System directories created

echo.
echo 📋 Step 3: Installing dependencies...
echo ------------------------------------

REM Cài đặt PHP dependencies
echo 🔄 Installing PHP dependencies...
composer install --no-dev --optimize-autoloader
if %errorlevel% neq 0 (
    echo ❌ PHP dependencies installation failed
    pause
    exit /b 1
)
echo ✅ PHP dependencies installed

REM Cài đặt Node.js dependencies nếu có
if "%SKIP_NODE%"=="0" (
    echo 🔄 Installing Node.js dependencies...
    npm ci --production
    if %errorlevel% neq 0 (
        echo ⚠️  Node.js dependencies installation failed, continuing...
    ) else (
        echo ✅ Node.js dependencies installed
        
        REM Build frontend assets
        echo 🔄 Building frontend assets...
        npm run build
        if %errorlevel% neq 0 (
            echo ⚠️  Frontend build failed, continuing...
        ) else (
            echo ✅ Frontend assets built
        )
    )
)

echo.
echo 📋 Step 4: Environment configuration...
echo --------------------------------------

REM Kiểm tra file .env
if not exist "%PROJECT_DIR%\.env" (
    echo 🔄 Creating .env file from template...
    copy "%PROJECT_DIR%\.env.example" "%PROJECT_DIR%\.env" >nul
    echo ✅ .env file created
) else (
    echo ✅ .env file already exists
)

echo.
echo 📋 Step 5: Database setup...
echo ----------------------------

REM Kiểm tra kết nối database
echo 🔄 Testing database connection...
php "%PROJECT_DIR%\tools\test-database.php"
if %errorlevel% neq 0 (
    echo ⚠️  Database connection failed. Please check your database configuration.
    echo    You can manually run: php tools\setup-db-simple.php
) else (
    echo ✅ Database connection successful
    
    REM Chạy setup database
    echo 🔄 Setting up database...
    php "%PROJECT_DIR%\tools\setup-db-simple.php"
    if %errorlevel% neq 0 (
        echo ❌ Database setup failed
    ) else (
        echo ✅ Database setup completed
    )
)

echo.
echo 📋 Step 6: System health check...
echo ---------------------------------

REM Kiểm tra hệ thống
echo 🔄 Running system health check...
php "%PROJECT_DIR%\tools\test-system.php"

echo.
echo 📋 Step 7: Final configuration...
echo --------------------------------

REM Tạo file index.php nếu chưa có
if not exist "%PROJECT_DIR%\public\index.php" (
    echo 🔄 Creating public directory and index.php...
    if not exist "%PROJECT_DIR%\public" mkdir "%PROJECT_DIR%\public"
    
    echo ^<?php > "%PROJECT_DIR%\public\index.php"
    echo require_once __DIR__ . '/../bootstrap.php'; >> "%PROJECT_DIR%\public\index.php"
    echo. >> "%PROJECT_DIR%\public\index.php"
    echo // Route to appropriate controller based on URL >> "%PROJECT_DIR%\public\index.php"
    echo $requestUri = $_SERVER['REQUEST_URI'] ?? '/'; >> "%PROJECT_DIR%\public\index.php"
    echo $path = parse_url($requestUri, PHP_URL_PATH); >> "%PROJECT_DIR%\public\index.php"
    echo. >> "%PROJECT_DIR%\public\index.php"
    echo switch ($path) { >> "%PROJECT_DIR%\public\index.php"
    echo     case '/': >> "%PROJECT_DIR%\public\index.php"
    echo         echo 'BVOTE Voting System - Welcome!'; >> "%PROJECT_DIR%\public\index.php"
    echo         break; >> "%PROJECT_DIR%\public\index.php"
    echo     case '/health': >> "%PROJECT_DIR%\public\index.php"
    echo         require_once __DIR__ . '/../pages/HealthCheckPage.php'; >> "%PROJECT_DIR%\public\index.php"
    echo         break; >> "%PROJECT_DIR%\public\index.php"
    echo     case '/vote': >> "%PROJECT_DIR%\public\index.php"
    echo         require_once __DIR__ . '/../vote.php'; >> "%PROJECT_DIR%\public\index.php"
    echo         break; >> "%PROJECT_DIR%\public\index.php"
    echo     default: >> "%PROJECT_DIR%\public\index.php"
    echo         http_response_code(404); >> "%PROJECT_DIR%\public\index.php"
    echo         include __DIR__ . '/../templates/404.php'; >> "%PROJECT_DIR%\public\index.php"
    echo         break; >> "%PROJECT_DIR%\public\index.php"
    echo } >> "%PROJECT_DIR%\public\index.php"
    
    echo ✅ Public directory and index.php created
)

echo.
echo 🎉 BVOTE Windows Deployment Completed!
echo ======================================
echo.
echo 📋 System Information:
echo    • Project Directory: %PROJECT_DIR%
echo    • Public Directory: %PROJECT_DIR%\public
echo    • Storage Directory: %PROJECT_DIR%\storage
echo    • Environment File: %PROJECT_DIR%\.env
echo.
echo 🌐 Access Information:
echo    • Local Development: http://localhost:8000
echo    • Health Check: http://localhost:8000/health
echo    • Vote System: http://localhost:8000/vote
echo.
echo 🔧 Development Commands:
echo    • Start PHP Server: php -S localhost:8000 -t public
echo    • Test System: php tools\test-system.php
echo    • Test Database: php tools\test-database.php
echo    • Setup Database: php tools\setup-db-simple.php
echo.
echo ⚠️  Important Notes:
echo    • This is a development setup
echo    • For production, use proper web server (Apache/Nginx)
echo    • Configure database connection in .env file
echo    • Test all functionality before production use
echo.
echo 🚀 Your BVOTE system is now ready for development!
echo    Start the development server with: php -S localhost:8000 -t public
echo.

pause
