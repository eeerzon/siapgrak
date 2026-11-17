# FR — Penjurian Info Lomba

**Status:** Selesai (2026-11-16). Permintaan user: penjurian dipindah dari modul event panitia ke Info Lomba (`competition_listings`) dengan tabel terpisah. Data event diabaikan dulu. Modul event lama (`events`, `judging_rounds`, `score_sheets`, dst.) tidak diubah dan tetap di `/judge`.

## Tabel (migrasi `2026_11_16_100001`)

| Tabel | Isi | Terhubung ke |
|---|---|---|
| `competition_categories` | Kategori lomba | `competition_listing_id` |
| `competition_participants` | Peserta/tim, nomor & urutan tampil | `competition_listing_id`, kategori |
| `competition_rounds` | Babak | kategori |
| `competition_criteria` | Kriteria, bobot %, rentang nilai `DECIMAL(10,3)` | babak |
| `competition_judges` | Juri + kode akses 8 karakter | `id_user` (admin pemilik) |
| `competition_judge_assignments` | Juri yang menilai sebuah babak | babak, juri |
| `competition_judgings` | Penjurian = lembar nilai satu juri untuk satu peserta di satu babak, status `draft`/`submitted`/`locked` | `competition_listing_id`, babak, peserta, juri |
| `competition_scores` | Nilai per kriteria | penjurian, kriteria |
| `competition_judging_revisions` | Riwayat buka ulang (alasan wajib) | penjurian, `id_user` |
| `competition_participant_results` | Hasil/peringkat per babak | `competition_listing_id`, babak, peserta |

Semua foreign key `RESTRICT`: data yang sudah punya nilai tidak bisa terhapus lewat cascade. Hapus kategori, peserta, babak, kriteria, lepas juri, atau hapus Info Lomba ditolak dengan pesan kalau sudah ada data penilaian.

## Keputusan

- **A. Kepemilikan:** ruang kerja penjurian hanya untuk pemilik Info Lomba (`PublicContentPolicy`). Juri milik admin yang membuatnya, dan hanya juri sendiri yang bisa ditugaskan.
- **B. Panel Juri `/juri`:** login dengan kode akses (sesi `competition_judge_id`, terpisah dari `/judge`). Juri hanya melihat babak yang ditugaskan dan lembarnya sendiri. Membuka halaman peserta tidak membuat lembar kosong; lembar dibuat saat pertama kali disimpan.
- **C. Alur status:** draft → kirim (semua kriteria wajib terisi) → dikunci admin. Buka ulang (dari terkirim atau dikunci) wajib alasan, tercatat di `competition_judging_revisions`, nilai lama tetap ada.
- **D. Hasil:** hanya lembar terkirim/dikunci. Nilai per kriteria dirata-rata antarjuri; nilai akhir = Σ(rata-rata × bobot/100). Peserta dengan kriteria belum lengkap tidak diperingkat. Nilai sama berbagi peringkat (1, 1, 3). Hitung ulang = hapus lalu bangun ulang per babak.
- **F. Siap dinilai dan terkunci (2026-11-16):** bobot kriteria wajib diisi (> 0). Juri baru bisa menyimpan atau mengirim nilai kalau babak punya kriteria dan total bobotnya tepat 100% (`isReadyForJudging`). Pengecekan dilakukan sebelum lembar dibuat, jadi percobaan yang ditolak tidak meninggalkan lembar. Begitu babak punya satu lembar nilai (`hasStarted`), kriteria terkunci total: tidak bisa ditambah, diubah (termasuk nama dan keterangan, sejak 2026-11-17), atau dihapus, dan tombol Edit disembunyikan. Dengan begitu semua lembar dinilai dengan aturan yang sama dan nilai akhir selalu berskala 0–100.
- **G. Hasil publik (2026-11-17):** satu halaman per Info Lomba, `/info-lomba/{slug}/hasil` (404 kalau Info Lomba masih draft atau belum punya babak). Halaman mandiri yang mengikuti desain prototipe `kreen-judge-prototype/judge-mobile` (`public_results_screen.dart`): tanpa menu situs, hanya logo dan nama Siapgrak, warna yang sama dengan dashboard admin (bar atas navy, konten terang slate-50 dengan kartu putih, aksen merah brand; mode gelap opsional lewat tombol, diingat di localStorage), nilai per kriteria ditampilkan berbobot (nilai × bobot/100) sehingga jumlahnya sama dengan total juri, dan kotak foto berisi angka nomor tampil. kartu per peserta berisi peringkat (SERI kalau sama), nomor, nama, total tiap juri, dan nilai akhir, lalu tabel kriteria × juri saat kartu dibuka. Juri anonim ("Juri 1", "Juri 2", … menurut urutan penugasan; "Juri" kalau hanya satu). Nilai hanya tampil untuk babak yang **sudah dipublikasikan admin** (`competition_rounds.results_published_at`). Sebelum itu tampil "{babak} masih berlangsung", sesuai aturan CLAUDE.md bahwa ranking sementara tidak boleh bocor. "Publikasikan Hasil" menghitung ulang dulu dan gagal kalau belum ada peserta dengan nilai lengkap. "Tarik dari publik" mengembalikannya ke privat. Selama hasil tampil publik, lembar nilai babak itu tidak bisa dibuka ulang. Tombol "Salin Link" di tab Lembar Nilai dan Hasil muncul begitu ada satu lembar terkirim atau dikunci; kalau Clipboard API gagal (http di LAN atau izin ditolak), sistem memakai `execCommand('copy')`, lalu meminta Ctrl+C.
- **H. Kejelasan di layar (2026-11-17):** "Kirim Skor Final" mengirim form nilai yang sama dengan `kirim=1` (simpan lalu kirim dalam satu request) dan nonaktif sampai semua kriteria terisi. Kode akses diabaikan selain huruf dan angka. Tab Lembar Nilai punya daftar cek kesiapan per babak, dan menugaskan juri ke babak yang belum siap memberi catatan. Tab Hasil menampilkan "Belum diperingkat" beserta alasan dan juri yang ditunggu. Salin Link nonaktif selama Info Lomba draft, dan Buka ulang tampil nonaktif selama hasil publik.
- **E. Disederhanakan dibanding modul lama:** tanpa kriteria bertingkat, tanpa penugasan juri per kriteria, tanpa finalisasi terpisah (publikasi per babak sudah ada, lihat G).

## URL

- Admin: `/dashboard/content/competitions/{id}/judging` (tab Kategori & Peserta, Babak & Juri, Lembar Nilai, Hasil), `/dashboard/content/judges`.
- Juri: `/juri/masuk`.
- Publik: `/info-lomba/{slug}/hasil`.

## Test

`tests/Feature/CompetitionJudging/CompetitionJudgingTest.php` (20 test, termasuk siap dinilai, bobot wajib, kriteria terkunci, hasil publik, dan kejelasan di layar): kepemilikan, setup dan juri milik sendiri, larangan hapus, login kode akses, juri hanya babaknya, lihat tidak membuat lembar, draft → kirim lengkap → terkunci, juri tidak melihat nilai juri lain, kunci & buka ulang tercatat, perhitungan hasil.
