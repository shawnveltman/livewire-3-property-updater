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
        // Check if the 'base_path' disk is configured
        $baseDiskConfig = config('filesystems.disks.base_path');

        if (!$baseDiskConfig) {
            $this->error("The 'base_path' disk is not configured. Please add it to your filesystems configuration.");
            $this->line("For more information, check the documentation of the Livewire3PropertyUpdater package.");
            return 1;
        }

        if ($baseDiskConfig['root'] !== base_path()) {
            $this->error("The 'base_path' disk does not point to the application base path. Please ensure it's correctly configured.");
            $this->line("For more information, check the documentation of the Livewire3PropertyUpdater package.");
            return 1;
        }

        $disk = config('livewire-3-property-updater.disk', 'local'); // Default to 'local' if not specified in config
        $startDirectory = config('livewire-3-property-updater.start_directory');
        $files = Storage::disk($disk)->allFiles($startDirectory);

        foreach ($files as $file) {
            $contents = Storage::disk($disk)->get($file);

            // Add the Computed use statement if not present and if there's any get{Property}Property method in the file
            if (preg_match('/public function get(\w+)Property\(\)/', $contents) && !str_contains($contents, 'use Livewire\Attributes\Computed;')) {
                // Insert the use statement right before the class declaration
                $contents = preg_replace(
                    '/(class\s)/',
                    "use Livewire\Attributes\Computed;\n\n$1",
                    $contents
                );
            }

            // Keep replacing until there are no more matches
            while (preg_match('/public function get(\w+)Property\(\)/', $contents, $matches)) {
                $originalProperty = $matches[1];
                $snakeCaseProperty = Str::snake($originalProperty);

                // Replace old pattern with new one
                $contents = str_replace(
                    "public function get{$originalProperty}Property()",
                    "#[Computed]\npublic function {$snakeCaseProperty}()",
                    $contents
                );
            }

            // Save the updated contents
            Storage::disk($disk)->put($file, $contents);
        }


        return 0;
    }
}
