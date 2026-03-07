<?php

namespace App\Jobs;

use App\Models\ReclassificationApplication;
use App\Models\ReclassificationPeriod;
use App\Services\ReclassificationNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendApprovedListForwardedToPresidentNotifications implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * @param  int[]  $applicationIds
     */
    public function __construct(
        public array $applicationIds,
        public int $periodId
    ) {
    }

    public function handle(ReclassificationNotificationService $notifications): void
    {
        if (empty($this->applicationIds) || $this->periodId <= 0) {
            return;
        }

        $period = ReclassificationPeriod::find($this->periodId);
        if (!$period) {
            return;
        }

        $apps = ReclassificationApplication::query()
            ->with(['faculty', 'period'])
            ->whereIn('id', $this->applicationIds)
            ->get();

        if ($apps->isEmpty()) {
            return;
        }

        $notifications->notifyApprovedListForwardedToPresident($apps, $period);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Failed queued VPAA approved-list forward notifications.', [
            'period_id' => $this->periodId,
            'application_ids' => $this->applicationIds,
            'error' => $e->getMessage(),
        ]);
    }
}
