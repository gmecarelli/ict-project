# Analisi funzionalità Grafici — IctInterface v2.0

Data analisi: 2026-03-02

---

## 1) Obiettivo

Aggiungere al package IctInterface un servizio per la produzione di grafici (line e bar) a partire da dati DB, con asse X temporale (mesi, anni). L'applicazione recupera i dati e li passa al package in un formato standard; il package si occupa del rendering.

Priorità: utilizzare funzionalità native Livewire. Package esterno solo se necessario.

---

## 2) Valutazione tecnologie

### 2.1 Livewire nativo — Non dispone di componente chart

Livewire 3 non include componenti chart built-in. La documentazione ufficiale indica di collegare una libreria JS di charting e gestire i dati via Livewire, ma non fornisce un componente pronto.

### 2.2 Flux Chart — Scartato

Flux offre un componente `<flux:chart>` leggero, zero-dependency, con supporto line, bar, area, multi-series e tooltip. È la soluzione più integrata con Livewire. Tuttavia richiede stack Tailwind CSS e licenza Flux Pro. Dato che il package usa Bootstrap 5.3 e non ha Tailwind, Flux è **incompatibile** con lo stack attuale. Resta opzione futura se si migra a Flux (fase 11).

### 2.3 asantibanez/livewire-charts — Raccomandato ✅

Package open source (MIT), attivamente mantenuto (v4.2.0, dicembre 2025). Basato su ApexCharts JS, compatibile Livewire 3, agnostico rispetto al framework CSS (funziona con Bootstrap 5.3). Supporta i tipi richiesti:

- `LineChartModel` / `MultiLineChartModel` — grafici linea singola e multi-serie
- `ColumnChartModel` / `MultiColumnChartModel` — grafici barre singola e multi-serie
- Reattività via `reactiveKey()`
- Click events, tooltip, animazioni, colori custom, JSON config per opzioni ApexCharts avanzate

**Dipendenze:** `livewire/livewire ^3.0`, ApexCharts (incluso nel bundle JS del package).

### 2.4 Alternativa: Chart.js diretto + Alpine

Possibile ma richiede scrivere manualmente l'integrazione Livewire→JS (dispatch eventi, `wire:ignore`, init/update canvas). Più effort senza vantaggi rispetto a livewire-charts che fa già questo lavoro.

### Scelta: `asantibanez/livewire-charts` v4.x

---

## 3) Architettura proposta

```
┌─────────────────────────────────────┐
│          APPLICAZIONE (app/)        │
│                                     │
│  Controller / Livewire Component    │
│  ↓ prepara i dati dal DB            │
│  ↓ formatta secondo struttura std   │
│  ↓ chiama ChartService              │
└──────────────┬──────────────────────┘
               │ array PHP standard
               ▼
┌─────────────────────────────────────┐
│     PACKAGE (IctInterface)          │
│                                     │
│  Services/ChartService.php          │
│  - buildLineChart($config)          │
│  - buildMultiLineChart($config)     │
│  - buildBarChart($config)           │
│  - buildMultiBarChart($config)      │
│                                     │
│  Livewire/ChartComponent.php        │
│  - riceve config, produce il model  │
│  - rendering via livewire-charts    │
│                                     │
│  views/livewire/chart.blade.php     │
└─────────────────────────────────────┘
```

L'applicazione è responsabile solo di:
1. Eseguire la query DB
2. Formattare il risultato nella struttura dati standard (vedi sezione 4)
3. Passare i dati al componente Livewire del package

Il package è responsabile di:
1. Ricevere i dati formattati
2. Costruire il chart model (LineChartModel, MultiLineChartModel, ecc.)
3. Renderizzare il grafico

---

## 4) Struttura dati standard

### 4.1 Principio

Il dato è sempre un **array di array associativi** (array di "righe"), dove ogni riga ha una chiave per l'etichetta temporale (label) e una o più chiavi per i valori numerici.

### 4.2 Serie singola (una linea o un gruppo di barre)

Caso d'uso: andamento fatturato mensile, conteggio ordini per mese, ecc.

