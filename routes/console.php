<?php

use App\Jobs\SyncBusinessPartnersFromSapJob;
use App\Jobs\SyncDepartmentsFromSapJob;
use App\Jobs\SyncProjectsFromSapJob;
use Illuminate\Support\Facades\Schedule;

Schedule::command('sap:ping')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->when(fn () => filled(config('services.sap.base_url')));

Schedule::job(new SyncProjectsFromSapJob)
    ->dailyAt('02:15')
    ->withoutOverlapping()
    ->when(fn () => filled(config('services.sap.base_url')));

Schedule::job(new SyncDepartmentsFromSapJob)
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->when(fn () => filled(config('services.sap.base_url')));

Schedule::job(new SyncBusinessPartnersFromSapJob)
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->when(fn () => filled(config('services.sap.base_url')));
