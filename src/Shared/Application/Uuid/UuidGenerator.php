<?php

namespace Shared\Application\Uuid;

interface UuidGenerator
{
    public function uuid4(): string;
}
