<?php

namespace Database\Seeders;

use App\Models\CompetitionListing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Real competitions copied from the siapgrak.id Instagram feed (poster + caption),
 * unlike PublicContentDemoSeeder which is fictional. Run it explicitly:
 *   php artisan db:seed --class=InstagramCompetitionSeeder
 * Records are matched by title; existing ones are skipped, never overwritten.
 * Posters live in database/seeders/assets/instagram and are copied to the public disk.
 */
class InstagramCompetitionSeeder extends Seeder
{
    public function run(): void
    {
        $this->save('LKBB Puncak Berbaris Jilid 1', 'lkbb-puncak-berbaris-jilid-1.jpg', [
            'organizer_name' => 'Paskibra SMK Bhinneka Nusantara',
            // The post only says "se-Jabodetabek (OPEN)"; the venue is not announced.
            'location' => 'Jabodetabek',
            'province' => null,
            'levels' => ['sd', 'smp', 'sma', 'umum'],
            'start_date' => '2027-03-20',
            'end_date' => '2027-03-21',
            // "01 November 2026 s.d Kuota Peserta Full": no fixed closing date.
            'registration_deadline' => null,
            'technical_meeting_date' => '2027-02-27',
            'technical_meeting_location' => null,
            'is_managed' => false,
            'external_url' => null,
            'contact_persons' => [['name' => 'Kang Ilyas', 'phone' => '089606415680']],
            'body' => <<<'CAPTION'
                ⛰️ SAATNYA PUNCAKKAN PRESTASI SATUANMU! ⚡

                Panggilan resmi untuk seluruh peleton Paskibra se-Jabodetabek (OPEN)! Bersiaplah untuk menembus batas dan membuktikan siapa yang layak bertengger di puncak kompetisi:

                🏆 LKBB PUNCAK BERBARIS JILID 1 🏆
                SD/MI - SMP/MTS - SMA/SMK/MA - PURNA/UMUM

                🔥 TOTAL HADIAH PULUHAN JUTA RUPIAH & ALL TROPHY! 🔥

                ✨ DEWAN JURI INDEPENDEN & PROFESSIONAL:
                • Juri PBB: Pak De Suroto | Pak De Agus | Kang Lucky
                • Juri Vafor: Cahya Hendrawan | Adam Shawn
                • Juri Kostum: Kang Dona

                🗓️ CATAT TANGGAL PENTINGNYA:
                • Pendaftaran: 01 November 2026 s.d Kuota Peserta Full
                • Technical Meeting: 27 Februari 2027
                • Competition Day: 20 - 21 Maret 2027

                👥 KUOTA PESERTA TERBATAS:
                • SD/MI Sederajat: 20 Tim
                • SMP/MTS Sederajat: 40 Tim
                • SMA/SMK Sederajat: 40 Tim
                • PURNA/CLUB: 20 Tim

                💰 Investasi Pendaftaran: Rp 650.000 / Tim
                💳 Pembayaran: BCA 7361510309 a.n Muhamad Ilyas Inayah

                📲 CONTACT PERSON & INFORMASI:
                • WhatsApp: 089606415680 (Kang Ilyas)

                Jangan tunggu kuota habis, amankan slot tim kalian dari sekarang! 🚀

                Official Media Partner: @siapgrak.id
                CAPTION,
        ]);

        $this->save('LKBB Mirovement Season 7', 'lkbb-mirovement-season-7.jpg', [
            'organizer_name' => 'Mirovement SMKN 21 & Paskibra SMKN 21',
            'location' => 'SMKN 21 Jakarta',
            'province' => 'DKI Jakarta',
            'levels' => ['smp', 'sma'],
            'start_date' => '2026-12-19',
            'end_date' => null,
            'registration_deadline' => '2026-11-28',
            'technical_meeting_date' => '2026-11-28',
            'technical_meeting_location' => 'SMKN 21 Jakarta',
            'is_managed' => false,
            'external_url' => null,
            'contact_persons' => [
                ['name' => 'Fatmaa (Tingkat SMP)', 'phone' => '0857-7374-5241'],
                ['name' => 'Jagad (Tingkat SMA)', 'phone' => '0812-9865-3689'],
            ],
            'body' => <<<'CAPTION'
                🗡️ SANG KERIS: SEBUAH AJANG KREASI BARIS-BERBARIS! 🗡️

                Panggilan untuk seluruh satuan Paskibra SMP/MTs & SMA/SMK/MA se-Jakarta Pusat dan sekitarnya! Siapkan mental, kekompakan, dan kebanggaan pasukanmu di panggung bergengsi:

                🏆 LKBB MIROVEMENT SEASON 7 🏆
                PBB - Variasi - Formasi

                Saatnya menunjukkan ukiran prestasi terbaikmu di arena! ⚔️

                📌 SAVE THE DATE:
                • Pendaftaran: 20 Oktober – 28 November 2026
                • Technical Meeting: 28 November 2026 (SMKN 21 Jakarta)
                • Pelaksanaan Lomba: 19 Desember 2026 (SMKN 21 Jakarta)

                🎁 TOTAL HADIAH:
                • 30 Piala 🏆
                • 96 Medali 🏅
                • Uang Pembinaan 💰

                💵 Investasi Pendaftaran: Rp 650.000 / Tim

                📲 CONTACT PERSON (RESERVASI SLOT):
                • Tingkat SMP: 0857-7374-5241 (Fatmaa)
                • Tingkat SMA: 0812-9865-3689 (Jagad)

                Jangan sampai ketinggalan! Amankan slot pasukanmu sekarang juga! 🚀

                Official Media Partner: @siapgrak.id
                Organized by: @mirovement.smkn21 | @paskibra.smkn21

                #SiapGrak #MirovementSeason7 #SangKeris #LKBB2026 #LombaPaskibra #PaskibraJakarta #PaskibraIndonesia #EventPaskibra #InfoLKBB
                CAPTION,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function save(string $title, string $poster, array $attributes): void
    {
        // Never overwrite an existing record: admins edit these after seeding.
        if (CompetitionListing::where('title', $title)->exists()) {
            return;
        }

        // A missing poster leaves the cover empty instead of failing the whole seed.
        $source = database_path("seeders/assets/instagram/{$poster}");
        $path = null;

        if (is_file($source)) {
            $path = "content/instagram/{$poster}";
            Storage::disk('public')->put($path, file_get_contents($source));
        }

        (new CompetitionListing(['title' => $title]))->forceFill($attributes + [
            'cover_path' => $path,
            'status' => 'published',
            // Owned by the first platform admin so it shows up in their admin panel.
            'id_user' => User::where('is_platform_admin', true)->orderBy('id_user')->value('id_user'),
        ])->save();
    }
}
