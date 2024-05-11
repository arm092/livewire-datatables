<?php

namespace Arm092\LivewireDatatables;

use Illuminate\Support\Carbon;

class TimeColumn extends Column
{
    public string $type = 'time';
    public string|\Closure|array|null $callbackFunction;

    public function __construct()
    {
        parent::__construct();
        $this->format();
    }

    public function format($format = null): static
    {
        $this->callbackFunction = static function ($value) use ($format) {
            return $value ? Carbon::parse($value)->format($format ?? config('livewire-datatables.default_time_format')) : null;
        };

        return $this;
    }
}
