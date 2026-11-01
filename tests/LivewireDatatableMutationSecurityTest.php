<?php

namespace Arm092\LivewireDatatables\Tests;

use Arm092\LivewireDatatables\Tests\Classes\DummyModelPolicy;
use Arm092\LivewireDatatables\Tests\Classes\SecureDummyTable;
use Arm092\LivewireDatatables\Tests\Models\DummyHasOneModel;
use Arm092\LivewireDatatables\Tests\Models\DummyModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

class LivewireDatatableMutationSecurityTest extends TestCase
{
    #[Test]
    public function deletion_requires_policy_authorization_and_respects_the_builder_scope(): void
    {
        Gate::policy(DummyModel::class, DummyModelPolicy::class);

        $protected = factory(DummyModel::class)->create([
            'category' => 'allowed',
            'subject' => 'protected',
        ]);
        $allowed = factory(DummyModel::class)->create(['category' => 'allowed']);
        $excluded = factory(DummyModel::class)->create(['category' => 'excluded']);

        Livewire::test(SecureDummyTable::class)
            ->call('delete', $protected->getKey())
            ->assertForbidden();

        $this->assertNotNull($protected->fresh());

        Livewire::test(SecureDummyTable::class)
            ->call('delete', $allowed->getKey())
            ->assertOk();

        $this->assertNull($allowed->fresh());

        $this->assertModelNotFound(function () use ($excluded) {
            Livewire::test(SecureDummyTable::class)
                ->call('delete', $excluded->getKey())
                ->assertNotFound();
        });
    }

    #[Test]
    public function editing_requires_policy_scope_and_a_configured_editable_column(): void
    {
        Gate::policy(DummyModel::class, DummyModelPolicy::class);

        $allowed = factory(DummyModel::class)->create([
            'category' => 'allowed',
            'subject' => 'original',
        ]);

        Livewire::test(SecureDummyTable::class)
            ->call('edited', 'changed', 'dummy_models.id', 'category', $allowed->getKey())
            ->assertForbidden();

        $this->assertSame('allowed', $allowed->fresh()->category);

        Livewire::test(SecureDummyTable::class)
            ->call('edited', 'changed', 'dummy_models.id', 'subject', $allowed->getKey())
            ->assertOk();

        $this->assertSame('changed', $allowed->fresh()->subject);
    }

    #[Test]
    public function model_class_cannot_be_changed_by_the_client(): void
    {
        Livewire::test(SecureDummyTable::class)
            ->set('model', DummyHasOneModel::class)
            ->assertForbidden();
    }

    #[Test]
    public function mutations_are_denied_when_the_model_has_no_policy(): void
    {
        $model = factory(DummyModel::class)->create([
            'category' => 'allowed',
            'subject' => 'original',
        ]);

        Livewire::test(SecureDummyTable::class)
            ->call('delete', $model->getKey())
            ->assertForbidden();

        Livewire::test(SecureDummyTable::class)
            ->call('edited', 'changed', 'dummy_models.id', 'subject', $model->getKey())
            ->assertForbidden();

        $this->assertSame('original', $model->fresh()->subject);
    }

    private function assertModelNotFound(callable $callback): void
    {
        try {
            $callback();
        } catch (ModelNotFoundException) {
            $this->addToAssertionCount(1);
        }
    }
}
