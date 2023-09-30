<?php

namespace Shawnveltman\Livewire3PropertyUpdater\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Livewire3PropertyUpdaterCommand extends Command
{
    public $signature = 'shawnveltman:livewire-3-property-updater';
    public $description = 'My command';

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

        // Add the Computed use statement if not present and if there's any get{Property}Property method in the file
        $does_not_already_have_computed_include = !Str::contains($contents, 'use Livewire\Attributes\Computed;');
        $file_has_property_pattern              = preg_match('/public function get(\w+)Property\(\)/', $contents);
        if ($file_has_property_pattern && $does_not_already_have_computed_include)
        {
            // Insert the use statement right before the class declaration
            $contents = preg_replace(
                '/(class\s)/',
                "use Livewire\Attributes\Computed;\n\n$1",
                $contents
            );
        }

        // Replace old property pattern
        $contents = $this->replace_livewire_properties($contents);

        // Save the updated contents
        Storage::disk($disk)->put($file, $contents);
    }

    private function replace_livewire_properties(string $contents): string
    {
        while (preg_match('/public function get(\w+)Property\(\)/', $contents, $matches)) {
            $originalProperty = $matches[1];
            $transformed_property = $this->get_transformed_property_name($originalProperty);
            $contents = str_replace(
                "public function get{$originalProperty}Property()",
                "#[Computed]\npublic function {$transformed_property}()",
                $contents
            );
        }

        return $contents;
    }


    private function get_transformed_property_name(string $originalProperty): string
    {
        $method_style = config('livewire-3-property-updater.method_name_style', 'snake_case');

        if (strtolower(trim($method_style)) === 'snake_case') {
            return Str::snake($originalProperty);
        }

        // Default to TitleCase (StudlyCase in Laravel's helper functions)
        return Str::studly($originalProperty);
    }

}
