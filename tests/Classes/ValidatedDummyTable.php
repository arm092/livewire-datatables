<?php

namespace Arm092\LivewireDatatables\Tests\Classes;

use Arm092\LivewireDatatables\Column;
use Arm092\LivewireDatatables\Livewire\LivewireDatatable;
use Arm092\LivewireDatatables\Tests\Models\DummyModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ValidatedDummyTable extends LivewireDatatable
{
    public ?int $perPage = 10;

    public string|null|Model $model = DummyModel::class;

    public function getColumns(): array|Model
    {
        return [
            Column::name('subject')
                ->editable()
                ->rules(fn (Model $model) => [
                    'required',
                    'string',
                    'min:2',
                    Rule::unique('dummy_models', 'subject')->ignoreModel($model),
                ]),
            Column::name('category'),
        ];
    }
}
