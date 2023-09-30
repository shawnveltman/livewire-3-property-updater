<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

beforeEach(function () {
    // Set the disk configuration for the tests
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

/** @test */
it('transforms computed properties set to null', function () {
    $content = <<<'EOD'
<?php

namespace App\Http\Livewire;

class TestComponent extends Component
{
    #[Computed]
    public function foo() { return 'bar'; }

    public function setToNull() { $this->foo = null; }
}
EOD;

    $filePath = $this->setup_file_with_content('TestComponent.php', $content);
    artisan('shawnveltman:livewire-null-property-updater')->assertExitCode(0);

    $contents = Storage::disk('base_path')->get($filePath);
    expect($contents)->toContain('unset($this->foo);');
});

/** @test */
it('does not transform non-computed properties', function () {
    $content = <<<'EOD'
<?php

namespace App\Http\Livewire;

class TestComponent extends Component
{
    public function randomMethod() { return true; }

    public function setSomethingToNull() { $this->random = null; }
}
EOD;

    $filePath = $this->setup_file_with_content('TestComponent.php', $content);
    artisan('shawnveltman:livewire-null-property-updater')->assertExitCode(0);

    $contents = Storage::disk('base_path')->get($filePath);
    expect($contents)->toContain('$this->random = null;');
});

/** @test */
it('ignores files without computed properties set to null', function () {
    $content = <<<'EOD'
<?php

namespace App\Http\Livewire;

class TestComponent extends Component
{
    #[Computed]
    public function foo() { return 'bar'; }

    public function randomMethod() { return true; }
}
EOD;

    $filePath = $this->setup_file_with_content('TestComponent.php', $content);
    artisan('shawnveltman:livewire-null-property-updater')->assertExitCode(0);

    $contents = Storage::disk('base_path')->get($filePath);
    expect($contents)->toBe($content);
});

/** @test */
it('shows an error when the base_path disk is not configured', function () {
    // Mock the config to return null for the base_path disk
    config()->set('filesystems.disks.base_path', null);

    $commandResult = artisan('shawnveltman:livewire-null-property-updater');

    $commandResult->expectsOutput("The 'base_path' disk is not configured. Please add it to your filesystems configuration.")
        ->expectsOutput('For more information, check the documentation of the Livewire3PropertyUpdater package.')
        ->assertExitCode(1);
});

/** @test */
it('handles multiple spaces when transforming computed properties set to null', function () {
    $content = <<<'EOD'
<?php

namespace App\Http\Livewire;

class TestComponent extends Component
{
    #[Computed]
    public function foo() { return 'bar'; }

    public function setToNullWithSpaces() { $this->foo   =   null; }
}
EOD;

    $filePath = $this->setup_file_with_content('TestComponent.php', $content);
    artisan('shawnveltman:livewire-null-property-updater')->assertExitCode(0);

    $contents = Storage::disk('base_path')->get($filePath);
    expect($contents)->toContain('unset($this->foo);');
});

/** @test */
it('handles multiple properties in a file', function () {
    $content = <<<'EOD'
<?php

namespace App\Http\Livewire;

class TestComponent extends Component
{
    #[Computed]
    public function foo() { return 'bar'; }
    
    #[Computed]
    public function bar() { return 'baz'; }

    public function setMultiplePropertiesToNull() {
        $this->foo = null;
        $this->bar = null;
    }
}
EOD;

    $filePath = $this->setup_file_with_content('TestComponent.php', $content);
    artisan('shawnveltman:livewire-null-property-updater')->assertExitCode(0);

    $contents = Storage::disk('base_path')->get($filePath);
    expect($contents)->toContain('unset($this->foo);')
        ->toContain('unset($this->bar);');
});

/** @test */
it('handles multiple instances of the same property set to null', function () {
    $content = <<<'EOD'
<?php

namespace App\Http\Livewire;

class TestComponent extends Component
{
    #[Computed]
    public function foo() { return 'bar'; }

    public function setToNullMultipleTimes() {
        if(someCondition) {
            $this->foo = null;
        } else {
            $this->foo = null;
        }
    }
}
EOD;

    $filePath = $this->setup_file_with_content('TestComponent.php', $content);
    artisan('shawnveltman:livewire-null-property-updater')->assertExitCode(0);

    $contents = Storage::disk('base_path')->get($filePath);
    $instancesOfUnset = substr_count($contents, 'unset($this->foo);');
    expect($instancesOfUnset)->toBe(2);
});
