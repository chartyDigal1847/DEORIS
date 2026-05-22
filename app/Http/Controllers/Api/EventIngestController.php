<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\EcosystemEventReceived;
use App\Services\EventHub\EventLogService;
use App\Services\EventHub\EventValidator;
use Deoris\Integration\DTO\EcosystemEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventIngestController extends Controller
{
    public function store(Request $request, EventValidator $validator, EventLogService $logs): JsonResponse
    {
        $payload = $request->all();
        $payload['source_module'] = (string) $request->header('X-DEORIS-Module');

        $event = EcosystemEvent::fromArray($payload);

        $validator->validate($event);
        $logs->received($event);

        EcosystemEventReceived::dispatch($event->toArray());

        return response()->json([
            'accepted' => true,
            'event_id' => $event->id,
            'correlation_id' => $event->correlationId,
        ], 202);
    }
}
