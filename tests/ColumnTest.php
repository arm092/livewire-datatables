<?php

namespace Arm092\LivewireDatatables\Tests;

use Arm092\LivewireDatatables\Column;
use Arm092\LivewireDatatables\DateColumn;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ColumnTest extends TestCase
{
    #[Test]
    public function it_can_generate_a_column_from_a_table_column()
    {
        $subject = Column::name('table.column');

        $this->assertEquals('table.column', $subject->name);
        $this->assertEquals('Column', $subject->label);
    }

    #[Test]
    public function it_can_generate_a_column_from_a_scope()
    {
        $subject = Column::scope('fakeScope', 'Alias');

        $this->assertEquals('fakeScope', $subject->scope);
        $this->assertEquals('Alias', $subject->label);
    }

    #[Test]
    public function it_can_generate_a_delete_column()
    {
        $subject = Column::delete();

        $this->assertEquals(['id'], $subject->additionalSelects);
        $this->assertEquals('', $subject->label);
        $this->assertIsCallable($subject->callbackFunction);
    }

    #[Test]
    #[DataProvider('settersDataProvider')]
    public function it_sets_properties_and_parameters($method, $value, $attribute)
    {
        $subject = Column::name('table.column')->$method($value);

        $this->assertEquals($value, $subject->$attribute);
    }

    public static function settersDataProvider(): array
    {
        return [
            ['label', 'Bob Vance', 'label'],
            ['searchable', true, 'searchable'],
            ['filterable', ['Michael Scott', 'Dwight Shrute'], 'filterable'],
            ['hide', true, 'hidden'],
            ['additionalSelects', ['hello world'], 'additionalSelects'],
        ];
    }

    #[Test]
    public function it_returns_an_array_from_column()
    {
        $subject = Column::name('table.column')
            ->label('Column')
            ->filterable(['A', 'B', 'C'])
            ->hide()
            ->linkTo('model', 8)
            ->toArray();

        $this->assertSame('string', $subject['type']);
        $this->assertSame('table.column', $subject['name']);
        $this->assertNull($subject['base']);
        $this->assertSame('Column', $subject['label']);
        $this->assertSame(['A', 'B', 'C'], $subject['filterable']);
        $this->assertTrue($subject['hidden']);
        $this->assertIsCallable($subject['callbackFunction']);
        $this->assertIsCallable($subject['exportCallback']);
        $this->assertSame('', $subject['raw']);
        $this->assertNull($subject['sort']);
        $this->assertFalse($subject['defaultSort']);
        $this->assertFalse($subject['searchable']);
        $this->assertTrue($subject['sortable']);
        $this->assertSame([], $subject['params']);
        $this->assertSame([], $subject['additionalSelects']);
        $this->assertNull($subject['scope']);
        $this->assertNull($subject['scopeFilter']);
        $this->assertNull($subject['select']);
        $this->assertSame('group_concat', $subject['aggregate']);
        $this->assertSame('left', $subject['headerAlign']);
        $this->assertFalse($subject['preventExport']);
        $this->assertTrue(! array_key_exists('width', $subject) || $subject['width'] === '' || $subject['width'] === null);
        $this->assertNull($subject['filterOn']);
        $this->assertNull($subject['hideable']);
        $this->assertSame(0, $subject['index']);
        $this->assertSame('left', $subject['contentAlign']);
        $this->assertFalse($subject['summary']);
        $this->assertTrue($subject['wrappable']);
    }

    #[Test]
    public function it_returns_an_array_from_raw()
    {
        $subject = DateColumn::raw('SELECT column FROM table AS table_column')
            ->filterable()
            ->defaultSort('asc')
            ->format('yyy-mm-dd')
            ->toArray();

        $this->assertSame('date', $subject['type']);
        $this->assertSame('table_column', $subject['name']);
        $this->assertNull($subject['base']);
        $this->assertSame('table_column', $subject['label']);
        $this->assertTrue($subject['filterable']);
        $this->assertFalse($subject['hidden']);
        $this->assertIsCallable($subject['callbackFunction']);
        $this->assertSame('SELECT column FROM table AS table_column', $subject['raw']);
        $this->assertSame('SELECT column FROM table', $subject['sort']);
        $this->assertSame('asc', $subject['defaultSort']);
        $this->assertFalse($subject['searchable']);
        $this->assertTrue($subject['sortable']);
        $this->assertSame([], $subject['params']);
        $this->assertSame([], $subject['additionalSelects']);
        $this->assertNull($subject['scope']);
        $this->assertNull($subject['scopeFilter']);
        $this->assertEquals(DB::raw('SELECT column FROM table'), $subject['select']);
        $this->assertSame('left', $subject['headerAlign']);
        $this->assertFalse($subject['preventExport']);
        $this->assertTrue(! array_key_exists('width', $subject) || $subject['width'] === '' || $subject['width'] === null);
        $this->assertNull($subject['exportCallback']);
        $this->assertNull($subject['filterOn']);
        $this->assertNull($subject['hideable']);
        $this->assertSame(0, $subject['index']);
        $this->assertSame('left', $subject['contentAlign']);
        $this->assertFalse($subject['summary']);
        $this->assertTrue($subject['wrappable']);
    }

    #[Test]
    public function it_returns_width_property_from_column()
    {
        $subject = Column::name('table.column')
            ->label('Column')
            ->width('1em')
            ->toArray();

        $this->assertSame('string', $subject['type']);
        $this->assertSame('table.column', $subject['name']);
        $this->assertSame('Column', $subject['label']);
        $this->assertFalse($subject['filterable']);
        $this->assertFalse($subject['hidden']);
        $this->assertNull($subject['callbackFunction']);
        $this->assertSame('', $subject['raw']);
        $this->assertFalse($subject['defaultSort']);
        $this->assertFalse($subject['searchable']);
        $this->assertTrue($subject['sortable']);
        $this->assertSame('group_concat', $subject['aggregate']);
        $this->assertSame('left', $subject['headerAlign']);
        $this->assertFalse($subject['preventExport']);
        $this->assertSame('1em', $subject['width']);
        $this->assertNull($subject['exportCallback']);
        $this->assertSame(0, $subject['index']);
        $this->assertSame('left', $subject['contentAlign']);
        $this->assertFalse($subject['summary']);
        $this->assertTrue($subject['wrappable']);
    }

    public function check_invalid_width_unit_not_returning_value()
    {
        $subject = Column::name('table.column')
            ->label('Column')
            ->width('1laravel')
            ->toArray();

        $this->assertEquals([
            'type' => 'string',
            'name' => 'table.column',
            'base' => null,
            'label' => 'Column',
            'filterable' => null,
            'hidden' => null,
            'callbackFunction' => null,
            'raw' => null,
            'sort' => null,
            'defaultSort' => null,
            'searchable' => null,
            'params' => [],
            'additionalSelects' => [],
            'scope' => null,
            'scopeFilter' => null,
            'filterView' => null,
            'select' => null,
            'joins' => null,
            'aggregate' => 'group_concat',
            'headerAlign' => 'left',
            'preventExport' => null,
            'width' => null,
        ], $subject);
    }

    public function check_adding_px_to_numeric_width_input()
    {
        $subject = Column::name('table.column')
            ->label('Column')
            ->width('5')
            ->toArray();

        $this->assertEquals([
            'type' => 'string',
            'name' => 'table.column',
            'base' => null,
            'label' => 'Column',
            'filterable' => null,
            'hidden' => null,
            'callbackFunction' => null,
            'raw' => null,
            'sort' => null,
            'defaultSort' => null,
            'searchable' => null,
            'params' => [],
            'additionalSelects' => [],
            'scope' => null,
            'scopeFilter' => null,
            'filterView' => null,
            'select' => null,
            'joins' => null,
            'aggregate' => 'group_concat',
            'headerAlign' => 'left',
            'preventExport' => null,
            'width' => '5px',
        ], $subject);
    }
}
