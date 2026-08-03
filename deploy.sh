#!/bin/bash

# BARA Platform Zero-Downtime Production Deployment Script

set -e

echo "🚀 Starting BARA Platform Zero-Downtime Deployment..."

# 1. Bring up containers in detached mode
echo "📦 Building and starting Docker containers..."
docker compose up -d --build --remove-orphans

# 2. Run Database Migrations for Tenant Schemas
echo "🗄️ Executing database migrations..."
docker compose exec -T app php artisan migrate --path=database/migrations/tenant --force

# 3. Clear and Re-cache Application Configuration
echo "⚡ Optimizing application caches..."
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app php artisan event:cache

# 4. Restart Background Workers & Reverb WebSockets
echo "🔄 Restarting Queue Workers & Reverb WebSockets..."
docker compose exec -T queue php artisan queue:restart
docker compose restart reverb

echo "✅ BARA Platform Deployment Completed Successfully!"
