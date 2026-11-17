# Alur Info Lomba sampai Hasil Penilaian

Panduan untuk admin yang mengelola lomba di Siapgrak: dari membuat Info Lomba, menyiapkan penjurian, juri menilai, sampai peringkat tampil di halaman publik.

Semua langkah admin dilakukan setelah masuk ke dashboard (`/login`) dengan akun **admin platform**. Setiap admin hanya melihat dan mengelola Info Lomba dan juri miliknya sendiri.

---

## Gambaran singkat

```
1. Buat Info Lomba            → tampil di /info-lomba (kalau statusnya published)
2. Siapkan penjurian          → kategori, peserta, babak, kriteria (bobot total 100%)
3. Tambah & tugaskan juri     → setiap juri dapat kode akses 8 karakter
4. Juri menilai di /juri      → simpan draft, lalu kirim
5. Admin cek lembar nilai     → kunci, atau buka ulang dengan alasan
6. Hitung & publikasikan      → peringkat tampil di /info-lomba/{slug}/hasil
7. Bagikan link hasil         → tombol "Salin Link"
```

---

## 1. Membuat Info Lomba

**Menu:** Events → tab **Info Lomba** → **+ Tambah Info Lomba**

| Isian | Keterangan |
|---|---|
| Judul | Wajib. Alamat halaman (slug) dibuat otomatis dari judul dan **tidak berubah** walau judul diedit, jadi link yang sudah dibagikan tetap hidup. |
| Ringkasan, Isi Artikel | Teks biasa, misalnya caption Instagram. |
| Poster | JPG/PNG/WEBP, maks 2 MB, **wajib rasio 4:5** (contoh 1080×1350 piksel). |
| Penyelenggara, Kota/Tempat, Provinsi | Untuk kartu lomba dan filter di halaman publik. |
| Tanggal Mulai / Selesai | Tanggal Mulai wajib. |
| Batas Pendaftaran | Setelah tanggal ini, tombol daftar dan contact person disembunyikan dan diganti "Pendaftaran sudah ditutup". Kalau kosong, pendaftaran dianggap buka sampai lomba dimulai. |
| Technical Meeting | Tanggal dan tempat. Disembunyikan kalau tanggalnya kosong. |
| Jenjang | SD/MI, SMP/MTs, SMA/SMK/MA, Umum. Dipakai untuk filter. |
| Pengelolaan | Centang "Event dikelola oleh Kreen" kalau pendaftaran lewat Kreenconnect. Kalau tidak, isi **Link Pendaftaran** atau kosongkan dan isi Contact Person. |
| Contact Person | Maksimal 3, nama dan nomor WhatsApp. Tampil sebagai tombol "Chat WhatsApp". |
| Status | **Draft** = belum tampil di publik. **Published** = tampil di `/info-lomba`. |

> Halaman hasil penilaian hanya bisa dibuka publik kalau Info Lomba berstatus **Published**.

---

## 2. Menyiapkan penjurian

**Menu:** Events → Info Lomba → buka lomba → tombol **Penjurian**

Halaman Penjurian punya empat tab. Kerjakan berurutan.

### Tab "Kategori & Peserta"

1. **Tambah kategori**, misalnya "SMA/SMK Sederajat" dan "SMP/MTs Sederajat".
2. **Tambah peserta** ke tiap kategori: nama tim, asal sekolah/instansi, **nomor tampil** (misalnya `SMA-01`, unik per kategori), dan urutan tampil.
3. Data peserta bisa diedit lewat tombol **Edit** di daftar.

### Tab "Babak & Juri"

1. **Tambah babak** per kategori, misalnya "Babak Final".
2. **Tambah kriteria** di setiap babak:
   - Nama dan keterangan untuk juri (misalnya "Kerapian barisan, ketepatan aba-aba").
   - **Bobot %, wajib diisi.**
   - Rentang nilai (default 0–100).
