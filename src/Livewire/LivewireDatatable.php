<?php

namespace Arm092\LivewireDatatables\Livewire;

use Arm092\LivewireDatatables\Column;
use Arm092\LivewireDatatables\ColumnSet;
use Arm092\LivewireDatatables\Exports\DatatableExport;
use Arm092\LivewireDatatables\Traits\WithCallbacks;
use Arm092\LivewireDatatables\Traits\WithPresetDateFilters;
use Arm092\LivewireDatatables\Traits\WithPresetTimeFilters;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LivewireDatatable extends Component
{
    use WithPagination, WithCallbacks, WithPresetDateFilters, WithPresetTimeFilters;

    public const SEPARATOR = '|**lwdt**|';
    public Model|string|null $model = null;
    public $columns;
    public $search;
    public string|int|null $sortIndex = null;
    public bool $direction = true;
    public array $activeDateFilters = [];
    public array $activeDatetimeFilters = [];
    public array $activeTimeFilters = [];
    public array $activeSelectFilters = [];
    public array $activeBooleanFilters = [];
    public array $activeTextFilters = [];
    public array $activeNumberFilters = [];
    public array $defaultFilters = [];
    public ?bool $hideHeader = false;
    public ?bool $hidePagination = false;
    public ?int $perPage = null;
    public string|array $include = [];
    public string|array $exclude = [];
    public string|array $hide = [];
    public string|array $dates = [];
    public string|array $times = [];
    public string|array $searchable = [];
    public bool $exportable = false;
    public ?string $hideable = null;
    public array $params = [];
    public array $selected = [];
    public ?string $beforeTableSlot = null;
    public ?string $buttonsSlot = null;
    public ?string $afterTableSlot = null;
    public $complex;
    public $complexQuery;
    public $title;
    public $name;
    public array $columnGroups = [];
//    public array $freshColumns = [];
    public $userFilter;
    public bool $persistSearch = true;
    public bool $persistComplexQuery = true;
    public bool $persistHiddenColumns = true;
    public bool $persistSort = true;
    public bool $persistPerPage = true;
    public bool $persistFilters = true;
    public array $visibleSelected = [];
    public int $row = 1;
    public string $tablePrefix = '';
    public $actions;
    public $massActionOption;

    /**
     * @var array List your groups and the corresponding label (or translation) here.
     *            The label can be an i18n placeholder like 'app.my_string' and it will be automatically translated via __().
     *
     * Group labels are optional. If they are omitted, the 'name' of the group will be displayed to the user.
     *
     * @example ['group1' => 'app.toggle_group1', 'group2' => 'app.toggle_group2']
     */
    public array $groupLabels = [];

    protected ?Builder $query = null;

    protected array $operators = [
        '=' => '=',
        '>' => '>',
        '<' => '<',
        '<>' => '<>',
        '>=' => '>=',
        '<=' => '<=',
        'equals' => '=',
        'does not equal' => '<>',
        'contains' => 'LIKE',
        'does not contain' => 'NOT LIKE',
        'begins with' => 'LIKE',
        'ends with' => 'LIKE',
        'is empty' => '=',
        'is not empty' => '<>',
        'includes' => '=',
        'does not include' => '<>',
    ];

    protected array $viewColumns = [
        'index',
        'hidden',
        'label',
        'tooltip',
        'group',
        'summary',
        'content',
        'headerAlign',
        'contentAlign',
        'type',
        'filterable',
        'hideable',
        'sortable',
        'complex',
        'filterView',
        'name',
        'params',
        'wrappable',
        'width',
        'minWidth',
        'maxWidth',
        'preventExport',
    ];

    /**
     * This event allows controlling the options of the datatable from foreign livewire components
     * by using $dispatch.
     *
     * @example $this->dispatch('applyToTable', perPage: 25); // in any other livewire component on the same page
     * @throws Exception
     */
    #[On('applyToTable')]
    public function applyToTable($options): void
    {
        if (isset($options['sort'])) {
            $this->sort($options['sort'], $options['direction'] ?? null);
        }

        if (isset($options['hiddenColumns']) && is_array($options['hiddenColumns'])) {
            // first display all columns,
            $this->resetHiddenColumns();

            // then hide all columns that should be hidden:
            foreach ($options['hiddenColumns'] as $columnToHide) {
                foreach ($this->columns as $key => $column) {
                    if ($column['name'] === $columnToHide) {
                        $this->columns[$key]['hidden'] = true;
                    }
                }
            }
        }

        foreach ([
                     'perPage',
                     'search',
                     'activeSelectFilters',
                     'activeDateFilters',
                     'activeDatetimeFilters',
                     'activeTimeFilters',
                     'activeBooleanFilters',
                     'activeTextFilters',
                     'activeNumberFilters',
                     'hide',
                     'selected',
                     'pinnedRecords',
                 ] as $property) {
            if (isset($options[$property])) {
                $this->$property = $options[$property];
            }
        }

        $this->setSessionStoredFilters();
    }

    /**
     * Call to clear all searches, filters, selections, return to page 1 and set perPage to default.
     */
    #[On('resetTable')]
    public function resetTable(): void
    {
        $this->perPage = config('livewire-datatables.default_per_page', 10);
        $this->sortIndex = $this->defaultSort();
        $this->search = null;
        $this->setPage(1);
        $this->activeSelectFilters = [];
        $this->activeDateFilters = [];
        $this->activeDatetimeFilters = [];
        $this->activeTimeFilters = [];
        $this->activeTextFilters = [];
        $this->activeBooleanFilters = [];
        $this->activeNumberFilters = [];
        $this->hide = null;
        $this->resetHiddenColumns();
        $this->selected = [];
    }

    /**
     * Display all columns, also those that are currently hidden.
     * Should get called when resetting the table.
     */
    public function resetHiddenColumns(): void
    {
        foreach ($this->columns as $key => $column) {
            $this->columns[$key]['hidden'] = false;
        }
    }

    public function updatedSearch(): void
    {
        $this->visibleSelected = ($this->search) ? array_intersect($this->getQuery()->get()->pluck('checkbox_attribute')->toArray(), $this->selected) : $this->selected;
        $this->setPage(1);
    }

    public function mount(
        $model = false,
        $include = [],
        $exclude = [],
        $hide = [],
        $dates = [],
        $times = [],
        $searchable = [],
        $sortIndex = null,
        $hideHeader = null,
        $hidePagination = null,
        $perPage = null,
        $exportable = false,
        $hideable = false,
        $beforeTableSlot = false,
        $buttonsSlot = false,
        $afterTableSlot = false,
        $params = []
    ): void
    {
        foreach ([
                     'model',
                     'include',
                     'exclude',
                     'hide',
                     'dates',
                     'times',
                     'searchable',
                     'sortIndex',
                     'hideHeader',
                     'hidePagination',
                     'exportable',
                     'hideable',
                     'beforeTableSlot',
                     'buttonsSlot',
                     'afterTableSlot',
                 ] as $property) {
            $this->$property = $this->$property ?: $$property;
        }

        $this->params = $params;

        $this->columns = $this->getViewColumns();
        $this->actions = $this->getMassActions();
        $this->initialiseSearch();
        $this->initialiseSort();
        $this->initialiseHiddenColumns();
        $this->initialiseDefaultFilters();
        $this->initialiseFilters();
        $this->initialisePerPage();
        $this->initialiseColumnGroups();
        $this->model = $this->model ?: get_class($this->builder()->getModel());

        if (isset($this->pinnedRecords)) {
            $this->initialisePinnedRecords();
        }
    }

    // save settings
    public function dehydrate(): void
    {
        if ($this->persistSearch) {
            session()->put($this->sessionStorageKey() . '_search', $this->search);
        }
    }

    public function getColumns(): array|Model
    {
        return $this->modelInstance;
    }

    public function getViewColumns(): array
    {
        return collect($this->freshColumns)->map(function ($column) {
            return collect($column)
                ->only($this->viewColumns)
                ->toArray();
        })->toArray();
    }

    public function getComplexColumnsProperty(): Collection
    {
        return collect($this->columns)->filter(function ($column) {
            return $column['filterable'];
        });
    }

    public function getPersistKeyProperty(): ?string
    {
        return $this->persistComplexQuery
            ? Str::kebab(Str::afterLast(get_class($this), '\\'))
            : null;
    }

    public function getModelInstanceProperty()
    {
        return $this->model::firstOrFail();
    }

    public function builder(): Builder
    {
        return $this->model::query();
    }

    public function delete($id): void
    {
        $this->model::destroy($id);
    }

    public function getProcessedColumnsProperty(): ColumnSet
    {
        return ColumnSet::build($this->getColumns())
            ->include($this->include)
            ->exclude($this->exclude)
            ->hide($this->hide)
            ->formatDates($this->dates)
            ->formatTimes($this->times)
            ->search($this->searchable)
            ->sort($this->sortIndex);
    }

    public function resolveAdditionalSelects($column): Expression|string
    {
        $selects = collect($column->additionalSelects)->map(function ($select) use ($column) {
            return Str::contains($select, '.')
                ? $this->resolveColumnName($column, $select)
                : $this->query->getModel()->getTable() . '.' . $select;
        });

        if (DB::connection() instanceof SQLiteConnection) {
            // SQLite dialect.
            return $selects->count() > 1
                ? new Expression('(' .
                    collect($selects)->map(function ($select) {
                        return 'COALESCE(' . $this->tablePrefix . $select . ', \'\')';
                    })->join(" || '" . static::SEPARATOR . "' || ") . ')')
                : $selects->first();
        }

        // Default to MySql dialect.
        return $selects->count() > 1
            ? new Expression("CONCAT_WS('" . static::SEPARATOR . "' ," .
                collect($selects)->map(function ($select) {
                    return 'COALESCE(' . $this->tablePrefix . $select . ', \'\')';
                })->join(', ') . ')')
            : $selects->first();
    }

    public function resolveEditableColumnName($column): array
    {
        return [
            $column->select,
            $this->query->getModel()->getTable() . '.' . $this->query->getModel()->getKeyName() . ' AS ' . $column->name . '_edit_id',
        ];
    }

    public function getSelectStatements($withAlias = false, $export = false)
    {
        return $this->processedColumns->columns
            ->reject(fn($column) => $column->scope || $column->type === 'label' || ($export && $column->preventExport))
            ->map(function ($column) {
                if ($column->select) {
                    return $column;
                }

                if (Str::startsWith($column->name, 'callback_')) {
                    $column->select = $this->resolveAdditionalSelects($column);

                    return $column;
                }

                $column->select = $this->resolveColumnName($column);

                if ($column->isEditable()) {
                    $column->select = $this->resolveEditableColumnName($column);
                }

                return $column;
            })->when($withAlias, function ($columns) {
                return $columns->map(function ($column) {
                    if (!$column->select) {
                        return null;
                    }
                    if ($column->select instanceof Expression) {
                        $sep_string = config('database.default') === 'pgsql' ? '"' : '`';

                        return new Expression($column->select->getValue(DB::connection()->getQueryGrammar()) . ' AS ' . $sep_string . $column->name . $sep_string);
                    }

                    if (is_array($column->select)) {
                        $selects = $column->select;
                        $first = array_shift($selects) . ' AS ' . $column->name;
                        $others = array_map(static function ($select) {
                            return $select . ' AS ' . $select;
                        }, $selects);

                        return array_merge([$first], $others);
                    }

                    return $column->select . ' AS ' . $column->name;
                });
            }, fn($columns) => $columns->map->select);
    }

    protected function resolveColumnName($column, $additional = null)
    {
        if ($column->isBaseColumn()) {
            return $this->query->getModel()->getTable() . '.' . ($column->base ?? Str::before($column->name, ':'));
        }

        $relations = explode('.', Str::before(($additional ?: $column->name), ':'));
//        $aggregate = Str::after(($additional ?: $column->name), ':');

        if (!method_exists($this->query->getModel(), $relations[0])) {
            return $additional ?: $column->name;
        }

        $columnName = array_pop($relations);
        $aggregateName = implode('.', $relations);

        $relatedQuery = $this->query;

        while (count($relations) > 0) {
            $relation = array_shift($relations);

            if ($relatedQuery->getRelation($relation) instanceof HasMany || $relatedQuery->getRelation($relation) instanceof HasManyThrough || $relatedQuery->getRelation($relation) instanceof BelongsToMany) {
                $this->query->customWithAggregate($aggregateName, $column->aggregate ?? 'count', $columnName, $column->name);

                return null;
            }

            $useThrough = collect($this->query->getQuery()->joins)
                ->pluck('table')
                ->contains($relatedQuery->getRelation($relation)->getRelated()->getTable());

            $relatedQuery = $this->query->joinRelation($relation, null, 'left', $useThrough, $relatedQuery);
        }

        return $relatedQuery->getQuery()->from . '.' . $columnName;
    }

    public function getFreshColumnsProperty()
    {
        $columns = $this->processedColumns->columnsArray();

        $duplicates = collect($columns)->reject(function ($column) {
            return in_array($column['type'], Column::UNSORTABLE_TYPES, true);
        })->pluck('name')->duplicates();

        if ($duplicates->count()) {
            throw new RuntimeException('Duplicate Column Name(s): ' . implode(', ', $duplicates->toArray()));
        }

        return $columns;
    }

    public function sessionStorageKey(): string
    {
        return Str::snake(Str::afterLast(static::class, '\\')) . $this->name;
    }

    public function getSessionStoredSort(): void
    {
        if (!$this->persistSort) {
            return;
        }

        $this->sortIndex = session()->get($this->sessionStorageKey() . '_sort', $this->sortIndex);
        $this->direction = session()->get($this->sessionStorageKey() . '_direction', $this->direction);
    }

    public function getSessionStoredPerPage(): void
    {
        if (!$this->persistPerPage) {
            return;
        }

        $this->perPage = session()->get($this->sessionStorageKey() . '_perpage', $this->perPage);
    }

    public function setSessionStoredSort(): void
    {
        if (!$this->persistSort) {
            return;
        }

        session()->put([
            $this->sessionStorageKey() . '_sort' => $this->sortIndex,
            $this->sessionStorageKey() . '_direction' => $this->direction,
        ]);
    }

    public function setSessionStoredFilters(): void
    {
        if (!$this->persistFilters) {
            return;
        }

        session()->put([
            $this->sessionStorageKey() . '_filter' => [
                'text' => $this->activeTextFilters,
                'boolean' => $this->activeBooleanFilters,
                'select' => $this->activeSelectFilters,
                'date' => $this->activeDateFilters,
                'datetime' => $this->activeDatetimeFilters,
                'time' => $this->activeTimeFilters,
                'number' => $this->activeNumberFilters,
                'search' => $this->search,
            ],
        ]);
    }

    public function setSessionStoredHidden(): void
    {
        if (!$this->persistHiddenColumns) {
            return;
        }

        $hidden = collect($this->columns)->filter->hidden->keys()->toArray();

        session()->put([$this->sessionStorageKey() . '_hidden_columns' => $hidden]);
    }

    public function initialiseSearch(): void
    {
        if (!$this->persistSearch) {
            return;
        }

        $this->search = session()->get($this->sessionStorageKey() . '_search', $this->search);
    }

    public function initialiseSort(): void
    {
        $this->sortIndex = $this->defaultSort()
            ? $this->defaultSort()['key']
            : collect($this->freshColumns)->reject(function ($column) {
                return in_array($column['type'], Column::UNSORTABLE_TYPES, true) || $column['hidden'];
            })->keys()->first();

        $this->direction = $this->defaultSort() && $this->defaultSort()['direction'] === 'asc';
        $this->getSessionStoredSort();
    }

    public function initialiseHiddenColumns(): void
    {
        if (!$this->persistHiddenColumns) {
            return;
        }

        if (session()->has($this->sessionStorageKey() . '_hidden_columns')) {
            $this->columns = collect($this->columns)->map(function ($column, $index) {
                $column['hidden'] = in_array($index, session()->get($this->sessionStorageKey() . '_hidden_columns'));

                return $column;
            })->toArray();
        }
    }

    public function initialisePerPage(): void
    {
        $this->getSessionStoredPerPage();

        if (!$this->perPage) {
            $this->perPage = $this->perPage ?? config('livewire-datatables.default_per_page', 10);
        }
    }

    public function initialiseColumnGroups(): void
    {
        array_map(function ($column) {
            if (isset($column['group'])) {
                $this->columnGroups[$column['group']][] = $column['name'] ?? $column['label'];
            }
        }, $this->columns);
    }

    public function initialiseDefaultFilters(): void
    {
        if (empty($this->defaultFilters)) {
            return;
        }

        $columns = collect($this->columns);

        foreach ($this->defaultFilters as $columnName => $value) {
            $columnIndex = $columns->search(function ($column) use ($columnName) {
                return $column['name'] === $columnName;
            });

            if ($columnIndex === false) {
                continue;
            }

            $column = $columns[$columnIndex];

            if ($column['type'] === 'string') {
                $this->activeTextFilters[$columnIndex] = $value;
            }

            if ($column['type'] === 'boolean') {
                $this->activeBooleanFilters[$columnIndex] = $value;
            }

            if ($column['type'] === 'select') {
                $this->activeSelectFilters[$columnIndex] = $value;
            }

            if ($column['type'] === 'date') {
                $this->activeDateFilters[$columnIndex] = $value;
            }

            if ($column['type'] === 'datetime') {
                $this->activeDatetimeFilters[$columnIndex] = $value;
            }

            if ($column['type'] === 'time') {
                $this->activeTimeFilters[$columnIndex] = $value;
            }

            if ($column['type'] === 'number') {
                $this->activeNumberFilters[$columnIndex] = $value;
            }
        }
    }

    public function initialiseFilters(): void
    {
        if (!$this->persistFilters) {
            return;
        }

        $filters = session()->get($this->sessionStorageKey() . '_filter');

        if (!empty($filters['text'])) {
            $this->activeTextFilters = $filters['text'];
        }

        if (!empty($filters['boolean'])) {
            $this->activeBooleanFilters = $filters['boolean'];
        }

        if (!empty($filters['select'])) {
            $this->activeSelectFilters = $filters['select'];
        }

        if (!empty($filters['date'])) {
            $this->activeDateFilters = $filters['date'];
        }

        if (!empty($filters['datetime'])) {
            $this->activeDatetimeFilters = $filters['datetime'];
        }

        if (!empty($filters['time'])) {
            $this->activeTimeFilters = $filters['time'];
        }

        if (!empty($filters['number'])) {
            $this->activeNumberFilters = $filters['number'];
        }

        if (isset($filters['search'])) {
            $this->search = $filters['search'];
        }
    }

    public function defaultSort(): ?array
    {
        $columnIndex = collect($this->freshColumns)->search(function ($column) {
            return is_string($column['defaultSort']);
        });

        return is_numeric($columnIndex) ? [
            'key' => $columnIndex,
            'direction' => $this->freshColumns[$columnIndex]['defaultSort'],
        ] : null;
    }

    public function getSortString($dbtable)
    {
        $column = $this->freshColumns[$this->sortIndex];

        return match (true) {
            $column['sort'] => $column['sort'],
            $column['base'] => $column['base'],
            is_array($column['select']) => Str::before($column['select'][0], ' AS '),
            is_object($column['select']) => Str::before($column['select']->getValue(DB::connection()->getQueryGrammar()), ' AS '),
            $column['select'] => $this->getCorrectSortStringForJson($column),
            default => match ($dbtable) {
                'mysql' => new Expression('`' . $column['name'] . '`'),
                'pgsql' => new Expression('"' . $column['name'] . '"'),
                default => new Expression("'" . $column['name'] . "'"),
            },
        };
    }

    protected function getCorrectSortStringForJson($column): string
    {
        // check if the select string contains ->
        if (!Str::contains($column['select'], '->')) {
            return Str::before($column['select'], ' AS ');
        }

        // Extract the table and JSON field correctly
        $tableAndField = $this->extractTableAndField($column['select']);

        if ($tableAndField) {
            [$table, $field, $jsonField] = $tableAndField;
            return "json_unquote(json_extract(`$table`.`$field`, '$.\"$jsonField\"'))";
        }

        $table = $column['select'];
        $field = $column['name'];

        return "json_unquote(json_extract(`$table`.`$field`, '$.\"{$column['name']}\"'))";
    }

    public function extractTableAndField($inputString): ?array
    {
        $pattern = '/^([^.]+)\.([^.]+)->(.+)$/';
        if (preg_match($pattern, $inputString, $matches)) {
            [$noNeed, $table, $field, $jsonField] = $matches;

            return [$table, $field, $jsonField];
        }

        return null;
    }

    /**
     * Check has the user defined at least one column to display a summary row?
     */
    public function hasSummaryRow(): bool
    {
        foreach ($this->columns as $column) {
            if ($column['summary']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attempt so summarize each data cell of the given column.
     * In case we have a string or any other value that is not summarizable,
     * we return an empty string.
     */
    public function summarize($column): ?string
    {
        try {
            return $this->results->sum($column);
        } catch (\TypeError $e) {
            return '';
        }
    }

    public function updatingPerPage(): void
    {
        $this->refreshLivewireDatatable();
    }

    #[On('refreshLivewireDatatable')]
    public function refreshLivewireDatatable(): void
    {
        $this->setPage(1);
    }

    /**
     * Order the table by a given column index starting from 0.
     *
     * @param mixed $index which column to sort by
     * @param string|null $direction needs to be 'asc' or 'desc'. set to null to toggle the current direction.
     * @return void
     * @throws Exception
     */
    public function sort(int|string $index, string $direction = null): void
    {
        if (!in_array($direction, [null, 'asc', 'desc'], true)) {
            throw new RuntimeException("Invalid direction $direction given in sort() method. Allowed values: asc, desc.");
        }

        if ($this->sortIndex === (int)$index) {
            if ($direction === null) { // toggle direction
                $this->direction = !$this->direction;
            } else {
                $this->direction = $direction === 'asc';
            }
        } else {
            $this->sortIndex = (int)$index;
        }
        if ($direction !== null) {
            $this->direction = $direction === 'asc';
        }
        $this->setPage(1);

        session()->put([
            $this->sessionStorageKey() . '_sort' => $this->sortIndex,
            $this->sessionStorageKey() . '_direction' => $this->direction,
        ]);
    }

    public function toggle($index): void
    {
        if ($this->sortIndex == $index) {
            $this->initialiseSort();
        }

        if (!$this->columns[$index]['hidden']) {
            unset($this->activeSelectFilters[$index]);
        }

        $this->columns[$index]['hidden'] = !$this->columns[$index]['hidden'];

        $this->setSessionStoredHidden();
    }

    #[On('toggleGroup')]
    public function toggleGroup($group): void
    {
        if ($this->isGroupVisible($group)) {
            $this->hideGroup($group);
        } else {
            $this->showGroup($group);
        }
    }

    public function showGroup($group): void
    {
        foreach ($this->columns as $key => $column) {
            if ($column['group'] === $group) {
                $this->columns[$key]['hidden'] = false;
            }
        }

        $this->setSessionStoredHidden();
    }

    public function hideGroup($group): void
    {
        foreach ($this->columns as $key => $column) {
            if ($column['group'] === $group) {
                $this->columns[$key]['hidden'] = true;
            }
        }

        $this->setSessionStoredHidden();
    }

    /**
     * @return bool returns true if all columns of the given group are _completely_ visible.
     */
    public function isGroupVisible($group): bool
    {
        foreach ($this->columns as $column) {
            if ($column['group'] === $group && $column['hidden']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if all columns of the given group are _completely_ hidden.
     */
    public function isGroupHidden($group): bool
    {
        foreach ($this->columns as $column) {
            if ($column['group'] === $group && !$column['hidden']) {
                return false;
            }
        }

        return true;
    }

    public function doBooleanFilter($index, $value): void
    {
        $this->activeBooleanFilters[$index] = $value;
        $this->setVisibleSelected();
        $this->setPage(1);
        $this->setSessionStoredFilters();
    }

    public function doSelectFilter($index, $value): void
    {
        $this->activeSelectFilters[$index][] = $value;
        $this->setVisibleSelected();
        $this->setPage(1);
        $this->setSessionStoredFilters();
    }

    #[On('doTextFilter')]
    public function doTextFilter($index, $value): void
    {
        foreach (explode(' ', $value) as $val) {
            $this->activeTextFilters[$index][] = $val;
        }
        $this->setVisibleSelected();
        $this->setPage(1);
        $this->setSessionStoredFilters();
    }

    public function doDateFilterStart($index, $start): void
    {
        $this->activeDateFilters[$index]['start'] = $start;
        $this->setVisibleSelected();
        $this->setPage(1);
        $this->setSessionStoredFilters();
    }

    public function doDateFilterEnd($index, $end): void
    {
        $this->activeDateFilters[$index]['end'] = $end;
        $this->setVisibleSelected();
        $this->setPage(1);
        $this->setSessionStoredFilters();
    }

    public function doDatetimeFilterStart($index, $start): void
    {
        $this->activeDatetimeFilters[$index]['start'] = $start;
        $this->setVisibleSelected();
        $this->setPage(1);
        $this->setSessionStoredFilters();
    }

    public function doDatetimeFilterEnd($index, $end): void
    {
        $this->activeDatetimeFilters[$index]['end'] = $end;
        $this->setVisibleSelected();
        $this->setPage(1);
        $this->setSessionStoredFilters();
    }

    public function doTimeFilterStart($index, $start): void
    {
        $this->activeTimeFilters[$index]['start'] = $start;
        $this->setVisibleSelected();
        $this->setPage(1);
        $this->setSessionStoredFilters();
    }

    public function doTimeFilterEnd($index, $end): void
    {
        $this->activeTimeFilters[$index]['end'] = $end;
        $this->setVisibleSelected();
        $this->setPage(1);
        $this->setSessionStoredFilters();
    }

    public function doNumberFilterStart($index, $start): void
    {
        $this->activeNumberFilters[$index]['start'] = ($start != '') ? (int)$start : null;
        $this->clearEmptyNumberFilter($index);
        $this->setVisibleSelected();
        $this->setPage(1);
        $this->setSessionStoredFilters();
    }

    public function doNumberFilterEnd($index, $end): void
    {
        $this->activeNumberFilters[$index]['end'] = ($end != '') ? (int)$end : null;
        $this->clearEmptyNumberFilter($index);
        $this->setVisibleSelected();
        $this->setPage(1);
        $this->setSessionStoredFilters();
    }

    public function clearEmptyNumberFilter($index): void
    {
        if ((!isset($this->activeNumberFilters[$index]['start']) || $this->activeNumberFilters[$index]['start'] == '') && (!isset($this->activeNumberFilters[$index]['end']) || $this->activeNumberFilters[$index]['end'] == '')) {
            $this->removeNumberFilter($index);
        }
        $this->setPage(1);
        $this->setSessionStoredFilters();
    }

    public function removeSelectFilter($column, $key = null): void
    {
        unset($this->activeSelectFilters[$column][$key]);
        $this->visibleSelected = $this->selected;
        if (isset($this->activeSelectFilters[$column]) && count($this->activeSelectFilters[$column]) < 1) {
            unset($this->activeSelectFilters[$column]);
        }
        $this->setPage(1);
        $this->setSessionStoredFilters();
    }

    public function clearAllFilters(): void
    {
        $this->activeDateFilters = [];
        $this->activeDatetimeFilters = [];
        $this->activeTimeFilters = [];
        $this->activeSelectFilters = [];
        $this->activeBooleanFilters = [];
        $this->activeTextFilters = [];
        $this->activeNumberFilters = [];
        $this->complexQuery = null;
        $this->userFilter = null;
        $this->visibleSelected = $this->selected;
        $this->setPage(1);
        $this->setSessionStoredFilters();

        $this->dispatch('reset-query')->to(ComplexQuery::class);
    }

    public function removeBooleanFilter($column): void
    {
        unset($this->activeBooleanFilters[$column]);
        $this->visibleSelected = $this->selected;
        $this->setSessionStoredFilters();
    }

    public function removeTextFilter($column, $key = null): void
    {
        if (isset($key)) {
            unset($this->activeTextFilters[$column][$key]);
            if (!isset($this->activeTextFilters[$column]) || count($this->activeTextFilters[$column]) < 1) {
                unset($this->activeTextFilters[$column]);
            }
        } else {
            unset($this->activeTextFilters[$column]);
        }
        $this->visibleSelected = $this->selected;
        $this->setSessionStoredFilters();
    }

    public function removeNumberFilter($column): void
    {
        unset($this->activeNumberFilters[$column]);
        $this->visibleSelected = $this->selected;
        $this->setSessionStoredFilters();
    }

    public function getColumnFilterStatement($index): array|string
    {
        if (isset($this->freshColumns[$index]) && $this->freshColumns[$index]['type'] === 'editable') {
            return [$this->getSelectStatements()[$index][0]];
        }

        if (isset($this->freshColumns[$index]) && $this->freshColumns[$index]['filterOn']) {
            return Arr::wrap($this->freshColumns[$index]['filterOn']);
        }

        if (isset($this->freshColumns[$index]) && $this->freshColumns[$index]['scope']) {
            return 'scope';
        }

        if (isset($this->freshColumns[$index]) && $this->freshColumns[$index]['raw']) {
            return [(string)$this->freshColumns[$index]['sort']];
        }

        return Arr::wrap($this->getSelectStatements()[$index]);
    }

    public function addScopeSelectFilter($query, $index, $value): ?bool
    {
        if (!isset($this->freshColumns[$index]['scopeFilter'])) {
            return null;
        }

        return (bool)$query->{$this->freshColumns[$index]['scopeFilter']}($value);
    }

    public function addScopeNumberFilter($query, $index, $value): ?bool
    {
        if (!isset($this->freshColumns[$index]['scopeFilter'])) {
            return null;
        }

        return (bool)$query->{$this->freshColumns[$index]['scopeFilter']}($value);
    }

    public function addAggregateFilter($query, $index, $filter, $operand = null): void
    {
        $column = $this->freshColumns[$index];
        $relation = Str::before($column['name'], '.');
        $aggregate = $this->columnAggregateType($column);
        $field = Str::before(explode('.', $column['name'])[1], ':');

        $filter = Arr::wrap($filter);

        $query->when($column['type'] === 'boolean', function ($query) use ($filter, $relation, $field, $aggregate) {
            $query->where(function ($query) use ($filter, $relation, $field, $aggregate) {
                if (Arr::wrap($filter)[0]) {
                    $query->hasAggregate($relation, $field, $aggregate);
                } else {
                    $query->hasAggregate($relation, $field, $aggregate, '<');
                }
            });
        })->when($aggregate === 'group_concat' && count($filter), function ($query) use ($filter, $relation, $field, $aggregate) {
            $query->where(function ($query) use ($filter, $relation, $field, $aggregate) {
                foreach ($filter as $value) {
                    $query->hasAggregate($relation, $field, $aggregate, 'like', '%' . $value . '%');
                }
            });
        })->when(isset($filter['start']), function ($query) use ($filter, $relation, $field, $aggregate) {
            $query->hasAggregate($relation, $field, $aggregate, '>=', $filter['start']);
        })->when(isset($filter['end']), function ($query) use ($filter, $relation, $field, $aggregate) {
            $query->hasAggregate($relation, $field, $aggregate, '<=', $filter['end']);
        })->when(isset($operand), function ($query) use ($filter, $relation, $field, $aggregate, $operand) {
            $query->hasAggregate($relation, $field, $aggregate, $operand, $filter);
        });
    }

    public function searchableColumns(): Collection
    {
        return collect($this->freshColumns)->filter(function ($column, $key) {
            return $column['searchable'];
        });
    }

    public function scopeColumns(): Collection
    {
        return collect($this->freshColumns)->filter(function ($column, $key) {
            return isset($column['scope']);
        });
    }

    public function getHeaderProperty(): bool
    {
        return method_exists(static::class, 'header');
    }

    public function getShowHideProperty()
    {
        return $this->showHide() ?? $this->showHide;
    }

    public function getPaginationControlsProperty()
    {
        return $this->paginationControls() ?? $this->paginationControls;
    }

    public function getResultsProperty(): Collection|LengthAwarePaginator
    {
        $this->row = 1;

        return $this->mapCallbacks(
            $this->getQuery()->paginate($this->perPage)
        );
    }

    public function getSelectFiltersProperty()
    {
        return collect($this->freshColumns)->filter->selectFilter;
    }

    public function getBooleanFiltersProperty()
    {
        return collect($this->freshColumns)->filter->booleanFilter;
    }

    public function getTextFiltersProperty()
    {
        return collect($this->freshColumns)->filter->textFilter;
    }

    public function getNumberFiltersProperty()
    {
        return collect($this->freshColumns)->filter->numberFilter;
    }

    public function getActiveFiltersProperty(): bool
    {
        return count($this->activeDateFilters)
            || count($this->activeDatetimeFilters)
            || count($this->activeTimeFilters)
            || count($this->activeSelectFilters)
            || count($this->activeBooleanFilters)
            || count($this->activeTextFilters)
            || count($this->activeNumberFilters)
            || is_array($this->complexQuery)
            || $this->userFilter;
    }

    public function columnIsRelation($column): bool
    {
        return Str::contains($column['name'], '.') && method_exists($this->builder()->getModel(), Str::before($column['name'], '.'));
    }

    public function columnIsAggregateRelation($column): bool
    {
        if (!$this->columnIsRelation($column)) {
            return false;
        }
        $relation = $this->builder()->getRelation(Str::before($column['name'], '.'));

        return $relation instanceof HasManyThrough || $relation instanceof HasMany || $relation instanceof belongsToMany;
    }

    public function columnAggregateType($column): string
    {
        $aggregate = $column['type'] === 'string' ? 'group_concat' : 'count';

        return Str::contains($column['name'], ':')
            ? Str::after(explode('.', $column['name'])[1], ':')
            : $aggregate;
    }

    public function buildDatabaseQuery($export = false): void
    {
        $this->query = $this->builder();

        $this->tablePrefix = $this->query->getConnection()->getTablePrefix() ?? '';

        $this->query->addSelect(
            $this->getSelectStatements(true, $export)
                ->filter()
                ->flatten()
                ->toArray()
        );

        $this->addGlobalSearch()
            ->addScopeColumns()
            ->addSelectFilters()
            ->addBooleanFilters()
            ->addTextFilters()
            ->addNumberFilters()
            ->addDateRangeFilter()
            ->addDatetimeRangeFilter()
            ->addTimeRangeFilter()
            ->addComplexQuery()
            ->addSort();

        if (isset($this->pinnedRecors)) {
            $this->applyPinnedRecords();
        }
    }

    #[On('complexQuery')]
    public function setComplexQueryRules($rules): void
    {
        $this->complexQuery = $rules;
    }

    public function addComplexQuery(): static
    {
        if (!$this->complexQuery) {
            return $this;
        }

        $this->query->where(function ($query) {
            $this->processNested($this->complexQuery, $query);
        });

        return $this;
    }

    private function complexOperator($operand)
    {
        return $operand ? $this->operators[$operand] : '=';
    }

    private function complexValue($rule)
    {
        if (isset($rule['content']['operand'])) {
            if ($rule['content']['operand'] === 'contains') {
                return '%' . $rule['content']['value'] . '%';
            }

            if ($rule['content']['operand'] === 'does not contain') {
                return '%' . $rule['content']['value'] . '%';
            }

            if ($rule['content']['operand'] === 'begins with') {
                return $rule['content']['value'] . '%';
            }

            if ($rule['content']['operand'] === 'ends with') {
                return '%' . $rule['content']['value'];
            }

            if ($rule['content']['operand'] === 'is empty' || $rule['content']['operand'] === 'is not empty') {
                return '';
            }
        }

        return $rule['content']['value'];
    }

    public function processNested($rules = null, $query = null, $logic = 'and')
    {
        collect($rules)->each(function ($rule) use ($query, $logic) {
            if ($rule['type'] === 'rule' && isset($rule['content']['column'])) {
                $query->where(function ($query) use ($rule) {
                    if (!$this->addScopeSelectFilter($query, $rule['content']['column'], $rule['content']['value'])) {
                        if ($this->columnIsAggregateRelation($this->freshColumns[$rule['content']['column']])) {
                            $this->addAggregateFilter($query, $rule['content']['column'], $this->complexValue($rule), $this->complexOperator($rule['content']['operand']));
                        } else {
                            foreach ($this->getColumnFilterStatement($rule['content']['column']) as $column) {
                                if ($rule['content']['operand'] === 'is empty') {
                                    $query->whereNull($column);
                                } else if ($rule['content']['operand'] === 'is not empty') {
                                    $query->whereNotNull($column);
                                } else if ($this->columns[$rule['content']['column']]['type'] === 'boolean') {
                                    if ($rule['content']['value'] === 'true') {
                                        $query->whereNotNull(Str::contains($column, '(') ? DB::raw($column) : $column);
                                    } else {
                                        $query->whereNull(Str::contains($column, '(') ? DB::raw($column) : $column);
                                    }
                                } else {
                                    $columnName = Str::contains($column, '(') ? DB::raw($column) : $column;
                                    $col = (isset($this->freshColumns[$rule['content']['column']]['round']) && $this->freshColumns[$rule['content']['column']]['round'] !== null)
                                        ? DB::raw('ROUND(' . $column . ', ' . $this->freshColumns[$rule['content']['column']]['round'] . ')')
                                        : $columnName;

                                    $query->orWhere(
                                        $col,
                                        $this->complexOperator($rule['content']['operand']),
                                        $this->complexValue($rule)
                                    );
                                }
                            }
                        }
                    }
                }, null, null, $logic);
            } else {
                $query->where(function ($q) use ($rule) {
                    $this->processNested($rule['content'], $q, $rule['logic']);
                }, null, null, $logic);
            }
        });

        return $query;
    }

    public function addGlobalSearch(): static
    {
        if (!$this->search) {
            return $this;
        }

        $this->query->where(function ($query) {
            foreach (explode(' ', $this->search) as $search) {
                $query->where(function ($query) use ($search) {
                    $this->searchableColumns()->each(function ($column, $i) use ($query, $search) {
                        $query->orWhere(function ($query) use ($i, $search) {
                            foreach ($this->getColumnFilterStatement($i) as $column) {
                                $query->when(is_array($column), function ($query) use ($search, $column) {
                                    foreach ($column as $col) {
                                        $columnString = is_string($col) ? $col : $col->getValue(DB::connection()->getQueryGrammar());
                                        $query->orWhereRaw('LOWER(' . (Str::contains(mb_strtolower($columnString), 'concat') ? '' : $this->tablePrefix) . $columnString . ') like ?', '%' . mb_strtolower($search) . '%');
                                    }
                                }, function ($query) use ($search, $column) {
                                    $columnString = is_string($column) ? $column : $column->getValue(DB::connection()->getQueryGrammar());
                                    $query->orWhereRaw('LOWER(' . (Str::contains(mb_strtolower($columnString), 'concat') ? '' : $this->tablePrefix) . $columnString . ') like ?', '%' . mb_strtolower($search) . '%');
                                });
                            }
                        });
                    });
                });
            }
        });

        return $this;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function addScopeColumns(): static
    {
        $this->scopeColumns()->each(function ($column) {
            $this->query->{$column['scope']}($column['label']);
        });

        return $this;
    }

    public function addSelectFilters(): static
    {
        if (count($this->activeSelectFilters) < 1) {
            return $this;
        }

        $this->query->where(function ($query) {
            foreach ($this->activeSelectFilters as $index => $activeSelectFilter) {
                $query->where(function ($query) use ($index, $activeSelectFilter) {
                    foreach ($activeSelectFilter as $value) {
                        if (isset($this->freshColumns[$index]) && $this->columnIsAggregateRelation($this->freshColumns[$index])) {
                            $this->addAggregateFilter($query, $index, $activeSelectFilter);
                        } else if (!$this->addScopeSelectFilter($query, $index, $value)) {
                            if (isset($this->freshColumns[$index]) && $this->freshColumns[$index]['type'] === 'json') {
                                $query->where(function ($query) use ($value, $index) {
                                    foreach ($this->getColumnFilterStatement($index) as $column) {
                                        $query->whereRaw('LOWER(' . $this->tablePrefix . $column . ') like ?', [mb_strtolower("%$value%")]);
                                    }
                                });
                            } else {
                                $query->orWhere(function ($query) use ($value, $index) {
                                    foreach ($this->getColumnFilterStatement($index) as $column) {
                                        if (Str::contains(mb_strtolower($column), 'concat')) {
                                            $query->orWhereRaw('LOWER(' . $this->tablePrefix . $column . ') like ?', [mb_strtolower("%$value%")]);
                                        } else {
                                            $query->orWhereRaw($column . ' = ?', $value);
                                        }
                                    }
                                });
                            }
                        }
                    }
                });
            }
        });

        return $this;
    }

    public function addBooleanFilters(): static
    {
        if (count($this->activeBooleanFilters) < 1) {
            return $this;
        }
        $this->query->where(function ($query) {
            foreach ($this->activeBooleanFilters as $index => $value) {
                if ($this->getColumnFilterStatement($index) === 'scope') {
                    $this->addScopeSelectFilter($query, $index, $value);
                } else if (isset($this->freshColumns[$index]) && $this->columnIsAggregateRelation($this->freshColumns[$index])) {
                    $this->addAggregateFilter($query, $index, $value);
                } else if (isset($this->freshColumns[$index]) && $this->freshColumns[$index]['type'] === 'string') {
                    if ($value == 1) {
                        $query->whereNotNull($this->getColumnFilterStatement($index)[0])
                            ->where($this->getColumnFilterStatement($index)[0], '<>', '');
                    } else if ($value !== '') {
                        $query->where(function ($query) use ($index) {
                            $query->whereNull(DB::raw($this->getColumnFilterStatement($index)[0]))
                                ->orWhere(DB::raw($this->getColumnFilterStatement($index)[0]), '');
                        });
                    }
                } else if ($value == 1) {
                    $query->where(DB::raw($this->getColumnFilterStatement($index)[0]), '>', 0);
                } else if ($value !== '') {
                    $query->where(function ($query) use ($index) {
                        $query->whereNull(DB::raw($this->getColumnFilterStatement($index)[0]))
                            ->orWhere(DB::raw($this->getColumnFilterStatement($index)[0]), 0);
                    });
                }
            }
        });

        return $this;
    }

    public function addTextFilters(): static
    {
        if (!count($this->activeTextFilters)) {
            return $this;
        }

        $this->query->where(function ($query) {
            foreach ($this->activeTextFilters as $index => $activeTextFilter) {
                $query->where(function ($query) use ($index, $activeTextFilter) {
                    foreach ($activeTextFilter as $value) {
                        if (isset($this->freshColumns[$index]) && $this->columnIsRelation($this->freshColumns[$index])) {
                            $this->addAggregateFilter($query, $index, $activeTextFilter);
                        } else {
                            $query->orWhere(function ($query) use ($index, $value) {
                                foreach ($this->getColumnFilterStatement($index) as $column) {
                                    $column = is_array($column) ? $column[0] : $column;
                                    $query->orWhereRaw('LOWER(' . $this->tablePrefix . $column . ') like ?', [mb_strtolower("%$value%")]);
                                }
                            });
                        }
                    }
                });
            }
        });

        return $this;
    }

    public function addNumberFilters(): static
    {
        if (!count($this->activeNumberFilters)) {
            return $this;
        }
        $this->query->where(function ($query) {
            foreach ($this->activeNumberFilters as $index => $filter) {
                if (isset($this->freshColumns[$index]) && $this->columnIsAggregateRelation($this->freshColumns[$index])) {
                    $this->addAggregateFilter($query, $index, $filter);
                } else {
                        $this->addScopeNumberFilter($query, $index, [
                            $filter['start'] ?? 0,
                            $filter['end'] ?? 9999999999,
                        ]) ?? $query->when(isset($filter['start']), function ($query) use ($filter, $index) {
                        $query->whereRaw($this->getColumnFilterStatement($index)[0] . ' >= ?', $filter['start']);
                    })->when(isset($filter['end']), function ($query) use ($filter, $index) {
                        if (isset($this->freshColumns[$index]['round']) && $this->freshColumns[$index]['round'] !== null) {
                            $query->whereRaw('ROUND(' . $this->getColumnFilterStatement($index)[0] . ',' . $this->freshColumns[$index]['round'] . ') <= ?', $filter['end']);
                        } else {
                            $query->whereRaw($this->getColumnFilterStatement($index)[0] . ' <= ?', $filter['end']);
                        }
                    });
                }
            }
        });

        return $this;
    }

    public function addDateRangeFilter(): static
    {
        if (!count($this->activeDateFilters)) {
            return $this;
        }

        $this->query->where(function ($query) {
            foreach ($this->activeDateFilters as $index => $filter) {
                if (!((isset($filter['start']) && $filter['start']) || (isset($filter['end']) && $filter['end']))) {
                    break;
                }
                $query->whereBetween($this->getColumnFilterStatement($index)[0], [
                    isset($filter['start']) && $filter['start'] ? $filter['start'] : config('livewire-datatables.default_time_start', '0000-00-00'),
                    isset($filter['end']) && $filter['end'] ? $filter['end'] : config('livewire-datatables.default_time_end', '9999-12-31'),
                ]);
            }
        });

        return $this;
    }

    public function addDatetimeRangeFilter(): static
    {
        if (!count($this->activeDatetimeFilters)) {
            return $this;
        }

        $this->query->where(function ($query) {
            foreach ($this->activeDatetimeFilters as $index => $filter) {
                if (!((isset($filter['start']) && $filter['start'] != '') || (isset($filter['end']) && $filter['end'] != ''))) {
                    break;
                }
                $query->whereBetween($this->getColumnFilterStatement($index)[0], [
                    isset($filter['start']) && $filter['start'] != '' ? $filter['start'] : config('livewire-datatables.default_time_start', '0000-00-00 00:00'),
                    isset($filter['end']) && $filter['end'] != '' ? $filter['end'] : config('livewire-datatables.default_time_end', '9999-12-31 23:59'),
                ]);
            }
        });

        return $this;
    }

    public function addTimeRangeFilter(): static
    {
        if (!count($this->activeTimeFilters)) {
            return $this;
        }

        $this->query->where(function ($query) {
            foreach ($this->activeTimeFilters as $index => $filter) {
                $start = isset($filter['start']) && $filter['start'] != '' ? $filter['start'] : '00:00:00';
                $end = isset($filter['end']) && $filter['end'] != '' ? $filter['end'] : '23:59:59';

                if ($end < $start) {
                    $query->where(function ($subQuery) use ($index, $start, $end) {
                        $subQuery->whereBetween($this->getColumnFilterStatement($index)[0], [$start, '23:59'])
                            ->orWhereBetween($this->getColumnFilterStatement($index)[0], ['00:00', $end]);
                    });
                } else {
                    $query->whereBetween($this->getColumnFilterStatement($index)[0], [$start, $end]);
                }
            }
        });

        return $this;
    }

    /**
     * Set the 'ORDER BY' clause of the SQL query.
     *
     * Do not set a 'ORDER BY' clause if the column to be sorted does not have a name assigned.
     * This could be a 'label' or 'checkbox' column which is not 'sortable' by SQL by design.
     */
    public function addSort(): static
    {
        if (isset($this->sortIndex, $this->freshColumns[$this->sortIndex]) && $this->freshColumns[$this->sortIndex]['name']) {
            if (isset($this->pinnedRecords) && $this->pinnedRecords) {
                $this->query->orderBy(DB::raw('FIELD(id,' . implode(',', $this->pinnedRecords) . ')'), 'DESC');
            }
            // Use the modified getSortString to get the sort expression
            $sortExpression = $this->getSortString($this->query->getConnection()->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME));

            $this->query->orderBy(DB::raw($sortExpression), $this->direction ? 'asc' : 'desc');
        }

        return $this;
    }

    public function getCallbacksProperty()
    {
        return collect($this->freshColumns)->filter->callbackFunction->mapWithKeys(function ($column) {
            return [$column['name'] => $column['callbackFunction']];
        });
    }

    public function getExportCallbacksProperty()
    {
        return collect($this->freshColumns)->filter->exportCallback->mapWithKeys(function ($column) {
            return [$column['name'] => $column['exportCallback']];
        });
    }

    public function getEditablesProperty()
    {
        return collect($this->freshColumns)->filter(function ($column) {
            return $column['type'] === 'editable';
        })->mapWithKeys(function ($column) {
            return [$column['name'] => true];
        });
    }

    public function mapCallbacks($paginatedCollection, $export = false): Collection|LengthAwarePaginator 
    {
        $callbacks = $this->callbacks->toArray();
        $exportCallbacks = $this->exportCallbacks->toArray();
        $editables = $this->editables->toArray();
        $searchableColumns = [];
        $searchHighlightEnabled = $this->search && !config('livewire-datatables.suppress_search_highlights');
        if ($searchHighlightEnabled) {
            foreach ($this->searchableColumns() as $column) {
                $searchableColumns[$column->name] = true;
            }
        }

        foreach ($paginatedCollection as $row) {
            foreach ($row as $name => $value) {
                $callbackValue = array_key_exists($name, $callbacks) ? $callbacks[$name] : null;
                $exportCallBackValue = array_key_exists($name, $exportCallbacks) ? $exportCallbacks[$name] : null;
                $isEditable = array_key_exists($name, $editables);
                if ($searchHighlightEnabled && array_key_exists($name, $searchableColumns)) {
                    $row->$name = $this->highlight($row->$name, $this->search);
                }

                if ($export && !is_null($exportCallBackValue)) {
                    $values = Str::contains($value, static::SEPARATOR) ? explode(static::SEPARATOR, $value) : [$value, $row];
                    $row->$name = $exportCallBackValue(...$values);
                    continue;
                }

                if ($isEditable) {
                    $row->$name = view('datatables::editable', [
                        'value' => $value,
                        'key' => $this->builder()->getModel()->getQualifiedKeyName(),
                        'column' => Str::after($name, '.'),
                        'rowId' => $row->{$name . '_edit_id'},
                    ]);
                    continue;
                }

                if (is_null($callbackValue)) {
                    continue;
                }

                if (is_string($callbackValue)) {
                    $row->$name = $this->{$callbackValue}($value, $row);
                    continue;
                }

                if (Str::startsWith($name, 'callback_')) {
                    $row->$name = $callbackValue(...explode(static::SEPARATOR, $value));
                    continue;
                }

                if (is_callable($callbackValue)) {
                    $row->$name = $callbackValue($value, $row);
                }
            }
        }

        return $paginatedCollection;
    }

    public function getDisplayValue($index, $value)
    {
        return is_array($this->freshColumns[$index]['filterable']) && is_numeric($value)
            ? collect($this->freshColumns[$index]['filterable'])->firstWhere('id', '=', $value)['name'] ?? $value
            : $value;
    }

    /*  This can be called to apply highlighting of the search term to some string.
     *  Motivation: Call this from your Column::Callback to apply highlight to a chosen section of the result.
     */
    public function highlightStringWithCurrentSearchTerm(string $originalString): array|string
    {
        if (!$this->search) {
            return $originalString;
        }

        return static::highlightString($originalString, $this->search);
    }

    /* Utility function for applying highlighting to given string */
    public static function highlightString(string $originalString, string $searchingForThisSubstring): array|string
    {
        $searchStringNicelyHighlightedWithHtml = view(
            'datatables::highlight',
            ['slot' => $searchingForThisSubstring]
        )->render();
        $stringWithHighlightedSubstring = str_ireplace(
            $searchingForThisSubstring,
            $searchStringNicelyHighlightedWithHtml,
            $originalString
        );

        return $stringWithHighlightedSubstring;
    }

    public function isRtl($value): bool
    {
        $rtlChar = '/[\x{0590}-\x{083F}]|[\x{08A0}-\x{08FF}]|[\x{FB1D}-\x{FDFF}]|[\x{FE70}-\x{FEFF}]/u';

        return preg_match($rtlChar, $value) != 0;
    }

    public function highlight($value, $string)
    {
//        if ($this->isRtl($value)) {
//            $output = $string;
//        }
        $output = substr($value, stripos($value, $string), strlen($string));

        if ($value instanceof View) {
            return $value->with(['value' => str_ireplace($string, (string)view('datatables::highlight', ['slot' => $output]), $value->gatherData()['value'] ?? $value->gatherData()['slot'])]);
        }

        return str_ireplace($string, (string)view('datatables::highlight', ['slot' => $output]), $value);
    }

    public function render()
    {
        $this->dispatch('refreshDynamic');

        if ($this->persistPerPage) {
            session()->put([$this->sessionStorageKey() . '_perpage' => $this->perPage]);
        }

        return view('datatables::datatable')->layoutData(['title' => $this->title]);
    }

    public function export(string $filename = 'DatatableExport.xlsx'): BinaryFileResponse
    {
//        $this->forgetComputed();

        $export = new DatatableExport($this->getExportResultsSet());
        $export->setFilename($filename);

        return $export->download();
    }

    public function getExportResultsSet(): Collection
    {
        return $this->mapCallbacks(
            $this->getQuery()->when(count($this->selected), function ($query) {
                return $query->havingRaw('checkbox_attribute IN (' . implode(',', $this->selected) . ')');
            })->get(),
            true
        )->map(function ($item) {
            return collect($this->getColumns())->reject(function ($value, $key) {
                return is_object($value) && ($value->preventExport || $value->hidden);
            })->mapWithKeys(function ($value, $key) use ($item) {
                return [$value->label ?? $value->name => $item->{$value->name}];
            })->all();
        });
    }

    public function getQuery($export = false): \Illuminate\Database\Query\Builder
    {
        $this->buildDatabaseQuery($export);

        return $this->query->toBase();
    }

    public function checkboxQuery(): Collection
    {
        return $this->query->reorder()->get()->map(function ($row) {
            return (string)$row->checkbox_attribute;
        });
    }

    public function toggleSelectAll(): void
    {
        $visible_checkboxes = $this->getQuery()->get()->pluck('checkbox_attribute')->toArray();
        $visible_checkboxes = array_map('strval', $visible_checkboxes);
        if ($this->searchOrFilterActive()) {
            if (count($this->visibleSelected) === count($visible_checkboxes)) {
                $this->selected = array_values(array_diff($this->selected, $visible_checkboxes));
                $this->visibleSelected = [];
            } else {
                $this->selected = array_unique(array_merge($this->selected, $visible_checkboxes));
                sort($this->selected);
                $this->visibleSelected = $visible_checkboxes;
            }
        } else {
            if (count($this->selected) === $this->getQuery()->getCountForPagination()) {
                $this->selected = [];
            } else {
                $this->selected = $this->checkboxQuery()->values()->toArray();
            }
            $this->visibleSelected = $this->selected;
        }

//        $this->forgetComputed();
    }

    public function updatedSelected(): void
    {
        if ($this->searchOrFilterActive()) {
            $this->setVisibleSelected();
        } else {
            $this->visibleSelected = $this->selected;
        }
    }

    public function rowIsSelected($row): bool
    {
        return isset($row->checkbox_attribute) && in_array($row->checkbox_attribute, $this->selected, true);
    }

    #[On('saveQuery')]
    public function saveQuery($name, $rules): void
    {
        // Override this method with your own method for saving
    }

    #[On('deleteQuery')]
    public function deleteQuery($id): void
    {
        // Override this method with your own method for deleting
    }

    public function getSavedQueries(): void
    {
        // Override this method with your own method for getting saved queries
    }

    /**
     * Override this method with your own method for creating mass actions
     */
    public function buildActions(): array
    {
        return [];
    }

    public function rowClasses($row, $loop): string
    {
        // Override this method with your own method for adding classes to a row
        if ($this->rowIsSelected($row)) {
            return config('livewire-datatables.default_classes.row.selected', 'divide-x divide-gray-100 text-sm text-gray-900 bg-yellow-100');
        }

        if ($loop->even) {
            return config('livewire-datatables.default_classes.row.even', 'divide-x divide-gray-100 text-sm text-gray-900 bg-gray-100');
        }

        return config('livewire-datatables.default_classes.row.odd', 'divide-x divide-gray-100 text-sm text-gray-900 bg-gray-50');
    }

    public function cellClasses($row, $column): string
    {
        // Override this method with your own method for adding classes to a cell
        return config('livewire-datatables.default_classes.cell', 'text-sm text-gray-900');
    }

    public function getMassActions(): array
    {
        return collect($this->massActions)->map(function ($action) {
            return collect($action)->only(['group', 'value', 'label'])->toArray();
        })->toArray();
    }

    public function getMassActionsProperty(): array
    {
        $actions = collect($this->buildActions())->flatten();

        $duplicates = $actions->pluck('value')->duplicates();

        if ($duplicates->count()) {
            throw new RuntimeException('Duplicate Mass Action(s): ' . implode(', ', $duplicates->toArray()));
        }

        return $actions->toArray();
    }

    public function getMassActionsOptionsProperty(): Collection
    {
        return collect($this->actions)->groupBy(function ($item) {
            return $item['group'];
        }, true);
    }

    public function massActionOptionHandler(): BinaryFileResponse|bool
    {
        if (!$this->massActionOption) {
            return false;
        }

        $option = $this->massActionOption;

        $action = collect($this->massActions)->filter(function ($item) use ($option) {
            return $item->value === $option;
        })->shift();

        $collection = collect($action);

        if ($collection->get('isExport')) {
            $datatableExport = new DatatableExport($this->getExportResultsSet());

            $datatableExport->setFileName($collection->get('fileName'));

            $datatableExport->setStyles($collection->get('styles'));

            $datatableExport->setColumnWidths($collection->get('widths'));

            return $datatableExport->download();
        }

        if (!count($this->selected)) {
            $this->massActionOption = null;

            return false;
        }

        if (is_callable($action?->callable) && $collection->has('callable')) {
            $action?->callable($option, $this->selected);
        }

        $this->massActionOption = null;

        return true;
    }

    private function searchOrFilterActive(): bool
    {
        return !empty($this->search) || $this->getActiveFiltersProperty();
    }

    private function setVisibleSelected(): void
    {
        $this->visibleSelected = array_intersect($this->getQuery()->get()->pluck('checkbox_attribute')->toArray(), $this->selected);
        $this->visibleSelected = array_map('strval', $this->visibleSelected);
    }
}
