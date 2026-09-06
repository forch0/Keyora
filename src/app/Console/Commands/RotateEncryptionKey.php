<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PersonalVaultItem;
use App\Models\SecureNote;
use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class RotateEncryptionKey extends Command
{
    protected $signature = 'key:rotate
                            {--dry-run : Show what would be re-encrypted without making changes}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Re-encrypt all encrypted data with the current APP_KEY. Run after updating APP_KEY and setting APP_PREVIOUS_KEYS.';

    private int $processed = 0;

    private int $skipped = 0;

    private int $failed = 0;

    /**
     * Models and their encrypted fields.
     *
     * Each entry: [modelClass, table, encryptableFields, customFieldsColumn, tenantScoped]
     *
     * @var list<array{0: class-string<Model>, 1: string, 2: list<string>, 3: string|null, 4: bool}>
     */
    private array $models;

    public function __construct()
    {
        parent::__construct();

        $this->models = [
            [PersonalVaultItem::class, 'personal_vault_items', ['username', 'password', 'notes'], 'custom_fields', false],
            [VaultItem::class, 'vault_items', ['username', 'password', 'notes'], 'custom_fields', true],
            [SecureNote::class, 'secure_notes', ['content'], null, false],
            [User::class, 'users', ['two_factor_secret', 'two_factor_recovery_codes'], null, false],
        ];
    }

    public function handle(): int
    {
        if (! config('app.previous_keys')) {
            $this->warn('APP_PREVIOUS_KEYS is not set.');
            $this->warn('If you just changed APP_KEY, set APP_PREVIOUS_KEYS to the old key(s) so Laravel can decrypt existing data.');
            $this->warn('Example: APP_PREVIOUS_KEYS=base64:oldkey1here,base64:oldkey2here');
            $this->newLine();

            if (! $this->option('force') && ! $this->confirm('Continue anyway? (Only useful if data is already decryptable with the current key)')) {
                return self::FAILURE;
            }
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN — no changes will be made.');
            $this->newLine();
        }

        $this->info('Re-encrypting data with current APP_KEY...');
        $this->newLine();

        foreach ($this->models as [$modelClass, $table, $encryptableFields, $customFieldsColumn, $tenantScoped]) {
            $this->reEncryptTable($table, $encryptableFields, $customFieldsColumn, $dryRun);
        }

        $this->newLine();
        $this->info("Summary: {$this->processed} re-encrypted, {$this->skipped} skipped, {$this->failed} failed.");

        if ($this->failed > 0) {
            $this->error('Some records could not be re-encrypted. Check the logs for details.');

            return self::FAILURE;
        }

        if (! $dryRun && $this->processed > 0) {
            $this->newLine();
            $this->info('All encrypted data has been re-encrypted with the current APP_KEY.');
            $this->warn('You can now remove APP_PREVIOUS_KEYS from your .env once you are confident all data is migrated.');
        }

        return self::SUCCESS;
    }

    /**
     * Re-encrypt all rows in a table using direct DB queries.
     *
     * @param  list<string>  $encryptableFields
     */
    private function reEncryptTable(string $table, array $encryptableFields, ?string $customFieldsColumn, bool $dryRun): void
    {
        $this->output->write("  {$table}: ...");

        DB::table($table)->orderBy('id')->chunk(100, function ($records) use ($table, $encryptableFields, $customFieldsColumn, $dryRun): void {
            foreach ($records as $record) {
                /** @var array<string, mixed> $row */
                $row = (array) $record;
                $this->reEncryptRow($table, $row, $encryptableFields, $customFieldsColumn, $dryRun);
            }
        });

        $this->output->write("\r  {$table}: done");
        $this->newLine();
    }

    /**
     * Re-encrypt a single row using direct DB queries.
     *
     * @param  list<string>  $encryptableFields
     * @param  array<string, mixed>  $record  Row from DB::table()
     */
    private function reEncryptRow(string $table, array $record, array $encryptableFields, ?string $customFieldsColumn, bool $dryRun): void
    {
        try {
            $updates = [];

            foreach ($encryptableFields as $field) {
                /** @var mixed $rawValue */
                $rawValue = $record[$field] ?? null;

                if (! is_string($rawValue) || $rawValue === '') {
                    continue;
                }

                $decrypted = Crypt::decryptString($rawValue);
                $newEncrypted = Crypt::encryptString($decrypted);

                if ($newEncrypted !== $rawValue) {
                    $updates[$field] = $newEncrypted;
                }
            }

            if ($customFieldsColumn !== null) {
                /** @var mixed $rawCustom */
                $rawCustom = $record[$customFieldsColumn] ?? null;

                if (is_string($rawCustom) && $rawCustom !== '') {
                    $decryptedJson = Crypt::decryptString($rawCustom);
                    $newEncryptedJson = Crypt::encryptString($decryptedJson);

                    if ($newEncryptedJson !== $rawCustom) {
                        $updates[$customFieldsColumn] = $newEncryptedJson;
                    }
                }
            }

            if ($updates === []) {
                $this->skipped++;

                return;
            }

            /** @var int $id */
            $id = $record['id'];

            if (! $dryRun) {
                DB::table($table)->where('id', $id)->update($updates);
            }

            $this->processed++;
        } catch (\Throwable $e) {
            $this->failed++;
            /** @var int $id */
            $id = $record['id'] ?? 0;
            $this->newLine();
            $this->error("    Failed to re-encrypt {$table} ID {$id}: {$e->getMessage()}");
        }
    }
}
