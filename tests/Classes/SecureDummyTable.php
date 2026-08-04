<?php

namespace Arm092\LivewireDatatables\Tests\Classes;

use Arm092\LivewireDatatables\Column;
use Arm092\LivewireDatatables\Livewire\LivewireDatatable;
use Arm092\LivewireDatatables\Tests\Models\DummyModel;
use Arm092\LivewireDatatables\Traits\CanPinRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SecureDummyTable extends LivewireDatatable
{
    use CanPinRecords;

    public ?int $perPage = 10;

    public string|null|Model $model = DummyModel::class;

    public function builder(): Builder
    {
        return parent::builder()->where('category', 'allowed');
    }

    public function getColumns(): array|Model
    {
        return [
            Column::checkbox(),
            Column::name('subject')->editable()->searchable(),
            Column::name('category'),
            Column::name('body'),
            Column::delete(),
        ];
    }
}
