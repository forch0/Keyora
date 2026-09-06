<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PersonalVaultTagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, PersonalVaultItem> $items
 */
#[Fillable(['user_id', 'name', 'color'])]
class PersonalVaultTag extends Model
{
    /** @use HasFactory<PersonalVaultTagFactory> */
    use HasFactory;

    /**
     * The user who owns this tag.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vault items tagged with this tag.
     *
     * @return BelongsToMany<PersonalVaultItem, $this>
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(PersonalVaultItem::class, 'personal_vault_item_tag', 'tag_id', 'item_id');
    }
}
