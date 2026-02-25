#

# **- Installazione manuale del package**

Installare i packages necessari al funzionamento del package IctInterface con composer.

*Se questo passaggio fosse già stato fatto in fase di installazione del progetto si deve saltare*

...

    composer require barryvdh/laravel-dompdf
    composer require maatwebsite/excel
    composer require kris/laravel-form-builder
    composer require hongyukeji/laravel-hook
    composer require fideloper/proxy

...

Copiare la cartella gmecarelli nella cartella vendor del progetto

Aprire il file **composer.json** dell'applicazione e nell'array *autoload* inserire questa riga

...

    "Gmecarelli\\IctInterface\\": "vendor/gmecarelli/ict-interface/src/",

...

Posizionarsi nella cartella vendor/gmecarelli/ict-interface e lanciare il comando

...

    composer update

...

Posizionarsi della directory di root del progetto e lanciare lo stesso comando sopra dalla nuova posizione (cartella del progetto)
#


# **- Configurazione files laravel per installazione Package ICT**

## **Configurazione del Middleware del package**

Nel file app/Http/Kernel.php inserire questa riga nell'array **$routeMiddleware**

...

    'islogged' => \Gmecarelli\IctInterface\Middleware\AuthIct::class,

...

## **Configurazione del ServiceProvider del package**

Dentro file **config/app.php** nell'array **providers**, sotto il commento "Packages Service Providers" inserire:

...

    Gmecarelli\IctInterface\Providers\IctServiceProvider::class,
    Kris\LaravelFormBuilder\FormBuilderServiceProvider::class,
    Maatwebsite\Excel\ExcelServiceProvider::class,
    Barryvdh\DomPDF\ServiceProvider::class,
    Hongyukeji\Hook\HookServiceProvider::class,
    Hongyukeji\Hook\HookBladeServiceProvider::class,

..

Questo permetterà l'inclusione nell'applicazione di tutti i packages installati

Nell'array **aliases** aggiungere

...

        'FormBuilder' => Kris\LaravelFormBuilder\Facades\FormBuilder::class,
        'Excel' => Maatwebsite\Excel\Facades\Excel::class,
        'PDF' => Barryvdh\DomPDF\Facade\Pdf::class,
        'Hook' => Hongyukeji\Hook\Facades\Hooks::class,

...

## **Configurazione per l'attività di logging**

Nel file **config/logging**, all'array channels, aggiungere questi 2 elementi. 

- il canale **log** è per normali log dell'applicazione

- il canale **cronlog** è per i log di eventuali job

...

    'log' => [
            'driver' => 'daily',
            'path' => storage_path('logs/debug.log'),
            'level' => 'debug'
        ],

        'cronlog' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cron_debug.log'),
            'level' => 'debug'
        ],
...

## **Pubblicazione degli assets**

Configurare il file *.env*

Posizionarsi nella cartella root del progetto ed inviare questo comando

...

    php artisan vendor:publish --tag=assets --force

...

Questo provvederà a pubblicare (di fatto copiare) il file nella cartella resources/assets del package nella cartella public/assets
