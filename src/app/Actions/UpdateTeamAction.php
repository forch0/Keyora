<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;

class UpdateTeamAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(Team $team, array $attributes): Team
    {
        $team->update($attributes);

        return $team->fresh() ?? $team;
    }
}
