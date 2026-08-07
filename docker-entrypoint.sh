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
MIGRATE_OK=false

until php artisan migrate --force 2>&1; do
    COUNT=$((COUNT + 1))
    if [ "$COUNT" -ge "$MAX_RETRIES" ]; then
        echo "⚠️  WARNING: Migration gagal setelah $MAX_RETRIES percobaan."
        echo "   Server tetap akan dijalankan, tapi fitur DB tidak akan bekerja."
        echo ""
        echo "   ─── FIX YANG DIPERLUKAN ───────────────────────────────────"
        echo "   Render tidak bisa akses IPv6 (Supabase direct connection)."
        echo "   Ganti env vars di Render dashboard:"
        echo "   DB_HOST    = aws-0-[REGION].pooler.supabase.com"
        echo "   DB_USERNAME= postgres.sumjwjzcxfrvwfeqyvnw"
        echo "   DB_PORT    = 5432"
        echo "   ────────────────────────────────────────────────────────────"
        MIGRATE_OK=false
        break
    fi
    echo "  ⟳ Percobaan $COUNT/$MAX_RETRIES gagal, retry dalam 3 detik..."
    sleep 3
done

if [ "$MIGRATE_OK" != "false" ]; then
    echo "✅ Migration berhasil!"
fi

echo "============================================"
echo " Launching Apache... (DB status: see above)"
echo "============================================"

exec "$@"
