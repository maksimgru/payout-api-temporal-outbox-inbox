<?php

namespace Shared\Application\Clock;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
