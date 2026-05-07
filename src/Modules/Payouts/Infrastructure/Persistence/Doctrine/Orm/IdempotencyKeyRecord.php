<?php

namespace Modules\Payouts\Infrastructure\Persistence\Doctrine\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'idempotency_keys')]
class IdempotencyKeyRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(name: 'idempotency_key', type: 'string', length: 255, unique: true)]
    public string $key;

    #[ORM\Column(name: 'request_hash', type: 'string', length: 64)]
    public string $requestHash;

    #[ORM\Column(name: 'payout_id', type: 'integer')]
    public int $payoutId;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'response_payload', type: 'json')]
    public array $responsePayload = [];

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt;
}
