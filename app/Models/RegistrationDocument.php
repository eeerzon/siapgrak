<?php

namespace App\Models;

use App\Enums\RegistrationDocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationDocument extends Model
{
    use HasFactory;

    /**
     * file_path / status are set by the controller (upload handling), never
     * mass-assigned from request input.
     *
     * @var list<string>
     */
    protected $fillable = [
        'document_type',
        'title',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RegistrationDocumentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
