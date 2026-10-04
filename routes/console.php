<?php

use Illuminate\Support\Facades\Schedule;

// Keep the public demo tidy: rebuild it every night.
Schedule::command('demo:seed --fresh')
    ->dailyAt('04:00')
    ->when(fn () => config('dealership.demo'));

Schedule::command('queue:prune-failed --hours=168')->daily();
