<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dot.Tasks scheduled tasks
Schedule::command('tasks:check-due-soon')->dailyAt('07:00');
Schedule::command('tasks:detect-escalation-candidates')->dailyAt('07:30');
