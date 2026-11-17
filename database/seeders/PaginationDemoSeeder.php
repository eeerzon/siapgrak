<?php

namespace Database\Seeders;

use App\Models\AgendaItem;
use App\Models\CompetitionListing;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Extra fictional content so the public lists have more than one page
 * (8 competitions per page, agenda loaded 8 at a time). Run it explicitly:
 *   php artisan db:seed --class=PaginationDemoSeeder
 * Records are matched by title; existing ones are skipped, never overwritten.
 */
class PaginationDemoSeeder extends Seeder
{
    /** Gradient top/bottom colours per poster theme. */
    private const THEMES = [
        'navy' => [[14, 24, 56], [31, 50, 99]],
        'maroon' => [[92, 14, 30], [163, 12, 37]],
        'forest' => [[8, 60, 46], [16, 120, 86]],
        'brown' => [[70, 34, 14], [140, 74, 28]],
        'teal' => [[10, 52, 70], [14, 116, 144]],
        'plum' => [[52, 20, 70], [104, 46, 132]],
    ];

    /** Bundled with macOS; when missing the posters are drawn without text. */
    private const FONT = '/System/Library/Fonts/Supplemental/Arial Bold.ttf';

    private ?int $owner = null;

    public function run(): void
    {
        $this->owner = User::where('is_platform_admin', true)->orderBy('id_user')->value('id_user');

        $this->seedCompetitions();
        $this->seedAgenda();
    }

    private function seedCompetitions(): void
    {
        $body = "Lomba terbuka untuk satuan Paskibra dengan kategori PBB murni, variasi, dan formasi.\n\nPendaftaran dibatasi sesuai kuota. Peserta wajib hadir saat technical meeting untuk pengambilan nomor urut.\n\nData ini fiktif dan hanya dipakai sebagai contoh.";
        $contacts = [['name' => 'Kak Contoh Panitia', 'phone' => '0800-0000-0003']];

        foreach ([
            ['LKBB Garuda Muda Cup', 'SMAN Contoh 3 Depok', 'Jawa Barat', ['smp', 'sma'], 9, 24, 'navy'],
            ['Kejuaraan PBB Pelajar Bali', 'GOR Contoh, Denpasar', 'Bali', ['sd', 'smp'], 12, 33, 'teal'],
            ['Lomba Variasi Formasi Kota Bekasi', 'Stadion Contoh, Bekasi', 'Jawa Barat', ['sma'], 15, 38, 'maroon'],
            ['LKBB Bumi Sriwijaya', 'Palembang', 'Sumatera Selatan', ['smp', 'sma', 'umum'], 20, 50, 'forest'],
            ['Paskibra Championship Makassar', 'Lapangan Contoh, Makassar', 'Sulawesi Selatan', ['sma'], 26, 57, 'brown'],
            ['Festival Tata Upacara Banten', 'Serang', 'Banten', ['sd', 'smp', 'sma'], 30, 66, 'plum'],
        ] as [$title, $location, $province, $levels, $deadlineDays, $startDays, $theme]) {
            if (CompetitionListing::where('title', $title)->exists()) {
                continue;
            }

            $startDate = today()->addDays($startDays);

            (new CompetitionListing(['title' => $title]))->forceFill([
                'body' => $body,
                'organizer_name' => 'Panitia Contoh '.Str::after($location, ', '),
                'location' => $location,
                'province' => $province,
                'levels' => $levels,
                'registration_deadline' => today()->addDays($deadlineDays),
                'start_date' => $startDate,
                'technical_meeting_date' => today()->addDays($deadlineDays),
                'technical_meeting_location' => $location,
                'is_managed' => false,
                'contact_persons' => $contacts,
                'cover_path' => $this->poster('INFO LOMBA', $title, $startDate, $theme),
                'status' => 'published',
                'id_user' => $this->owner,
            ])->save();
        }
    }

