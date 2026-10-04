<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * "Save the demo exactly as it looks now" and "put it back like that".
 *
 * The saved copy lives in plain files (storage/app/demo-baseline): every table's rows
 * plus a copy of the pictures uploaded through the admin panel. Nothing is added to the
 * database, so no migration is needed.
 *
 * restore() checks the whole saved copy first and applies the database part in one
 * transaction, so a damaged copy or a failure part-way leaves the site untouched.
 * Sessions, caches and queued jobs are not part of it, which is why a signed-in
 * admin stays signed in.
 */
class DemoBaseline
{
    /** Tables that are never saved or touched. */
    private const SKIP = [
        'migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches',
        'failed_jobs', 'password_reset_tokens', 'personal_access_tokens',
    ];

    /** Cleared on restore but not saved (short-lived codes). */
    private const WIPE_ONLY = ['otp_verifications'];

    private const KEEP_FILES = ['.gitkeep', '.htaccess'];

    private const JSON = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR;

    public static function path(string $sub = ''): string
    {
        $base = (string) (config('shop.demo_baseline_path') ?: storage_path('app/demo-baseline'));

        return $sub === '' ? $base : $base.'/'.$sub;
    }

    public static function uploadsPath(): string
    {
        return (string) (config('shop.demo_uploads_path') ?: public_path('images/uploads'));
    }

    /** Details of the saved copy, or null when there is none (or it was never finished). */
    public static function meta(): ?array
    {
        $file = self::path('meta.json');

        if (! is_file($file) || ! is_file(self::path('data.jsonl'))) {
            return null;
        }

        $meta = json_decode((string) @file_get_contents($file), true);

        return is_array($meta) && ! empty($meta['complete']) ? $meta : null;
    }

    public static function exists(): bool
    {
        return self::meta() !== null;
    }

    /**
     * Save the site as it is right now.
     *
     * @return array{saved_at: string, tables: array<string, int>, uploads: int, complete: bool}
     */
    public static function save(): array
    {
        @set_time_limit(300);
        $lock = self::lock();

        $final = self::path();
        $tmp = $final.'.new';
        $old = $final.'.old';

        try {
            File::deleteDirectory($tmp);
            File::ensureDirectoryExists($tmp.'/uploads');

            $counts = [];
            $fh = fopen($tmp.'/data.jsonl', 'wb');

            if (! $fh) {
                throw new \RuntimeException('Could not write to the storage folder. Check that storage/ is writable.');
            }

            foreach (self::tables() as $table) {
                $columns = Schema::getColumnListing($table);
                fwrite($fh, json_encode(['table' => $table, 'columns' => $columns], self::JSON)."\n");
                $counts[$table] = 0;

                foreach (DB::table($table)->cursor() as $row) {
                    $row = (array) $row;
                    fwrite($fh, json_encode(array_map(fn ($c) => $row[$c] ?? null, $columns), self::JSON)."\n");
                    $counts[$table]++;
                }
            }

            fclose($fh);

            $files = 0;
            $uploads = self::uploadsPath();

            if (is_dir($uploads)) {
                foreach (File::allFiles($uploads, true) as $file) {
                    if (in_array($file->getFilename(), self::KEEP_FILES, true)) {
                        continue;
                    }

                    $to = $tmp.'/uploads/'.$file->getRelativePathname();
                    File::ensureDirectoryExists(dirname($to));
                    File::copy($file->getPathname(), $to);
                    $files++;
                }
            }

            $meta = ['saved_at' => now()->toIso8601String(), 'tables' => $counts, 'uploads' => $files, 'complete' => true];
            file_put_contents($tmp.'/meta.json', json_encode($meta, self::JSON | JSON_PRETTY_PRINT));

            // Swap in the new copy only now that it is complete.
            File::deleteDirectory($old);

            if (is_dir($final) && ! @rename($final, $old)) {
                throw new \RuntimeException('Could not replace the previous saved copy.');
            }

            if (! @rename($tmp, $final)) {
                if (is_dir($old)) {
                    @rename($old, $final);
                }

                throw new \RuntimeException('Could not finish saving.');
            }

            File::deleteDirectory($old);

            return $meta;
        } catch (\Throwable $e) {
            File::deleteDirectory($tmp);

            throw $e instanceof \RuntimeException ? $e : new \RuntimeException('Saving failed: '.$e->getMessage(), 0, $e);
        } finally {
            self::unlock($lock);
        }
    }

