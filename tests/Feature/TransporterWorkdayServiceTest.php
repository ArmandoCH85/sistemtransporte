<?php

use App\Models\TransporterWorkLog;
use App\Models\User;
use App\Services\TransporterWorkdayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it closes a transportista workday only once per day', function () {
    $transporter = User::factory()->create();
    $service = app(TransporterWorkdayService::class);
    $now = Carbon::parse('2026-02-23 18:17:00', config('app.timezone'));

    $first = $service->closeForToday($transporter, $now);

    expect($first['created'])->toBeTrue();
    expect(TransporterWorkLog::count())->toBe(1);
    expect($first['log']->work_date->toDateString())->toBe('2026-02-23');
    expect($first['log']->started_at->format('H:i:s'))->toBe('08:00:00');
    expect($first['log']->ended_at->format('H:i:s'))->toBe('18:17:00');

    $second = $service->closeForToday($transporter, $now->copy()->addHour());

    expect($second['created'])->toBeFalse();
    expect(TransporterWorkLog::count())->toBe(1);
    expect($second['log']->id)->toBe($first['log']->id);
});

test('it creates a new work log on a different day', function () {
    $transporter = User::factory()->create();
    $service = app(TransporterWorkdayService::class);

    $service->closeForToday($transporter, Carbon::parse('2026-02-23 18:17:00', config('app.timezone')));
    $secondDay = $service->closeForToday($transporter, Carbon::parse('2026-02-24 18:01:00', config('app.timezone')));

    expect($secondDay['created'])->toBeTrue();
    expect(TransporterWorkLog::count())->toBe(2);
});
