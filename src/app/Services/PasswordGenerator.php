<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Cryptographically secure password generator with customizable criteria.
 */
class PasswordGenerator
{
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';

    private const NUMBERS = '0123456789';

    private const SYMBOLS = '!@#$%^&*()-_=+[]{}|;:,.<>?';

    /** Characters that look similar to each other */
    private const SIMILAR = '0O1lI';

    /** Ambiguous characters that can be confused in certain fonts */
    private const AMBIGUOUS = '{}[]()/\'"`~,;.<>';

    /**
     * Generate a password with the given options.
     *
     * @param  array<string, mixed>  $options
     */
    public function generate(array $options = []): string
    {
        $length = max(8, min(128, (int) ($options['length'] ?? 16)));
        $useUppercase = (bool) ($options['uppercase'] ?? true);
        $useLowercase = (bool) ($options['lowercase'] ?? true);
        $useNumbers = (bool) ($options['numbers'] ?? true);
        $useSymbols = (bool) ($options['symbols'] ?? true);
        $excludeSimilar = (bool) ($options['exclude_similar'] ?? false);
        $excludeAmbiguous = (bool) ($options['exclude_ambiguous'] ?? false);
        $minUppercase = (int) ($options['min_uppercase'] ?? 1);
        $minLowercase = (int) ($options['min_lowercase'] ?? 1);
        $minNumbers = (int) ($options['min_numbers'] ?? 1);
        $minSymbols = (int) ($options['min_symbols'] ?? 1);

        // Build character pools with exclusions applied
        $pools = $this->buildPools(
            $useUppercase,
            $useLowercase,
            $useNumbers,
            $useSymbols,
            $excludeSimilar,
            $excludeAmbiguous,
        );

        // Build the password: first fill minimums, then fill remaining with all enabled chars
        $password = '';

        // Fill minimum character counts per category
        if ($useUppercase && $minUppercase > 0) {
            $password .= $this->randomFromPool($pools['uppercase'], $minUppercase);
        }

        if ($useLowercase && $minLowercase > 0) {
            $password .= $this->randomFromPool($pools['lowercase'], $minLowercase);
        }

        if ($useNumbers && $minNumbers > 0) {
            $password .= $this->randomFromPool($pools['numbers'], $minNumbers);
        }

        if ($useSymbols && $minSymbols > 0) {
            $password .= $this->randomFromPool($pools['symbols'], $minSymbols);
        }

        // Combine all enabled pools for filling the rest
        $allChars = '';
        if ($useUppercase) {
            $allChars .= $pools['uppercase'];
        }

        if ($useLowercase) {
            $allChars .= $pools['lowercase'];
        }

        if ($useNumbers) {
            $allChars .= $pools['numbers'];
        }

        if ($useSymbols) {
            $allChars .= $pools['symbols'];
        }

        // Fill the remaining length
        $remaining = $length - strlen($password);
        if ($remaining > 0 && $allChars !== '') {
            $password .= $this->randomFromPool($allChars, $remaining);
        }

        // Shuffle the final password so minimums aren't always at the start
        return $this->shuffleString($password);
    }

    /**
     * Build character pools with exclusions applied.
     *
     * @return array<string, string>
     */
    private function buildPools(
        bool $useUppercase,
        bool $useLowercase,
        bool $useNumbers,
        bool $useSymbols,
        bool $excludeSimilar,
        bool $excludeAmbiguous,
    ): array {
        $exclude = '';
        if ($excludeSimilar) {
            $exclude .= self::SIMILAR;
        }

        if ($excludeAmbiguous) {
            $exclude .= self::AMBIGUOUS;
        }

        $filter = static fn (string $pool): string => $exclude === ''
            ? $pool
            : str_replace(str_split($exclude), '', $pool);

        return [
            'uppercase' => $useUppercase ? $filter(self::UPPERCASE) : '',
            'lowercase' => $useLowercase ? $filter(self::LOWERCASE) : '',
            'numbers' => $useNumbers ? $filter(self::NUMBERS) : '',
            'symbols' => $useSymbols ? $filter(self::SYMBOLS) : '',
        ];
    }

    /**
     * Get $count random characters from a pool using random_int().
     */
    private function randomFromPool(string $pool, int $count): string
    {
        if ($pool === '' || $count <= 0) {
            return '';
        }

        $result = '';
        $poolLength = strlen($pool);

        for ($i = 0; $i < $count; $i++) {
            $result .= $pool[random_int(0, $poolLength - 1)];
        }

        return $result;
    }

    /**
     * Shuffle a string using random_int() for cryptographic security.
     */
    private function shuffleString(string $string): string
    {
        $chars = str_split($string);
        $length = count($chars);

        for ($i = $length - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }
}
