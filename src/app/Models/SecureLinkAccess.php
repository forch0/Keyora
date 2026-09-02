<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SecureLinkAccessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $secure_link_id
 * @property string $ip_address
 * @property string $user_agent
 * @property string|null $email
 * @property Carbon $accessed_at
 */
class SecureLinkAccess extends Model
{
    /** @use HasFactory<SecureLinkAccessFactory> */
    use HasFactory;

    protected $fillable = [
        'secure_link_id',
        'ip_address',
        'user_agent',
        'email',
        'accessed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SecureLink, $this>
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(SecureLink::class, 'secure_link_id');
    }
}
