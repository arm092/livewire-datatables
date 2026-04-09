<?php

namespace Arm092\LivewireDatatables\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Arm092\LivewireDatatables\Commands\MakeDatatableCommand;
use Arm092\LivewireDatatables\Tests\LivewireTestCase as TestCase;
use PHPUnit\Framework\Attributes\Test;

class MakeDatatableCommandTest extends TestCase
{
    #[Test]
    public function component_is_created_by_make_command()
    {
        Artisan::call('make:livewire-datatable', ['name' => 'foo']);

        $this->assertTrue(File::exists($this->livewireClassesPath('Foo.php')));
    }

    #[Test]
    public function dot_nested_component_is_created_by_make_command()
    {
        Artisan::call('make:livewire-datatable', ['name' => 'foo.bar']);

        $this->assertTrue(File::exists($this->livewireClassesPath('Foo/Bar.php')));
    }

    #[Test]
    public function forward_slash_nested_component_is_created_by_make_command()
    {
        Artisan::call('make:livewire-datatable', ['name' => 'foo/bar']);

        $this->assertTrue(File::exists($this->livewireClassesPath('Foo/Bar.php')));
    }

    #[Test]
    public function multiword_component_is_created_by_make_command()
    {
        Artisan::call('make:livewire-datatable', ['name' => 'foo-bar']);

        $this->assertTrue(File::exists($this->livewireClassesPath('FooBar.php')));
    }

    #[Test]
    public function pascal_case_component_is_automatically_converted_by_make_command()
    {
        Artisan::call('make:livewire-datatable', ['name' => 'FooBar.FooBar']);

        $this->assertTrue(File::exists($this->livewireClassesPath('FooBar/FooBar.php')));
    }

    #[Test]
    public function snake_case_component_is_automatically_converted_by_make_command()
    {
        Artisan::call('make:livewire-datatable', ['name' => 'text_replace']);

        $this->assertTrue(File::exists($this->livewireClassesPath('TextReplace.php')));
    }

    #[Test]
    public function snake_case_component_is_automatically_converted_by_make_command_on_nested_component()
    {
        Artisan::call('make:livewire-datatable', ['name' => 'TextManager.text_replace']);

        $this->assertTrue(File::exists($this->livewireClassesPath('TextManager/TextReplace.php')));
    }

    #[Test]
    public function new_component_model_name_matches_option()
    {
        Artisan::call(MakeDatatableCommand::class, ['name' => 'foo', '--model' => 'bar']);

        $this->assertStringContainsString(
            'public string|null|Model $model = Bar::class;',
            File::get($this->livewireClassesPath('Foo.php'))
        );
    }

    #[Test]
    public function a_component_is_not_created_with_a_reserved_class_name()
    {
        Artisan::call('make:livewire-datatable', ['name' => 'component']);

        $this->assertFalse(File::exists($this->livewireClassesPath('Component.php')));
    }

    protected function livewireClassesPath($path = '')
    {
        return app_path('Livewire/Datatables' . ($path ? '/' . $path : ''));
    }

    protected function livewireViewsPath($path = '')
    {
        return resource_path('views') . '/livewire' . ($path ? '/' . $path : '');
    }
}
