<?php

declare(strict_types=1);

namespace Conia\Value;

use \NumberFormatter;
use Conia\Locale;


class Decimal extends Value
{
    public readonly ?float $value;

    public function __construct(
        array $data,
        Locale $locale
    ) {
        parent::__construct($data, $locale);

        if (is_numeric($data['value'] ?? null)) {
            $this->value = floatval($data['value']);
        } else {
            $this->value = null;
        }
    }

    protected function getFormatter(int $style, int $digits, ?string $locale = null): NumberFormatter
    {
        $formatter = new NumberFormatter($locale ?: $this->locale->id, $style);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $digits);

        return $formatter;
    }

    public function __toString(): string
    {
        if ($this->value === null) {
            return '';
        }

        return $this->value;
    }

    public function localize(?int $digits = 2, ?string $locale = null): string
    {
        if ($this->value) {
            $formatter = $this->getFormatter(NumberFormatter::DECIMAL, $digits, $locale);

            return $formatter->format($this->value);
        }

        return '';
    }

    public function currency(string $currency, ?int $digits = 2, ?string $locale = null): string
    {
        if ($this->value) {
            $formatter = $this->getFormatter(NumberFormatter::CURRENCY, $digits, $locale);

            return $formatter->formatCurrency($this->value, $currency);
        }

        return '';
    }

    public function json(): mixed
    {
        return $this->value;
    }
}