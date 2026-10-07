<?php

use Illuminate\Support\Facades\Schedule;

// onOneServer: with several containers running the scheduler, each job still runs once (cache lock).
Schedule::command('passport:send-reminders')->dailyAt('08:00')->withoutOverlapping()->onOneServer();
Schedule::command('passport:sync-recalls')->weeklyOn(1, '06:00')->withoutOverlapping()->onOneServer();
Schedule::command('passport:housekeeping')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('queue:prune-failed --hours=168')->daily()->onOneServer();

// Keep the public demo tidy: rebuild it every night. This wipes the database, so it needs its own opt-in
// on top of demo mode — a developer's local .env with DEMO_MODE=true must never lose their data at 4am.
Schedule::command('demo:seed --fresh')
    ->dailyAt('04:00')
    ->onOneServer()
    ->when(fn () => config('passport.demo') && config('passport.demo_nightly_reset'));
