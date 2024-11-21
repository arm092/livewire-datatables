<?php

namespace Arm092\LivewireDatatables;

use Arm092\LivewireDatatables\Livewire\LivewireDatatable;
use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Column implements \ArrayAccess
{
    public string $type = 'string';
    public int $index = 0;
    public string $label = '';
    public array $tooltip;
    public string $name;
    public string|array|Expression|null $select = null;
    public array $joins;
    public ?string $base = null;
    public string $raw = '';
    public bool $searchable = false;
    public bool $sortable = false;
    public string|array|null $filterOn = null;
    public bool|array $filterable = false;
    public string|bool|null $hideable = null;
    public ?string $sort = null;
    public bool|string $defaultSort = false;
    public string|array|Closure|null $callbackFunction = null;
    public bool $hidden = false;
    public ?string $scope = null;
    public ?string $scopeFilter = null;
    public array $params = [];
    public array $additionalSelects = [];
    public string $filterView;
    public string $headerAlign = 'left';
    public string $contentAlign = 'left';
    public bool $preventExport = false;
    public string $width;
    public string $minWidth;
    public string $maxWidth;
    public ?Closure $exportCallback = null;
    public string $aggregate;

    /**
     * @var bool should the sum of all summarizable cells in this column be
     *           displayed as a summary at the bottom of the table?
     */
    public bool $summary = false;

    /**
     * @var bool allow the content of the column to wrap into multiple lines.
     */
    public bool $wrappable = true;

    /**
     * @var string (optional) you can group your columns to let the user toggle the visibility of a group at once.
     */
    public string $group;

    /** @var array list all column types that are not sortable by SQL here */
    public const UNSORTABLE_TYPES = ['label', 'checkbox'];

    public function __construct()
    {
        $this->sortable = config('livewire-datatables.default_sortable', true);
    }

    public static function name($name): static
    {
        $column = new static;
        $column->name = $name;
        $column->aggregate = Str::contains($name, ':') ? Str::after($name, ':') : $column->aggregate();
        $column->label = (string)Str::of($name)->after('.')->ucfirst()->replace('_', ' ');

        if (Str::contains(Str::lower($name), ' as ')) {
            $column->name = array_reverse(preg_split('/ as /i', $name))[0];
            $column->label = array_reverse(preg_split('/ as /i', $name))[1];
            $column->base = preg_split('/ as /i', $name)[0];
        }

        return $column;
    }

    public static function index(LivewireDatatable $datatable, $attribute = 'id'): static
    {
        $column = new static;
        $column->name = $attribute;
        $column->label = '#';
        $column->callbackFunction = static function () use ($datatable) {
            return $datatable->getPage() * $datatable->perPage - $datatable->perPage + $datatable->row++;
        };

        return $column;
    }

    public static function raw($raw): static
    {
        $column = new static;
        $column->raw = $raw;
        $column->name = Str::after($raw, ' AS ');
        $column->select = DB::raw(Str::before($raw, ' AS '));
        $column->label = (string)Str::of($raw)->afterLast(' AS ')->replace('`', '');
        $column->sort = (string)Str::of($raw)->beforeLast(' AS ');

        return $column;
    }

    /**
     * Make a callback function.
     *
     * @param array|string $columns The (comma separated) columns that should be retrieved from the database.
     *                                      Is being translated directly into the `.sql`.
     * @param Closure|string $callback A callback that defines how the retrieved columns are processed.
     * @param array $params Optional additional parameters that are passed to the given Closure.
     * @param string|null $callbackName string          Optional string that defines the 'name' of the column.
     *                                      Leave empty to let livewire autogenerate a distinct value.
     * @return Column
     */
    public static function callback(
        array|string   $columns,
        Closure|string $callback,
        array          $params = [],
        ?string        $callbackName = null
    ): static
    {
        $column = new static;

        $column->name = 'callback_' . ($callbackName ?? (string)crc32(json_encode(func_get_args())));
        $column->callbackFunction = $callback;
        $column->additionalSelects = is_array($columns) ? $columns : array_map('trim', explode(',', $columns));
        $column->params = $params;

        return $column;
    }

    public static function checkbox($attribute = 'id'): static
    {
        return static::name($attribute . ' as checkbox_attribute')
            ->setType('checkbox')
            ->excludeFromExport();
    }

    public static function scope(string $scope, $alias): static
    {
        $column = new static;
        $column->scope = $scope;
        $column->name = $alias;
        $column->label = $alias;
        $column->sortBy("`$alias`");

        return $column;
    }

    public function setScope(string $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public static function delete($name = 'id'): static
    {
        return static::callback($name, static function ($value) {
            return view('datatables::delete', ['value' => $value]);
        });
    }

    public function setCallback(Closure|string $callback, array $params = []): static
    {
        $this->callbackFunction = $callback;
        $this->params = $params;

        return $this;
    }

    public function label($label): static
    {
        $this->label = $label;

        return $this;
    }

    public function wrap(): static
    {
        $this->wrappable = true;

        return $this;
    }

    public function unwrap(): static
    {
        $this->wrappable = false;

        return $this;
    }

    public function enableSummary(): static
    {
        $this->summary = true;

        return $this;
    }

    public function disableSummary(): static
    {
        $this->summary = false;

        return $this;
    }

    public function setIndex($index): static
    {
        $this->index = $index;

        return $this;
    }

    public function tooltip($text, $label = null): static
    {
        $this->tooltip = [
            'text' => $text,
            'label' => $label,
        ];

        return $this;
    }

    public function sortBy($column): static
    {
        $this->sort = $column;

        return $this;
    }

    public function defaultSort($direction = true): static
    {
        $this->defaultSort = $direction;

        return $this;
    }

    public function hideable($hideable = true): static
    {
        $this->hideable = $hideable;

        return $this;
    }

    public function sortable(): static
    {
        $this->sortable = true;

        return $this;
    }

    public function unsortable(): static
    {
        $this->sortable = false;

        return $this;
    }

    public function searchable(): static
    {
        $this->searchable = true;

        return $this;
    }

    public function filterable(array|Collection $options = [], ?string $scopeFilter = null): static
    {
        $options = $options instanceof Collection ? $options->toArray() : $options;
        $this->filterable = $options ?: true;
        $this->scopeFilter = $scopeFilter;

        return $this;
    }

    public function filterOn($query): static
    {
        $this->filterOn = $query;

        return $this;
    }

    public function booleanFilterable(): static
    {
        $this->filterable = true;
        $this->filterView = 'boolean';

        return $this;
    }

    public function exportCallback($exportCallback): static
    {
        $this->exportCallback = $exportCallback;

        return $this;
    }

    public function excludeFromExport(): static
    {
        $this->preventExport = true;

        return $this;
    }

    public function linkTo($model, $pad = null): static
    {
        $this->callbackFunction = static function ($value) use ($model, $pad) {
            return view('datatables::link', [
                'href' => url("/$model/$value"),
                'slot' => $pad ? str_pad($value, $pad, '0', STR_PAD_LEFT) : $value,
            ]);
        };

        $this->exportCallback = static function ($value) {
            return $value;
        };

        return $this;
    }

    public function link($href, $slot = null): static
    {
        $this->callbackFunction = static function ($caption, $row) use ($href, $slot) {
            $substitutes = ['{{caption}}' => $caption];

            foreach ($row as $attribute => $value) {
                $substitutes["{{{$attribute}}}"] = $value;
            }

            if (!$slot) {
                $slot = $caption;
            }

            return view('datatables::link', [
                'href' => strtr($href, $substitutes),
                'slot' => strtr($slot, $substitutes),
            ]);
        };

        $this->exportCallback = static function ($value) {
            return $value;
        };

        return $this;
    }

    public function truncate($length = 16): static
    {
        $this->callbackFunction = static function ($value) use ($length) {
            return view('datatables::tooltip', ['slot' => $value, 'length' => $length]);
        };

        $this->exportCallback = static function ($value) {
            return $value;
        };

        return $this;
    }

    public function round($precision = 0): static
    {
        $this->callbackFunction = static function ($value) use ($precision) {
            return $value ? round($value, $precision) : null;
        };

        return $this;
    }

    public function view($view, $data = []): static
    {
        $this->callbackFunction = static function ($value, $row) use ($view, $data) {
            return view($view, ['value' => $value, 'row' => $row, ...$data]);
        };

        $this->exportCallback = static function ($value) {
            return $value;
        };

        return $this;
    }

    public function filterView($view): static
    {
        $this->filterView = $view;

        return $this;
    }

    public function additionalSelects($selects): static
    {
        $this->additionalSelects = is_array($selects) ? $selects : array_map('trim', explode(',', $selects));

        return $this;
    }

    public function editable($editable = true): static
    {
        $this->exportCallback = static function ($value) {
            return $value;
        };

        return $editable ? $this->setType('editable') : $this;
    }

    public function isEditable(): bool
    {
        return $this->type === 'editable';
    }

    public function hide(): static
    {
        $this->hidden = true;

        return $this;
    }

    public function alignRight(): static
    {
        $this->headerAlign = 'right';
        $this->contentAlign = 'right';

        return $this;
    }

    public function alignCenter(): static
    {
        $this->headerAlign = 'center';
        $this->contentAlign = 'center';

        return $this;
    }

    public function headerAlignRight(): static
    {
        $this->headerAlign = 'right';

        return $this;
    }

    public function contentAlignRight(): static
    {
        $this->contentAlign = 'right';

        return $this;
    }

    public function headerAlignCenter(): static
    {
        $this->headerAlign = 'center';

        return $this;
    }

    public function contentAlignCenter(): static
    {
        $this->contentAlign = 'center';

        return $this;
    }

    public function toggleHidden(): void
    {
        $this->hidden = !$this->hidden;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function aggregate(): string
    {
        return $this->type === 'string'
            ? 'group_concat'
            : 'count';
    }

    public function isBaseColumn(): bool
    {
        return !Str::startsWith($this->name, 'callback_') && !Str::contains($this->name, '.') && !$this->raw;
    }

    public function field(): string
    {
        return Str::afterLast($this->name, '.');
    }

    /**
     * You can use group(null) to revoke a column from a group, if necessary.
     *
     * @see array LivewireDatatable->groupLabels to assign a human readable and translatable label for the group
     */
    public function group($group): static
    {
        $this->group = $group;

        return $this;
    }

    public function relations(): ?Collection
    {
        return $this->isBaseColumn() ? null : collect(explode('.', Str::beforeLast($this->name, '.')));
    }

    public function isType($type): bool
    {
        return $type === $this->type;
    }

    public function setType($type): static
    {
        $this->type = $type;

        return $this;
    }

    public function addParams($params): static
    {
        $this->params = $params;

        return $this;
    }

    public function width($width): static
    {
        if (preg_match('/^\\d*\\.?\\d+$/i', $width) === 1) {
            $width .= 'px';
        }

        if (preg_match('/^(\\d*\\.?\\d+)\\s?(cm|mm|in|px|pt|pc|em|ex|ch|rem|vw|vmin|vmax|%+)$/i', $width) === 0) {
            return $this;
        }

        $this->width = $width;

        return $this;
    }

    public function minWidth($minWidth): static
    {
        if (preg_match('/^\\d*\\.?\\d+$/i', $minWidth) === 1) {
            $minWidth .= 'px';
        }

        if (preg_match('/^(\\d*\\.?\\d+)\\s?(cm|mm|in|px|pt|pc|em|ex|ch|rem|vw|vmin|vmax|%+)$/i', $minWidth) === 0) {
            return $this;
        }

        $this->minWidth = $minWidth;

        return $this;
    }

    public function maxWidth($maxWidth): static
    {
        if (preg_match('/^\\d*\\.?\\d+$/i', $maxWidth) === 1) {
            $maxWidth .= 'px';
        }

        if (preg_match('/^(\\d*\\.?\\d+)\\s?(cm|mm|in|px|pt|pc|em|ex|ch|rem|vw|vmin|vmax|%+)$/i', $maxWidth) === 0) {
            return $this;
        }

        $this->maxWidth = $maxWidth;

        return $this;
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset) && !is_null($this->{$offset});
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->{$offset} = null;
    }
}
