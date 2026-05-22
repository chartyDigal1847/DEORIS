<?php

namespace Deoris\Integration\Events;

final readonly class AdmissionApproved extends BaseEvent
{
    public function name(): string
    {
        return 'AdmissionApproved';
    }
}
