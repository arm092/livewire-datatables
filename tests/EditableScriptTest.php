<?php

namespace Arm092\LivewireDatatables\Tests;

use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\Test;

class EditableScriptTest extends TestCase
{
    #[Test]
    public function it_serves_the_csp_safe_editable_alpine_component(): void
    {
        $this->get(route('livewire-datatables.editable-script'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertSee("Alpine.data('livewireDatatableEditable'", false);
    }

    #[Test]
    public function editable_view_uses_only_simple_csp_safe_alpine_expressions(): void
    {
        $view = view('datatables::editable', [
            'rowId' => 1,
            'column' => 'slug',
            'columnIndex' => 2,
            'value' => 'example',
            'errors' => new ViewErrorBag,
        ])->render();

        $this->assertStringContainsString('x-data="livewireDatatableEditable"', $view);
        $this->assertStringContainsString('x-on:click="startEditing"', $view);
        $this->assertStringContainsString('x-bind:class="editedClass"', $view);
        $this->assertStringContainsString('x-show="edit" style="display: none"', $view);
        $this->assertStringNotContainsString('x-data="{', $view);
        $this->assertStringNotContainsString('$nextTick(()', $view);
    }
}
