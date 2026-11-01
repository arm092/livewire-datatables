<?php

namespace Arm092\LivewireDatatables\Tests\Classes;

use Arm092\LivewireDatatables\Column;
use Arm092\LivewireDatatables\Livewire\LivewireDatatable;
use Arm092\LivewireDatatables\Tests\Models\DummyModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SecureDummyTable extends LivewireDatatable
{
    public ?int $perPage = 10;

    public string|null|Model $model = DummyModel::class;

    public function builder(): Builder
    {
        return parent::builder()->where('category', 'allowed');
    }

    public function getColumns(): array|Model
    {
        return [
            Column::name('subject')->editable(),
            Column::name('category'),
            Column::delete(),
        ];
    }
}
