<?php

namespace Modules\Users\Domain\Entity;

use DateTimeImmutable;

final class User
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $email,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
