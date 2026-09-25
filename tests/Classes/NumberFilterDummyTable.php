<?php

namespace Arm092\LivewireDatatables\Tests\Classes;

use Arm092\LivewireDatatables\Livewire\LivewireDatatable;
use Arm092\LivewireDatatables\NumberColumn;
use Arm092\LivewireDatatables\Tests\Models\DummyModel;
use Illuminate\Database\Eloquent\Model;

class NumberFilterDummyTable extends LivewireDatatable
{
    public string|null|Model $model = DummyModel::class;

    public function getColumns(): array|Model
    {
        return [
            NumberColumn::name('id')->filterable(),
        ];
    }
}
