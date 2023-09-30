<?php

namespace Shawnveltman\Livewire3PropertyUpdater\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase as Orchestra;
use Shawnveltman\Livewire3PropertyUpdater\Livewire3PropertyUpdaterServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Shawnveltman\\Livewire3PropertyUpdater\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            Livewire3PropertyUpdaterServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_livewire-3-property-updater_table.php.stub';
        $migration->up();
        */
    }

    function setup_file_with_content($filename, $content)
    {
        $tempDirectory = base_path('tests/temp');
        Storage::disk('base_path')->makeDirectory($tempDirectory);
        config()->set('livewire-3-property-updater.start_directory', $tempDirectory);
        Storage::disk('base_path')->put($tempDirectory . '/' . $filename, $content);
        return $tempDirectory . '/' . $filename;
    }
}
