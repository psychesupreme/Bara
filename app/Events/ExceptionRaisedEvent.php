<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ExceptionRaisedEvent implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $exceptionId,
        public string $code,
        public string $exceptionType,
        public string $repName,
        public string $customerName,
        public string $reason,
        public string $severity,
        public string $timestamp
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('exception-stream'),
            new Channel('dispatch-channel'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->exceptionId,
            'code' => $this->code,
            'exception_type' => $this->exceptionType,
            'rep_name' => $this->repName,
            'customer_name' => $this->customerName,
            'reason' => $this->reason,
            'severity' => $this->severity,
            'timestamp' => $this->timestamp,
        ];
    }
}
