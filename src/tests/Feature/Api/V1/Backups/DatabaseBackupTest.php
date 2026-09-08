<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Backups;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    private string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupDir = storage_path('app/test_backups');
        File::deleteDirectory($this->backupDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupDir);
        parent::tearDown();
    }

    public function test_backup_command_creates_backup_file(): void
    {
        $this->artisan('db:backup', ['--path' => $this->backupDir])
            ->assertSuccessful()
            ->expectsOutputToContain('Backup created:');

        $files = File::files($this->backupDir);
        $this->assertNotEmpty($files, 'Backup file should be created.');
        $this->assertTrue(str_ends_with($files[0]->getFilename(), '.sql'));
    }

    public function test_backup_command_rotates_old_backups(): void
    {
        File::makeDirectory($this->backupDir, 0755, true, true);

        // Create 5 backup files with different timestamps
        for ($i = 0; $i < 5; $i++) {
            $filename = "zekura_backup_2026-09-0{$i}_000000.sql";
            File::put("{$this->backupDir}/{$filename}", '-- old backup --');
            // Slightly modify mtime so rotation order is deterministic
            touch("{$this->backupDir}/{$filename}", now()->subDays(5 - $i)->timestamp);
        }

        // Run backup with --keep=3 (should keep only 3 most recent, including the new one)
        $this->artisan('db:backup', ['--path' => $this->backupDir, '--keep' => 3])
            ->assertSuccessful();

        $files = File::files($this->backupDir);
        $sqlFiles = array_filter($files, fn ($f) => str_ends_with($f->getFilename(), '.sql'));

        // Should have exactly 3 files (the new backup + 2 most recent old ones)
        $this->assertCount(3, $sqlFiles, 'Should keep 3 files total after rotation.');
    }
}
