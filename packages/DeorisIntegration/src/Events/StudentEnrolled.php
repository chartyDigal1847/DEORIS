<?php

namespace Deoris\Integration\Events;

final readonly class StudentEnrolled extends BaseEvent
{
    public function name(): string
    {
        return 'StudentEnrolled';
    }
}
