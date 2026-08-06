<?php

namespace Arm092\LivewireDatatables\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
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

        $model->setAttribute($field, $this->validateEditableValue($column, $model, $field, $value));
        $model->save();

        $this->dispatch('fieldEdited', rowId: $rowId, column: $field);
    }

    protected function validateEditableValue($column, Model $model, string $field, mixed $value): mixed
    {
        $rules = $column['validationRules'] ?? [];

        if ($rules instanceof \Closure) {
            $rules = $rules($model, $field, $value);
        }

        if (!$rules) {
            return $value;
        }

        $rules = is_array($rules) ? $rules : [$rules];
        $key = "editable.{$model->getKey()}.{$field}";
        $data = [];
        Arr::set($data, $key, $value);
        $this->resetErrorBag($key);

        $validated = Validator::make(
            $data,
            [$key => $rules],
            [],
            [$key => $column['label'] ?: $field],
        )->validate();

        return Arr::get($validated, $key);
    }

    protected function authorizeModelActionIfPolicyExists(string $ability, Model $model): void
    {
        if (!Gate::getPolicyFor($model)) {
            if (config('livewire-datatables.strict_mutations', false)) {
                throw new AuthorizationException(
                    'Mutation ['.$ability.'] denied because no policy is registered for ['.$model::class.'].'
                );
            }

            return;
        }

        Gate::authorize($ability, $model);
    }
}
