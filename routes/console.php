<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

// Where each job's output and logged errors go. Laravel's default is /dev/null; the Docker image points this at
// the container's log so the jobs show up in the platform's logs. If that can't be written to (the container
// runs as another user, or on Windows), the jobs must still run, so fall back to the platform's null device.
$configured = config('passport.schedule_output');
$output = is_writable($configured) ? $configured : (windows_os() ? 'NUL' : '/dev/null');

// onOneServer: with several containers running the scheduler, each job still runs once (cache lock).
Schedule::command('passport:send-reminders')->dailyAt('08:00')->withoutOverlapping()->onOneServer()->appendOutputTo($output);
Schedule::command('passport:buyer-alerts')->dailyAt('07:30')->withoutOverlapping()->onOneServer()->appendOutputTo($output);
Schedule::command('passport:sync-recalls')->weeklyOn(1, '06:00')->withoutOverlapping()->onOneServer()->appendOutputTo($output);
Schedule::command('passport:housekeeping')->hourly()->withoutOverlapping()->onOneServer()->appendOutputTo($output);
Schedule::command('queue:prune-failed --hours=168')->daily()->onOneServer()->appendOutputTo($output);

// Keep the public demo tidy: rebuild it every night. This wipes the database, so it needs its own opt-in
// on top of demo mode — a developer's local .env with DEMO_MODE=true must never lose their data at 4am.
// The demo always runs on SQLite, so a site on a real database server is never wiped, whatever its settings.
Schedule::command('demo:seed --fresh')
    ->dailyAt('04:00')
    ->onOneServer()
    ->appendOutputTo($output)
    ->when(fn () => config('passport.demo') && config('passport.demo_nightly_reset') && DB::connection()->getDriverName() === 'sqlite');
