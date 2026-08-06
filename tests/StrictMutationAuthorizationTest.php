<?php

namespace Arm092\LivewireDatatables\Tests;

use Arm092\LivewireDatatables\Tests\Classes\AllowingDummyModelPolicy;
use Arm092\LivewireDatatables\Tests\Classes\SecureDummyTable;
use Arm092\LivewireDatatables\Tests\Models\DummyModel;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

class StrictMutationAuthorizationTest extends TestCase
{
    #[Test]
    public function strict_mode_denies_editing_when_the_model_has_no_policy(): void
    {
        config()->set('livewire-datatables.strict_mutations', true);
        $model = factory(DummyModel::class)->create(['category' => 'allowed', 'subject' => 'Original']);

        Livewire::test(SecureDummyTable::class)
            ->call('edited', 'Blocked', 1, $model->getKey())
            ->assertForbidden();

        $this->assertSame('Original', $model->fresh()->subject);
    }

    #[Test]
    public function strict_mode_denies_deleting_when_the_model_has_no_policy(): void
    {
        config()->set('livewire-datatables.strict_mutations', true);
        $model = factory(DummyModel::class)->create(['category' => 'allowed']);

        Livewire::test(SecureDummyTable::class)
            ->call('delete', $model->getKey())
            ->assertForbidden();

        $this->assertNotNull($model->fresh());
    }

    #[Test]
    public function strict_mode_allows_mutations_authorized_by_a_registered_policy(): void
    {
        config()->set('livewire-datatables.strict_mutations', true);
        Gate::policy(DummyModel::class, AllowingDummyModelPolicy::class);
        $model = factory(DummyModel::class)->create(['category' => 'allowed', 'subject' => 'Original']);

        Livewire::test(SecureDummyTable::class)
            ->call('edited', 'Allowed', 1, $model->getKey())
            ->assertOk();

        $this->assertSame('Allowed', $model->fresh()->subject);
    }

    #[Test]
    public function compatibility_mode_still_allows_mutations_without_a_policy(): void
    {
        config()->set('livewire-datatables.strict_mutations', false);
        $model = factory(DummyModel::class)->create(['category' => 'allowed', 'subject' => 'Original']);

        Livewire::test(SecureDummyTable::class)
            ->call('edited', 'Allowed', 1, $model->getKey())
            ->assertOk();

        $this->assertSame('Allowed', $model->fresh()->subject);
    }
}
