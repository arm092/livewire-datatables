<?php

namespace Arm092\LivewireDatatables\Tests;

use Arm092\LivewireDatatables\Column;
use Arm092\LivewireDatatables\ColumnSet;
use Arm092\LivewireDatatables\Tests\Models\DummyModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ColumnSetTest extends TestCase
{
    #[Test]
    public function it_can_generate_an_array_of_columns_from_a_model()
    {
        $model = factory(DummyModel::class)->create();

        $subject = ColumnSet::build($model);

        $this->assertCount(8, $subject->columns());

        $subject->columns()->each(function ($column) {
            $this->assertIsObject($column, Column::class);
        });
    }

    #[Test]
    #[DataProvider('fieldDataProvider')]
    public function it_can_correctly_populate_the_columns_from_the_model($name, $index, $column)
    {
        $model = factory(DummyModel::class)->create();

        $subject = ColumnSet::build($model)->columns();

        $this->assertEquals($name, $subject[$index]->label);
        $this->assertEquals($column, $subject[$index]->name);
        $this->assertEmpty($subject[$index]->callbackFunction);
        $this->assertFalse($subject[$index]->filterable);
        $this->assertEmpty($subject[$index]->scope);
        $this->assertNull($subject[$index]->scopeFilter);
        $this->assertFalse($subject[$index]->hidden);
    }

    public static function fieldDataProvider(): array
    {
        return [
            ['Subject', 0, 'subject'],
            ['Category', 1, 'category'],
            ['Body', 2, 'body'],
            ['Flag', 3, 'flag'],
            ['Expires at', 4, 'expires_at'],
            ['Updated at', 5, 'updated_at'],
            ['Created at', 6, 'created_at'],
            ['Id', 7, 'id'],
        ];
    }

    #[Test]
    public function it_can_exclude_columns()
    {
        $model = factory(DummyModel::class)->create();

        $subject = ColumnSet::build($model)
            ->exclude(['id', 'body'])
            ->columns();

        $this->assertCount(6, $subject);

        $this->assertArrayNotHasKey(7, $subject);
        $this->assertArrayNotHasKey(2, $subject);
    }

    #[Test]
    public function it_can_include_columns()
    {
        $model = factory(DummyModel::class)->create();

        $subject = ColumnSet::build($model)
            ->include(['id', 'body'])
            ->columns();

        $this->assertCount(2, $subject);

        $this->assertEquals('id', $subject[0]->name);
        $this->assertEquals('body', $subject[1]->name);
    }

    #[Test]
    public function it_can_rename_columns()
    {
        $model = factory(DummyModel::class)->create();

        $subject = ColumnSet::build($model)
            ->include(['id|ident', 'body|main text'])
            ->columns();

        $this->assertCount(2, $subject);

        $this->assertEquals('id', $subject[0]->name);
        $this->assertEquals('ident', $subject[0]->label);
        $this->assertEquals('body', $subject[1]->name);
        $this->assertEquals('main text', $subject[1]->label);
    }
}
