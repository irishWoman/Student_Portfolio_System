<?php

use Illuminate\Support\Facades\Schedule;

/**
 * Scheduled work. Point cron at `php artisan schedule:run` every minute, or run
 * the commands manually during a demo.
 */

// Deadline reminders and overdue flags: once a day, early.
Schedule::command('portfolio:process-deadlines')->dailyAt('06:00');

// Keep attainment snapshots fresh for the chair's dashboard.
Schedule::command('portfolio:recompute-attainment')->dailyAt('01:00');
