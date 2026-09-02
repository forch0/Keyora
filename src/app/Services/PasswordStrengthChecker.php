<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Password strength checker based on entropy scoring, character variety,
 * common password detection, and pattern penalties.
 */
class PasswordStrengthChecker
{
    /** @var list<string>|null */
    private ?array $commonPasswords = null;

    /**
     * Check password strength and return analysis.
     *
     * @return array<string, mixed>
     */
    public function check(string $password): array
    {
        $length = strlen($password);
        $hasUpper = preg_match('/[A-Z]/', $password) === 1;
        $hasLower = preg_match('/[a-z]/', $password) === 1;
        $hasNumber = preg_match('/[0-9]/', $password) === 1;
        $hasSymbol = preg_match('/[^A-Za-z0-9]/', $password) === 1;

        // Calculate charset size for entropy
        $charsetSize = 0;
        if ($hasLower) {
            $charsetSize += 26;
        }

        if ($hasUpper) {
            $charsetSize += 26;
        }

        if ($hasNumber) {
            $charsetSize += 10;
        }

        if ($hasSymbol) {
            $charsetSize += 32;
        }

        $entropy = $charsetSize > 0 ? (float) ($length * log($charsetSize, 2)) : 0.0;

        // Build criteria
        $criteria = [
            'length' => $length >= 12,
            'uppercase' => $hasUpper,
            'lowercase' => $hasLower,
            'numbers' => $hasNumber,
            'symbols' => $hasSymbol,
            'no_common_patterns' => true,
        ];

        // Score calculation
        $score = 0;

        // Length scoring
        $score += match (true) {
            $length >= 20 => 80,
            $length >= 16 => 60,
            $length >= 12 => 40,
            $length >= 8 => 20,
            default => 0,
        };

        // Character variety (10 points each)
        if ($hasUpper) {
            $score += 10;
        }

        if ($hasLower) {
            $score += 10;
        }

        if ($hasNumber) {
            $score += 10;
        }

        if ($hasSymbol) {
            $score += 10;
        }

        // Penalties
        $suggestions = [];

        // Common password check
        if ($this->isCommonPassword($password)) {
            $score = min($score, 10);
            $criteria['no_common_patterns'] = false;
            $suggestions[] = 'This is a commonly used password. Choose something unique.';
        }

        // Sequential characters penalty (abc, 123, qwerty)
        if ($this->hasSequentialChars($password)) {
            $score = max(0, $score - 15);
            $criteria['no_common_patterns'] = false;
            $suggestions[] = 'Avoid sequential characters like "abc" or "123".';
        }

        // Repeated characters penalty (aaaa, 1111)
        if ($this->hasRepeatedChars($password)) {
            $score = max(0, $score - 10);
            $criteria['no_common_patterns'] = false;
            $suggestions[] = 'Avoid repeated characters like "aaaa" or "1111".';
        }

        // Generate suggestions for missing criteria
        if (! $criteria['length']) {
            $suggestions[] = 'Use at least 12 characters.';
        }

        if (! $hasUpper) {
            $suggestions[] = 'Add uppercase letters.';
        }

        if (! $hasLower) {
            $suggestions[] = 'Add lowercase letters.';
        }

        if (! $hasNumber) {
            $suggestions[] = 'Add numbers.';
        }

        if (! $hasSymbol) {
            $suggestions[] = 'Add symbols (!@#$%^&*).';
        }

        // Clamp score to 0-100
        $score = max(0, min(100, $score));

        // Determine strength label
        $strength = match (true) {
            $score <= 20 => 'very_weak',
            $score <= 40 => 'weak',
            $score <= 60 => 'fair',
            $score <= 80 => 'strong',
            default => 'very_strong',
        };

        return [
            'score' => $score,
            'strength' => $strength,
            'entropy' => round($entropy, 1),
            'criteria' => $criteria,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Check if the password is in the common passwords list.
     */
    private function isCommonPassword(string $password): bool
    {
        if ($this->commonPasswords === null) {
            $path = resource_path('data/common-passwords.txt');

            if (file_exists($path)) {
                /** @var list<string> $passwords */
                $passwords = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $this->commonPasswords = $passwords;
            } else {
                $this->commonPasswords = [];
            }
        }

        return in_array(strtolower($password), array_map('strtolower', $this->commonPasswords), true);
    }

    /**
     * Check for sequential characters (abc, 123, qwerty patterns).
     */
    private function hasSequentialChars(string $password): bool
    {
        $lower = strtolower($password);
        $length = strlen($lower);

        if ($length < 3) {
            return false;
        }

        // Check for alphabetical sequences (abc, cba)
        for ($i = 0; $i < $length - 2; $i++) {
            $a = ord($lower[$i]);
            $b = ord($lower[$i + 1]);
            $c = ord($lower[$i + 2]);

            // Forward sequence (abc, 123)
            if ($b === $a + 1 && $c === $b + 1) {
                return true;
            }

            // Reverse sequence (cba, 321)
            if ($b === $a - 1 && $c === $b - 1) {
                return true;
            }
        }

        // Check for keyboard sequences (qwerty, asdf)
        $keyboardSequences = ['qwerty', 'qwertyuiop', 'asdfgh', 'asdfghjkl', 'zxcvbn', 'zxcvbnm', 'qazwsx', 'wasd'];
        foreach ($keyboardSequences as $seq) {
            if (str_contains($lower, $seq) || str_contains($lower, strrev($seq))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for repeated characters (aaaa, 1111, abab).
     */
    private function hasRepeatedChars(string $password): bool
    {
        $length = strlen($password);

        if ($length < 4) {
            return false;
        }

        // Check for 4+ identical consecutive characters
        $count = 1;
        for ($i = 1; $i < $length; $i++) {
            if ($password[$i] === $password[$i - 1]) {
                $count++;
                if ($count >= 4) {
                    return true;
                }
            } else {
                $count = 1;
            }
        }

        // Check for 3+ repeating patterns (ababab)
        for ($patternLen = 1; $patternLen <= 3; $patternLen++) {
            if ($length < $patternLen * 3) {
                continue;
            }

            for ($i = 0; $i <= $length - $patternLen * 3; $i++) {
                $pattern = substr($password, $i, $patternLen);
                $expected = str_repeat($pattern, 3);
                $actual = substr($password, $i, $patternLen * 3);

                if ($expected === $actual) {
                    return true;
                }
            }
        }

        return false;
    }
}
