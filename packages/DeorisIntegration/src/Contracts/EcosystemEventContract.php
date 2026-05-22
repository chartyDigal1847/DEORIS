<?php

namespace Deoris\Integration\Contracts;

use Deoris\Integration\DTO\EcosystemEvent;

interface EcosystemEventContract
{
    public function name(): string;

    public function toEcosystemEvent(): EcosystemEvent;
}
