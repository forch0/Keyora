<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DatabaseBackup extends Command
{
    protected $signature = 'db:backup
                            {--path= : Custom backup directory path}
                            {--keep=7 : Number of backup files to keep (rotation)}';

    protected $description = 'Create a database backup (SQL dump) and rotate old backups.';

    public function handle(): int
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $backupDir = $this->option('path') ?: storage_path('app/backups');
        $keep = (int) $this->option('keep');

        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_His');
        $filename = "keyora_backup_{$timestamp}.sql";
        $filepath = "{$backupDir}/{$filename}";

        try {
            $dumped = match ($driver) {
                'sqlite' => $this->backupSqlite($connection, $filepath),
                'mysql' => $this->backupMysql($connection, $filepath),
                'pgsql' => $this->backupPgsql($connection, $filepath),
                default => throw new \RuntimeException("Unsupported database driver: {$driver}"),
            };

            if (! $dumped) {
                $this->error('Backup failed: could not create dump file.');

                return self::FAILURE;
            }

            $this->info("Backup created: {$filepath}");

            // Rotate old backups
            $this->rotateBackups($backupDir, $keep);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function backupSqlite(string $connection, string $filepath): bool
    {
        $database = config("database.connections.{$connection}.database");

        if ($database === ':memory:' || $database === null) {
            // For in-memory SQLite, use the .dump command via the CLI if available,
            // otherwise export table schemas and data via PHP.
            return $this->backupSqliteMemory($filepath);
        }

        // For file-based SQLite, copy the database file
        return copy($database, $filepath);
    }

    private function backupSqliteMemory(string $filepath): bool
    {
        // Export all tables as SQL statements
        $tables = DB::connection()->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        $sql = [];
        foreach ($tables as $table) {
            $tableName = $table->name;

            // Get the CREATE TABLE statement
            $createRow = DB::connection()->selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$tableName]);
            if ($createRow !== null) {
                $sql[] = "DROP TABLE IF EXISTS `{$tableName}`;";
                $sql[] = $createRow->sql.';';
            }

            // Get all rows
            $rows = DB::connection()->table($tableName)->get();
            foreach ($rows as $row) {
                $values = array_map(function ($value): string {
                    if ($value === null) {
                        return 'NULL';
                    }

                    return "'".str_replace("'", "''", (string) $value)."'";
                }, (array) $row);

                $columns = implode('`, `', array_keys((array) $row));
                $valueList = implode(', ', $values);
                $sql[] = "INSERT INTO `{$tableName}` (`{$columns}`) VALUES ({$valueList});";
            }
        }

        return File::put($filepath, implode("\n", $sql)) !== false;
    }

    private function backupMysql(string $connection, string $filepath): bool
    {
        $host = config("database.connections.{$connection}.host", '127.0.0.1');
        $port = config("database.connections.{$connection}.port", 3306);
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines %s > %s 2>/dev/null',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath),
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        return $exitCode === 0 && File::exists($filepath) && File::size($filepath) > 0;
    }

    private function backupPgsql(string $connection, string $filepath): bool
    {
        $host = config("database.connections.{$connection}.host", '127.0.0.1');
        $port = config("database.connections.{$connection}.port", 5432);
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");

        $command = sprintf(
            'PGPASSWORD=%s pg_dump --host=%s --port=%s --username=%s --format=plain --no-owner %s > %s 2>/dev/null',
            escapeshellarg(config("database.connections.{$connection}.password", '')),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($filepath),
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        return $exitCode === 0 && File::exists($filepath) && File::size($filepath) > 0;
    }

    private function rotateBackups(string $dir, int $keep): void
    {
        $files = collect(File::files($dir))
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), '.sql'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        if ($files->count() <= $keep) {
            return;
        }

        $toDelete = $files->slice($keep);
        foreach ($toDelete as $file) {
            File::delete($file->getPathname());
            $this->info("Rotated old backup: {$file->getFilename()}");
        }
    }
}