3. **Pastikan total bobot tepat 100%.** Status babak terlihat di pojok kartu:

   | Label | Arti |
   |---|---|
   | Belum siap · bobot X% dari 100% | Juri **belum bisa** menilai. Tambah atau ubah kriteria sampai totalnya 100%. |
   | Siap dinilai · bobot 100% | Juri bisa mulai menilai. |
   | Penilaian berjalan · kriteria terkunci | Sudah ada juri yang menyimpan nilai. Kriteria tidak bisa diubah lagi (lihat aturan di bawah). |

4. **Tugaskan juri** ke babak (pilih dari daftar juri milik Anda). Kode akses juri tampil di sini.

> **Aturan kunci kriteria:** begitu ada satu lembar nilai di sebuah babak, kriteria **terkunci**: tidak bisa ditambah, diubah, atau dihapus, dan tombol Edit tidak tampil lagi. Ini supaya semua peserta dinilai dengan kriteria yang sama dan nilai akhir selalu berskala 0–100. Jadi rapikan kriteria, termasuk nama dan keterangannya, **sebelum** juri mulai menilai.

---

## 3. Menambah juri

**Menu:** Events → tab **Juri** (atau tombol **Kelola Juri** di halaman Penjurian)

1. Isi nama dan keterangan, lalu **Tambah Juri**.
2. **Kode akses 8 karakter** dibuat otomatis, misalnya `GRDA2345`. Hurufnya tanpa 0/O/1/I/L supaya tidak tertukar saat dibacakan atau ditulis.
3. Berikan kode ini ke juri, misalnya ditulis di kartu meja juri.
4. **Ganti kode akses** kalau kode bocor; kode lama langsung tidak berlaku.
5. Ubah status ke **Nonaktif** untuk mencegah juri masuk.

Juri tidak butuh akun atau password, cukup kode akses.

---

## 4. Juri menilai

**Alamat untuk juri:** `/juri/masuk` (dari HP atau tablet, lewat browser)

1. Juri memasukkan **kode akses**.
2. Beranda menampilkan babak yang ditugaskan beserta progresnya ("3 dari 6 peserta sudah dikirim nilainya").
3. Pilih babak, lalu pilih peserta. Status setiap peserta: **Belum dinilai**, **Draft x/y**, **Terkirim**, atau **Dikunci**.
4. Isi nilai setiap kriteria (sesuai rentang) dan catatan kalau perlu:
   - **Simpan Draft:** nilai tersimpan dan masih bisa diubah.
   - **Kirim Skor Final:** menyimpan isian lalu langsung mengirimnya dalam satu langkah. Tombol ini nonaktif sampai **semua kriteria terisi**, dengan keterangan berapa kriteria yang belum diisi. Setelah dikirim, juri tidak bisa mengubahnya lagi.
5. Kode akses boleh diketik huruf kecil, dengan spasi, atau dengan tanda hubung (misalnya `grda-2345`).

**Yang tidak bisa dilakukan juri:**
- Melihat nilai juri lain.
- Melihat peringkat.
- Menilai babak yang tidak ditugaskan kepadanya.
- Menilai babak yang total bobotnya belum 100%. Muncul pesan "Babak ini belum bisa dinilai".

---

## 5. Memantau lembar nilai

**Tab "Lembar Nilai"** di halaman Penjurian

- Tabel **peserta × juri** memperlihatkan status setiap lembar.
- **Kunci:** mengunci lembar yang sudah dikirim, sebagai tanda sudah diperiksa panitia.
- Di atas setiap babak ada **daftar cek kesiapan**: bobot 100%, jumlah juri yang ditugaskan, dan jumlah peserta. Label kuning berarti masih ada yang kurang.
- **Buka ulang:** mengembalikan lembar ke draft supaya juri bisa memperbaiki nilai.
  - **Alasan wajib diisi** dan tercatat di riwayat, beserta siapa yang membuka dan status sebelumnya.
  - Nilai lama tidak hilang; juri hanya memperbaiki yang salah.
  - Tidak bisa dilakukan selama hasil babak itu sedang tampil di publik. Tombolnya tampil abu-abu; tarik dulu hasilnya (lihat langkah 6).