```php
$chartData = [
    'title'  => 'Fatturato mensile 2025',
    'type'   => 'line',  // oppure 'bar'
    'labels' => ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
    'series' => [
        [
            'name'  => 'Fatturato',
            'color' => '#4d7496',
            'data'  => [12000, 15000, 13500, 16000, 14200, 17800, 19000, 18500, 20000, 21000, 19500, 22000],
        ],
    ],
];
```

**Vincoli:**
- `labels` e `data` devono avere lo stesso numero di elementi
- `labels` sono stringhe (etichette asse X)
- `data` sono valori numerici (int o float)
- `color` è un colore esadecimale; se omesso il package assegna un default
- `type` accetta: `'line'`, `'bar'`

### 4.3 Multi-serie (confronto di più dati nel tempo)

Caso d'uso: confronto fatturato 2024 vs 2025, confronto vendite per categoria, ecc.

```php
$chartData = [
    'title'  => 'Confronto fatturato 2024 vs 2025',
    'type'   => 'line',  // oppure 'bar'
    'labels' => ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
    'series' => [
        [
            'name'  => '2024',
            'color' => '#90cdf4',
            'data'  => [10000, 12000, 11000, 13000, 12500, 15000, 16000, 15500, 17000, 18000, 17500, 19000],
        ],
        [
            'name'  => '2025',
            'color' => '#4d7496',
            'data'  => [12000, 15000, 13500, 16000, 14200, 17800, 19000, 18500, 20000, 21000, 19500, 22000],
        ],
    ],
];
```

### 4.4 Asse X con anni

```php
$chartData = [
    'title'  => 'Fatturato annuale',
    'type'   => 'bar',
    'labels' => ['2020', '2021', '2022', '2023', '2024', '2025'],
    'series' => [
        [
            'name'  => 'Fatturato',
            'color' => '#4d7496',
            'data'  => [180000, 210000, 250000, 280000, 310000, 350000],
        ],
    ],
];
```

### 4.5 Struttura formale

```
$chartData = [
    'title'   => string,                    // titolo del grafico
    'type'    => 'line' | 'bar',            // tipo grafico
    'labels'  => string[],                  // etichette asse X (N elementi)
    'series'  => [                          // 1 o più serie
        [
            'name'  => string,              // nome della serie (legenda)
            'color' => ?string,             // colore hex (opzionale)
            'data'  => number[],            // valori numerici (N elementi)
        ],
        // ... altre serie
    ],
    'options' => ?[                         // opzionale, override avanzati
        'animated'    => bool,              // default true
        'height'      => string,            // default '24rem'
        'legend'      => bool,              // default true se multi-serie
        'yAxisFormat' => ?string,           // es. '€ {value}', '{value} %'
    ],
];
```

---

## 5) Esempio pratico — dall'app al grafico

### 5.1 Preparazione dati nell'applicazione

```php
// app/Http/Controllers/DashboardController.php

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Query: fatturato mensile per anno corrente
        $rows = DB::table('invoices')
            ->selectRaw('MONTH(invoice_date) as mese, SUM(total) as totale')
            ->whereYear('invoice_date', now()->year)
            ->groupByRaw('MONTH(invoice_date)')
            ->orderByRaw('MONTH(invoice_date)')
            ->get();

        $labels = [];
        $data = [];
        $mesi = ['Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];

        foreach ($rows as $row) {
            $labels[] = $mesi[$row->mese - 1];
            $data[] = (float) $row->totale;
        }

        $chartData = [
            'title'  => 'Fatturato ' . now()->year,
            'type'   => 'line',
            'labels' => $labels,
            'series' => [
                ['name' => 'Fatturato', 'color' => '#4d7496', 'data' => $data],
            ],
        ];

        return view('dashboard', compact('chartData'));
    }
}
```

### 5.2 Utilizzo nella vista Blade

```blade
{{-- resources/views/dashboard.blade.php --}}

<livewire:ict-chart :config="$chartData" />
```

### 5.3 Esempio multi-serie con confronto anni

