<?php

namespace Shawnveltman\Livewire3PropertyUpdater;

use Shawnveltman\Livewire3PropertyUpdater\Commands\Livewire3PropertyUpdaterCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class Livewire3PropertyUpdaterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('livewire-3-property-updater')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_livewire-3-property-updater_table')
            ->hasCommand(Livewire3PropertyUpdaterCommand::class);
    }
}
