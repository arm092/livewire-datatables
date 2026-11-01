<?php

namespace Arm092\LivewireDatatables\Tests\Classes;

use Arm092\LivewireDatatables\Tests\Models\DummyModel;
use Illuminate\Contracts\Auth\Authenticatable;

class DummyModelPolicy
{
    public function update(?Authenticatable $user, DummyModel $model): bool
    {
        return $model->subject !== 'protected';
    }

    public function delete(?Authenticatable $user, DummyModel $model): bool
    {
        return $model->subject !== 'protected';
    }
}