```php
// Query confronto 2024 vs 2025
$years = [2024, 2025];
$series = [];

foreach ($years as $year) {
    $rows = DB::table('invoices')
        ->selectRaw('MONTH(invoice_date) as mese, SUM(total) as totale')
        ->whereYear('invoice_date', $year)
        ->groupByRaw('MONTH(invoice_date)')
        ->orderByRaw('MONTH(invoice_date)')
        ->get()
        ->keyBy('mese');

    $data = [];
    for ($m = 1; $m <= 12; $m++) {
        $data[] = isset($rows[$m]) ? (float) $rows[$m]->totale : 0;
    }

    $series[] = [
        'name'  => (string) $year,
        'color' => $year === 2024 ? '#90cdf4' : '#4d7496',
        'data'  => $data,
    ];
}

$chartData = [
    'title'  => 'Confronto fatturato 2024 vs 2025',
    'type'   => 'line',
    'labels' => ['Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'],
    'series' => $series,
];
```

---

## 6) Implementazione nel package

### 6.1 Dipendenza composer

```bash
composer require asantibanez/livewire-charts "^4.0"
```

Poi pubblicare gli assets:

```bash
php artisan vendor:publish --tag=livewire-charts:scripts
```

Aggiungere in `layouts/app.blade.php`:

```blade
@livewireChartsScripts
```

### 6.2 File da creare nel package

#### `Services/ChartService.php`

Servizio che riceve `$chartData` (struttura sezione 4) e restituisce il chart model corretto di livewire-charts.

```php
<?php
namespace Packages\IctInterface\Services;

use Asantibanez\LivewireCharts\Models\LineChartModel;
use Asantibanez\LivewireCharts\Models\MultiLineChartModel;
use Asantibanez\LivewireCharts\Models\ColumnChartModel;
use Asantibanez\LivewireCharts\Models\MultiColumnChartModel;

class ChartService
{
    public function build(array $config): LineChartModel|MultiLineChartModel|ColumnChartModel|MultiColumnChartModel
    {
        $type = $config['type'] ?? 'line';
        $isMulti = count($config['series'] ?? []) > 1;

        return match (true) {
            $type === 'line' && !$isMulti  => $this->buildLineChart($config),
            $type === 'line' && $isMulti   => $this->buildMultiLineChart($config),
            $type === 'bar' && !$isMulti   => $this->buildBarChart($config),
            $type === 'bar' && $isMulti    => $this->buildMultiBarChart($config),
        };
    }

    protected function buildLineChart(array $config): LineChartModel
    {
        $model = (new LineChartModel())
            ->setTitle($config['title'] ?? '')
            ->setAnimated($config['options']['animated'] ?? true);

        $serie = $config['series'][0];
        foreach ($config['labels'] as $i => $label) {
            $model->addPoint($label, $serie['data'][$i] ?? 0);
        }

        if (!empty($serie['color'])) {
            $model->setJsonConfig(['colors' => [$serie['color']]]);
        }

        return $model;
    }

    protected function buildMultiLineChart(array $config): MultiLineChartModel
    {
        $model = (new MultiLineChartModel())
            ->setTitle($config['title'] ?? '')
            ->setAnimated($config['options']['animated'] ?? true);

        $colors = [];
        foreach ($config['series'] as $serie) {
            $colors[] = $serie['color'] ?? '#999999';
            foreach ($config['labels'] as $i => $label) {
                $model->addSeriesPoint($serie['name'], $label, $serie['data'][$i] ?? 0);
            }
        }
        $model->setJsonConfig(['colors' => $colors]);

        return $model;
    }

    protected function buildBarChart(array $config): ColumnChartModel
    {
        $model = (new ColumnChartModel())
            ->setTitle($config['title'] ?? '')
            ->setAnimated($config['options']['animated'] ?? true);

        $serie = $config['series'][0];
        $color = $serie['color'] ?? '#4d7496';

        foreach ($config['labels'] as $i => $label) {
            $model->addColumn($label, $serie['data'][$i] ?? 0, $color);
        }

        return $model;
    }

    protected function buildMultiBarChart(array $config): MultiColumnChartModel
    {
        $model = (new MultiColumnChartModel())
            ->setTitle($config['title'] ?? '')
            ->setAnimated($config['options']['animated'] ?? true);

        $colors = [];
        foreach ($config['series'] as $serie) {
            $colors[] = $serie['color'] ?? '#999999';
            foreach ($config['labels'] as $i => $label) {
                $model->addSeriesColumn($serie['name'], $label, $serie['data'][$i] ?? 0);
            }
        }
        $model->setJsonConfig(['colors' => $colors]);

        return $model;
    }
}
```

