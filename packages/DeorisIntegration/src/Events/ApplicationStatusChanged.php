<?php

namespace Deoris\Integration\Events;

final readonly class ApplicationStatusChanged extends BaseEvent
{
    public function name(): string
    {
        return 'ApplicationStatusChanged';
    }
}
