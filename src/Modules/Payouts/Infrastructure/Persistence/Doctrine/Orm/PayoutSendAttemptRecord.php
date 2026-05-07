<?php

namespace Modules\Payouts\Infrastructure\Persistence\Doctrine\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'payout_send_attempts')]
class PayoutSendAttemptRecord
{
    public const string RESULT_ACCEPTED = 'accepted';
    public const string RESULT_TEMPORARY_ERROR = 'temporary_error';
    public const string RESULT_PERMANENT_ERROR = 'permanent_error';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(name: 'payout_id', type: 'integer')]
    public int $payoutId;

    #[ORM\Column(name: 'attempt_number', type: 'smallint')]
    public int $attemptNumber;

    #[ORM\Column(name: 'http_status', type: 'integer', nullable: true)]
    public ?int $httpStatus = null;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    public ?string $result = null;

    #[ORM\Column(name: 'error_type', type: 'string', length: 64, nullable: true)]
    public ?string $errorType = null;

    #[ORM\Column(name: 'error_message', type: 'text', nullable: true)]
    public ?string $errorMessage = null;

    #[ORM\Column(name: 'started_at', type: 'datetime_immutable')]
    public DateTimeImmutable $startedAt;

    #[ORM\Column(name: 'finished_at', type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $finishedAt = null;
}
