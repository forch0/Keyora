<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Encryptable;
use Database\Factories\PersonalVaultItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $type
 * @property string|null $username
 * @property string|null $password
 * @property string|null $url
 * @property string|null $notes
 * @property array<string, mixed>|null $metadata
 * @property array<int, array{key: string, value: string}>|null $custom_fields
 * @property bool $favorite
 * @property int|null $folder_id
 * @property Carbon|null $archived_at
 * @property Carbon|null $last_accessed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read PersonalVaultFolder|null $folder
 * @property-read Collection<int, PersonalVaultTag> $tags
 */
#[Fillable(['user_id', 'name', 'type', 'username', 'password', 'url', 'notes', 'metadata', 'custom_fields', 'favorite', 'folder_id', 'last_accessed_at', 'archived_at'])]
class PersonalVaultItem extends Model
{
    /** @use HasFactory<PersonalVaultItemFactory> */
    use Encryptable, HasFactory;

    use SoftDeletes;

    /**
     * Fields that are automatically encrypted at rest via the Encryptable trait.
     */
    /** @var list<string> */
    protected array $encryptable = ['username', 'password', 'notes'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'favorite' => 'boolean',
            'archived_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }

    /**
     * The user who owns this vault item.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The folder this item belongs to (nullable — root items have no folder).
     *
     * @return BelongsTo<PersonalVaultFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(PersonalVaultFolder::class);
    }

    /**
     * Tags associated with this item.
     *
     * @return BelongsToMany<PersonalVaultTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PersonalVaultTag::class, 'personal_vault_item_tag', 'item_id', 'tag_id');
    }

    /**
     * Scope items by type.
     *
     * @param  Builder<static>  $builder
     * @return Builder<static>
     */
    public function scopeOfType(Builder $builder, string $type): Builder
    {
        return $builder->where('type', $type);
    }

    /**
     * Scope only favorite items.
     *
     * @param  Builder<static>  $builder
     * @return Builder<static>
     */
    public function scopeFavorite(Builder $builder): Builder
    {
        return $builder->where('favorite', true);
    }

    /**
     * Scope only non-archived items.
     *
     * @param  Builder<static>  $builder
     * @return Builder<static>
     */
    public function scopeActive(Builder $builder): Builder
    {
        return $builder->whereNull('archived_at');
    }

    /**
     * Override getAttribute for encrypted fields.
     * - custom_fields: decrypt then JSON-decode
     * - username/password/notes: decrypt via Encryptable logic
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if ($key === 'custom_fields') {
            $value = parent::getAttribute('custom_fields');

            if ($value === null || $value === '') {
                return;
            }

            try {
                return json_decode(Crypt::decryptString($value), true);
            } catch (\Throwable) {
                return;
            }
        }

        $value = parent::getAttribute($key);

        if ($this->isEncryptableField($key) && is_string($value) && $value !== '') {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value;
    }

    /**
     * Override setAttribute for custom_fields: JSON-encode then encrypt.
     * For other encryptable fields, delegate to the Encryptable trait logic.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if ($key === 'custom_fields' && $value !== null && $value !== '') {
            $value = Crypt::encryptString(json_encode($value) ?: '[]');

            return parent::setAttribute($key, $value);
        }

        // For encryptable fields, encrypt before passing to parent
        if ($this->isEncryptableField($key) && $value !== null && $value !== '') {
            $value = Crypt::encryptString((string) $value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Check if the given key is an encryptable field.
     */
    private function isEncryptableField(string $key): bool
    {
        return in_array($key, $this->encryptable, true);
    }
}
