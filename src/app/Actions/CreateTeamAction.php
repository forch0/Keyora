<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use App\Models\User;

class CreateTeamAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(User $creator, array $attributes): Team
    {
        $team = Team::create([
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'color' => $attributes['color'] ?? null,
            'created_by' => $creator->id,
        ]);

        // Creator becomes team lead
        $team->members()->attach($creator->id, [
            'role' => 'lead',
            'joined_at' => now(),
        ]);

        return $team->loadCount(['members', 'vaultItems']);
    }
}
