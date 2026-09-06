<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Test-only model used to verify the BelongsToTenant trait.
 * Not exposed via API; exists solely for trait behavior tests.
 */
class TenantScopedModel extends Model
{
    use BelongsToTenant;

    protected $fillable = ['name', 'tenant_id'];
}
