<?php

namespace Arm092\LivewireDatatables;

use BadMethodCallException;
use Closure;
use Illuminate\Support\Collection;

class Action
{
    public string $value;
    public string $label;
    public string $group = '';
    public string $fileName;
    public bool $isExport = false;
    public array $styles = [];
    public array $widths = [];
    public Closure $callable;

    public function __call($method, $args)
    {
        if (is_callable([$this, $method])) {
            return call_user_func_array($this->$method, $args);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }

    public static function value($value): static
    {
        $action = new static;
        $action->value = $value;

        return $action;
    }

    public function label($label): static
    {
        $this->label = $label;

        return $this;
    }

    public function group($group): static
    {
        $this->group = $group;

        return $this;
    }

    public static function groupBy($group, $actions): ?Collection
    {
        if ($actions instanceof Closure) {
            return collect($actions())->each(function ($item) use ($group) {
                $item->group = $group;
            });
        }

        return null;
    }

    public function export($fileName): static
    {
        $this->fileName = $fileName;
        $this->isExport();

        return $this;
    }

    public function isExport($isExport = true): static
    {
        $this->isExport = $isExport;

        return $this;
    }

    public function styles($styles): static
    {
        $this->styles = $styles;

        return $this;
    }

    public function widths($widths): static
    {
        $this->widths = $widths;

        return $this;
    }

    public function callback($callable): static
    {
        $this->callable = $callable;

        return $this;
    }
}
