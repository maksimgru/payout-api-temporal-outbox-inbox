<?php

namespace Shared\Infrastructure\Laravel\Uuid;

use Illuminate\Support\Str;
use Shared\Application\Uuid\UuidGenerator;

final class LaravelUuidGenerator implements UuidGenerator
{
    public function uuid4(): string
    {
        return (string) Str::uuid();
    }
}
