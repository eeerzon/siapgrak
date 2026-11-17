<?php

namespace Database\Seeders;

use App\Models\AgendaItem;
use App\Models\CommunityPost;
use App\Models\CompetitionListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Fictional sample content for previewing the public site. Run it explicitly:
 *   php artisan db:seed --class=PublicContentDemoSeeder
 * Records are matched by title; existing ones are skipped, never overwritten.
 */
class PublicContentDemoSeeder extends Seeder
{
    private const COLORS = [
        'navy' => [[15, 23, 42], [3, 105, 161]],
        'red' => [[127, 29, 29], [220, 38, 38]],
        'green' => [[6, 78, 59], [16, 185, 129]],
        'amber' => [[120, 53, 15], [245, 158, 11]],
        'slate' => [[30, 41, 59], [100, 116, 139]],
    ];

    public function run(): void
    {
        $this->seedCompetitions();
        $this->seedAgenda();
        $this->seedCommunityPosts();
    }

    private function seedCompetitions(): void
    {
        $body = "Kejuaraan ini mempertemukan pasukan baris-berbaris dari berbagai sekolah untuk beradu kekompakan, ketepatan, dan kreativitas formasi.\n\nKategori yang dilombakan meliputi PBB murni, variasi formasi, dan danton terbaik. Penilaian dilakukan oleh dewan juri berpengalaman.\n\nPeserta diharapkan hadir saat technical meeting untuk menerima pembagian nomor urut dan penjelasan tata tertib.";

        // 0800 numbers are not mobile numbers, so the demo WhatsApp links reach nobody.
        $contacts = [['name' => 'Kak Contoh Satu', 'phone' => '0800-0000-0001'], ['name' => 'Kak Contoh Dua', 'phone' => '0800-0000-0002']];

        foreach ([
            ['Kejuaraan LKBB Nusantara Piala Garuda', 'Kejuaraan tingkat SMA se-Indonesia dengan kategori PBB murni, variasi formasi, dan danton terbaik.', 'Siapgrak', 'Jakarta', 'DKI Jakarta', ['sma'], 7, 12, 13, true, 'demo-lkbb-piala-garuda', null, null, 'navy'],
            ['Lomba Paskibra Antar Sekolah Kota Harmoni', 'Terbuka untuk tingkat SMP dan SMA, kuota 40 pasukan.', 'OSIS SMA Contoh 1', 'Bandung', 'Jawa Barat', ['smp', 'sma'], 14, 19, null, false, null, 'https://example.com/lomba-paskibra', null, 'red'],
            ['Open Tournament LKBB Jawa Tengah', 'Turnamen terbuka dengan piala bergilir dan pembinaan juri setelah lomba.', 'Komunitas Baris Berbaris Contoh', 'Semarang', 'Jawa Tengah', ['smp', 'sma', 'umum'], 21, 30, 31, false, null, null, $contacts, 'green'],
            ['Festival Baris Berbaris Pelajar', 'Festival dengan kategori SD, SMP, dan SMA, cocok untuk pasukan yang baru pertama kali berlomba.', 'Siapgrak', 'Yogyakarta', 'DI Yogyakarta', ['sd', 'smp', 'sma'], 40, 45, 46, true, 'demo-festival-baris-pelajar', null, null, 'amber'],
            ['Liga Paskibra Sumatera', null, 'Forum Pelatih Contoh', 'Medan', 'Sumatera Utara', ['sma'], -2, 60, null, false, null, null, [$contacts[0]], null],
            ['Kejuaraan LKBB Piala Bahari', 'Edisi tahun lalu dengan 32 pasukan peserta.', 'Siapgrak', 'Surabaya', 'Jawa Timur', ['smp', 'sma'], -50, -40, -39, false, null, null, null, 'slate'],
            ['Lomba Tata Upacara Pelajar', 'Lomba tata upacara tingkat kota yang telah selesai digelar.', 'OSIS SMA Contoh 2', 'Malang', 'Jawa Timur', ['sma'], null, -75, null, false, null, null, null, null],
        ] as [$title, $excerpt, $organizer, $location, $province, $levels, $deadlineDays, $startDays, $endDays, $managed, $kreenSlug, $externalUrl, $contactPersons, $color]) {
            $this->save(CompetitionListing::class, $title, [
                'excerpt' => $excerpt,
                'body' => $body,
                'organizer_name' => $organizer,
                'location' => $location,
                'province' => $province,
                'levels' => $levels,
                'registration_deadline' => $deadlineDays === null ? null : today()->addDays($deadlineDays),
                'start_date' => today()->addDays($startDays),
                'end_date' => $endDays === null ? null : today()->addDays($endDays),
                'is_managed' => $managed,
                'kreen_slug' => $kreenSlug,
                'external_url' => $externalUrl,
                'contact_persons' => $contactPersons,
                'cover_path' => $color ? $this->cover($color, poster: true) : null,
                'status' => 'published',
            ]);
        }

        $this->save(CompetitionListing::class, 'Contoh Draft Lomba (tidak tampil di publik)', [
            'start_date' => today()->addDays(25),
            'status' => 'draft',
        ]);
    }

