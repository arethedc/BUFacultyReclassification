<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReclassificationPromotedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $applicationId,
        public string $fromRank,
        public string $toRank,
        public string $cycleYear = '',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Congratulations! You have been promoted.',
            'message' => "You have been promoted from {$this->fromRank} to {$this->toRank}.",
            'application_id' => $this->applicationId,
            'from_rank' => $this->fromRank,
            'to_rank' => $this->toRank,
            'cycle_year' => $this->cycleYear,
        ];
    }
}

