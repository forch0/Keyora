<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Trait for models with fields that should be encrypted at rest.
 *
 * Define a protected $encryptable array on the model listing the column
 * names that should be transparently encrypted/decrypted. The trait
 * uses Laravel's accessor/mutator infrastructure so it integrates
 * cleanly with casts and other model behavior.
 *
 * - When setting a value: it is encrypted via Crypt::encryptString()
 * - When getting a value: it is decrypted via Crypt::decryptString()
 * - Null values are passed through unchanged (no encryption of nulls)
 * - On decryption failure: returns null and logs the error (fail-closed).
 *   Returning raw ciphertext as if it were plaintext is a security risk
 *   — a corrupted or key-rotated value must never be served as "the secret".
 *
 * Uses AES-256-CBC via Laravel's Crypt facade (ADR-003).
 */
trait Encryptable
{
    /**
     * Boot the trait — register encrypted accessors for each encryptable field.
     */
    protected static function bootEncryptable(): void
    {
        // Accessors are registered dynamically via method resolution,
        // so we don't need to do anything at boot. The get/setAttribute
        // overrides below handle the encryption/decryption.
    }

    /**
     * Get an attribute from the model, decrypting if it's in $encryptable.
     *
     * On decryption failure, returns null and logs the error. This is
     * fail-closed: a corrupted or key-rotated value is never served as
     * plaintext.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if ($this->isEncryptable($key) && is_string($value) && $value !== '') {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable $e) {
                // Fail closed: return null instead of serving raw ciphertext.
                // Log the error with context for investigation (key rotation,
                // corrupted data, or a tampered record).
                Log::error('Encryptable decryption failed', [
                    'model' => static::class,
                    'key' => $this->getKey(),
                    'field' => $key,
                    'error' => $e->getMessage(),
                ]);

                return;
            }
        }

        return $value;
    }

    /**
     * Set a value on the model, encrypting if it's in $encryptable.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if ($this->isEncryptable($key) && $value !== null && $value !== '') {
            $value = Crypt::encryptString((string) $value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Check if the given key is in the $encryptable array.
     *
     * Uses property_exists so the trait works on models that declare
     * the property without triggering PHPStan nullability warnings.
     */
    private function isEncryptable(string $key): bool
    {
        /** @var list<string> $encryptable */
        $encryptable = $this->encryptable;

        return in_array($key, $encryptable, true);
    }
}
