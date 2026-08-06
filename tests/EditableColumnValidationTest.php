<?php

namespace Arm092\LivewireDatatables\Tests;

use Arm092\LivewireDatatables\Tests\Classes\ValidatedDummyTable;
use Arm092\LivewireDatatables\Tests\Models\DummyModel;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

class EditableColumnValidationTest extends TestCase
{
    #[Test]
    public function it_rejects_an_invalid_editable_value_without_mutating_the_model(): void
    {
        $model = factory(DummyModel::class)->create(['subject' => 'Original']);

        $component = Livewire::test(ValidatedDummyTable::class)
            ->call('edited', 'x', 0, $model->getKey())
            ->assertHasErrors(["editable.{$model->getKey()}.subject" => 'min']);

        $this->assertSame('Original', $model->fresh()->subject);

        $component
            ->call('edited', 'Valid value', 0, $model->getKey())
            ->assertHasNoErrors();

        $this->assertSame('Valid value', $model->fresh()->subject);
    }

    #[Test]
    public function it_supports_model_aware_unique_rules(): void
    {
        $model = factory(DummyModel::class)->create(['subject' => 'Current value']);
        factory(DummyModel::class)->create(['subject' => 'Already used']);

        Livewire::test(ValidatedDummyTable::class)
            ->call('edited', 'Current value', 0, $model->getKey())
            ->assertHasNoErrors()
            ->call('edited', 'Already used', 0, $model->getKey())
            ->assertHasErrors(["editable.{$model->getKey()}.subject" => 'unique']);

        $this->assertSame('Current value', $model->fresh()->subject);
    }

    #[Test]
    public function it_saves_the_validated_value_and_dispatches_the_existing_event(): void
    {
        $model = factory(DummyModel::class)->create(['subject' => 'Original']);

        Livewire::test(ValidatedDummyTable::class)
            ->call('edited', 'Updated value', 0, $model->getKey())
            ->assertHasNoErrors()
            ->assertDispatched('fieldEdited', rowId: $model->getKey(), column: 'subject');

        $this->assertSame('Updated value', $model->fresh()->subject);
    }
}
