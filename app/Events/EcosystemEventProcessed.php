<?php

namespace App\Events;

use App\Models\EventLog;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EcosystemEventProcessed implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public EventLog $eventLog)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('event-monitoring')];
    }

    public function broadcastAs(): string
    {
        return 'event.processed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->eventLog->id,
            'event_id' => $this->eventLog->event_id,
            'event_name' => $this->eventLog->event_name,
            'source_module' => $this->eventLog->source_module,
            'status' => $this->eventLog->status,
            'error' => $this->eventLog->error,
            'processed_at' => $this->eventLog->processed_at?->toAtomString(),
        ];
    }
}
