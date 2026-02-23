<?php

namespace App\Services;

use App\Models\TransporterWorkLog;
use App\Models\User;
use Carbon\Carbon;

class TransporterWorkdayService
{
    /**
     * Close a transportista day once, keeping a fixed 08:00 start.
     *
     * @return array{created: bool, log: TransporterWorkLog}
     */
    public function closeForToday(User $transporter, ?Carbon $now = null): array
    {
        $now = $now
            ? $now->copy()->timezone(config('app.timezone'))
            : Carbon::now(config('app.timezone'));

        $workDate = $now->toDateString();
        $startedAt = Carbon::parse("{$workDate} 08:00:00", config('app.timezone'));

        $log = TransporterWorkLog::firstOrCreate(
            [
                'transporter_id' => $transporter->id,
                'work_date' => $workDate,
            ],
            [
                'started_at' => $startedAt,
                'ended_at' => $now,
            ]
        );

        return [
            'created' => $log->wasRecentlyCreated,
            'log' => $log,
        ];
    }
}
