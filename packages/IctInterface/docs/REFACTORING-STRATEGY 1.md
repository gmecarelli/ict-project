# Refactoring Strategy — IctInterface

> **Obiettivo principale**: Eliminare `kris/laravel-form-builder` e sostituirlo con **Livewire 3 + Bootstrap 5.3**
> **Approccio**: **C** — Livewire 3 + Bootstrap 5.3 ora; Flux + Tailwind CSS in una fase futura separata
> **Target**: Laravel 12.x
> **Ambiente**: Solo sviluppo (nessun vincolo di produzione)
> **Team**: Team Leader + Senior Developer

---

## Indice

- [Visione d'insieme](#visione-dinsieme)
- [Prerequisiti](#prerequisiti)
- [FASE 0 — Preparazione ambiente](#fase-0--preparazione-ambiente)
- [FASE 1 — Refactoring architetturale preparatorio](#fase-1--refactoring-architetturale-preparatorio)
- [FASE 2 — Creazione layer Livewire base](#fase-2--creazione-layer-livewire-base)
- [FASE 3 — Migrazione FilterForm → Livewire](#fase-3--migrazione-filterform--livewire)
- [FASE 4 — Migrazione SearchForm → Livewire](#fase-4--migrazione-searchform--livewire)
- [FASE 5 — Migrazione AppFormsBuilder (form editable) → Livewire](#fase-5--migrazione-appformsbuilder-form-editable--livewire)
- [FASE 6 — Migrazione ChildForm → Livewire](#fase-6--migrazione-childform--livewire)
- [FASE 7 — Migrazione ModalForms → Livewire + Bootstrap Modal](#fase-7--migrazione-modalforms--livewire--bootstrap-modal)
- [FASE 8 — Refactoring StandardController](#fase-8--refactoring-standardcontroller)
- [FASE 9 — Eliminazione laravel-form-builder](#fase-9--eliminazione-laravel-form-builder)
- [FASE 10 — Pulizia e modernizzazione](#fase-10--pulizia-e-modernizzazione)
- [FASE 11 — (Futura) Migrazione a Flux + Tailwind CSS](#fase-11--futura-migrazione-a-flux--tailwind-css)
- [Appendice A — Mapping tipi campo](#appendice-a--mapping-tipi-campo)
- [Appendice B — Mapping struttura Bootstrap](#appendice-b--mapping-struttura-bootstrap)
- [Appendice C — Schema componenti Livewire target](#appendice-c--schema-componenti-livewire-target)

---

## Visione d'insieme

### Stato attuale vs. Stato target

```
STATO ATTUALE                           STATO TARGET (Approccio C)
─────────────                           ──────────────────────────
kris/laravel-form-builder        →      Livewire 3 Components
jQuery AJAX per modali           →      Bootstrap 5.3 Modal + Livewire
jQuery AJAX per child forms      →      Livewire nested components
jQuery per multiselect           →      Bootstrap 5.3 Select + Livewire
Form rendering server-side       →      Livewire reactive forms
Full page reload su submit       →      Livewire wire:submit (SPA-like)
Bootstrap 5 manuale              →      Bootstrap 5.3 (upgrade)
JavaScript inline nelle blade    →      Alpine.js (incluso in Livewire)

   ┌──────────────────────────────────────────────────────────┐
   │  FASE FUTURA (separata): Bootstrap 5.3 → Flux + Tailwind │
   └──────────────────────────────────────────────────────────┘
```

### Principio guida

Il refactoring procede **form-type per form-type** (filter → search → editable → child → modal), testando ogni step prima di procedere al successivo. In ogni step:

1. Si crea il componente Livewire equivalente
2. Si aggiorna la vista blade per usare il nuovo componente
3. Si verifica che tutto funzioni
4. Si rimuove il vecchio codice solo quando il nuovo è validato

---

## Prerequisiti

Prima di iniziare qualsiasi step:

- [ ] Avere una copia funzionante dell'applicazione host con IctInterface
- [ ] Avere il database popolato con dati di test (form, form_fields, reports, etc.)
- [ ] Conoscenza base di Livewire 3 e Alpine.js

---

## FASE 0 — Preparazione ambiente

### Step 0.1 — Upgrade a Laravel 12.x

| | |
|---|---|
| **What** | Aggiornare il progetto host da Laravel 10.x a Laravel 12.x |
| **Why** | Target del refactoring; Livewire 3 richiede versioni recenti di Laravel |
| **Effort** | M |
| **Risk** | Medium |
| **Dependencies** | Nessuna |

**Azioni**:
1. Aggiornare `composer.json`: `"laravel/framework": "^12.0"`
2. Aggiornare tutte le dipendenze compatibili
3. Seguire la guida di upgrade ufficiale Laravel
4. Verificare compatibilità di `barryvdh/laravel-dompdf` e `maatwebsite/excel` con Laravel 12
5. Risolvere eventuali breaking changes

### Step 0.2 — Installare Livewire 3

| | |
|---|---|
| **What** | Installare e configurare Livewire 3 nel progetto |
| **Why** | Base per tutti i nuovi componenti form |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | Step 0.1 |

**Azioni**:
```bash
composer require livewire/livewire:^3.0
```
- Aggiungere `@livewireStyles` e `@livewireScripts` nel layout `app.blade.php`
- Verificare che Livewire funzioni con una test page

### Step 0.3 — Aggiornare Bootstrap a 5.3

| | |
|---|---|
| **What** | Aggiornare Bootstrap dalla versione attuale alla 5.3.x |
| **Why** | Bootstrap 5.3 include miglioramenti significativi (color modes, nuovi componenti, utility migliorate). Ci avvicina ad una base CSS moderna senza cambiare framework |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | Step 0.1 |

**Azioni**:
1. Aggiornare Bootstrap via npm: `npm install bootstrap@^5.3`
2. Verificare che le classi CSS esistenti funzionino (Bootstrap 5.3 è backward-compatible con 5.x)
3. Aggiornare eventuali import JS di Bootstrap (es. modal, tooltip)
4. Verificare che layout, tabelle e componenti del package si renderizzino correttamente

> **Nota (Approccio C)**: Flux UI richiede Tailwind CSS e NON può funzionare con Bootstrap. La migrazione a Flux + Tailwind è pianificata come [FASE 11](#fase-11--futura-migrazione-a-flux--tailwind-css) separata, dopo il completamento di tutto il refactoring dei form.

---

## FASE 1 — Refactoring architetturale preparatorio

### Step 1.1 — Separare i Service dal controller base

| | |
|---|---|
| **What** | Fare in modo che `ApplicationService`, `FormService`, `ReportService` NON estendano `IctController` |
| **Why** | I servizi non devono essere controller. Questa separazione è necessaria per poter usare Livewire |
| **Effort** | M |
| **Risk** | Medium |
| **Dependencies** | Step 0.1 |

**Azioni**:
1. Creare una nuova classe `Packages\IctInterface\Support\BaseService` che contenga i metodi comuni attualmente in `IctController`:
   - `stringToArray()`
   - `replaceTags()`
   - `getFormId()`
   - `loadFormIdReport()`
   - `isAdmin()`
   - `setFlashMessages()`
2. `ApplicationService` deve estendere `BaseService` invece di `IctController`
3. `Logger` deve diventare una classe standalone (non estendere nulla di controller)
4. `MenuService` deve estendere `BaseService`
5. Aggiornare tutti i riferimenti

### Step 1.2 — Rimuovere codice residuo Hook

| | |
|---|---|
| **What** | Rimuovere tutti gli import e riferimenti a `Hongyukeji\Hook\Facades\Hook` |
| **Why** | Dipendenza non utilizzata confermata dall'utente |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | Nessuna |

**Azioni**:
1. Rimuovere `use Hongyukeji\Hook\Facades\Hook;` da `FormService.php`
2. Rimuovere `use Hongyukeji\Hook\Facades\Hook;` da `AjaxController.php`
3. Rimuovere il package da composer se presente

### Step 1.3 — Eliminare `env()` fuori da config

| | |
|---|---|
| **What** | Spostare tutte le chiamate `env()` in un file di configurazione |
| **Why** | Incompatibile con config caching di Laravel; necessario per Laravel 12 |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | Nessuna |

**Azioni**:
1. Creare `config/ict.php`:
```php
return [
    'upload_dir' => env('UPLOAD_DIR', 'upload'),
    'logger_level' => env('LOGGER_LEVEL', 1),
    'app_url' => env('APP_URL', 'http://localhost'),
];
```
2. Pubblicare il config dal ServiceProvider
3. Sostituire `env('UPLOAD_DIR')` con `config('ict.upload_dir')` in `FormService`
4. Sostituire `env('LOGGER_LEVEL')` con `config('ict.logger_level')` in `Logger`
5. Sostituire `env('APP_URL')` con `config('ict.app_url')` in `FormService`

### Step 1.4 — Fixare il ServiceProvider

| | |
|---|---|
| **What** | Rimuovere gli `app->make()` inutili e registrare correttamente i servizi |
| **Why** | Il ServiceProvider attuale forza l'istanziazione di tutti i controller e modelli |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | Step 1.1 |

**Azioni**:
1. Rimuovere tutti i `$this->app->make(...)` dal metodo `register()`
2. Registrare solo i binding necessari:
```php
public function register()
{
    $this->mergeConfigFrom(__DIR__.'/../config/ict.php', 'ict');

    $this->app->singleton(FormService::class);
    $this->app->singleton(ReportService::class);
    $this->app->singleton(MenuService::class);
}
```

### Step 1.5 — Creare un DynamicFormService dedicato

| | |
|---|---|
| **What** | Estrarre da `FormService` la logica di lettura form/campi dal DB in un servizio dedicato che sarà usato sia dal vecchio codice che dai nuovi componenti Livewire |
| **Why** | Questo servizio sarà il **ponte** tra il vecchio sistema e il nuovo. I componenti Livewire lo useranno per leggere la configurazione dei form dal DB |
| **Effort** | M |
| **Risk** | Medium |
| **Dependencies** | Step 1.1 |

**Azioni**:
1. Creare `Packages\IctInterface\Services\DynamicFormService`:

```php
class DynamicFormService
{
    /**
     * Carica le proprietà di un form dal DB
     */
    public function getFormProperties(int $formId): ?object

    /**
     * Carica i campi di un form dal DB, ordinati per position
     */
    public function getFormFields(int $formId): Collection

    /**
     * Carica il form filtro per un report
     */
    public function getFilterForm(int $reportId): ?object

    /**
     * Carica il form search per un report
     */
    public function getSearchForm(int $reportId): ?object

    /**
     * Carica il form modale per un report
     */
    public function getModalForm(int $reportId): ?object

    /**
     * Genera le opzioni per una select dal type_attr
     * (estrae da ApplicationService::getArrayOptions)
     */
    public function getSelectOptions(string $typeAttr, mixed $contextValue = null): array

    /**
     * Genera le regole di validazione da un set di campi
     */
    public function getValidationRules(int $formId, ?int $recordId = null): array

    /**
     * Verifica se un campo è criptato
     */
    public function isFieldCrypted(string $fieldName, int $formId): bool
}
```

2. Questo servizio deve usare il **Query Builder di Eloquent** (non raw SQL) per le select options
3. Mantenere la compatibilità con il DSL `type_attr` esistente
4. Il vecchio `FormService` deve delegare a questo servizio le operazioni di lettura

---

## FASE 2 — Creazione layer Livewire base

### Step 2.1 — Creare il componente base `DynamicForm`

| | |
|---|---|
| **What** | Creare un componente Livewire astratto che legge la configurazione dal DB e renderizza un form Bootstrap |
| **Why** | Tutti i tipi di form (filter, search, editable, modal, child) estenderanno questa classe base |
| **Effort** | L |
| **Risk** | Medium |
| **Dependencies** | Step 1.5 |

**Azioni**:
1. Creare `Packages\IctInterface\Livewire\DynamicForm`:

```php
namespace Packages\IctInterface\Livewire;

use Livewire\Component;

abstract class DynamicForm extends Component
{
    public int $formId;
    public ?int $recordId = null;
    public array $formData = [];
    public array $fields = [];
    public ?object $formProperties = null;

    protected DynamicFormService $formService;

    public function mount(int $formId, ?int $recordId = null, ?object $model = null): void
    {
        $this->formId = $formId;
        $this->recordId = $recordId;
        $this->formService = app(DynamicFormService::class);
        $this->formProperties = $this->formService->getFormProperties($formId);
        $this->fields = $this->formService->getFormFields($formId)->toArray();

        if ($model) {
            $this->populateFromModel($model);
        }
    }

    protected function populateFromModel(object $model): void
    {
        foreach ($this->fields as $field) {
            $name = $field['name'];
            $this->formData[$name] = $model->$name ?? $field['default_value'] ?? null;
        }
    }

    public function getRules(): array
    {
        return $this->formService->getValidationRules($this->formId, $this->recordId);
    }

    abstract public function submit(): void;
    abstract public function render();
}
```

2. Creare la vista base `livewire/dynamic-form.blade.php` che itera sui campi e usa Bootstrap 5.3:

```blade
<form wire:submit="submit">
    <div class="row">
        @foreach($fields as $field)
            <div class="col-sm-{{ $field['col_size'] ?? 12 }} mb-3">
                <x-ict-dynamic-field
                    :field="$field"
                    :value="$formData[$field['name']] ?? null"
                    wire:model="formData.{{ $field['name'] }}"
                />
            </div>
        @endforeach
    </div>

    <button type="submit" class="btn btn-primary">
        {{ $submitLabel }}
    </button>
</form>
```

### Step 2.2 — Creare il componente `DynamicField`

| | |
|---|---|
| **What** | Creare un componente Blade/Livewire che renderizza un singolo campo basato sul tipo configurato in DB |
| **Why** | Sostituisce `FormService::renderField()` — è il mapping tra il tipo DB e il componente Bootstrap |
| **Effort** | L |
| **Risk** | Medium |
| **Dependencies** | Step 2.1 |

**Azioni**:
1. Creare il componente Blade `Packages\IctInterface\View\Components\DynamicField`
2. Implementare il mapping dei tipi (vedi [Appendice A](#appendice-a--mapping-tipi-campo))

La vista del componente deve switchare sul tipo:

```blade
@props(['field', 'value', 'wireModel'])

@switch($field['type'])
    @case('text')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <input type="text"
            class="form-control @error($wireModel) is-invalid @enderror"
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
            placeholder="{{ $field['default_value'] ?? '' }}"
            {{ str_contains($field['rules'] ?? '', 'required') ? 'required' : '' }}
        >
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break

    @case('select')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <select class="form-select @error($wireModel) is-invalid @enderror"
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
        >
            <option value="">- Seleziona -</option>
            @foreach($field['options'] as $code => $optionLabel)
                <option value="{{ $code }}">{{ $optionLabel }}</option>
            @endforeach
        </select>
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break

    @case('date')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <input type="date"
            class="form-control @error($wireModel) is-invalid @enderror"
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
        >
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break

    @case('textarea')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <textarea class="form-control @error($wireModel) is-invalid @enderror"
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
            rows="3"
        ></textarea>
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break

    @case('hidden')
        <input type="hidden" wire:model="{{ $wireModel }}" value="{{ $value }}">
        @break

    @case('number')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <input type="number"
            class="form-control @error($wireModel) is-invalid @enderror"
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
        >
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break

    @case('file')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <input type="file"
            class="form-control @error($wireModel) is-invalid @enderror"
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
        >
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break

    @case('checkbox')
        <div class="form-check">
            <input type="checkbox"
                class="form-check-input @error($wireModel) is-invalid @enderror"
                wire:model="{{ $wireModel }}"
                id="{{ $field['name'] }}"
            >
            <label class="form-check-label" for="{{ $field['name'] }}">{{ $field['label'] }}</label>
            @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        @break

    @case('radio')
        <label class="form-label d-block">{{ $field['label'] }}</label>
        @foreach($field['options'] as $code => $optionLabel)
            <div class="form-check form-check-inline">
                <input type="radio"
                    class="form-check-input"
                    wire:model="{{ $wireModel }}"
                    value="{{ $code }}"
                    id="{{ $field['name'] }}_{{ $code }}"
                >
                <label class="form-check-label" for="{{ $field['name'] }}_{{ $code }}">{{ $optionLabel }}</label>
            </div>
        @endforeach
        @error($wireModel) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @break
@endswitch
```

### Step 2.3 — Registrare i componenti Livewire nel ServiceProvider

| | |
|---|---|
| **What** | Registrare tutti i componenti Livewire nel ServiceProvider |
| **Why** | Livewire deve sapere dove trovare i componenti del package |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | Step 2.1, Step 2.2 |

**Azioni**:
Nel metodo `boot()` del ServiceProvider aggiungere:
```php
use Livewire\Livewire;

Livewire::component('ict-dynamic-form', DynamicForm::class);
Livewire::component('ict-filter-form', FilterFormComponent::class);
// ... etc
```

---

## FASE 3 — Migrazione FilterForm → Livewire

### Step 3.1 — Creare `FilterFormComponent` Livewire

| | |
|---|---|
| **What** | Creare il componente Livewire che sostituisce `FilterForm extends Kris\LaravelFormBuilder\Form` |
| **Why** | I FilterForm sono i più semplici (GET, nessun salvataggio DB). Ideali per iniziare |
| **Effort** | M |
| **Risk** | Low |
| **Dependencies** | Step 2.2 |

**Azioni**:
1. Creare `Packages\IctInterface\Livewire\FilterFormComponent`:

```php
class FilterFormComponent extends DynamicForm
{
    public int $reportId;

    public function mount(int $reportId): void
    {
        $this->reportId = $reportId;
        $formService = app(DynamicFormService::class);
        $filterForm = $formService->getFilterForm($reportId);

        if ($filterForm) {
            parent::mount($filterForm->id);
            // Pre-popola dai parametri GET correnti
            foreach ($this->fields as $field) {
                if (request()->filled($field['name'])) {
                    $this->formData[$field['name']] = request($field['name']);
                }
            }
        }
    }

    public function submit(): void
    {
        // Redirect con i filtri come query string
        $params = array_filter($this->formData);
        $params['report'] = $this->reportId;
        $params['filter'] = 'Y';

        $this->redirect(url()->current() . '?' . http_build_query($params));
    }

    public function render()
    {
        return view('ict::livewire.filter-form');
    }
}
```

2. Creare la vista `livewire/filter-form.blade.php`

### Step 3.2 — Aggiornare `StandardController::getIndex()` per supportare entrambi i sistemi

| | |
|---|---|
| **What** | Modificare `getIndex()` per passare alla view i dati necessari sia al vecchio FilterForm che al nuovo Livewire component |
| **Why** | Durante la transizione, le viste devono poter usare entrambi i sistemi |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | Step 3.1 |

**Azioni**:
1. In `getIndex()`, aggiungere ai params:
```php
$params['useNewFilters'] = true; // flag per la transizione
$params['reportId'] = request('report');
```

### Step 3.3 — Aggiornare `report.blade.php` per usare il FilterForm Livewire

| | |
|---|---|
| **What** | Nella vista report, sostituire il rendering del vecchio FilterForm con il componente Livewire |
| **Why** | Primo form convertito end-to-end |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | Step 3.2 |

**Azioni**:
Nella vista `report.blade.php`, sostituire:
```blade
{{-- VECCHIO --}}
{!! form($filters) !!}

{{-- NUOVO --}}
@livewire('ict-filter-form', ['reportId' => $report['id']])
```

### Step 3.4 — Testare e validare i FilterForm

| | |
|---|---|
| **What** | Testare tutti i report che hanno filtri configurati |
| **Why** | Validare che i filtri funzionino identicamente al vecchio sistema |
| **Effort** | M |
| **Risk** | Low |
| **Dependencies** | Step 3.3 |

**Checklist test**:
- [ ] I campi filtro si visualizzano correttamente
- [ ] Le select sono popolate con le opzioni corrette
- [ ] Il submit filtra i dati del report
- [ ] I valori dei filtri sono preservati dopo il submit
- [ ] I campi `multiple` (select multiple) funzionano
- [ ] Il pulsante "Filtra" funziona

---

## FASE 4 — Migrazione SearchForm → Livewire

### Step 4.1 — Creare `SearchFormComponent` Livewire

| | |
|---|---|
| **What** | Creare il componente Livewire per i SearchForm |
| **Why** | Identico ai filtri ma con campo `search=on` |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | FASE 3 completata |

**Azioni**:
Estendere `FilterFormComponent` con l'aggiunta del campo hidden `search=on`:
```php
class SearchFormComponent extends FilterFormComponent
{
    public function submit(): void
    {
        $params = array_filter($this->formData);
        $params['report'] = $this->reportId;
        $params['filter'] = 'Y';
        $params['search'] = 'on';

        $this->redirect(url()->current() . '?' . http_build_query($params));
    }
}
```

### Step 4.2 — Aggiornare le viste che usano SearchForm

| | |
|---|---|
| **What** | Sostituire il rendering SearchForm nelle viste |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | Step 4.1 |

---

## FASE 5 — Migrazione AppFormsBuilder (form editable) → Livewire

Questa è **la fase più complessa** perché i form editable gestiscono create, edit, update con validazione e salvataggio.

### Step 5.1 — Creare `EditableFormComponent` Livewire

| | |
|---|---|
| **What** | Creare il componente Livewire per i form di create/edit |
| **Why** | Sostituisce `AppFormsBuilder` — il form più usato nel package |
| **Effort** | XL |
| **Risk** | High |
| **Dependencies** | FASE 3+4 completate, Step 1.5 |

**Azioni**:
1. Creare `Packages\IctInterface\Livewire\EditableFormComponent`:

```php
class EditableFormComponent extends DynamicForm
{
    public int $reportId;
    public ?string $redirectUrl = null;

    // Proprietà per gestire child forms
    public bool $hasChild = false;
    public ?int $childFormId = null;

    public function mount(int $reportId, ?int $recordId = null): void
    {
        $this->reportId = $reportId;
        $formService = app(DynamicFormService::class);

        // Cerca il form editable per questo report
        $form = Form::where('report_id', $reportId)
                     ->where('type', 'editable')
                     ->first();

        if ($form) {
            $model = null;
            if ($recordId) {
                // Edit: carica il model esistente
                // Il model viene determinato dal controller/context
                $model = $this->resolveModel($recordId);
            }

            parent::mount($form->id, $recordId, $model);

            // Gestione child
            if ($form->id_child) {
                $this->hasChild = true;
                $this->childFormId = $form->id_child;
            }
        }
    }

    public function submit(): void
    {
        $rules = $this->getRules();
        $validated = $this->validate($rules);

        // Gestione cifratura
        foreach ($validated as $key => $value) {
            if ($this->formService->isFieldCrypted($key, $this->formId)) {
                $validated[$key] = Crypt::encryptString($value);
            }
        }

        if ($this->recordId) {
            // UPDATE
            $this->model->where('id', $this->recordId)->update($validated);
            session()->flash('message', 'Record aggiornato con successo');
        } else {
            // INSERT
            $record = $this->model->create($validated);
            $this->recordId = $record->id;
            session()->flash('message', 'Record creato con successo');
        }

        DB::commit();
        $this->redirect($this->redirectUrl ?? url()->previous());
    }

    public function render()
    {
        return view('ict::livewire.editable-form');
    }
}
```

### Step 5.2 — Gestire il pre-filling dei campi in edit

| | |
|---|---|
| **What** | Implementare la logica di pre-compilazione dei campi quando si modifica un record esistente |
| **Why** | In edit mode, i campi devono essere pre-popolati con i valori del record |
| **Effort** | M |
| **Risk** | Medium |
| **Dependencies** | Step 5.1 |

**Azioni**:
- Gestire campi criptati (decrypt prima del display)
- Gestire campi select (selected value)
- Gestire campi date (formato DB → formato display)
- Gestire campi file (mostrare link al file esistente)

### Step 5.3 — Gestire la validazione dinamica con errori Bootstrap

| | |
|---|---|
| **What** | Implementare la validazione real-time con i messaggi d'errore Bootstrap (`is-invalid` + `invalid-feedback`) |
| **Why** | Livewire gestisce la validazione diversamente da laravel-form-builder; Bootstrap 5.3 ha classi native per mostrare gli errori |
| **Effort** | M |
| **Risk** | Low |
| **Dependencies** | Step 5.1 |

**Azioni**:
- Usare `$this->validate()` di Livewire
- Le regole vengono dal DB (campo `rules` in `form_fields`)
- Supportare la sostituzione `#id` per le regole `unique`
- Usare le classi Bootstrap `is-invalid` e `<div class="invalid-feedback">` con la direttiva `@error` di Blade

### Step 5.4 — Gestire upload file nel form Livewire

| | |
|---|---|
| **What** | Implementare l'upload file con `Livewire\WithFileUploads` |
| **Why** | I form editable possono avere campi file |
| **Effort** | M |
| **Risk** | Medium |
| **Dependencies** | Step 5.1 |

**Azioni**:
- Usare il trait `WithFileUploads` di Livewire
- Mantenere la logica di rinominazione file da `FormService::_setFileName()`
- Gestire il salvataggio con la stessa struttura directory

### Step 5.5 — Aggiornare le viste builder.blade.php e form correlate

| | |
|---|---|
| **What** | Sostituire `{!! form($form) !!}` con `@livewire('ict-editable-form', [...])` |
| **Why** | Le viste form devono usare i nuovi componenti |
| **Effort** | M |
| **Risk** | Medium |
| **Dependencies** | Step 5.1-5.4 |

### Step 5.6 — Testare e validare i form editable

| | |
|---|---|
| **What** | Test completo create/edit/update su tutte le entità |
| **Effort** | L |
| **Risk** | Low |
| **Dependencies** | Step 5.5 |

**Checklist test**:
- [ ] Create: il form vuoto si visualizza
- [ ] Create: la validazione funziona (required, unique, etc.)
- [ ] Create: il submit crea il record nel DB
- [ ] Edit: i campi sono pre-compilati
- [ ] Edit: i campi criptati si decriptano correttamente
- [ ] Edit: le select mostrano il valore selezionato
- [ ] Update: i dati vengono aggiornati
- [ ] File upload: il file viene caricato e rinominato
- [ ] Redirect: dopo submit si torna alla pagina corretta

---

## FASE 6 — Migrazione ChildForm → Livewire

### Step 6.1 — Creare `ChildFormComponent` Livewire (nested)

| | |
|---|---|
| **What** | Creare un componente Livewire per i form child (items) che permetta di aggiungere/rimuovere righe dinamicamente |
| **Why** | Sostituisce il sistema jQuery AJAX di aggiunta child + `ChildForm extends Form` |
| **Effort** | XL |
| **Risk** | High |
| **Dependencies** | FASE 5 completata |

**Azioni**:
1. Creare `Packages\IctInterface\Livewire\ChildFormComponent`:

```php
class ChildFormComponent extends Component
{
    public int $childFormId;
    public int $parentId;
    public string $foreignKey;
    public array $items = []; // Array di righe child
    public array $childFields = [];

    public function mount(int $childFormId, ?int $parentId = null, string $foreignKey = 'parent_id'): void
    {
        $this->childFormId = $childFormId;
        $this->parentId = $parentId;
        $this->foreignKey = $foreignKey;

        $formService = app(DynamicFormService::class);
        $this->childFields = $formService->getFormFields($childFormId)->toArray();

        // Se edit, carica gli items esistenti
        if ($parentId) {
            $this->loadExistingItems();
        }
    }

    public function addItem(): void
    {
        $newItem = [];
        foreach ($this->childFields as $field) {
            $newItem[$field['name']] = $field['default_value'] ?? null;
        }
        $this->items[] = $newItem;
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function render()
    {
        return view('ict::livewire.child-form');
    }
}
```

2. Creare la vista `livewire/child-form.blade.php`:
```blade
<div>
    {{-- Tabella items esistenti --}}
    @if(count($items) > 0)
        <table class="table table-bordered">
            <thead>...</thead>
            <tbody>
                @foreach($items as $index => $item)
                    <tr>
                        @foreach($childFields as $field)
                            <td>
                                <x-ict-dynamic-field
                                    :field="$field"
                                    wire:model="items.{{ $index }}.{{ $field['name'] }}"
                                />
                            </td>
                        @endforeach
                        <td>
                            <button type="button" class="btn btn-danger btn-sm"
                                wire:click="removeItem({{ $index }})">
                                <i class="fas fa-minus-circle"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <button type="button" class="btn btn-primary btn-sm" wire:click="addItem">
        <i class="fas fa-plus-circle"></i> Aggiungi riga
    </button>
</div>
```

### Step 6.2 — Integrare ChildForm nel EditableForm

| | |
|---|---|
| **What** | Quando un form editable ha un `id_child`, includere il `ChildFormComponent` nella vista |
| **Why** | I child devono essere salvati insieme al parent |
| **Effort** | M |
| **Risk** | Medium |
| **Dependencies** | Step 6.1 |

### Step 6.3 — Gestire il salvataggio parent + children atomico

| | |
|---|---|
| **What** | Il submit del form parent deve salvare anche tutti i children in una transazione |
| **Why** | Attualmente `StandardController::getStore()` salva parent e poi children |
| **Effort** | M |
| **Risk** | High |
| **Dependencies** | Step 6.2 |

---

## FASE 7 — Migrazione ModalForms → Livewire + Bootstrap Modal

### Step 7.1 — Creare `ModalFormComponent` con Bootstrap Modal + Livewire

| | |
|---|---|
| **What** | Creare un componente Livewire che usa Bootstrap 5.3 Modal per i form in modale |
| **Why** | Sostituisce il sistema jQuery AJAX di caricamento form in modali. Bootstrap 5.3 Modal è già presente nel progetto e supporta apertura/chiusura via JavaScript API o Alpine.js |
| **Effort** | L |
| **Risk** | Medium |
| **Dependencies** | FASE 5 completata |

**Azioni**:

1. Creare il componente Livewire `ModalFormComponent`:
```php
class ModalFormComponent extends DynamicForm
{
    public bool $showModal = false;

    public function openModal(?int $recordId = null): void
    {
        $this->recordId = $recordId;
        if ($recordId) {
            $this->populateFromModel($this->resolveModel($recordId));
        } else {
            $this->resetFormData();
        }
        $this->showModal = true;
        $this->dispatch('modal-opened');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetFormData();
    }

    public function submit(): void
    {
        $validated = $this->validate($this->getRules());
        // ... save logic ...
        $this->closeModal();
        $this->dispatch('record-saved');
    }
}
```

2. Creare la vista `livewire/modal-form.blade.php` usando Bootstrap Modal:
```blade
<div>
    {{-- Bootstrap 5.3 Modal controllato da Alpine.js (incluso in Livewire) --}}
    <div x-data="{ show: @entangle('showModal') }"
         x-show="show"
         x-transition
         class="modal fade"
         :class="{ 'show d-block': show }"
         tabindex="-1"
         @keydown.escape.window="show = false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $formProperties->name ?? 'Form' }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit="submit">
                        @foreach($fields as $field)
                            <div class="mb-3">
                                <x-ict-dynamic-field
                                    :field="$field"
                                    wire:model="formData.{{ $field['name'] }}"
                                />
                            </div>
                        @endforeach
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Annulla</button>
                    <button type="button" class="btn btn-primary" wire:click="submit">Salva</button>
                </div>
            </div>
        </div>
    </div>
    <div x-show="show" class="modal-backdrop fade show" x-transition></div>
</div>
```

### Step 7.2 — Aggiornare AjaxController per emettere eventi Livewire

| | |
|---|---|
| **What** | Dove necessario, convertire le chiamate AJAX in dispatch di eventi Livewire |
| **Why** | Le modali Bootstrap si aprono/chiudono via Alpine.js + Livewire `$dispatch`, non via jQuery `$.get()` |
| **Effort** | M |
| **Risk** | Medium |
| **Dependencies** | Step 7.1 |

### Step 7.3 — Rimuovere i file JavaScript delle modali

| | |
|---|---|
| **What** | Rimuovere `modal-js.blade.php`, `modal-item.blade.php`, `modal.blade.php` |
| **Why** | Sostituiti dal componente Bootstrap Modal + Livewire |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | Step 7.2 |

---

## FASE 8 — Refactoring StandardController

### Step 8.1 — Rimuovere `FormBuilder` dai metodi del trait

| | |
|---|---|
| **What** | I metodi `index()`, `create()`, `edit()` non devono più ricevere `FormBuilder` |
| **Why** | I form sono ora generati da Livewire, non dal FormBuilder |
| **Effort** | M |
| **Risk** | Medium |
| **Dependencies** | FASI 3-7 completate |

**Azioni**:
Trasformare le firme da:
```php
public function index(FormBuilder $formBuilder, Request $request)
```
a:
```php
public function index(Request $request)
```

I parametri passati alla view cambiano:
```php
// Invece di passare $form (oggetto FormBuilder)
// Si passa solo l'ID del report e del record
return view($viewName, [
    'reportId' => $reportId,
    'recordId' => $id,
    'report' => $this->reportData,
    // ... altri dati del report
]);
```

### Step 8.2 — Semplificare il flusso CRUD

| | |
|---|---|
| **What** | Spostare la logica di salvataggio (store/update) nei componenti Livewire |
| **Why** | Il submit del form avviene ora via Livewire `wire:submit`, non via HTTP POST tradizionale |
| **Effort** | L |
| **Risk** | High |
| **Dependencies** | Step 8.1 |

**Nota critica**: I metodi `store()`, `update()`, `destroy()` del trait possono rimanere come route handlers per i form che non sono ancora migrati o per operazioni che non passano per Livewire (es. delete via button).

---

## FASE 9 — Eliminazione laravel-form-builder

### Step 9.1 — Verificare che nessun codice usi più laravel-form-builder

| | |
|---|---|
| **What** | Grep completo del codebase per verificare l'assenza di riferimenti a `Kris\LaravelFormBuilder` |
| **Why** | Sicurezza prima della rimozione |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | FASI 3-8 completate |

**Azioni**:
```bash
grep -r "Kris\\\\LaravelFormBuilder" src/packages/IctInterface/
grep -r "form_builder" src/packages/IctInterface/
grep -r "FormBuilder" src/packages/IctInterface/
```

### Step 9.2 — Rimuovere i file Form obsoleti

| | |
|---|---|
| **What** | Eliminare le classi Form che estendevano `Kris\LaravelFormBuilder\Form` |
| **Why** | Non più utilizzate |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | Step 9.1 |

**File da eliminare**:
- `Forms/AppFormsBuilder.php`
- `Forms/ChildForm.php`
- `Forms/FilterForm.php`
- `Forms/ModalForms.php`
- `Forms/SearchForm.php`

### Step 9.3 — Rimuovere la dipendenza dal composer.json

| | |
|---|---|
| **What** | Rimuovere `kris/laravel-form-builder` dal `composer.json` del progetto host |
| **Why** | Obiettivo principale del refactoring raggiunto |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | Step 9.2 |

```bash
composer remove kris/laravel-form-builder
```

---

## FASE 10 — Pulizia e modernizzazione

### Step 10.1 — Rimuovere JavaScript jQuery obsoleto

| | |
|---|---|
| **What** | Eliminare i file blade con JavaScript jQuery inline |
| **Effort** | M |
| **Risk** | Low |

**File da eliminare/riscrivere**:
- `layouts/modal-js.blade.php` → sostituito da Bootstrap Modal + Livewire
- `layouts/form-child-js.blade.php` → sostituito da Livewire ChildForm
- `layouts/delete-js.blade.php` → sostituito da Livewire confirmation (Alpine.js + Bootstrap Modal)
- `multiselect-js.blade.php` → sostituito da Livewire
- `multiselect/multiselect-js.blade.php` → sostituito da Livewire

### Step 10.2 — Rimuovere `FormService::renderField()` e metodi correlati

| | |
|---|---|
| **What** | Eliminare tutti i metodi di rendering che facevano da ponte con laravel-form-builder |
| **Effort** | M |
| **Risk** | Low |

**Metodi da eliminare da FormService**:
- `renderField()`
- `childRenderField()`
- `setOptionsField()`
- `getOptionsFields()`
- `getForm()`
- `childGetForm()`
- `setClassForm()` / `getClassForm()`
- `setOptionsForm()`

### Step 10.3 — Fixare sicurezza SQL in getArrayOptions

| | |
|---|---|
| **What** | Riscrivere `getArrayOptions()` usando il Query Builder di Eloquent invece di concatenazione SQL |
| **Why** | Eliminare il rischio SQL injection |
| **Effort** | M |
| **Risk** | Medium |

### Step 10.4 — Aggiungere `$fillable` ai modelli

| | |
|---|---|
| **What** | Sostituire `$guarded = []` con `$fillable` espliciti su tutti i modelli |
| **Why** | Sicurezza — mass assignment protection |
| **Effort** | M |
| **Risk** | Medium |

---

## FASE 11 — (Futura) Migrazione a Flux + Tailwind CSS

> **Nota**: Questa fase è **separata e opzionale**. Va affrontata solo DOPO il completamento di tutte le fasi 0-10. Rappresenta la seconda parte dell'Approccio C: passare da Bootstrap 5.3 a Flux UI + Tailwind CSS.

### Step 11.1 — Installare Tailwind CSS accanto a Bootstrap

| | |
|---|---|
| **What** | Configurare Tailwind CSS in coesistenza con Bootstrap 5.3 |
| **Why** | Flux UI richiede Tailwind CSS. Durante la migrazione graduale entrambi i framework CSS saranno presenti |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | FASE 10 completata |

**Azioni**:
1. Installare Tailwind CSS via npm/Vite
2. Configurare un prefisso Tailwind (es. `tw-`) per evitare conflitti con Bootstrap durante la transizione
3. Aggiornare `vite.config.js` per processare sia Bootstrap che Tailwind
4. Verificare che entrambi i framework funzionino simultaneamente

### Step 11.2 — Installare Flux UI

| | |
|---|---|
| **What** | Installare e configurare Flux UI nel progetto |
| **Why** | Componenti UI pronti all'uso, design system coerente |
| **Effort** | S |
| **Risk** | Low |
| **Dependencies** | Step 11.1 |

**Azioni**:
```bash
composer require livewire/flux
```
- Pubblicare gli asset Flux
- Verificare che i componenti Flux si renderizzino correttamente

### Step 11.3 — Sostituire componenti Bootstrap con Flux

| | |
|---|---|
| **What** | Migrare progressivamente i componenti Bootstrap a Flux UI |
| **Why** | Uniformità UI, Flux offre componenti più ricchi e integrati con Livewire |
| **Effort** | XL |
| **Risk** | Medium |
| **Dependencies** | Step 11.2 |

**Azioni**:
1. Sostituire `DynamicField` da HTML Bootstrap a componenti `<flux:*>`
2. Sostituire Bootstrap Modal con `<flux:modal>`
3. Sostituire bottoni Bootstrap con `<flux:button>`
4. Convertire il layout da grid Bootstrap a grid Tailwind
5. Migrare le tabelle report a componenti Flux/Tailwind
6. Rimuovere Bootstrap dal progetto

### Step 11.4 — Rimuovere Bootstrap

| | |
|---|---|
| **What** | Rimuovere Bootstrap 5.3 e usare solo Tailwind CSS |
| **Why** | Una volta completata la migrazione, Bootstrap non è più necessario |
| **Effort** | M |
| **Risk** | Low |
| **Dependencies** | Step 11.3 |

---

## Appendice A — Mapping tipi campo

| Tipo DB (`form_fields.type`) | Vecchio (laravel-form-builder) | Nuovo (Bootstrap 5.3 + Livewire) |
|---|---|---|
| `text` | `$form->add('name', 'text', [...])` | `<input type="text" class="form-control" wire:model="..." />` |
| `select` | `$form->add('name', 'select', ['choices' => [...]])` | `<select class="form-select" wire:model="..."><option>...</option></select>` |
| `date` | `$form->add('name', 'date', [...])` | `<input type="date" class="form-control" wire:model="..." />` |
| `textarea` | `$form->add('name', 'textarea', [...])` | `<textarea class="form-control" wire:model="..."></textarea>` |
| `hidden` | `$form->add('name', 'hidden', [...])` | `<input type="hidden" wire:model="..." />` |
| `number` | `$form->add('name', 'number', [...])` | `<input type="number" class="form-control" wire:model="..." />` |
| `file` | `$form->add('name', 'file', [...])` | `<input type="file" class="form-control" wire:model="..." />` (con `WithFileUploads`) |
| `checkbox` | `$form->add('name', 'checkbox', [...])` | `<input type="checkbox" class="form-check-input" wire:model="..." />` |
| `radio` | `$form->add('name', 'radio', [...])` | `<input type="radio" class="form-check-input" wire:model="..." />` (in `form-check`) |
| `submit` | `$form->add('button', 'submit', [...])` | `<button type="submit" class="btn btn-primary">` |

---

## Appendice B — Mapping struttura Bootstrap

### Form Layout

| Vecchio (Bootstrap 5 + form-builder) | Nuovo (Bootstrap 5.3 + Livewire) |
|---|---|
| `<div class="form-group col-sm-4">` | `<div class="col-sm-4 mb-3">` (Bootstrap grid invariato) |
| `wrapper_params: class:form-group` | Gestito da `DynamicField` component |
| `clearbox` (clearfix) | `<div class="row">` (Bootstrap grid) |
| form-builder validation | Livewire `$this->validate()` + Bootstrap `is-invalid` / `invalid-feedback` |
| `{!! form($form) !!}` | `@livewire('ict-editable-form', ['reportId' => $reportId])` |

### Modali

| Vecchio (Bootstrap + jQuery) | Nuovo (Bootstrap 5.3 + Livewire + Alpine.js) |
|---|---|
| `<div class="modal fade">` + jQuery `.modal('show')` | Bootstrap Modal controllato da Alpine.js `x-show` + `@entangle('showModal')` |
| AJAX `$.get()` per caricare il form | Componente Livewire con `wire:click="openModal"` |
| AJAX `$.post()` per salvare | `wire:submit` / `wire:click="submit"` su Livewire component |

### Bottoni

| Vecchio | Nuovo |
|---|---|
| `<button class="btn btn-success">` | `<button class="btn btn-success" wire:click="...">` (invariato) |
| `<button class="btn btn-danger">` | `<button class="btn btn-danger" wire:click="...">` (invariato) |
| `<button class="btn btn-primary">` | `<button class="btn btn-primary" wire:click="...">` (invariato) |

> **Nota**: I bottoni Bootstrap rimangono invariati. L'unica differenza è l'aggiunta di `wire:click` o `wire:submit` al posto degli event handler jQuery.

---

## Appendice C — Schema componenti Livewire target

```
Packages\IctInterface\Livewire\
├── DynamicForm.php                 # Classe base astratta
├── FilterFormComponent.php         # Form filtri (GET)
├── SearchFormComponent.php         # Form ricerca (GET + search flag)
├── EditableFormComponent.php       # Form create/edit (POST/PUT)
├── ChildFormComponent.php          # Form items child (nested)
├── ModalFormComponent.php          # Form in modali (Bootstrap Modal + Livewire)
├── ReportTable.php                 # (futuro) Tabella report reattiva
└── DynamicSelect.php               # (futuro) Select con ricerca asincrona

Packages\IctInterface\View\Components\
├── DynamicField.php                # Componente che mappa tipo DB → Bootstrap input
├── (componenti esistenti da mantenere/aggiornare)

Packages\IctInterface\Services\
├── DynamicFormService.php          # Lettura configurazione form dal DB
└── (FormService ridotto alle sole operazioni di salvataggio)
```

---

## Timeline suggerita

### Fasi 0-10: Livewire 3 + Bootstrap 5.3

| Fase | Effort stimato | Note |
|---|---|---|
| FASE 0 | 2-3 giorni | Setup ambiente (Laravel 12, Livewire 3, Bootstrap 5.3) |
| FASE 1 | 3-4 giorni | Refactoring architetturale preparatorio |
| FASE 2 | 4-5 giorni | Creazione layer base Livewire |
| FASE 3 | 2-3 giorni | Migrazione FilterForm |
| FASE 4 | 1 giorno | Migrazione SearchForm (simile a FilterForm) |
| FASE 5 | 5-7 giorni | Migrazione form editable (la più complessa) |
| FASE 6 | 3-4 giorni | Migrazione ChildForm |
| FASE 7 | 3-4 giorni | Migrazione ModalForms (Bootstrap Modal + Livewire) |
| FASE 8 | 2-3 giorni | Refactoring StandardController |
| FASE 9 | 1 giorno | Rimozione laravel-form-builder |
| FASE 10 | 3-5 giorni | Pulizia e modernizzazione |
| **TOTALE FASI 0-10** | **~25-40 giorni** | **Obiettivo primario completato** |

### FASE 11 (Futura): Flux + Tailwind CSS

| Fase | Effort stimato | Note |
|---|---|---|
| FASE 11 | 7-10 giorni | Migrazione da Bootstrap 5.3 a Flux + Tailwind CSS |

> **Nota**: Il team è composto da Team Leader + Senior Developer. Le stime sono per un developer a tempo pieno. Con il supporto LLM per la generazione del codice, i tempi possono ridursi significativamente.
>
> **Approccio C**: Le FASI 0-10 producono un sistema completamente funzionante con **Livewire 3 + Bootstrap 5.3**, senza dipendenze da `kris/laravel-form-builder`. La FASE 11 (Flux + Tailwind) è indipendente e può essere pianificata quando il team lo riterrà opportuno.
