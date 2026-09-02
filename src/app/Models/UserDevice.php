<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserDeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $device_fingerprint
 * @property string $browser
 * @property string $os
 * @property string $device_type
 * @property string $ip_address
 * @property Carbon $last_seen_at
 * @property Carbon $first_seen_at
 */
class UserDevice extends Model
{
    /** @use HasFactory<UserDeviceFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'browser',
        'os',
        'device_type',
        'ip_address',
        'last_seen_at',
        'first_seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'first_seen_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
