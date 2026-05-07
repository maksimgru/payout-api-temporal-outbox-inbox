<?php

namespace Shared\Domain\ValueObject;

use Shared\Domain\Exception\InvalidArgument;

final readonly class Money
{
    private function __construct(
        public int $amountMinor,
        public Currency $currency,
    ) {
        if ($amountMinor <= 0) {
            throw new InvalidArgument('Money amount must be positive.');
        }
    }

    public static function fromMinor(int $amountMinor, Currency $currency): self
    {
        return new self($amountMinor, $currency);
    }

    public static function fromDecimalString(string $amount, Currency $currency): self
    {
        $amount = trim($amount);
        $scale = $currency->minorUnits();
        $pattern = $scale === 0
            ? '/^\d+$/'
            : '/^\d+(\.\d{1,'.$scale.'})?$/';

        if (! preg_match($pattern, $amount)) {
            throw new InvalidArgument('Invalid money amount format for '.$currency->code.'.');
        }

        [$major, $minor] = array_pad(explode('.', $amount, 2), 2, '');
        $minor = str_pad($minor, $scale, '0');
        $majorMinor = (int) $major * (10 ** $scale);
        $amountMinor = $majorMinor + (int) $minor;

        return new self($amountMinor, $currency);
    }

    public function toDecimalString(): string
    {
        $scale = $this->currency->minorUnits();

        if ($scale === 0) {
            return (string) $this->amountMinor;
        }

        $divisor = 10 ** $scale;
        $major = intdiv($this->amountMinor, $divisor);
        $minor = $this->amountMinor % $divisor;

        return $major.'.'.str_pad((string) $minor, $scale, '0', STR_PAD_LEFT);
    }
}
