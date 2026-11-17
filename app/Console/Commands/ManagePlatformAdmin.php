<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ManagePlatformAdmin extends Command
{
    protected $signature = 'siapgrak:platform-admin {email} {--revoke : Cabut akses admin platform}';

    protected $description = 'Beri atau cabut akses admin platform untuk mengelola konten halaman utama';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('User dengan email tersebut tidak ditemukan.');

            return self::FAILURE;
        }

        $user->forceFill(['is_platform_admin' => ! $this->option('revoke')])->save();

        $this->info($this->option('revoke')
            ? "Akses admin platform {$user->email} dicabut."
            : "{$user->email} sekarang admin platform.");

        return self::SUCCESS;
    }
}
