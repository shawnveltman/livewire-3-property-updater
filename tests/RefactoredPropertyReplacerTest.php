<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

beforeEach(function () {
    Config::set('filesystems.disks.base_path', [
        'driver' => 'local',
        'root'   => base_path(),
    ]);
});

afterEach(function () {
    $tempDirectory = base_path('tests/temp');

    // Ensure the 'base_path' disk is configured before proceeding
    if (config('filesystems.disks.base_path') && Storage::disk('base_path')->exists($tempDirectory))
    {
        Storage::disk('base_path')->deleteDirectory($tempDirectory);
    }
});

describe('Livewire Property Updater', function () {

    it('converts livewire properties', function () {
        $content   = 'public function getFooProperty() {...}';
        $file_path = $this->setup_file_with_content('SampleComponent.php', $content);
        artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);
        $contents = Storage::disk('base_path')->get($file_path);
        expect($contents)
            ->toContain('#[Computed]')
            ->toContain('public function foo()');
    });

    it('updates properties without overwriting existing use statements', function () {
        $content   = <<<'EOD'
<?php

namespace App\Http\Livewire;

use Illuminate\Support\Collection;

class ExistingUseStatementsComponent extends Component
{
    public function getFooProperty() { return 'bar'; }
}
EOD;
        $file_path = $this->setup_file_with_content('ExistingUseStatementsComponent.php', $content);
        artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);
        $contents = Storage::disk('base_path')->get($file_path);
        expect($contents)
            ->toContain('use Illuminate\Support\Collection;')
            ->toContain('use Livewire\Attributes\Computed;')
            ->toContain('#[Computed]' . PHP_EOL . 'public function foo()');
    });

    it('makes no changes to a file without livewire properties', function () {
        $content   = <<<'EOD'
<?php

namespace App\Http\Livewire;

class NoPropertiesComponent extends Component
{
    public function someRandomMethod() { return true; }
}
EOD;
        $file_path = $this->setup_file_with_content('NoPropertiesComponent.php', $content);
        artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);
        $contents = Storage::disk('base_path')->get($file_path);
        expect($contents)->toBe($content);
    });

    it('ignores methods that are not livewire properties', function () {
        $content   = <<<'EOD'
<?php

namespace App\Http\Livewire;

class GetMethodComponent extends Component
{
    public function getSomething() { return true; }
}
EOD;
        $file_path = $this->setup_file_with_content('GetMethodComponent.php', $content);
        artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);
        $contents = Storage::disk('base_path')->get($file_path);
        expect($contents)
            ->not->toContain('use Livewire\Attributes\Computed;')
            ->toContain('public function getSomething()');
    });

    it('correctly updates only the livewire properties in a mixed file', function () {
        $content   = <<<'EOD'
<?php

namespace App\Http\Livewire;

class MixedMethodsComponent extends Component
{
    public function getSomething() { return true; }
    public function getFooProperty() { return 'bar'; }
}
EOD;
        $file_path = $this->setup_file_with_content('MixedMethodsComponent.php', $content);
        artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);
        $contents = Storage::disk('base_path')->get($file_path);
        expect($contents)
            ->toContain('use Livewire\Attributes\Computed;')
            ->toContain('#[Computed]' . PHP_EOL . 'public function foo()')
            ->toContain('public function getSomething()');
    });

    it('does not affect files outside the specified directory', function () {
        $outsideDirectory = base_path('tests/outside_temp');
        Storage::disk('base_path')->makeDirectory($outsideDirectory);
        $content = 'public function getFooProperty() {...}';
        Storage::disk('base_path')->put($outsideDirectory . '/OutsideComponent.php', $content);
        config()->set('livewire-3-property-updater.start_directory', base_path('tests/temp'));
        artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);
        $contents = Storage::disk('base_path')->get($outsideDirectory . '/OutsideComponent.php');
        expect($contents)->toBe($content);
        Storage::disk('base_path')->deleteDirectory($outsideDirectory);
    });

    it('shows an error when the base_path disk is not configured', function () {
        // Mock the config to return null for the base_path disk
        config()->set('filesystems.disks.base_path', null);

        $commandResult = artisan('shawnveltman:livewire-3-property-updater');

        // Assert the error message
        $commandResult->expectsOutput("The 'base_path' disk is not configured. Please add it to your filesystems configuration.")
            ->expectsOutput('For more information, check the documentation of the Livewire3PropertyUpdater package.')
            ->assertExitCode(1); // 1 typically represents a general error in CLI applications
    });

    it('shows an error when the base_path disk does not point to the application base path', function () {
        config()->set('filesystems.disks.base_path.root', base_path('some/other/path'));
        $commandResult = artisan('shawnveltman:livewire-3-property-updater');
        $commandResult->expectsOutput("The 'base_path' disk does not point to the application base path. Please ensure it's correctly configured.")
            ->expectsOutput('For more information, check the documentation of the Livewire3PropertyUpdater package.')
            ->assertExitCode(1);
    });

    it('correctly updates all instances of the livewire properties in a file', function () {
        $content   = <<<'EOD'
<?php

namespace App\Http\Livewire;

class MultiplePropertiesComponent extends Component
{
    public function getFirstProperty() { return 'first'; }
    public function someRandomMethod() { return true; }
    public function getSecondProperty() { return 'second'; }
    public function anotherRandomMethod() { return false; }
    public function getLastProperty() { return 'last'; }
}
EOD;
        $file_path = $this->setup_file_with_content('MultiplePropertiesComponent.php', $content);
        artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);
        $contents = Storage::disk('base_path')->get($file_path);
        expect($contents)
            ->toContain('#[Computed]' . PHP_EOL . 'public function first()')
            ->toContain('public function someRandomMethod()')
            ->toContain('#[Computed]' . PHP_EOL . 'public function second()')
            ->toContain('public function anotherRandomMethod()')
            ->toContain('#[Computed]' . PHP_EOL . 'public function last()');
    });

    it('transforms method names to snake_case based on config', function () {
        config()->set('livewire-3-property-updater.method_name_style', 'snake_case');
        $content   = 'public function getSomeRandomProperty() {...}';
        $file_path = $this->setup_file_with_content('SnakeCaseComponent.php', $content);

        artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);

        $contents = Storage::disk('base_path')->get($file_path);
        expect($contents)
            ->toContain('#[Computed]')
            ->toContain('public function some_random()');
    });

    it('transforms method names to StudlyCase based on config', function () {
        // Mock the config to transform method names to StudlyCase
        config()->set('livewire-3-property-updater.method_name_style', 'StudlyCase');

        $content   = 'public function getAnotherRandomProperty() {...}';
        $file_path = $this->setup_file_with_content('StudlyCaseComponent.php', $content);

        // Run the command
        artisan('shawnveltman:livewire-3-property-updater')->assertExitCode(0);

        // Assert file contents were updated
        $contents = Storage::disk('base_path')->get($file_path);
        expect($contents)
            ->toContain('#[Computed]')
            ->toContain('public function AnotherRandom()');
    });

});
