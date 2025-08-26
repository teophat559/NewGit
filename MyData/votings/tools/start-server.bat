@echo off
REM BVOTE Start Server Script for Windows
REM Khởi động development server

echo 🚀 Starting BVOTE Development Server...
echo ======================================
echo.

set PROJECT_DIR=%~dp0..

REM Kiểm tra PHP
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP is not available. Please install PHP first.
    pause
    exit /b 1
)

REM Kiểm tra thư mục public
if not exist "%PROJECT_DIR%\public" (
    echo ❌ Public directory not found. Please run deploy-windows.bat first.
    pause
    exit /b 1
)

REM Kiểm tra file index.php
if not exist "%PROJECT_DIR%\public\index.php" (
    echo ❌ index.php not found. Please run deploy-windows.bat first.
    pause
    exit /b 1
)

echo ✅ PHP is available
echo ✅ Public directory found
echo ✅ index.php found
echo.

echo 🌐 Starting development server...
echo 📍 URL: http://localhost:8000
echo 📁 Document Root: %PROJECT_DIR%\public
echo.
echo 🔧 Available endpoints:
echo    • Home: http://localhost:8000/
echo    • Health Check: http://localhost:8000/health
echo    • Vote System: http://localhost:8000/vote
echo.
echo ⚠️  Press Ctrl+C to stop the server
echo.

REM Khởi động PHP development server
cd /d "%PROJECT_DIR%\public"
php -S localhost:8000

echo.
echo 🛑 Server stopped.
pause
