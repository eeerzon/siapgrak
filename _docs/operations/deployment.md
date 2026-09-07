# Deployment — Siapgrak

**Status:** belum pernah deploy. Platform hosting belum diputuskan per 2026-09-07 — dokumen ini diisi konkret begitu keputusan platform dibuat (kemungkinan mengikuti pola project Kreen lain di Hostinger, lihat `_meta/PORT-REGISTRY.md` dan memory `reference_hostinger_*` untuk pola existing, atau Vercel/lain kalau diputuskan berbeda).

## Environment

```text
dev          local development (Laravel Sail/Herd/Valet — belum ditentukan)
staging      belum ada
production   belum ada, domain siapgrak.id belum di-setup
```

## Environment Variables Wajib (minimum yang sudah diketahui dibutuhkan)

```text
APP_URL
APP_TIMEZONE=Asia/Jakarta
DB_* (koneksi MySQL)
KREENCONNECT_API_BASE_URL       (placeholder, belum ada endpoint resmi — lihat architecture/integration.md)
KREENCONNECT_API_CLIENT_ID
KREENCONNECT_API_CLIENT_SECRET
KREENCONNECT_WEBHOOK_SECRET     (kalau webhook dipakai)
```

Daftar ini akan bertambah seiring Phase 0-6 dikerjakan — update dokumen ini setiap ada env var baru (lihat `quality/definition-of-done.md`).

## Cara Deploy (Belum Ditentukan)

Prosedur konkret (git push trigger / manual deploy / CI pipeline) akan diisi begitu platform hosting diputuskan. Placeholder urutan generik Laravel:

```text
1. git pull / deploy artifact ke server
2. composer install --no-dev --optimize-autoloader
3. php artisan migrate --force
4. php artisan config:cache
5. php artisan route:cache
6. php artisan view:cache
7. restart queue worker (kalau ada job berjalan)
8. restart PHP-FPM/web server
```

## Trigger Deploy

Belum ditentukan (manual vs auto via git push) — akan diputuskan bersamaan dengan pemilihan platform hosting.
