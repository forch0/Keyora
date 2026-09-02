<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;

/**
 * Whitelisted subject types for access grants.
 *
 * Prevents arbitrary class-string injection via user input — only
 * User, Team, or Tenant can be the subject of an access grant.
 */
enum SubjectType: string
{
    case User = 'user';
    case Team = 'team';
    case Tenant = 'tenant';

    /**
     * Get the fully qualified class name for this subject type.
     *
     * @return class-string<User|Team|Tenant>
     */
    public function classString(): string
    {
        return match ($this) {
            self::User => User::class,
            self::Team => Team::class,
            self::Tenant => Tenant::class,
        };
    }

    /**
     * Create from a fully qualified class string.
     */
    public static function fromClassString(string $class): self
    {
        return match ($class) {
            User::class => self::User,
            Team::class => self::Team,
            Tenant::class => self::Tenant,
            default => throw new \ValueError("Invalid subject type class: {$class}"),
        };
    }

    /**
     * Get all valid class strings (for validation rules).
     *
     * @return list<string>
     */
    public static function validClassStrings(): array
    {
        return [
            User::class,
            Team::class,
            Tenant::class,
        ];
    }
}
