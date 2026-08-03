@echo off
TITLE BARA Platform Staging Launch & Mobile Local Network Server
echo ======================================================================
echo 🚀 Starting BARA Platform Server (Local Wi-Fi Network Enabled: 0.0.0.0:8000)...
echo ======================================================================

echo [1/3] Starting Laravel HTTP Core API on http://0.0.0.0:8000 (Local IP: http://192.168.100.6:8000) ...
start "BARA Laravel Server" cmd /k "php artisan serve --host=0.0.0.0 --port=8000"

echo [2/3] Starting Laravel Reverb WebSocket Server on port 8080 ...
start "BARA Reverb WebSockets" cmd /k "php artisan reverb:start --host=0.0.0.0 --port=8080"

echo [3/3] Starting Vite Web Admin Asset Compiler ...
start "BARA Vite Compiler" cmd /k "npm run dev"

echo ======================================================================
echo ✅ All Staging & Local Mobile Network Services Active!
echo 📱 Mobile API Base URL: http://192.168.100.6:8000/api/v1
echo 🌐 Web Admin Dashboard: http://192.168.100.6:8000/dashboard
echo 📡 Reverb WebSockets: ws://192.168.100.6:8080/app
echo ======================================================================
pause
