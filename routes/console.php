<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('passport:send-reminders')->dailyAt('08:00');
Schedule::command('passport:sync-recalls')->weeklyOn(1, '06:00');
Schedule::command('passport:housekeeping')->hourly();
Schedule::command('queue:prune-failed --hours=168')->daily();

// Keep the public demo tidy: rebuild it every night.
Schedule::command('demo:seed --fresh')
    ->dailyAt('04:00')
    ->when(fn () => config('passport.demo'));
