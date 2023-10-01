<?php

namespace Shawnveltman\Livewire3PropertyUpdater\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DispatchIdentifierCommand extends Command
{
    public $signature = 'shawnveltman:dispatch-identifier';

    public $description = 'Identify and print occurrences of dispatch() method with specific pattern';

    public array $responses = [];

    public string $response_string = '';

    public bool $should_print = true;

    public function handle()
    {
        if (! $this->check_disk_configuration()) {
            return 1;
        }

        $disk = config('livewire-3-property-updater.disk', 'local');
        $startDirectory = config('livewire-3-property-updater.start_directory');
        $ignoreEvents = config('livewire-3-property-updater.ignore_event_names', []);
        $files = Storage::disk($disk)->allFiles($startDirectory);

        foreach ($files as $file) {
            $this->identify_dispatch_pattern($disk, $file, $ignoreEvents);
        }

        $response_string = implode(PHP_EOL, $this->responses);
        $this->response_string = $response_string;
        if ($this->should_print) {
            echo $response_string;
        }

        return 0;
    }

    private function check_disk_configuration(): bool
    {
        $baseDiskConfig = config('filesystems.disks.base_path');
        if (! $baseDiskConfig) {
            $this->error("The 'base_path' disk is not configured. Please add it to your filesystems configuration.");
            $this->line('For more information, check the documentation of the Livewire3PropertyUpdater package.');

            return false;
        }

        if ($baseDiskConfig['root'] !== base_path()) {
            $this->error("The 'base_path' disk does not point to the application base path. Please ensure it's correctly configured.");
            $this->line('For more information, check the documentation of the Livewire3PropertyUpdater package.');

            return false;
        }

        return true;
    }

    private function identify_dispatch_pattern(string $disk, string $file, array $ignoreEvents): void
    {
        $contents = Storage::disk($disk)->get($file);
        $lines = explode(PHP_EOL, $contents);

        foreach ($lines as $lineNumber => $line) {
            if (preg_match("/dispatch\(\s*'([^']+)'\s*,\s*(?!.*?:)/", $line, $matches)) {
                $eventName = $matches[1];

                if (! in_array($eventName, $ignoreEvents)) {
                    $output_string = "{$file}:".($lineNumber + 1);  // Using $file directly
                    $this->responses[] = $output_string;
                }
            }
        }
    }
}
