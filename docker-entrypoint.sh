#!/bin/bash
set -e

echo "============================================"
echo " Radar Ilmu 2 Bot — Starting up..."
echo "============================================"

# ── Laravel Bootstrap ──────────────────────────────────────────────────────────
echo "→ Caching config..."
php artisan config:cache

echo "→ Caching routes..."
php artisan route:cache

echo "→ Caching views..."
php artisan view:cache

echo "→ Running database migrations..."
php artisan migrate --force

echo "============================================"
echo " ✅ Startup complete. Launching Apache..."
echo "============================================"

# Hand off to the CMD (apache2-foreground)
exec "$@"
