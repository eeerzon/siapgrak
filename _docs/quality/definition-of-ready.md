# Definition of Ready

Checklist ini dijalankan sebelum requirement/sprint dianggap siap masuk coding. File tetap — bukan per-fitur.

- [ ] User story + acceptance criteria lengkap dan testable (lihat format di `brd/brd-core.md`)
- [ ] Semua open question sudah dijawab stakeholder (Ersa) — bukan diasumsikan AI. Lihat daftar Open Questions di SRS terkait (`srs/fr-*.md` §Open Questions) — kalau ada baris terisi, sprint belum ready.
- [ ] Trade-off teknis (kalau ada >1 opsi viable) sudah didiskusikan & diputuskan, dicatat di BRD atau ADR terpisah (lihat `decisions/README.md` untuk kapan butuh ADR)
- [ ] Dependency eksternal (API, data, akses, kredensial) sudah dikonfirmasi TERSEDIA — bukan "kemungkinan ada". **Khusus untuk sprint yang menyentuh Kreenconnect (Phase 6): kontrak API di `research/chatgpt-blueprint-v1.md` WAJIB dikonfirmasi ulang ke tim Kreenconnect sebelum sprint dimulai** — itu masih proposal.
- [ ] Scope sprint dibatasi eksplisit — instruksi coding menyebutkan apa yang TIDAK diimplementasikan di sprint ini, bukan cuma apa yang diimplementasikan (lihat contoh di `research/chatgpt-dev-checklist-migration-routes.md` §"First Development Instruction")
- [ ] Migration urutan sesuai `research/chatgpt-dev-checklist-migration-routes.md` §"Full Migration Order" — tidak melompat ke nomor migration dari sprint yang belum dikerjakan
- [ ] Tidak ada blocker yang diketahui tidak bisa diselesaikan dalam estimasi sprint
- [ ] Requirement sudah di-approve (Ersa)
