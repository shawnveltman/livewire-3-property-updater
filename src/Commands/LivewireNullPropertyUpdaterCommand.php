<?php

namespace Shawnveltman\Livewire3PropertyUpdater\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LivewireNullPropertyUpdaterCommand extends Command
{
    public $signature = 'shawnveltman:livewire-null-property-updater';
    public $description = 'Update Livewire computed properties to avoid setting them explicitly to null';

    public function handle()
    {
        if (!$this->check_disk_configuration())
        {
            return 1;
        }

        $disk           = config('livewire-3-property-updater.disk', 'local');
        $startDirectory = config('livewire-3-property-updater.start_directory');
        $files          = Storage::disk($disk)->allFiles($startDirectory);

        foreach ($files as $file)
        {
            $this->handle_file_update($disk, $file);
        }

        return 0;
    }

    private function check_disk_configuration(): bool
    {
        $baseDiskConfig = config('filesystems.disks.base_path');
        if (!$baseDiskConfig)
        {
            $this->error("The 'base_path' disk is not configured. Please add it to your filesystems configuration.");
            $this->line('For more information, check the documentation of the Livewire3PropertyUpdater package.');
            return false;
        }

        if ($baseDiskConfig['root'] !== base_path())
        {
            $this->error("The 'base_path' disk does not point to the application base path. Please ensure it's correctly configured.");
            $this->line('For more information, check the documentation of the Livewire3PropertyUpdater package.');
            return false;
        }

        return true;
    }

    private function handle_file_update(string $disk, string $file): void
    {
        $contents = Storage::disk($disk)->get($file);

        // Find computed properties
        preg_match_all('/#\[(Computed)\]\s*public function (\w+)\(\)/', $contents, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $propertyName = $match[2];
            $searchPattern = "/\\\$this->$propertyName\s*=\s*null;/";
            $replacement = "unset(\$this->$propertyName);";
            $contents = preg_replace($searchPattern, $replacement, $contents);
        }

        // Save the updated contents
        Storage::disk($disk)->put($file, $contents);
    }
}
