<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Shawnveltman\Livewire3PropertyUpdater\Commands\DispatchIdentifierCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use function Pest\Laravel\artisan;
use function PHPUnit\Framework\assertTrue;

beforeEach(function () {
    Config::set('filesystems.disks.base_path', [
        'driver' => 'local',
        'root'   => base_path(),
    ]);
});

afterEach(function () {
    $tempDirectory = base_path('tests/temp');

    if (Storage::disk('base_path')->exists($tempDirectory))
    {
        Storage::disk('base_path')->deleteDirectory($tempDirectory);
    }
});

/** @test */
it('prints the correct file and line number for dispatch pattern', function () {
    $content = <<<'EOD'
<?php

namespace App\Http\Livewire;

class TestComponent extends Component
{
    public function someMethod() {
        $this->dispatch('eventName', ['param1' => 'value1']);
    }
}
EOD;

    $filePath = $this->setup_file_with_content('TestComponent.php', $content);

    // Execute the command and capture its output
    $command = new DispatchIdentifierCommand();
    $command->should_print = false;
    $command->handle();

    // Verify that the output contains the expected file path and line number
    $response_string = $command->response_string;
    assertTrue(Str::contains('/' . $response_string, $filePath . ':8'));
});

/** @test */
it('does not print for files without the dispatch pattern', function () {
    $content = <<<'EOD'
<?php

namespace App\Http\Livewire;

class TestComponent extends Component
{
    public function someMethod() {
        return true;
    }
}
EOD;

    $filePath = $this->setup_file_with_content('TestComponent.php', $content);

    $commandResult = artisan('shawnveltman:dispatch-identifier');
    $commandResult->doesntExpectOutput($filePath . ':6')
        ->assertExitCode(0);
});

/** @test */
it('does not print events on the ignore list', function () {
    $content = <<<'EOD'
<?php

namespace App\Http\Livewire;

class TestComponent extends Component
{
    public function someMethod() {
        $this->dispatch('ignoreThisEvent', ['param1' => 'value1']);
    }
}
EOD;

    config()->set('livewire-3-property-updater.ignore_event_names', ['ignoreThisEvent']);

    $filePath = $this->setup_file_with_content('TestComponent.php', $content);

    $commandResult = artisan('shawnveltman:dispatch-identifier');
    $commandResult->doesntExpectOutput($filePath . ':6')
        ->assertExitCode(0);
});
