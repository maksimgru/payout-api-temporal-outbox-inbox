<?php

namespace Modules\Payouts\Application\Policy;

use DateTimeImmutable;

final class ProviderRetryPolicy
{
    /** @return list<int> */
    public function backoffSchedule(): array
    {
        return [10, 30, 60, 120, 300, 600, 900, 1800];
    }

    public function nextRetryAt(int $attemptNumber, DateTimeImmutable $now): DateTimeImmutable
    {
        $schedule = $this->backoffSchedule();
        $seconds = $schedule[max(0, min($attemptNumber - 1, count($schedule) - 1))];

        return $now->modify('+'.$seconds.' seconds');
    }
}
