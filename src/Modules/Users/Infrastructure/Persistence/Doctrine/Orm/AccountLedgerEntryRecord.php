<?php

namespace Modules\Users\Infrastructure\Persistence\Doctrine\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'account_ledger_entries')]
class AccountLedgerEntryRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    public int $userId;

    #[ORM\Column(type: 'string', length: 3)]
    public string $currency;

    #[ORM\Column(name: 'amount_minor', type: 'integer')]
    public int $amountMinor;

    #[ORM\Column(type: 'string', length: 16)]
    public string $direction;

    #[ORM\Column(type: 'string', length: 64)]
    public string $reason;

    #[ORM\Column(type: 'string', length: 128)]
    public string $reference;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt;
}
