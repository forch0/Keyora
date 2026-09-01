<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Encryptable;
use Database\Factories\VaultItemFactory;
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
 * @property int $tenant_id
 * @property int|null $team_id
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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Tenant $tenant
 * @property-read Team|null $team
 * @property-read User $user
 * @property-read VaultFolder|null $folder
 * @property-read Collection<int, VaultTag> $tags
 */
#[Fillable(['tenant_id', 'team_id', 'user_id', 'name', 'type', 'username', 'password', 'url', 'notes', 'metadata', 'custom_fields', 'favorite', 'folder_id'])]
class VaultItem extends Model
{
    /** @use HasFactory<VaultItemFactory> */
    use BelongsToTenant, Encryptable, HasFactory, SoftDeletes;

    /** @var list<string> */
    protected array $encryptable = ['username', 'password', 'notes'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'favorite' => 'boolean',
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
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<VaultFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(VaultFolder::class);
    }

    /**
     * @return BelongsToMany<VaultTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(VaultTag::class, 'vault_item_tag', 'item_id', 'tag_id');
    }

    /**
     * Scope to org-wide items (team_id = null).
     *
     * @param  Builder<static>  $builder
     * @return Builder<static>
     */
    public function scopeOrgWide(Builder $builder): Builder
    {
        return $builder->whereNull('team_id');
    }

    /**
     * Override getAttribute for encrypted fields (same pattern as PersonalVaultItem).
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
     * Override setAttribute for encrypted fields (same pattern as PersonalVaultItem).
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

        if ($this->isEncryptableField($key) && $value !== null && $value !== '') {
            $value = Crypt::encryptString((string) $value);
        }

        return parent::setAttribute($key, $value);
    }

    private function isEncryptableField(string $key): bool
    {
        return in_array($key, $this->encryptable, true);
    }
}
