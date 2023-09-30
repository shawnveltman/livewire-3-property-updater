<?php

namespace Shawnveltman\Livewire3PropertyUpdater\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Shawnveltman\Livewire3PropertyUpdater\Livewire3PropertyUpdater
 */
class Livewire3PropertyUpdater extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Shawnveltman\Livewire3PropertyUpdater\Livewire3PropertyUpdater::class;
    }
}
