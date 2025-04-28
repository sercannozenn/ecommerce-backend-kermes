<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->use([\Illuminate\Http\Middleware\HandleCors::class]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('discounts:expire')
                 ->everyMinute();
//                 ->dailyAt('01:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