---

## 6. Menghitung & mempublikasikan hasil

**Tab "Hasil"** di halaman Penjurian

1. **Hitung Hasil / Hitung Ulang:** menghitung peringkat untuk dilihat admin. Belum tampil ke publik.
2. **Publikasikan Hasil:** menghitung ulang, lalu menampilkan peringkat babak itu di halaman publik.
3. **Tarik dari publik:** menyembunyikan lagi hasilnya. Halaman publik kembali menampilkan "masih berlangsung".

**Cara nilai dihitung:**
- Hanya lembar **Terkirim** atau **Dikunci** yang dihitung. Draft diabaikan.
- Untuk setiap kriteria, nilai dari semua juri **dirata-rata**.
- **Nilai akhir** = jumlah dari (rata-rata kriteria × bobot kriteria ÷ 100).
- Peserta yang belum punya nilai lengkap untuk semua kriteria **tidak ikut diperingkat**.
- Nilai akhir yang sama mendapat peringkat yang sama dan ditandai **SERI** (contoh: 1, 1, 3).

**Contoh** (bobot PBB 60%, Formasi 40%, dua juri):

| | PBB | Formasi |
|---|---|---|
| Juri 1 | 90 | 80 |
| Juri 2 | 70 | 60 |
| Rata-rata | 80 | 70 |

Nilai akhir = 80 × 0,6 + 70 × 0,4 = **76**.

Publikasi ditolak kalau belum ada satu pun peserta dengan nilai lengkap.

Di bawah tabel peringkat ada daftar **"Belum diperingkat"**: peserta yang belum masuk peringkat beserta alasannya (misalnya "Belum ada nilai terkirim untuk: Formasi") dan juri yang masih ditunggu (misalnya "Hendra Wijaya (masih draft)").

---

## 7. Membagikan link hasil

- Di tab **Lembar Nilai** atau **Hasil**, blok **"Link hasil publik"** muncul begitu ada **satu lembar nilai yang sudah dikirim** juri.
- Tekan **Salin Link**, lalu bagikan ke peserta (WhatsApp, Instagram, dan sebagainya). Kalau browser menolak menyalin otomatis, link langsung terblok dan tombol meminta Anda menekan **Ctrl+C**.
- Bentuk link: `/info-lomba/{slug-lomba}/hasil`. Satu link berlaku untuk semua kategori dan babak di lomba itu.
- Link boleh dibagikan lebih awal. Selama babak belum dipublikasikan, pengunjung hanya melihat "{Babak} masih berlangsung".
- Kalau Info Lomba masih **Draft**, tombol Salin Link nonaktif, karena link-nya belum bisa dibuka publik. Ubah status Info Lomba ke **Published** dulu.

### Isi halaman hasil publik

- Satu kartu per peserta:
  - peringkat (dan tanda SERI)
  - nomor tampil, nama, dan asal
  - total nilai tiap juri
  - nilai akhir
- Kartu bisa diketuk untuk melihat **tabel nilai per kriteria per juri**.
- **Identitas juri dirahasiakan:** tampil sebagai "Juri 1", "Juri 2", dan seterusnya.
- Halaman detail Info Lomba menampilkan tautan **"Lihat hasil penilaian"** setelah ada babak yang diumumkan.

---

## Aturan penting (ringkas)

