<?php

namespace Deoris\Integration\Events;

final readonly class TuitionPaid extends BaseEvent
{
    public function name(): string
    {
        return 'TuitionPaid';
    }
}
