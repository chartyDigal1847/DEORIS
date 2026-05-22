<?php

namespace Deoris\Integration\Events;

final readonly class ExamCompleted extends BaseEvent
{
    public function name(): string
    {
        return 'ExamCompleted';
    }
}
