<?php

namespace Arm092\LivewireDatatables\Tests;

use Arm092\LivewireDatatables\Exports\DatatableExport;
use Arm092\LivewireDatatables\Tests\Classes\SecureDummyTable;
use Arm092\LivewireDatatables\Tests\Models\DummyModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\Test;

class LivewireDatatableSecurityTest extends TestCase
{
    #[Test]
    public function self_join_mutations_target_only_the_requested_base_record(): void
    {
        $parent = factory(DummyModel::class)->create(['subject' => 'Parent']);
        $child = factory(DummyModel::class)->create(['subject' => 'Child']);

        $table = new class(1) extends SecureDummyTable {
            public function builder(): \Illuminate\Database\Eloquent\Builder
            {
                return DummyModel::query()->leftJoin('dummy_models as parents', 'parents.id', '=', 'dummy_models.flag');
            }
        };
        $child->update(['flag' => $parent->id]);
        $table->mount();
        $table->edited('Updated child', 1, $child->id);

        $this->assertSame('Parent', $parent->fresh()->subject);
        $this->assertSame('Updated child', $child->fresh()->subject);

        $table->delete($child->id);
        $this->assertNull($child->fresh());
        $this->assertNotNull($parent->fresh());
    }

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

        $this->assertModelNotFound(function () use ($excluded) {
            return Livewire::test(SecureDummyTable::class)
                ->call('edited', 'Not allowed', 1, $excluded->getKey());
        });
    }

    #[Test]
    public function it_deletes_only_records_inside_the_builder_scope(): void
    {
        $excluded = factory(DummyModel::class)->create(['category' => 'excluded']);

        $this->assertModelNotFound(function () use ($excluded) {
            return Livewire::test(SecureDummyTable::class)
                ->call('delete', $excluded->getKey());
        });
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

    #[Test]
    public function it_generates_an_xlsx_workbook_through_the_excel_export_contract(): void
    {
        $export = new DatatableExport(collect([
            (object) ['Name' => 'Example', 'Value' => 42],
        ]));

        $contents = Excel::raw($export, ExcelWriter::XLSX);

        $this->assertStringStartsWith('PK', $contents);
        $this->assertGreaterThan(1000, strlen($contents));
    }

    private function assertModelNotFound(callable $callback): void
    {
        try {
            $callback()->assertStatus(404);
        } catch (ModelNotFoundException) {
            $this->addToAssertionCount(1);
        }
    }
}