| Aturan | Alasan |
|---|---|
| Juri baru bisa menilai kalau total bobot babak tepat 100%. | Nilai akhir selalu berskala 0–100. |
| Kriteria terkunci setelah penilaian dimulai. | Semua peserta dinilai dengan aturan yang sama. |
| Juri tidak bisa melihat nilai juri lain atau peringkat. | Menjaga independensi penilaian. |
| Peringkat baru tampil publik setelah admin mempublikasikan. | Peringkat sementara tidak boleh bocor saat penilaian masih berjalan. |
| Nilai yang sudah dikirim hanya bisa diubah lewat "Buka ulang" dengan alasan tercatat. | Tidak ada perubahan nilai diam-diam. |
| Peserta, kriteria, babak, atau Info Lomba yang sudah punya nilai tidak bisa dihapus. | Riwayat penilaian tidak boleh hilang. |

---

## Kendala yang sering terjadi

Sebagian besar kendala ini sudah dijelaskan langsung di layar. Tabel ini untuk rujukan cepat.

| Gejala | Penyebab & solusi |
|---|---|
| Juri melihat "Babak ini belum bisa dinilai". | Total bobot belum 100%. Daftar cek di tab Lembar Nilai menandainya kuning. Perbaiki di tab Babak & Juri. |
| Juri tidak melihat babak apa pun. | Juri belum ditugaskan. Daftar cek menampilkan "0 juri ditugaskan". Tugaskan di tab Babak & Juri. |
| "Kode akses tidak valid". | Kodenya memang salah, sudah diganti, atau juri berstatus nonaktif. Huruf kecil, spasi, dan tanda hubung tidak masalah. |
| Tombol Kirim Skor Final abu-abu. | Masih ada kriteria yang kosong; keterangan di atas tombol menyebut jumlahnya. |
| Tidak bisa menambah kriteria atau mengubah bobot. | Penilaian babak sudah dimulai (kriteria terkunci). Ini disengaja. |
| Tombol "Buka ulang" abu-abu. | Hasil babak sedang tampil publik. Tarik dulu di tab Hasil. |
| Peserta tidak muncul di peringkat. | Lihat daftar "Belum diperingkat" di tab Hasil untuk alasan dan juri yang masih ditunggu. |
| Tombol "Salin Link" abu-abu. | Info Lomba masih Draft. Ubah ke Published. |
| Blok "Link hasil publik" tidak muncul. | Belum ada satu pun lembar nilai yang dikirim juri. |

---|---|
| Juri melihat "Babak ini belum bisa dinilai". | Total bobot belum 100%. Perbaiki di tab Babak & Juri. |
| Juri tidak melihat babak apa pun. | Juri belum ditugaskan ke babak. Tugaskan di tab Babak & Juri. |
| "Kode akses tidak valid". | Kode salah ketik, sudah diganti, atau juri berstatus nonaktif. |
| Kirim Skor Final ditolak "Nilai belum lengkap". | Ada kriteria yang belum diisi. Isi, Simpan Draft, lalu Kirim. |
| Tidak bisa menambah kriteria atau mengubah bobot. | Penilaian babak sudah dimulai (kriteria terkunci). |
| Tombol "Buka ulang" tidak muncul. | Hasil babak sedang tampil publik. Tarik dulu di tab Hasil. |
| Peserta tidak muncul di peringkat. | Nilainya belum lengkap dari juri, atau lembarnya masih draft. |
| Link hasil 404. | Info Lomba masih Draft, atau belum punya babak. |
| Tombol "Salin Link" tidak muncul. | Belum ada satu pun lembar nilai yang dikirim juri. |

---

## Untuk developer

- Spesifikasi teknis: [`_docs/srs/fr-competition-judging.md`](../srs/fr-competition-judging.md) (tabel, keputusan, test) dan [`_docs/srs/fr-public-content.md`](../srs/fr-public-content.md) (Info Lomba).
- Aturan penjurian ada di `app/Services/CompetitionJudgingService.php`.
- Data contoh: `php artisan db:seed --class=CompetitionJudgingDemoSeeder` ("LKBB Garuda Muda Cup", kode juri `GRDA2345`, `GRDB3456`, `GRDC4567`).
- Modul event lama (`/dashboard/events`, panel `/judge`) terpisah dan tidak dipakai untuk alur ini.
