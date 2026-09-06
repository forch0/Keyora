<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NoteTagFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $color
 * @property-read Collection<int, SecureNote> $notes
 */
class NoteTag extends Model
{
    /** @use HasFactory<NoteTagFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'color',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsToMany<SecureNote, $this>
     */
    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(SecureNote::class, 'secure_note_tag', 'tag_id', 'note_id');
    }
}
