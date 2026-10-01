<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lembretes de aula ao vivo (precisa do cron do Laravel: * * * * * php artisan schedule:run)
Schedule::command('aulas:lembretes')->everyMinute()->withoutOverlapping();
