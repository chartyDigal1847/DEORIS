<?php

namespace Deoris\Integration\Events;

final readonly class LibraryPenaltyAdded extends BaseEvent
{
    public function name(): string
    {
        return 'LibraryPenaltyAdded';
    }
}
