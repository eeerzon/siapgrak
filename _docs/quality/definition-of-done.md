# Definition of Done

**Prinsip inti: "Done means verified, not implemented."** Kode yang sudah ditulis BUKAN kriteria selesai — verifikasi sesuai risiko fitur itu yang jadi kriteria.

- [ ] Requirement & acceptance criteria di SRS/BRD terpenuhi
- [ ] Authorization diverifikasi (bukan cuma authentication) — tenant isolation ditest eksplisit untuk resource yang punya `organization_id`/`event_id`
- [ ] Validation diverifikasi — semua field wajib di §Validation Rules SRS terkait sudah ditest
- [ ] Failure path ditest (bukan cuma golden path) — lihat §Testing Plan di SRS terkait
- [ ] Regression test ditambahkan kalau relevan (fix bug non-trivial wajib ada regression test)
- [ ] Data integrity dicek: uniqueness constraint, race condition (khusus competition number generation & checkout idempotency), idempotency
- [ ] Logging/observability memadai untuk fitur ini (`activity_logs` untuk aksi high-value sesuai `srs/nfr.md` §Observability)
- [ ] Dokumentasi terkait ter-update (SRS/BRD/ADR status → Implemented, kalau ada keputusan baru selama implementasi yang belum tercatat)
- [ ] Migration di-review — `up()` dan `down()` sama-sama jalan, index/FK sesuai checklist di `research/chatgpt-dev-checklist-migration-routes.md` §"Database Checklist per Migration"
- [ ] Rollback path dipahami sebelum deploy (lihat `operations/rollback.md`)
- [ ] Implikasi deployment didokumentasikan kalau ada env var baru/cron baru (`operations/deployment.md` diupdate)
- [ ] Lint/type check bersih (Laravel Pint 0 error)
- [ ] Tidak ada perubahan yang tidak terkait permintaan (surgical changes — lihat Prinsip Karpathy #3 di CLAUDE.md)
- [ ] Tidak melanggar Prinsip Tidak Bisa Diganggu Gugat di CLAUDE.md (khususnya: tidak ada tabel domain Kreenconnect yang dibuat, judge isolation tidak bocor, score sheet locked tidak bisa diedit diam-diam)
- [ ] `tasks.md` dipindahkan ke status selesai
