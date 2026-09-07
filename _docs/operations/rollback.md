# Rollback — Siapgrak

**Status:** belum pernah deploy, jadi belum pernah dites secara nyata. Isi di bawah adalah rencana yang wajib diverifikasi sebelum deploy pertama ke production (lihat `quality/release-checklist.md` §Rollback Decision).

## Aplikasi (Code)

Revert ke commit/deployment sebelumnya lewat mekanisme platform hosting (belum ditentukan — lihat `operations/deployment.md`). Karena repo tunggal tanpa microservices, rollback code selalu satu unit, tidak ada koordinasi antar service yang perlu disinkronkan.

## Migration

Setiap migration WAJIB punya `down()` yang bekerja (dicek di `quality/definition-of-done.md`). Migration yang **TIDAK aman di-reverse** (harus ditandai eksplisit di PR/commit-nya begitu terjadi):
- Migration yang menghapus kolom berisi data (data hilang permanen kalau di-rollback lalu roll-forward lagi tanpa backup)
- Migration yang mengubah tipe kolom secara destruktif (mis. DECIMAL → INT yang membuang presisi skor)

Belum ada migration seperti ini karena belum ada migration sama sekali — daftar ini akan bertambah seiring project berjalan, catat di sini begitu migration berisiko dibuat.

## Config

Rollback environment variable = revert ke `.env` versi sebelumnya (disimpan di password manager/secret store, bukan git — lihat `security/security-baseline.md`).

## Frontend Asset (Blade/Tailwind compiled)

Ikut rollback code (asset di-build ulang dari commit yang di-revert-kan).

## Queue Job

Belum ada job yang berjalan (Phase 0-5 belum pakai queue wajib). Begitu Phase 6 (sync Kreenconnect) mulai memakai scheduled job, tambahkan di sini: cara pause job yang sedang berjalan sebelum rollback, dan cara memastikan job tidak diproses dobel setelah rollback selesai.

## Verifikasi Rollback Berhasil

```text
1. Homepage & login accessible
2. Alur kritikal phase yang di-rollback berfungsi seperti versi sebelumnya
3. Tidak ada error baru di log dibanding sebelum deploy yang di-rollback
4. Data tidak corrupt (spot check beberapa row di tabel yang terdampak migration)
```

Update dokumen ini setiap ada rollback nyata yang dijalankan — catat apa yang berhasil/tidak, pindahkan pelajarannya ke `operations/runbook.md` kalau jadi failure mode yang berulang.
