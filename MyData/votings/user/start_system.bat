@echo off
echo ================================
echo BVOTE 2025 - Real-time Control System
echo ================================
echo.

echo [1/4] Checking PHP version...
php -v
if %errorlevel% neq 0 (
    echo ERROR: PHP not found or not in PATH
    pause
    exit /b 1
)

echo.
echo [2/4] Testing WebSocket configuration...
php test_websocket.php
if %errorlevel% neq 0 (
    echo ERROR: WebSocket test failed
    pause
    exit /b 1
)

echo.
echo [3/4] Starting WebSocket Server...
echo Server will run on port 8080
echo Press Ctrl+C to stop server
echo.
echo Admin Panel: http://localhost/admin/realtime_control.html
echo.

echo [4/4] Launching server...
php start_websocket.php

pause
