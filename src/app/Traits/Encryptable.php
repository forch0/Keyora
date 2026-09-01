<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

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
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if ($this->isEncryptable($key) && is_string($value) && $value !== '') {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                // If decryption fails (e.g., key rotation, corrupted data),
                // return the raw value so the app doesn't crash. Log this
                // in production — Module 20 (audit logging) will cover this.
                return $value;
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
        // @phpstan-ignore-next-line function.alreadyNarrowedType — trait is reusable; not all models declare $encryptable
        if (! property_exists($this, 'encryptable')) {
            return false;
        }

        /** @var list<string> $encryptable */
        $encryptable = $this->encryptable;

        return in_array($key, $encryptable, true);
    }
}
