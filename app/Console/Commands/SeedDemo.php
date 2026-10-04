<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SeedDemo extends Command
{
    protected $signature = 'demo:seed
        {--if-empty : Only seed when there are no users yet}
        {--fresh : Drop all tables and rebuild the demo from scratch}';

    protected $description = 'Seed the public demo (owners, cars with history, listings, deals, admin)';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
            $this->call('cache:clear');

            return self::SUCCESS;
        }

        if ($this->option('if-empty') && Schema::hasTable('users') && User::exists()) {
            $this->components->info('Demo already seeded, skipping.');

            return self::SUCCESS;
        }

        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--force' => true]);

        return self::SUCCESS;
    }
}
