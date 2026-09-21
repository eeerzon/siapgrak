# Deployment — Siapgrak

**Status:** belum pernah deploy. Platform hosting belum diputuskan per 2026-09-07 — dokumen ini diisi konkret begitu keputusan platform dibuat (kemungkinan mengikuti pola project Kreen lain di Hostinger, lihat `_meta/PORT-REGISTRY.md` dan memory `reference_hostinger_*` untuk pola existing, atau Vercel/lain kalau diputuskan berbeda).

## Local Development (Mac — di-setup 2026-09-10, dipindah ke XAMPP 2026-09-14)

```text
PHP        8.4 (Homebrew)
Node       26 (Homebrew) — npm run build manual, tidak ada Vite dev server
MySQL      XAMPP MariaDB 10.4.17 di PORT 3306 (default XAMPP).
           Start:  buka /Applications/XAMPP/manager-osx.app -> tab MySQL -> Start
                   (atau `sudo /Applications/XAMPP/xamppfiles/xampp startmysql` di
                   terminal langsung — Claude/AI TIDAK bisa jalanin ini sendiri,
                   butuh password admin Mac lewat prompt interaktif)
```

**Riwayat:** 2026-09-10 sempat pakai Homebrew MySQL 26.x di port 8065 (biar coexist dengan XAMPP yang saat itu dianggap bentrok di 3306). 2026-09-14 dipindah balik ke XAMPP (port 3306) atas permintaan Ersa — lebih mudah dicek lewat phpMyAdmin bawaan XAMPP. Config Homebrew MySQL (`/opt/homebrew/etc/my.cnf`, port 8065) **masih terpasang tapi sudah tidak dipakai project ini** — service-nya (`brew services list`) boleh di-stop kalau tidak dibutuhkan project lain.

**Database:** `siapgrak` (dev) + `siapgrak_testing` (phpunit) di XAMPP MariaDB — `root`, tanpa password (default XAMPP, localhost-only; bukan secret — production pakai user scoped + password, lihat `security/security-baseline.md`).

**`.env` lokal:**
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siapgrak
DB_USERNAME=root
DB_PASSWORD=
```

**Lihat data lewat phpMyAdmin:** `http://localhost/phpmyadmin` (bawaan XAMPP, otomatis nunjuk ke MariaDB port 3306 yang sama) — tidak perlu setup tambahan begitu XAMPP MySQL jalan.

**Test suite jalan di MySQL/MariaDB** (`phpunit.xml` → `DB_CONNECTION=mysql`, `DB_DATABASE=siapgrak_testing`) — paritas dengan target production, bukan sqlite lagi. Butuh database `siapgrak_testing` ada sebelum `php artisan test`. Kalau perlu sqlite cepat sesekali: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test`.

**Catatan:** `.env.example` masih ship `DB_CONNECTION=sqlite` sebagai fallback zero-setup (fresh clone tanpa MySQL tetap bisa `migrate`). Dev yang ikut paritas MySQL/XAMPP set `.env` manual sesuai di atas.

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
