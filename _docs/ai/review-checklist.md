# AI Review Checklist

Checklist yang WAJIB dijalankan Claude sendiri sebelum menyatakan pekerjaan selesai — sebelum lapor ke user.

- [ ] Lint (Laravel Pint) — 0 error
- [ ] Test golden path + minimal edge case sudah dijalankan (lihat `quality/test-strategy.md` untuk area berisiko tinggi yang wajib ditest lebih ketat)
- [ ] Tidak ada TODO/FIXME/dead code tersisa tanpa alasan jelas
- [ ] Tidak ada data sensitif ter-expose (log, response, client-side) — cek khusus credential Kreenconnect tidak bocor ke frontend/response API
- [ ] Konsisten dengan requirement di SRS/BRD terkait — tidak ada penyimpangan tanpa konfirmasi
- [ ] Semua file yang seharusnya diubah (sesuai §"File yang HARUS Diubah" di SRS) sudah diubah — tidak ada yang terlewat
- [ ] Dokumentasi terkait diupdate kalau ada perubahan signifikan (SRS status, `operations/deployment.md` kalau ada env var baru, `tasks/tasks.md` status sprint)
- [ ] Tidak melanggar Prinsip Tidak Bisa Diganggu Gugat di CLAUDE.md — cek khusus:
  - [ ] Tidak ada tabel domain Kreenconnect (`tickets/orders/payments/votes/*`) yang dibuat
  - [ ] Tidak ada scope creep ke sprint berikutnya (lihat `tasks/tasks.md` — sprint saat ini dibatasi eksplisit)
  - [ ] Tenant isolation (Policy) ada untuk resource baru yang punya `organization_id`/`event_id`
  - [ ] Judge isolation tidak bocor (kalau menyentuh area judging)

## Bug Workflow (non-trivial bug, wajib urutan ini)

```text
REPRODUCE / EVIDENCE → ROOT CAUSE → REGRESSION TEST → FIX → VERIFY ORIGINAL CASE → VERIFY RELATED CASES
```

Jangan fix langsung dari gejala yang dilaporkan tanpa reproduce dan cari root cause dulu.
