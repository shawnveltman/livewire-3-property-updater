<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

function setup_temp_directory(): string {
    $tempDirectory = base_path('tests/temp');
    Storage::disk('local')->makeDirectory($tempDirectory);

    // Mock the config to return our temp directory
    config()->set('livewire-3-property-updater.start_directory', $tempDirectory);

    return $tempDirectory;
}

it('converts livewire properties', function () {
    // Setup
    $tempDirectory = setup_temp_directory();
    Storage::disk('local')->put($tempDirectory . '/SampleComponent.php', 'public function getFooProperty() {...}');

    // Run the command
    artisan('livewire-3-property-updater')->assertExitCode(0);

    // Assert file contents were updated
    $contents = Storage::disk('local')->get($tempDirectory . '/SampleComponent.php');
    expect($contents)
        ->toContain('#[Computed]')
        ->toContain('public function foo()');

    // Cleanup
    Storage::disk('local')->deleteDirectory($tempDirectory);
});

it('updates properties without overwriting existing use statements', function () {
    // Setup
    $tempDirectory = setup_temp_directory();
    $tempFile = $tempDirectory . '/ExistingUseStatementsComponent.php';
    Storage::disk('local')->put($tempFile, <<<'EOD'
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
    artisan('livewire-3-property-updater')->assertExitCode(0);

    // Assert
    $contents = Storage::disk('local')->get($tempFile);
    expect($contents)
        ->toContain('use Illuminate\Support\Collection;')
        ->toContain('use Livewire\Attributes\Computed;')
        ->toContain('#[Computed]' . PHP_EOL . 'public function foo()');

    // Cleanup
    Storage::disk('local')->deleteDirectory($tempDirectory);
});

it('makes no changes to a file without livewire properties', function () {
    // Setup
    $tempDirectory = setup_temp_directory();
    $tempFile = $tempDirectory . '/NoPropertiesComponent.php';
    $originalContents = <<<'EOD'
<?php

namespace App\Http\Livewire;

class NoPropertiesComponent extends Component
{
    public function someRandomMethod() { return true; }
}
EOD;
    Storage::disk('local')->put($tempFile, $originalContents);

    // Run the command
    artisan('livewire-3-property-updater')->assertExitCode(0);

    // Assert
    $contents = Storage::disk('local')->get($tempFile);
    expect($contents)->toBe($originalContents);

    // Cleanup
    Storage::disk('local')->deleteDirectory($tempDirectory);
});

it('ignores methods that are not livewire properties', function () {
    // Setup
    $tempDirectory = setup_temp_directory();
    $tempFile = $tempDirectory . '/GetMethodComponent.php';
    Storage::disk('local')->put($tempFile, <<<'EOD'
<?php

namespace App\Http\Livewire;

class GetMethodComponent extends Component
{
    public function getSomething() { return true; }
}
EOD
    );

    // Run the command
    artisan('livewire-3-property-updater')->assertExitCode(0);

    // Assert
    $contents = Storage::disk('local')->get($tempFile);
    expect($contents)
        ->not->toContain('use Livewire\Attributes\Computed;')
        ->toContain('public function getSomething()');

    // Cleanup
    Storage::disk('local')->deleteDirectory($tempDirectory);
});

it('correctly updates only the livewire properties in a mixed file', function () {
    // Setup
    $tempDirectory = setup_temp_directory();
    $tempFile = $tempDirectory . '/MixedMethodsComponent.php';
    Storage::disk('local')->put($tempFile, <<<'EOD'
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
    artisan('livewire-3-property-updater')->assertExitCode(0);

    // Assert
    $contents = Storage::disk('local')->get($tempFile);
    expect($contents)
        ->toContain('use Livewire\Attributes\Computed;')
        ->toContain('#[Computed]' . PHP_EOL . 'public function foo()')
        ->toContain('public function getSomething()');

    // Cleanup
    Storage::disk('local')->deleteDirectory($tempDirectory);
});

it('does not affect files outside the specified directory', function () {
    $outsideDirectory = base_path('tests/outside_temp');
    Storage::disk('local')->makeDirectory($outsideDirectory);
    Storage::disk('local')->put($outsideDirectory . '/OutsideComponent.php', 'public function getFooProperty() {...}');

    // Mock the config to return a different directory
    config()->set('livewire-3-property-updater.start_directory', base_path('tests/temp'));

    // Run the command
    artisan('livewire-3-property-updater')->assertExitCode(0);

    // Assert file contents remain unchanged
    $contents = Storage::disk('local')->get($outsideDirectory . '/OutsideComponent.php');
    expect($contents)->toBe('public function getFooProperty() {...}');

    // Cleanup
    Storage::disk('local')->deleteDirectory($outsideDirectory);
});

