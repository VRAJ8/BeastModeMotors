<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SeedDemo extends Command
{
    protected $signature = 'demo:seed
        {--if-empty : Only seed when there are no users yet}
        {--fresh : Drop all tables and uploaded files, and rebuild the demo from scratch}
        {--force : Allow --fresh when demo mode is off}';

    protected $description = 'Seed the public demo (owners, cars with history, listings, deals, admin)';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            if (! config('passport.demo') && ! $this->option('force')) {
                $this->components->error('Demo mode is off, so this looks like real data. Pass --force if you really mean to wipe it.');

                return self::FAILURE;
            }

            $this->wipeDatabase();

            // Visitors' uploads go too, or they'd stay public (and pile up) after every reset. Only once the
            // database is wiped, so a failed reset never leaves records pointing at deleted files.
            foreach (array_unique([config('passport.disks.photos'), config('passport.disks.documents')]) as $disk) {
                Storage::disk($disk)->deleteDirectory('vehicles');
            }

            $this->call('migrate', ['--seed' => true, '--force' => true]);
            $this->call('cache:clear');

            return self::SUCCESS;
        }

        if (Schema::hasTable('users') && User::exists()) {
            if ($this->option('if-empty')) {
                $this->components->info('Demo already seeded, skipping.');

                return self::SUCCESS;
            }

            $this->components->error('The database already has data. Use --fresh to rebuild the demo from scratch.');

            return self::FAILURE;
        }

        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--force' => true]);

        return self::SUCCESS;
    }

    /**
     * migrate:fresh empties a SQLite file by truncating it, which corrupts the database while the queue worker and
     * scheduler hold it open in WAL mode. On SQLite the tables are dropped through SQLite itself instead.
     */
    private function wipeDatabase(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->call('db:wipe', ['--force' => true]);

            return;
        }

        Schema::withoutForeignKeyConstraints(function () {
            foreach (Schema::getTables() as $table) {
                if (! str_starts_with($table['name'], 'sqlite_')) {
                    Schema::drop($table['name']);
                }
            }
        });
    }
}