    private function seedAgenda(): void
    {
        foreach ([
            ['Technical Meeting Kejuaraan Piala Garuda', 'Daring via konferensi video', 3, '09:00', '11:00'],
            ['Workshop Juri LKBB Tingkat Dasar', 'Yogyakarta', 6, '13:30', '16:00'],
            ['Latihan Gabungan Komunitas Bandung', 'Lapangan Contoh, Bandung', 9, '07:00', '10:30'],
            ['Temu Pelatih Paskibra', 'Jakarta', 16, '10:00', null],
            ['Sosialisasi Peraturan Baris Berbaris Terbaru', 'Daring via konferensi video', 24, '19:30', '21:00'],
            ['Pembinaan Danton Pelajar', 'Semarang', -14, '08:00', '12:00'],
        ] as [$title, $location, $days, $start, $end]) {
            $date = today()->addDays($days)->toDateString();

            $this->save(AgendaItem::class, $title, [
                'description' => "Kegiatan ini terbuka untuk pelatih, pembina, dan anggota komunitas.\n\nPeserta diharapkan hadir 15 menit sebelum acara dimulai.",
                'location' => $location,
                'start_at' => "{$date} {$start}:00",
                'end_at' => $end ? "{$date} {$end}:00" : null,
                'status' => 'published',
            ]);
        }
    }

    private function seedCommunityPosts(): void
    {
        foreach ([
            ['achievement', 'Pasukan Contoh Raih Juara Umum Tingkat Provinsi', 'Kerja keras selama enam bulan berbuah gelar juara umum.', 'navy', 1],
            ['news', 'Komunitas Pelatih Gelar Latihan Gabungan', 'Lebih dari 100 anggota berlatih bersama akhir pekan lalu.', 'green', 3],
            ['achievement', 'Danton Terbaik Nasional dari Sekolah Contoh', 'Kisah perjalanan seorang danton pelajar menuju podium nasional.', 'red', 6],
            ['news', 'Tips Menyiapkan Pasukan Sebelum Lomba', null, null, 10],
            ['achievement', 'Tiga Pasukan Lolos ke Kejuaraan Nasional', 'Perwakilan daerah memastikan tempat di babak nasional.', 'amber', 15],
            ['news', 'Mengenal Penilaian Variasi Formasi', 'Hal-hal yang biasanya diperhatikan juri saat menilai formasi.', 'slate', 21],
        ] as [$type, $title, $excerpt, $color, $daysAgo]) {
            $this->save(CommunityPost::class, $title, [
                'type' => $type,
                'excerpt' => $excerpt,
                'body' => "Ini adalah contoh isi kabar untuk melihat tampilan halaman detail.\n\nParagraf kedua menunjukkan jarak antarparagraf pada artikel. Teks ini fiktif dan hanya dipakai sebagai data contoh.\n\nParagraf ketiga menutup contoh artikel ini.",
                'cover_path' => $color ? $this->cover($color) : null,
                'status' => 'published',
                'published_at' => now()->subDays($daysAgo),
            ]);
        }
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $attributes
     */
    private function save(string $model, string $title, array $attributes): void
    {
        // Owned by the first platform admin so it shows up in their admin panel.
        $owner = User::where('is_platform_admin', true)->orderBy('id_user')->value('id_user');

        // Never overwrite an existing record: admins edit these after seeding.
        if ($model::where('title', $title)->exists()) {
            return;
        }

        (new $model(['title' => $title]))->forceFill($attributes + ['id_user' => $owner])->save();
    }

    /**
     * Draws a simple gradient once per color and reuses the file on re-runs.
     * Posters use the Instagram feed ratio (4:5).
     */
    private function cover(string $color, bool $poster = false): string
    {
        $path = $poster ? "content/demo-{$color}-poster.jpg" : "content/demo-{$color}.jpg";

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        [[$r1, $g1, $b1], [$r2, $g2, $b2]] = self::COLORS[$color];
        [$width, $height] = $poster ? [1080, 1350] : [1200, 675];
        $image = imagecreatetruecolor($width, $height);

        for ($x = 0; $x < $width; $x++) {
            $ratio = $x / $width;
            imageline($image, $x, 0, $x, $height, imagecolorallocate(
                $image,
                (int) ($r1 + ($r2 - $r1) * $ratio),
                (int) ($g1 + ($g2 - $g1) * $ratio),
                (int) ($b1 + ($b2 - $b1) * $ratio),
            ));
        }

        ob_start();
        imagejpeg($image, null, 85);
        Storage::disk('public')->put($path, ob_get_clean());

        return $path;
    }
}
