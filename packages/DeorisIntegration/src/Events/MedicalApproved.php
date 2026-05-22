<?php

namespace Deoris\Integration\Events;

final readonly class MedicalApproved extends BaseEvent
{
    public function name(): string
    {
        return 'MedicalApproved';
    }
}
