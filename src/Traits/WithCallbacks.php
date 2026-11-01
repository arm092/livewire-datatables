<?php

namespace Arm092\LivewireDatatables\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

trait WithCallbacks
{
    public function edited($value, $key, $column, $rowId): void
    {
        $model = $this->builder()->whereKey($rowId)->firstOrFail();
        $qualifiedKey = $model->getQualifiedKeyName();
        $editable = collect($this->freshColumns)->first(function ($candidate) use ($column) {
            if (($candidate['type'] ?? null) !== 'editable') {
                return false;
            }

            $field = $candidate['base'] ?: Str::afterLast($candidate['name'], '.');

            return hash_equals((string) $field, (string) $column);
        });

        abort_unless($editable && hash_equals($qualifiedKey, (string) $key), 403);

        $this->authorizeModelAction('update', $model);

        $model->setAttribute($column, $value);
        $model->save();

        $this->dispatch('fieldEdited', rowId: $rowId, column: $column);
    }

    protected function authorizeModelAction(string $ability, Model $model): void
    {
        Gate::authorize($ability, $model);
    }
}
