<?php

namespace App\Events;

use App\Models\Activity;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityAssignedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Activity $activity,
        public string $assignedUserId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('user.' . $this->assignedUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'activity.assigned';
    }
}
