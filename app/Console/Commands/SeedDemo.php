<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SeedDemo extends Command
{
    protected $signature = 'demo:seed
        {--if-empty : Only seed when the showroom has no vehicles yet}
        {--fresh : Drop all tables and rebuild the demo from scratch}';

    protected $description = 'Seed the public demo showroom (admin + customer accounts, inventory, activity)';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
            $this->call('cache:clear');

            return self::SUCCESS;
        }

        if ($this->option('if-empty') && Schema::hasTable('vehicles') && Vehicle::exists()) {
            $this->components->info('Showroom already seeded, skipping.');

            return self::SUCCESS;
        }

        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--force' => true]);

        return self::SUCCESS;
    }
}
