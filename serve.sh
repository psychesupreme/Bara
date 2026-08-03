#!/bin/bash

# BARA Platform Staging Operations Launch Script (Local Wi-Fi Network Enabled: 0.0.0.0:8000)

echo "======================================================================"
echo "🚀 Launching BARA Platform Local Network Server & WebSockets..."
echo "======================================================================"

# Start Laravel HTTP Server bound to 0.0.0.0
php artisan serve --host=0.0.0.0 --port=8000 &
SERVER_PID=$!

# Start Laravel Reverb WebSocket Server
php artisan reverb:start --host=0.0.0.0 --port=8080 &
REVERB_PID=$!

# Start Vite Compiler
npm run dev &
VITE_PID=$!

echo "======================================================================"
echo "✅ Local Mobile Network Server Active!"
echo "📱 Mobile API Base URL: http://192.168.100.6:8000/api/v1"
echo "🌐 Web Admin Dashboard: http://192.168.100.6:8000/dashboard"
echo "📡 Reverb WebSockets: ws://192.168.100.6:8080/app"
echo "======================================================================"

# Wait for background processes
wait $SERVER_PID $REVERB_PID $VITE_PID
