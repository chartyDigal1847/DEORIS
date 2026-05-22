<?php

namespace Deoris\Integration\Events;

final readonly class ExamAssigned extends BaseEvent
{
    public function name(): string
    {
        return 'ExamAssigned';
    }
}
