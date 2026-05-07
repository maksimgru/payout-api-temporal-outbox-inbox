<?php

namespace Shared\Domain\ValueObject;

use Shared\Domain\Exception\InvalidArgument;

final readonly class Currency
{
    private const array MINOR_UNITS = [
        'USD' => 2,
        'EUR' => 2,
    ];

    private function __construct(public string $code)
    {
    }

    public static function fromString(string $value): self
    {
        $code = strtoupper(trim($value));

        if (! array_key_exists($code, self::MINOR_UNITS)) {
            throw new InvalidArgument('Only USD and EUR currencies are supported.');
        }

        return new self($code);
    }

    public function minorUnits(): int
    {
        return self::MINOR_UNITS[$this->code];
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }
}
