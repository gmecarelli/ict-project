<?php

namespace Packages\IctInterface\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class MakeActionHandler extends Command
{
    protected $signature = 'make:action {name : Nome della tabella o del model}';

    protected $description = 'Crea un ActionHandler personalizzato in app/Actions/';

    public function __construct(private Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $className = Str::studly($name) . 'ActionHandler';
        $path = app_path("Actions/{$className}.php");

        if ($this->files->exists($path)) {
            $this->error("ActionHandler {$className} esiste già!");
            return self::FAILURE;
        }

        $stub = $this->resolveStub();

        $stub = str_replace('{{ class }}', $className, $stub);

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $stub);

        $this->info("ActionHandler creato: app/Actions/{$className}.php");

        return self::SUCCESS;
    }

    protected function resolveStub(): string
    {
        // 1. Stub pubblicato nell'app (priorità massima)
        $publishedStub = base_path('stubs/ict-interface/action.custom.stub');
        if ($this->files->exists($publishedStub)) {
            return $this->files->get($publishedStub);
        }

        // 2. Stub del package
        $packageStub = __DIR__ . '/../../Stubs/action.custom.stub';
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

namespace App\Actions;

use Packages\IctInterface\Contracts\BaseActionHandler;

class {{ class }} extends BaseActionHandler
{
    public function beforeStore(string $tableName, array $data, int $formId): ?array
    {
        return $data;
    }

    public function beforeUpdate(string $tableName, array $data, int $formId, int $recordId): ?array
    {
        return $data;
    }

    public function beforeDelete(string $tableName, int $recordId, string $action): bool
    {
        return true;
    }
}
STUB;
    }
}
