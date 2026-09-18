<?php

namespace Database\Factories;

use App\Models\Registration;
use App\Models\RegistrationDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RegistrationDocument>
 */
class RegistrationDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'document_type' => fake()->randomElement(['surat_tugas', 'kartu_pelajar', 'pakta_integritas']),
            'title' => null,
            'file_path' => 'registration-documents/0/'.Str::ulid().'.pdf',
        ];
    }
}
