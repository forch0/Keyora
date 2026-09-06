<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PersonalVaultFolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property int|null $parent_id
 * @property string|null $icon
 * @property string|null $color
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read PersonalVaultFolder|null $parent
 * @property-read Collection<int, PersonalVaultFolder> $children
 * @property-read Collection<int, PersonalVaultItem> $items
 */
#[Fillable(['user_id', 'name', 'parent_id', 'icon', 'color', 'sort_order'])]
class PersonalVaultFolder extends Model
{
    /** @use HasFactory<PersonalVaultFolderFactory> */
    use HasFactory;

    /**
     * The user who owns this folder.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parent folder (for nesting).
     *
     * @return BelongsTo<PersonalVaultFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child folders.
     *
     * @return HasMany<PersonalVaultFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Vault items in this folder.
     *
     * @return HasMany<PersonalVaultItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PersonalVaultItem::class, 'folder_id');
    }
}
