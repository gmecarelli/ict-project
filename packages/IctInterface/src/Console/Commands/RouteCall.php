<?php

namespace Packages\IctInterface\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class RouteCall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'route:call 
                                {--uri=: url completa della rotta da chiamare}
                                {--route=: nome della rotta da chiamare}
                                {--facade=: nome del metodo da chiamare}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permette di richiamare una rotta specifica o un metodo di una facade direttamente da console, utile per testare funzionalità senza dover accedere all\'interfaccia web.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('uri')) {
            $this->callUri($this->option('uri'));
        } elseif ($this->option('route')) {
            $this->callRoute($this->option('route'));
        } elseif ($this->option('facade')) {
            $this->callFacade($this->option('facade'));
        } else {
            $this->error('Devi specificare almeno una delle opzioni: --uri, --route o --facade');
            return 1;
        }

        return 0;
    }

    /**
     * Esegue una rotta tramite URI completo.
     * Uso: php artisan route:call --uri="/api/users"
     */
    protected function callUri(string $uri): void
    {
        $request = Request::create($uri, 'GET');
        $response = app()['Illuminate\Contracts\Http\Kernel']->handle($request);
        $this->info($response->getContent());
    }

    /**
     * Risolve una rotta per nome e la esegue.
     * Uso: php artisan route:call --route="users.index"
     */
    protected function callRoute(string $routeName): void
    {
        $url = route($routeName);
        $request = Request::create($url, 'GET');
        $response = app()['Illuminate\Contracts\Http\Kernel']->handle($request);
        $this->info($response->getContent());
    }

    /**
     * Esegue un metodo statico di una Facade.
     * Formato: NomeFacade::nomeMetodo oppure NomeFacade::nomeMetodo(arg1,arg2)
     * Uso: php artisan route:call --facade="Cache::flush"
     *      php artisan route:call --facade="Config::get(app.name)"
     */
    protected function callFacade(string $expression): void
    {
        // Parsing di "Facade::metodo" o "Facade::metodo(arg1,arg2)"
        if (!str_contains($expression, '::')) {
            $this->error("Formato non valido. Usa: [Namespace\\]NomeFacade::nomeMetodo o [Namespace\\]NomeFacade::nomeMetodo(arg1,arg2)");
            return;
        }

        [$facadeName, $methodPart] = explode('::', $expression, 2);

        // Parsing di metodo e argomenti
        if (preg_match('/^(\w+)\((.+)\)$/', $methodPart, $matches)) {
            $method = $matches[1];
            $args = array_map('trim', explode(',', $matches[2]));
        } elseif (preg_match('/^(\w+)\(\)$/', $methodPart, $matches)) {
            $method = $matches[1];
            $args = [];
        } else {
            $method = $methodPart;
            $args = [];
        }

        // Risolve il fully-qualified class name della Facade
        $facadeClass = Str::contains($facadeName, '\\') ? $facadeName : "Illuminate\\Support\\Facades\\{$facadeName}";
        if (!class_exists($facadeClass)) {
            $this->error("Facade [{$facadeName}] non trovata come [{$facadeClass}]");
            return;
        }

        if (!method_exists($facadeClass, $method)) {
            $this->error("Metodo [{$method}] non trovato sulla facade [{$facadeName}]");
            return;
        }

        $result = $facadeClass::$method(...$args);

        if (is_null($result)) {
            $this->info("Eseguito {$facadeName}::{$method}() — nessun valore restituito");
        } elseif (is_scalar($result)) {
            $this->info((string) $result);
        } else {
            $this->info(print_r($result, true));
        }
    }

    protected function getOptions()
    {
        return [
            ['uri', null, InputOption::VALUE_REQUIRED, 'The path of the route to be called', null],
            ['route', null, InputOption::VALUE_REQUIRED, 'The name of the route to be called', null],
            ['facade', null, InputOption::VALUE_REQUIRED, 'The name of the facade method to be called', null],
        ];
    }
}
