<?php

namespace Deoris\Integration\Events;

final readonly class ClearanceUpdated extends BaseEvent
{
    public function name(): string
    {
        return 'ClearanceUpdated';
    }
}
