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

    public function format(int $places = 0): static
    {
        $this->callbackFunction = static function ($value) use ($places) {
            return number_format($value, $places, '.', ',');
        };

        return $this;
    }
}
