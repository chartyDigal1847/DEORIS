#!/bin/bash
# DEORIS Production Deployment Script
# Run this on your VPS after cloning the repo

set -e

echo "==> Copying production env..."
cp .env.production .env

echo "==> Building and starting containers..."
docker compose up -d --build

echo "==> Waiting for MySQL to be ready..."
sleep 10

echo "==> Running migrations..."
docker compose exec app php artisan migrate --force

echo "==> Caching config, routes, views..."
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache

echo "==> Setting storage permissions..."
docker compose exec app chmod -R 775 storage bootstrap/cache

echo "==> Done! DEORIS is running at https://deoris.net"
