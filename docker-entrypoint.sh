#!/bin/bash
set -e

echo "============================================"
echo " Radar Ilmu 2 Bot — Starting up..."
echo "============================================"

# ── Laravel Bootstrap ──────────────────────────────────────────────────────────
echo "→ Discovering packages..."
php artisan package:discover --ansi

echo "→ Caching config..."
php artisan config:cache

echo "→ Caching routes..."
php artisan route:cache

echo "→ Caching views..."
php artisan view:cache

# ── Database Migration with retry ─────────────────────────────────────────────
echo "→ Running database migrations..."
MAX_RETRIES=5
COUNT=0

until php artisan migrate --force 2>&1; do
    COUNT=$((COUNT + 1))
    if [ "$COUNT" -ge "$MAX_RETRIES" ]; then
        echo "❌ Migration gagal setelah $MAX_RETRIES percobaan."
        echo "   Pastikan DB_HOST menggunakan Supabase Connection Pooler (IPv4),"
        echo "   bukan direct connection (IPv6) yang tidak bisa diakses Render."
        exit 1
    fi
    echo "  ⟳ Percobaan $COUNT/$MAX_RETRIES gagal, coba lagi dalam 5 detik..."
    sleep 5
done

echo "============================================"
echo " ✅ Startup complete. Launching Apache..."
echo "============================================"

exec "$@"