    /**
     * Put the site back exactly as saved. Throws a RuntimeException with a plain message
     * when it cannot (the database part is all-or-nothing).
     */
    public static function restore(): void
    {
        $meta = self::meta() ?? throw new \RuntimeException('No saved demo state yet. Save one first.');

        @set_time_limit(300);
        $lock = self::lock();

        try {
            self::verify($meta);
            self::restoreDatabase();
            self::restoreUploads($meta);

            try {
                Cache::flush();
            } catch (\Throwable) {
                // a cache store that cannot be flushed is not a problem
            }
        } finally {
            self::unlock($lock);
        }
    }

    /** @return list<string> */
    private static function tables(): array
    {
        // On MySQL, getTables() without a name lists the tables of EVERY database on the
        // server (other projects too), so only this site's own database is asked for.
        $schema = in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) ? DB::getDatabaseName() : null;

        return collect(Schema::getTables($schema))
            ->pluck('name')
            ->unique()
            ->reject(fn ($n) => in_array($n, self::SKIP, true) || str_starts_with($n, 'sqlite_'))
            ->filter(fn ($n) => self::readable($n))
            ->values()
            ->all();
    }

    /**
     * A database can list a table that cannot be opened (a leftover from a deleted or
     * copied database: "Table ... doesn't exist"). Such a table has nothing to save, so it
     * is left alone instead of stopping the whole save. Any other error is still raised.
     */
    private static function readable(string $table): bool
    {
        try {
            DB::table($table)->limit(1)->get();

            return true;
        } catch (QueryException $e) {
            $missing = $e->getCode() === '42S02'
                || ($e->errorInfo[1] ?? null) === 1146
                || str_contains(strtolower($e->getMessage()), 'no such table');

            if ($missing) {
                return false;
            }

            throw $e;
        }
    }

    /** Reads the saved file from top to bottom, calling $onTable($table, $columns) and $onRow($values). */
    private static function read(callable $onTable, callable $onRow): void
    {
        $fh = fopen(self::path('data.jsonl'), 'rb');

        if (! $fh) {
            throw new \RuntimeException('The saved demo state cannot be read.');
        }

        try {
            $line = 0;

            while (($text = fgets($fh)) !== false) {
                $line++;

                if (trim($text) === '') {
                    continue;
                }

                try {
                    $data = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    throw new \RuntimeException("The saved demo state is damaged (line {$line}). Nothing was changed. Save it again.");
                }

                if (is_array($data) && array_is_list($data)) {
                    $onRow($data);
                } elseif (isset($data['table'], $data['columns'])) {
                    $onTable((string) $data['table'], (array) $data['columns']);
                } else {
                    throw new \RuntimeException("The saved demo state is damaged (line {$line}). Nothing was changed. Save it again.");
                }
            }
        } finally {
            fclose($fh);
        }
    }

    /** Whole-file check before anything is changed. */
    private static function verify(array $meta): void
    {
        $counts = [];
        $table = null;
        $width = 0;

        self::read(
            function (string $t, array $columns) use (&$counts, &$table, &$width) {
                if (isset($counts[$t])) {
                    throw new \RuntimeException('The saved demo state is damaged (a table appears twice). Nothing was changed. Save it again.');
                }

                $table = $t;
                $width = count($columns);
                $counts[$t] = 0;
            },
            function (array $row) use (&$counts, &$table, &$width) {
                if ($table === null || count($row) !== $width) {
                    throw new \RuntimeException('The saved demo state is damaged. Nothing was changed. Save it again.');
                }

                $counts[$table]++;
            },
        );

        if ($counts !== ($meta['tables'] ?? [])) {
            throw new \RuntimeException('The saved demo state is incomplete. Nothing was changed. Save it again.');
        }

        $folder = self::path('uploads');
        $have = is_dir($folder) ? count(File::allFiles($folder, true)) : 0;

        if ($have !== (int) ($meta['uploads'] ?? 0)) {
            throw new \RuntimeException('The saved pictures are incomplete. Nothing was changed. Save it again.');
        }
    }

    private static function restoreDatabase(): void
    {
        $driver = DB::getDriverName();
        $level = DB::transactionLevel();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        try {
            DB::beginTransaction();

            if ($driver === 'sqlite') {
                DB::statement('PRAGMA defer_foreign_keys = ON'); // when foreign keys cannot be switched off inside a transaction
            }

            $current = self::tables();

            foreach (array_unique(array_merge($current, array_filter(self::WIPE_ONLY, fn ($t) => Schema::hasTable($t)))) as $t) {
                DB::table($t)->delete();
            }

            $table = null;
            $use = [];
            $batch = [];
            $size = 50;

            $flush = function () use (&$table, &$batch) {
                if ($table !== null && $batch !== []) {
                    DB::table($table)->insert($batch);
                }

                $batch = [];
            };

            self::read(
                function (string $t, array $columns) use (&$table, &$use, &$size, $flush, $current) {
                    $flush();
                    $table = in_array($t, $current, true) ? $t : null; // a table that no longer exists is skipped
                    $have = $table ? array_flip(Schema::getColumnListing($table)) : [];
                    $use = array_filter($columns, fn ($c) => isset($have[$c])); // columns that no longer exist are skipped
                    $size = max(1, intdiv(800, max(1, count($use))));
                },
                function (array $row) use (&$table, &$use, &$batch, &$size, $flush) {
                    if ($table === null || $use === []) {
                        return;
                    }

                    $keys = array_keys($use);
                    $batch[] = array_combine(array_values($use), array_map(fn ($k) => $row[$k], $keys));

                    if (count($batch) >= $size) {
                        $flush();
                    }
                },
            );

            $flush();
            DB::commit();
        } catch (\Throwable $e) {
            while (DB::transactionLevel() > $level) {
                DB::rollBack();
            }

            throw new \RuntimeException('Reset could not finish and nothing was changed ('.$e->getMessage().').', 0, $e);
        } finally {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }
    }

    private static function restoreUploads(array $meta): void
    {
        $root = self::uploadsPath();
        File::ensureDirectoryExists($root);

        foreach (File::directories($root) as $dir) {
            File::deleteDirectory($dir);
        }

        foreach (File::files($root, true) as $file) {
            if (! in_array($file->getFilename(), self::KEEP_FILES, true)) {
                File::delete($file->getPathname());
            }
        }

        $source = self::path('uploads');
        $copied = 0;

        foreach (is_dir($source) ? File::allFiles($source, true) : [] as $file) {
            $to = $root.'/'.$file->getRelativePathname();
            File::ensureDirectoryExists(dirname($to));
            File::copy($file->getPathname(), $to);
            $copied++;
        }

        if ($copied !== (int) ($meta['uploads'] ?? 0)) {
            throw new \RuntimeException('The data was restored but some pictures could not be copied back. Press Reset once more.');
        }
    }

    /** One save/reset at a time. */
    private static function lock()
    {
        $file = self::path().'.lock';
        File::ensureDirectoryExists(dirname($file));
        $handle = fopen($file, 'c');

        if (! $handle || ! flock($handle, LOCK_EX | LOCK_NB)) {
            throw new \RuntimeException('Another reset or save is running. Try again in a moment.');
        }

        return $handle;
    }

    private static function unlock($handle): void
    {
        if (is_resource($handle)) {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
