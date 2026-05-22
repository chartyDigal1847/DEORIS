<?php

namespace Deoris\Integration\Events;

final readonly class GradeReleased extends BaseEvent
{
    public function name(): string
    {
        return 'GradeReleased';
    }
}
