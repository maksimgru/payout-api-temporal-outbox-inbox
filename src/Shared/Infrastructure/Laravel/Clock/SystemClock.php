<?php

namespace Shared\Infrastructure\Laravel\Clock;

use DateTimeImmutable;
use DateTimeZone;
use Shared\Application\Clock\Clock;

final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
