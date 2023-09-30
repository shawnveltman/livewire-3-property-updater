<?php

namespace Shawnveltman\Livewire3PropertyUpdater\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Livewire3PropertyUpdaterCommand extends Command
{
    public $signature = 'shawnveltman:livewire-3-property-updater';

    public $description = 'My command';

    public function handle(): void
    {
        $disk = config('livewire-3-property-updater.disk', 'local'); // Default to 'local' if not specified in config
        $startDirectory = config('livewire-3-property-updater.start_directory');
        $files = Storage::disk($disk)->allFiles($startDirectory);

        foreach ($files as $file) {
            $contents = Storage::disk($disk)->get($file);

            // ... rest of the logic ...

            // Check for the Computed attribute use statement
            if (preg_match('/public function get(\w+)Property\(\)/', $contents, $matches)) {
                $originalProperty = $matches[1];
                $snakeCaseProperty = Str::snake($originalProperty);

                // Check for the Computed attribute use statement
                if (!str_contains($contents, 'use Livewire\Attributes\Computed;')) {
                    // Insert the use statement right before the class declaration
                    $contents = preg_replace(
                        '/(class\s)/',
                        "use Livewire\Attributes\Computed;\n\n$1",
                        $contents
                    );
                }

                // Replace old pattern with new one
                $contents = str_replace(
                    "public function get{$originalProperty}Property()",
                    "#[Computed]\npublic function {$snakeCaseProperty}()",
                    $contents
                );

                Storage::disk($disk)->put($file, $contents);
            }
        }
    }
}
