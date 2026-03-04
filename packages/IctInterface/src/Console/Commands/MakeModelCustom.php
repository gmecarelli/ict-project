<?php

namespace Packages\IctInterface\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class MakeModelCustom extends Command
{
    protected $signature = 'make:model-custom 
        {name : Nome del model} 
        {--ict : Crea un controller personalizzato} 
        {--c : Crea un controller standard Laravel}';

    protected $description = 'Crea un model con opzione controller personalizzato (IctInterface)';

    public function __construct(private Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');

        // Crea il model con il comando nativo
        $this->call('make:model', ['name' => $name]);

        if ($this->option('ict')) {
            $this->createCustomController($name);
        } elseif ($this->option('c')) {
            $this->call('make:controller', ['name' => "{$name}Controller"]);
        }

        return self::SUCCESS;
    }

    protected function createCustomController(string $name): void
    {
        $controllerName = "{$name}Controller";
        $path = app_path("Http/Controllers/{$controllerName}.php");

        if ($this->files->exists($path)) {
            $this->error("Controller {$controllerName} esiste già!");
            return;
        }

        $stub = $this->resolveStub();

        $stub = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ model }}', '{{ modelVariable }}'],
            [
                'App\\Http\\Controllers',
                $controllerName,
                $name,
                Str::camel($name),
            ],
            $stub
        );

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $stub);

        $this->info("Controller personalizzato creato: app/Http/Controllers/{$controllerName}.php");
    }

    protected function resolveStub(): string
    {
        // 1. Stub pubblicato nell'app (priorità massima)
        $publishedStub = base_path('stubs/ict-interface/controller.custom.stub');
        if ($this->files->exists($publishedStub)) {
            return $this->files->get($publishedStub);
        }

        // 2. Stub del package
        $packageStub = __DIR__ . '/../../Stubs/controller.custom.stub';
        if ($this->files->exists($packageStub)) {
            return $this->files->get($packageStub);
        }

        // 3. Fallback inline
        return $this->defaultStub();
    }

    protected function defaultStub(): string
    {
        return <<<'STUB'
<?php

namespace {{ namespace }};

use Packages\IctInterface\Controllers\IctController;
use App\Models\{{ model }};
use Packages\IctInterface\Traits\LivewireController;

class {{ class }} extends IctController
{
    use LivewireController { 
            index as traitIndex; 
            create as traitCreate; 
            edit as traitEdit; 
        }

    public function __construct()
    {
        parent::__construct();
        $this->__init();
        $this->model = new {{ model }}();
        $this->foreignKey = null;
    }
}
STUB;
    }
}