<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ResourceViewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $resource_type
 * @property int $resource_id
 * @property Carbon $viewed_at
 */
class ResourceView extends Model
{
    /** @use HasFactory<ResourceViewFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'resource_type',
        'resource_id',
        'viewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
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
