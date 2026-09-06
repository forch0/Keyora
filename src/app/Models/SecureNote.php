<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Encryptable;
use Database\Factories\SecureNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $team_id
 * @property int $user_id
 * @property int|null $folder_id
 * @property string $title
 * @property string $content
 * @property string $content_format
 * @property bool $is_pinned
 */
class SecureNote extends Model
{
    /** @use HasFactory<SecureNoteFactory> */
    use Encryptable, HasFactory, SoftDeletes;

    /** @var list<string> */
    protected array $encryptable = ['content'];

    protected $fillable = [
        'tenant_id',
        'team_id',
        'user_id',
        'folder_id',
        'title',
        'content',
        'content_format',
        'is_pinned',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
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
     * @return BelongsTo<NoteFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(NoteFolder::class, 'folder_id');
    }

    /**
     * @return BelongsToMany<NoteTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(NoteTag::class, 'secure_note_tag', 'note_id', 'tag_id');
    }

    /**
     * Resolve route binding without tenant scope (notes can be personal).
     *
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        return static::query()->where($field ?? $this->getKeyName(), $value)->first();
    }
}
