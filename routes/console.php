<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('schoolpass:licenses-refresh')
    ->dailyAt('00:10')
    ->withoutOverlapping();

Schedule::command('schoolpass:support-expire')
    ->everyFiveMinutes()
    ->withoutOverlapping();

if (
    (bool) config(
        'family_memberships.google_play.enabled',
        false
    )
) {
    Schedule::command(
        'family:reconcile-google-play '
        .'--limit=200 '
        .'--stale-minutes=15'
    )
        ->everyFifteenMinutes()
        ->withoutOverlapping(20);
}