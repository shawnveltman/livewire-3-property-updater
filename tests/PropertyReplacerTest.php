<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

beforeEach(function () {
    // Set the disk configuration for the tests
    Config::set('filesystems.disks.base_path', [
        'driver' => 'local',
        'root' => base_path(),
    ]);
});

function setup_temp_directory(): string
{
    $tempDirectory = base_path('tests/temp');
    Storage::disk('base_path')->makeDirectory($tempDirectory);

    // Mock the config to return our temp directory
    config()->set('livewire-3-property-updater.start_directory', $tempDirectory);

    return $tempDirectory;
}

it('converts livewire properties', function () {
    // Setup
    $tempDirectory = setup_temp_directory();
    Storage::disk('base_path')->put($tempDirectory.'/SampleComponent.php', 'public function getFooProperty() {...}');

    // Run the command
    artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);

    // Assert file contents were updated
    $contents = Storage::disk('base_path')->get($tempDirectory.'/SampleComponent.php');
    expect($contents)
        ->toContain('#[Computed]')
        ->toContain('public function foo()');

    // Cleanup
    Storage::disk('base_path')->deleteDirectory($tempDirectory);
});

it('updates properties without overwriting existing use statements', function () {
    // Setup
    $tempDirectory = setup_temp_directory();
    $tempFile = $tempDirectory.'/ExistingUseStatementsComponent.php';
    Storage::disk('base_path')->put($tempFile, <<<'EOD'
<?php

namespace App\Http\Livewire;

use Illuminate\Support\Collection;

class ExistingUseStatementsComponent extends Component
{
    public function getFooProperty() { return 'bar'; }
}
EOD
    );

    // Run the command
    artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);

    // Assert
    $contents = Storage::disk('base_path')->get($tempFile);
    expect($contents)
        ->toContain('use Illuminate\Support\Collection;')
        ->toContain('use Livewire\Attributes\Computed;')
        ->toContain('#[Computed]'.PHP_EOL.'public function foo()');

    // Cleanup
    Storage::disk('base_path')->deleteDirectory($tempDirectory);
});

it('makes no changes to a file without livewire properties', function () {
    // Setup
    $tempDirectory = setup_temp_directory();
    $tempFile = $tempDirectory.'/NoPropertiesComponent.php';
    $originalContents = <<<'EOD'
<?php

namespace App\Http\Livewire;

class NoPropertiesComponent extends Component
{
    public function someRandomMethod() { return true; }
}
EOD;
    Storage::disk('base_path')->put($tempFile, $originalContents);

    // Run the command
    artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);

    // Assert
    $contents = Storage::disk('base_path')->get($tempFile);
    expect($contents)->toBe($originalContents);

    // Cleanup
    Storage::disk('base_path')->deleteDirectory($tempDirectory);
});

it('ignores methods that are not livewire properties', function () {
    // Setup
    $tempDirectory = setup_temp_directory();
    $tempFile = $tempDirectory.'/GetMethodComponent.php';
    Storage::disk('base_path')->put($tempFile, <<<'EOD'
<?php

namespace App\Http\Livewire;

class GetMethodComponent extends Component
{
    public function getSomething() { return true; }
}
EOD
    );

    // Run the command
    artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);

    // Assert
    $contents = Storage::disk('base_path')->get($tempFile);
    expect($contents)
        ->not->toContain('use Livewire\Attributes\Computed;')
        ->toContain('public function getSomething()');

    // Cleanup
    Storage::disk('base_path')->deleteDirectory($tempDirectory);
});

it('correctly updates only the livewire properties in a mixed file', function () {
    // Setup
    $tempDirectory = setup_temp_directory();
    $tempFile = $tempDirectory.'/MixedMethodsComponent.php';
    Storage::disk('base_path')->put($tempFile, <<<'EOD'
<?php

namespace App\Http\Livewire;

class MixedMethodsComponent extends Component
{
    public function getSomething() { return true; }
    public function getFooProperty() { return 'bar'; }
}
EOD
    );

    // Run the command
    artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);

    // Assert
    $contents = Storage::disk('base_path')->get($tempFile);
    expect($contents)
        ->toContain('use Livewire\Attributes\Computed;')
        ->toContain('#[Computed]'.PHP_EOL.'public function foo()')
        ->toContain('public function getSomething()');

    // Cleanup
    Storage::disk('base_path')->deleteDirectory($tempDirectory);
});

it('does not affect files outside the specified directory', function () {
    $outsideDirectory = base_path('tests/outside_temp');
    Storage::disk('base_path')->makeDirectory($outsideDirectory);
    Storage::disk('base_path')->put($outsideDirectory.'/OutsideComponent.php', 'public function getFooProperty() {...}');

    // Mock the config to return a different directory
    config()->set('livewire-3-property-updater.start_directory', base_path('tests/temp'));

    // Run the command
    artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);

    // Assert file contents remain unchanged
    $contents = Storage::disk('base_path')->get($outsideDirectory.'/OutsideComponent.php');
    expect($contents)->toBe('public function getFooProperty() {...}');

    // Cleanup
    Storage::disk('base_path')->deleteDirectory($outsideDirectory);
});

it('shows an error when the base_path disk is not configured', function () {
    // Mock the config to return null for the base_path disk
    config()->set('filesystems.disks.base_path', null);

    $commandResult = artisan('shawnveltman:livewire-3-property-updater');

    // Assert the error message
    $commandResult->expectsOutput("The 'base_path' disk is not configured. Please add it to your filesystems configuration.")
        ->expectsOutput("For more information, check the documentation of the Livewire3PropertyUpdater package.")
        ->assertExitCode(1); // 1 typically represents a general error in CLI applications
});

it('shows an error when the base_path disk does not point to the application base path', function () {
    // Mock the config to set the base_path disk root to a different path
    config()->set('filesystems.disks.base_path.root', base_path('some/other/path'));

    $commandResult = artisan('shawnveltman:livewire-3-property-updater');

    // Assert the error message
    $commandResult->expectsOutput("The 'base_path' disk does not point to the application base path. Please ensure it's correctly configured.")
        ->expectsOutput("For more information, check the documentation of the Livewire3PropertyUpdater package.")
        ->assertExitCode(1); // 1 typically represents a general error in CLI applications
});


