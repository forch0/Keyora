<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    case View = 'view';
    case Download = 'download';
    case Edit = 'edit';
    case Share = 'share';
    case Manage = 'manage';

    /**
     * Check if this permission satisfies the required permission.
     *
     * Uses rank-based comparison: a higher-or-equal rank satisfies a lower
     * requirement. Note that `download` and `edit` are both rank 2 — they
     * are parallel, not cumulative. `manage` satisfies all. `share` satisfies
     * `view` and `download`. `edit` satisfies `view`. `download` satisfies
     * `view`.
     */
    public function satisfies(Permission $required): bool
    {
        // If the required permission is the same or lower rank, we satisfy it.
        // But download and edit are parallel — neither satisfies the other.
        if ($this === $required) {
            return true;
        }

        // Manage satisfies everything
        if ($this === self::Manage) {
            return true;
        }

        // Download and edit are parallel (both rank 2) — neither satisfies the other
        if (($this === self::Download && $required === self::Edit) ||
            ($this === self::Edit && $required === self::Download)) {
            return false;
        }

        // Share satisfies view and download
        if ($this === self::Share && in_array($required, [self::View, self::Download], true)) {
            return true;
        }

        // Edit satisfies view
        if ($this === self::Edit && $required === self::View) {
            return true;
        }

        // Download satisfies view
        if ($this === self::Download && $required === self::View) {
            return true;
        }

        return false;
    }

    /**
     * Get the rank of this permission for comparison.
     *
     * Note: `download` and `edit` are both rank 2 (parallel).
     */
    public function rank(): int
    {
        return match ($this) {
            self::View => 1,
            self::Download => 2,
            self::Edit => 2,
            self::Share => 3,
            self::Manage => 4,
        };
    }

    /**
     * Get the full hierarchy as an array of values.
     *
     * @return list<string>
     */
    public static function hierarchy(): array
    {
        return ['view', 'download', 'edit', 'share', 'manage'];
    }
}
