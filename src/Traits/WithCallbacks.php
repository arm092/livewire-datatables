<?php

namespace Arm092\LivewireDatatables\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

trait WithCallbacks
{
    public function edited($value, $columnIndex, $rowId): void
    {
        $column = $this->freshColumns[$columnIndex] ?? null;

        abort_unless($column && $column['type'] === 'editable', 403);

        $field = $column['base'] ?: Str::afterLast($column['name'], '.');
        $model = $this->builder()->whereKey($rowId)->firstOrFail();

        $this->authorizeModelActionIfPolicyExists('update', $model);

        $model->setAttribute($field, $value);
        $model->save();

        $this->dispatch('fieldEdited', rowId: $rowId, column: $field);
    }

    protected function authorizeModelActionIfPolicyExists(string $ability, Model $model): void
    {
        if (Gate::getPolicyFor($model)) {
            Gate::authorize($ability, $model);
        }
    }
}