#### `Livewire/ChartComponent.php`

Componente Livewire che riceve la config e delega a ChartService.

```php
<?php
namespace Packages\IctInterface\Livewire;

use Livewire\Component;
use Packages\IctInterface\Services\ChartService;

class ChartComponent extends Component
{
    public array $config = [];

    public function mount(array $config = [])
    {
        $this->config = $config;
    }

    public function render()
    {
        $chartService = app(ChartService::class);
        $chartModel = $chartService->build($this->config);

        $type = $this->config['type'] ?? 'line';
        $isMulti = count($this->config['series'] ?? []) > 1;
        $height = $this->config['options']['height'] ?? '24rem';

        return view('ict::livewire.chart', [
            'chartModel' => $chartModel,
            'chartType'  => $type,
            'isMulti'    => $isMulti,
            'height'     => $height,
        ]);
    }
}
```

#### `resources/views/livewire/chart.blade.php`

```blade
<div>
    <div style="height: {{ $height }}">
        @if($chartType === 'line' && !$isMulti)
            <livewire:livewire-line-chart
                key="{{ $chartModel->reactiveKey() }}"
                :line-chart-model="$chartModel"
            />
        @elseif($chartType === 'line' && $isMulti)
            <livewire:livewire-line-chart
                key="{{ $chartModel->reactiveKey() }}"
                :line-chart-model="$chartModel"
            />
        @elseif($chartType === 'bar' && !$isMulti)
            <livewire:livewire-column-chart
                key="{{ $chartModel->reactiveKey() }}"
                :column-chart-model="$chartModel"
            />
        @elseif($chartType === 'bar' && $isMulti)
            <livewire:livewire-column-chart
                key="{{ $chartModel->reactiveKey() }}"
                :column-chart-model="$chartModel"
            />
        @endif
    </div>
</div>
```

### 6.3 Registrazione

In `IctServiceProvider.php`:

```php
// In register()
$this->app->singleton(\Packages\IctInterface\Services\ChartService::class);

// In boot(), aggiungere al blocco Livewire::component()
Livewire::component('ict-chart', \Packages\IctInterface\Livewire\ChartComponent::class);
```

Aggiungere `@livewireChartsScripts` in `layouts/app.blade.php` prima della chiusura `</body>`.

---

## 7) Riepilogo struttura dati — Riferimento rapido

### Serie singola

| Chiave | Tipo | Obbligatorio | Descrizione |
|--------|------|:---:|-------------|
| `title` | string | sì | Titolo del grafico |
| `type` | `'line'` \| `'bar'` | sì | Tipo di grafico |
| `labels` | string[] | sì | Etichette asse X |
| `series` | array | sì | Array con 1 elemento |
| `series[].name` | string | sì | Nome serie |
| `series[].color` | string | no | Colore hex |
| `series[].data` | number[] | sì | Valori (stessa lunghezza di labels) |
| `options` | array | no | Override (animated, height, legend) |

### Multi-serie

Stessa struttura, con `series` contenente 2 o più elementi. Il package rileva automaticamente se è single o multi in base al numero di serie.

### Regola fondamentale

> **`count(labels) === count(series[N].data)` per ogni serie N.**

---

## 8) Stima effort

| Attività | Giorni |
|----------|:------:|
| Installazione + configurazione livewire-charts | 0.5 |
| ChartService.php | 1 |
| ChartComponent.php + vista | 0.5 |
| Registrazione provider + layout | 0.5 |
| Test con dati reali (line singola, multi-line, bar singola, multi-bar) | 1 |
| Documentazione | 0.5 |
| **Totale** | **4** |

---

## 9) Evoluzione futura

- Aggiunta tipi: `area`, `pie`, `radar` (già supportati da livewire-charts, basta estendere ChartService)
- Migrazione a Flux Chart quando/se il package adotterà Tailwind (fase 11)
- Grafici configurabili da DB (tabella `charts` con query, tipo, colori) per rendering automatico senza codice applicativo
