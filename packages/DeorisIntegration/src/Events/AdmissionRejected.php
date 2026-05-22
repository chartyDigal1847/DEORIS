<?php

namespace Deoris\Integration\Events;

final readonly class AdmissionRejected extends BaseEvent
{
    public function name(): string
    {
        return 'AdmissionRejected';
    }
}
