@echo off
echo 🚀 Starting BVOTE Server...
echo ============================
echo.
echo 📍 URL: http://localhost:8000
echo 📁 Document Root: public
echo.
echo ⚠️  Press Ctrl+C to stop the server
echo.

cd public
php -S localhost:8000

echo.
echo 🛑 Server stopped.
pause
