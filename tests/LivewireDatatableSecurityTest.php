<?php

namespace Arm092\LivewireDatatables\Tests;

use Arm092\LivewireDatatables\Exports\DatatableExport;
use Arm092\LivewireDatatables\Tests\Classes\SecureDummyTable;
use Arm092\LivewireDatatables\Tests\Models\DummyModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\Test;

class LivewireDatatableSecurityTest extends TestCase
{
    #[Test]
    public function it_escapes_database_values_but_keeps_developer_views_renderable(): void
    {
        factory(DummyModel::class)->create([
            'category' => 'allowed',
            'subject' => 'Safe subject',
            'body' => '<script>alert("xss")</script>',
        ]);

        Livewire::test(SecureDummyTable::class)
            ->assertSeeHtml('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->assertDontSeeHtml('<script>alert("xss")</script>')
            ->assertSeeHtml('wire:click="delete');
    }

    #[Test]
    public function it_updates_only_configured_editable_columns_inside_the_builder_scope(): void
    {
        $allowed = factory(DummyModel::class)->create(['category' => 'allowed']);
        $excluded = factory(DummyModel::class)->create(['category' => 'excluded']);

        Livewire::test(SecureDummyTable::class)
            ->call('edited', 'Updated', 1, $allowed->getKey());

        $this->assertSame('Updated', $allowed->fresh()->subject);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(SecureDummyTable::class)
            ->call('edited', 'Not allowed', 1, $excluded->getKey());
    }

    #[Test]
    public function it_deletes_only_records_inside_the_builder_scope(): void
    {
        $excluded = factory(DummyModel::class)->create(['category' => 'excluded']);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(SecureDummyTable::class)
            ->call('delete', $excluded->getKey());
    }

    #[Test]
    public function it_binds_selected_and_pinned_identifiers_in_queries(): void
    {
        factory(DummyModel::class)->create(['category' => 'allowed']);

        $component = Livewire::test(SecureDummyTable::class)
            ->set('selected', ['1) OR 1=1 --']);

        $this->assertCount(0, $component->instance()->getExportResultsSet());

        $pinnedComponent = Livewire::test(SecureDummyTable::class)
            ->set('pinnedRecords', ['1) OR 1=1 --']);

        $this->assertCount(1, $pinnedComponent->instance()->getQuery()->get());
    }

    #[Test]
    public function it_keeps_allowed_pinned_records_visible_while_filtering(): void
    {
        factory(DummyModel::class)->create([
            'category' => 'allowed',
            'subject' => 'Matches search',
        ]);
        $pinned = factory(DummyModel::class)->create([
            'category' => 'allowed',
            'subject' => 'Pinned subject',
        ]);

        Livewire::test(SecureDummyTable::class)
            ->set('pinnedRecords', [$pinned->getKey()])
            ->set('search', 'Matches')
            ->assertSee('Matches search')
            ->assertSee('Pinned subject');
    }

    #[Test]
    public function it_exports_formula_like_strings_as_text(): void
    {
        $spreadsheet = new Spreadsheet();
        $cell = $spreadsheet->getActiveSheet()->getCell('A1');
        $export = new DatatableExport(collect());

        $export->bindValue($cell, '=HYPERLINK("https://example.test")');

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertSame('=HYPERLINK("https://example.test")', $cell->getValue());
    }
}
