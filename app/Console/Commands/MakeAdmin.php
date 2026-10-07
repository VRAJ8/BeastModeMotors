<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'passport:make-admin {email : The account to give trust & safety access to}
                            {--revoke : Take the access away instead}';

    protected $description = 'Give an existing account access to the trust & safety admin (/admin), or take it away';

    public function handle(): int
    {
        $user = User::where('email', strtolower(trim($this->argument('email'))))->first();

        if (! $user) {
            $this->components->error('No account uses that email. Sign up on the site first, then run this again.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();

        $this->components->info($this->option('revoke')
            ? "{$user->email} no longer has admin access."
            : "{$user->email} can now sign in to ".url('/admin').'.');

        return self::SUCCESS;
    }
}
