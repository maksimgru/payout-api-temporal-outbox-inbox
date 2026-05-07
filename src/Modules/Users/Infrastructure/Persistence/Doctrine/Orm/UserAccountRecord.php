<?php

namespace Modules\Users\Infrastructure\Persistence\Doctrine\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_accounts')]
#[ORM\UniqueConstraint(name: 'uniq_user_currency', columns: ['user_id', 'currency'])]
class UserAccountRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    public int $userId;

    #[ORM\Column(type: 'string', length: 3)]
    public string $currency;

    #[ORM\Column(name: 'balance_minor', type: 'integer')]
    public int $balanceMinor;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    public DateTimeImmutable $updatedAt;
}
