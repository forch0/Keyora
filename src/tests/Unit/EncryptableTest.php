<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\PersonalVaultItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Helpers\AuthHelper;
use Tests\TestCase;

class EncryptableTest extends TestCase
{
    use AuthHelper, RefreshDatabase;

    public function test_encrypted_field_round_trips_correctly(): void
    {
        $user = $this->createUser();

        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Test Item',
            'type' => 'password',
            'username' => 'myuser',
            'password' => 's3cret',
        ]);

        // Values should decrypt transparently on read
        $this->assertSame('myuser', $item->username);
        $this->assertSame('s3cret', $item->password);
    }

    public function test_tampered_ciphertext_returns_null_not_raw_value(): void
    {
        $user = $this->createUser();

        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Test Item',
            'type' => 'password',
            'username' => 'myuser',
            'password' => 's3cret',
        ]);

        // Corrupt the encrypted password directly in the database,
        // bypassing the setter so we have invalid ciphertext stored.
        DB::table('personal_vault_items')
            ->where('id', $item->id)
            ->update(['password' => 'tampered-not-valid-ciphertext']);

        // Re-fetch from DB so the model loads the tampered value
        $item->refresh();

        // Expect the error to be logged
        Log::shouldReceive('error')
            ->atLeast()
            ->once();

        // Fail-closed: must return null, never the raw tampered string
        $this->assertNull($item->password);
    }

    public function test_null_encryptable_values_pass_through(): void
    {
        $user = $this->createUser();

        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Test Item',
            'type' => 'password',
            'username' => 'myuser',
            'password' => 's3cret',
            'notes' => null,
        ]);

        $this->assertNull($item->notes);
    }

    public function test_empty_string_encryptable_values_pass_through(): void
    {
        $user = $this->createUser();

        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Test Item',
            'type' => 'password',
            'username' => 'myuser',
            'password' => 's3cret',
            'notes' => '',
        ]);

        $this->assertSame('', $item->notes);
    }
}
