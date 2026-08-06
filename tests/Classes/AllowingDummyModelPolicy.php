<?php

namespace Arm092\LivewireDatatables\Tests\Classes;

use Arm092\LivewireDatatables\Tests\Models\DummyModel;
use Illuminate\Contracts\Auth\Authenticatable;

class AllowingDummyModelPolicy
{
    public function update(?Authenticatable $user, DummyModel $model): bool
    {
        return true;
    }

    public function delete(?Authenticatable $user, DummyModel $model): bool
    {
        return true;
    }
}
