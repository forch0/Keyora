<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\FileFolderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileFolder extends Model
{
    /** @use HasFactory<FileFolderFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'team_id',
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
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<FileFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(FileFolder::class, 'parent_id');
    }

    /**
     * @return HasMany<FileFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(FileFolder::class, 'parent_id');
    }

    /**
     * @return HasMany<SecureFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(SecureFile::class, 'folder_id');
    }
}
