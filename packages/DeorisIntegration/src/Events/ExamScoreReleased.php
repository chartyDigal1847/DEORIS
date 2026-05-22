<?php

namespace Deoris\Integration\Events;

final readonly class ExamScoreReleased extends BaseEvent
{
    public function name(): string
    {
        return 'ExamScoreReleased';
    }
}
