<?php

namespace Deoris\Integration\Events;

final readonly class ApplicationSubmitted extends BaseEvent
{
    public function name(): string
    {
        return 'ApplicationSubmitted';
    }
}
