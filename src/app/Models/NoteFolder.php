<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NoteFolderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NoteFolder extends Model
{
    /** @use HasFactory<NoteFolderFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'team_id',
        'user_id',
        'name',
        'parent_id',
        'created_by',
    ];

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
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<NoteFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(NoteFolder::class, 'parent_id');
    }

    /**
     * @return HasMany<NoteFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(NoteFolder::class, 'parent_id');
    }

    /**
     * @return HasMany<SecureNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(SecureNote::class, 'folder_id');
    }

    /**
     * Resolve route binding without tenant scope.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        return static::query()->where($field ?? $this->getKeyName(), $value)->first();
    }
}