    private function seedAgenda(): void
    {
        foreach ([
            ['Technical Meeting LKBB Garuda Muda Cup', 'SMAN Contoh 3 Depok', 2, '09:00', '11:00', 'navy'],
            ['Latihan Gabungan Paskibra Jakarta Timur', 'Lapangan Contoh, Jakarta Timur', 4, '07:00', '10:00', 'maroon'],
            ['Kelas Danton untuk Pemula', 'Daring via konferensi video', 5, '19:00', '21:00', 'teal'],
            ['Workshop Penilaian PBB Murni', 'Aula Contoh, Bandung', 7, '08:30', '15:00', 'forest'],
            ['Uji Coba Formasi Antar Satuan', 'GOR Contoh, Bogor', 10, '13:00', '17:00', 'brown'],
            ['Temu Purna Paskibra Jawa Tengah', 'Semarang', 13, '10:00', '13:00', 'plum'],
            ['Pelatihan Pelatih Variasi Formasi', 'Yogyakarta', 15, '08:00', '16:00', 'navy'],
            ['Gladi Bersih Festival Tata Upacara', 'Serang', 18, '07:30', '11:30', 'maroon'],
            ['Diskusi Juri: Kesalahan Umum Saat Lomba', 'Daring via konferensi video', 21, '19:30', '21:00', 'teal'],
            ['Latihan Gabungan Komunitas Surabaya', 'Lapangan Contoh, Surabaya', 25, '06:30', '09:30', 'forest'],
            ['Sosialisasi Kuota Peserta LKBB Bumi Sriwijaya', 'Palembang', 28, '14:00', '16:00', 'brown'],
            ['Malam Apresiasi Pelatih Paskibra', 'Jakarta', 35, '18:30', '21:30', 'plum'],
        ] as [$title, $location, $days, $start, $end, $theme]) {
            if (AgendaItem::where('title', $title)->exists()) {
                continue;
            }

            $date = today()->addDays($days);

            (new AgendaItem(['title' => $title]))->forceFill([
                'description' => "Kegiatan terbuka untuk pelatih, pembina, dan anggota satuan.\n\nPeserta diharapkan datang 15 menit sebelum acara dimulai. Data ini fiktif dan hanya dipakai sebagai contoh.",
                'location' => $location,
                'cover_path' => $this->poster('AGENDA', $title, $date, $theme),
                'start_at' => $date->toDateString()." {$start}:00",
                'end_at' => $date->toDateString()." {$end}:00",
                'status' => 'published',
                'id_user' => $this->owner,
            ])->save();
        }
    }

    /**
     * 1080×1350 (4:5) poster: gradient, red-white corner ribbons, label, title and date.
     */
    private function poster(string $label, string $title, CarbonInterface $date, string $theme): string
    {
        $path = 'content/demo/'.Str::slug($title).'.jpg';

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        [$width, $height] = [1080, 1350];
        [[$r1, $g1, $b1], [$r2, $g2, $b2]] = self::THEMES[$theme];
        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            imageline($image, 0, $y, $width, $y, imagecolorallocate(
                $image,
                (int) ($r1 + ($r2 - $r1) * $ratio),
                (int) ($g1 + ($g2 - $g1) * $ratio),
                (int) ($b1 + ($b2 - $b1) * $ratio),
            ));
        }

        $red = imagecolorallocate($image, 200, 16, 46);
        $white = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocate($image, 210, 216, 230);

        // Diagonal ribbons in the top-left and bottom-right corners.
        foreach ([[0, $red], [65, $white], [120, $red]] as [$offset, $color]) {
            imagefilledpolygon($image, [0, 180 + $offset, 180 + $offset, 0, 230 + $offset, 0, 0, 230 + $offset], $color);
            imagefilledpolygon($image, [$width, $height - 230 - $offset, $width - 230 - $offset, $height, $width - 180 - $offset, $height, $width, $height - 180 - $offset], $color);
        }

        if (is_file(self::FONT)) {
            imagettftext($image, 30, 0, 90, 380, $red, self::FONT, $label);

            $y = 470;
            foreach ($this->wrapToWidth(Str::upper($title), 64, $width - 180) as $line) {
                imagettftext($image, 64, 0, 90, $y, $white, self::FONT, $line);
                $y += 92;
            }

            imagettftext($image, 34, 0, 90, $y + 40, $muted, self::FONT, $date->locale('id')->translatedFormat('j F Y'));
            imagettftext($image, 26, 0, 90, $height - 110, $white, self::FONT, 'SIAPGRAK.ID');
        }

        ob_start();
        imagejpeg($image, null, 85);
        Storage::disk('public')->put($path, ob_get_clean());

        return $path;
    }

    /**
     * Greedy word wrap by rendered width, so long words never run off the poster.
     *
     * @return list<string>
     */
    private function wrapToWidth(string $text, int $size, int $maxWidth): array
    {
        $lines = [];
        $line = '';

        foreach (explode(' ', $text) as $word) {
            $candidate = trim("{$line} {$word}");
            $box = imagettfbbox($size, 0, self::FONT, $candidate);

            if ($line !== '' && $maxWidth < $box[2] - $box[0]) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        return [...$lines, $line];
    }
}
