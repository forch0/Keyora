<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\PersonalVaultItem;
use App\Models\SecureNote;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AuthHelper;
use Tests\TestCase;

class RotateEncryptionKeyTest extends TestCase
{
    use AuthHelper, RefreshDatabase;

    public function test_key_rotate_dry_run_reports_without_changes(): void
    {
        [$user] = $this->createAndAuthUser();

        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Test Item',
            'type' => 'password',
            'username' => 'user1',
            'password' => 'secret',
        ]);

        $this->artisan('key:rotate', ['--dry-run' => true, '--force' => true])
            ->expectsOutputToContain('DRY RUN')
            ->expectsOutputToContain('re-encrypted')
            ->assertSuccessful();

        // Data should be unchanged
        $this->assertEquals('user1', $item->fresh()->username);
        $this->assertEquals('secret', $item->fresh()->password);
    }

    public function test_key_rotate_re_encrypts_data_with_current_key(): void
    {
        [$user] = $this->createAndAuthUser();

        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Test Item',
            'type' => 'password',
            'username' => 'user1',
            'password' => 'secret123',
        ]);

        // Capture the raw encrypted value
        $originalRaw = $item->getRawOriginal('password');

        // Run rotation
        $this->artisan('key:rotate', ['--force' => true])
            ->assertSuccessful();

        // The decrypted value should be the same
        $fresh = $item->fresh();
        $this->assertEquals('secret123', $fresh->password);

        // The raw encrypted value should be different (re-encrypted)
        $newRaw = $fresh->getRawOriginal('password');
        $this->assertNotEquals($originalRaw, $newRaw);
    }

    public function test_key_rotate_handles_secure_notes(): void
    {
        [$user, $token] = $this->createAndAuthUser();
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
        ]);
        $tenant->users()->attach($user, ['role' => 'admin']);

        $note = SecureNote::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'title' => 'Secret Note',
            'content' => 'top secret content',
        ]);

        $originalRaw = $note->getRawOriginal('content');

        $this->artisan('key:rotate', ['--force' => true])
            ->assertSuccessful();

        $fresh = $note->fresh();
        $this->assertEquals('top secret content', $fresh->content);
        $this->assertNotEquals($originalRaw, $fresh->getRawOriginal('content'));
    }

    public function test_key_rotate_handles_custom_fields(): void
    {
        [$user] = $this->createAndAuthUser();

        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Item with custom fields',
            'type' => 'password',
            'username' => 'user1',
            'password' => 'secret',
            'custom_fields' => [['key' => 'pin', 'value' => '1234']],
        ]);

        $originalRaw = $item->getRawOriginal('custom_fields');

        $this->artisan('key:rotate', ['--force' => true])
            ->assertSuccessful();

        $fresh = $item->fresh();
        $customFields = $fresh->custom_fields;
        $this->assertIsArray($customFields);
        $this->assertEquals('1234', $customFields[0]['value']);
        $this->assertNotEquals($originalRaw, $fresh->getRawOriginal('custom_fields'));
    }

    public function test_key_rotate_skips_null_and_empty_fields(): void
    {
        [$user] = $this->createAndAuthUser();

        $item = PersonalVaultItem::create([
            'user_id' => $user->id,
            'name' => 'Minimal Item',
            'type' => 'password',
            'username' => 'user1',
            'password' => 'secret',
            'notes' => null,
        ]);

        $this->artisan('key:rotate', ['--force' => true])
            ->assertSuccessful();

        $fresh = $item->fresh();
        $this->assertEquals('user1', $fresh->username);
        $this->assertNull($fresh->notes);
    }

    public function test_key_rotate_warns_without_previous_keys(): void
    {
        $this->artisan('key:rotate', ['--dry-run' => true, '--force' => true])
            ->expectsOutputToContain('APP_PREVIOUS_KEYS is not set');
    }
}
