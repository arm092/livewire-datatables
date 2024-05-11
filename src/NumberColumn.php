<?php

namespace Arm092\LivewireDatatables;

class NumberColumn extends Column
{
    public string $type = 'number';
    public string $headerAlign = 'right';
    public string $contentAlign = 'right';
    public int $roundPrecision = 0;

    public function round($precision = 0): static
    {
        $this->roundPrecision = $precision;

        $this->callbackFunction = function ($value) {
            return round($value, $this->roundPrecision);
        };

        return $this;
    }

    public function format(int $decimals = 0, ?string $decimalSeparator = '.', ?string $thousandsSeparator = ','): static
    {
        $this->callbackFunction = static fn($value) => number_format($value, $decimals, $decimalSeparator, $thousandsSeparator);

        return $this;
    }
}
