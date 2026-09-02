<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\SecureFileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecureFile extends Model
{
    /** @use HasFactory<SecureFileFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'team_id',
        'user_id',
        'folder_id',
        'name',
        'file_path',
        'mime_type',
        'size',
        'checksum',
        'description',
        'metadata',
        'download_enabled',
        'expires_at',
        'archived_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'download_enabled' => 'boolean',
            'expires_at' => 'datetime',
            'archived_at' => 'datetime',
            'size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for user() — the uploader.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<FileFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(FileFolder::class, 'folder_id');
    }

    /**
     * Scope to non-archived files.
     *
     * @param  Builder<static>  $builder
     * @return Builder<static>
     */
    public function scopeNotArchived(Builder $builder): Builder
    {
        return $builder->whereNull('archived_at');
    }

    /**
     * Scope to archived files.
     *
     * @param  Builder<static>  $builder
     * @return Builder<static>
     */
    public function scopeArchived(Builder $builder): Builder
    {
        return $builder->whereNotNull('archived_at');
    }

    /**
     * Check if the file is archived.
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
