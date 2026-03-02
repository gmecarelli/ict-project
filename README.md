# IctInterface — Laravel Dynamic CRUD Package

Package Laravel per la generazione dinamica di interfacce CRUD, report e form, interamente configurabili da database.

## Stack tecnologico

| Tecnologia | Versione | Ruolo |
|-----------|----------|-------|
| PHP | >= 8.2 | Runtime |
| Laravel | 12.x | Framework |
| Livewire | 3.x | Componenti reattivi |
| Bootstrap | 5.3 | UI framework |
| Maatwebsite Excel | 3.1 | Export Excel/CSV |
| DomPDF | 3.1 | Generazione PDF |
| Laravel Sanctum | 4.x | Autenticazione API |

## Installazione

Il package risiede in `packages/IctInterface/` e viene autoloadato via PSR-4 in `composer.json`:

```json
{
  "autoload": {
    "psr-4": {
      "Packages\\IctInterface\\": "packages/IctInterface/src/"
    },
    "files": [
      "packages/IctInterface/src/helpers.php"
    ]
  }
}
```

Il `IctServiceProvider` viene registrato automaticamente e si occupa di:
- Merge della configurazione `config/ict.php`
- Registrazione dei singleton (FormService, ReportService, MenuService, DynamicFormService, ActionHandlerResolver, AttachmentService)
- Registrazione dei 10 componenti Livewire
- Registrazione dei 9 Blade components
- Caricamento delle viste con namespace `ict`
- Caricamento delle route del package
- Pubblicazione degli asset statici

### Configurazione logging

Nel file `config/logging.php`, aggiungere i canali:

```php
'log' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/debug.log'),
    'level'  => 'debug',
],
'cronlog' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/cron_debug.log'),
    'level'  => 'debug',
],
```

### Pubblicazione asset

```bash
php artisan vendor:publish --tag=assets --force
```

### Migrazioni

```bash
php artisan migrate
```

Le 17 migrazioni creano le tabelle di sistema del package e includono seed data per report e form di configurazione.

---

## Funzionalita principali

### 1. Sistema Report dinamici

I report sono liste di record configurate interamente da database. Ogni report definisce:
- **Tabella sorgente** e query di base
- **Colonne** con tipo, label, posizione, ordinamento e parametri (tabella `report_columns`)
- **Azioni** disponibili per riga (edit, delete, disable, custom)
- **Filtri** associati tramite form di tipo `filter`
- **Somme/aggregazioni** su campi numerici

**Tipi di colonna supportati:** `text`, `date`, `integer`, `float`, `currency`, `percent`, `crypted`, `stoplight`, `stoplight_currency`, `stoplight_integer`, `stoplight_float`, `stoplight_percent`

La vista `ict::report` renderizza automaticamente la tabella con paginazione, ordinamento e filtri.

### 2. Sistema Form dinamici

I form sono configurati nel database tramite le tabelle `forms` e `form_fields`. Il `DynamicFormService` carica la configurazione e i componenti Livewire la renderizzano.

**Tipi di form:**

| Tipo | Componente Livewire | Descrizione |
|------|-------------------|-------------|
| `editable` | `ict-editable-form` | Form principale di creazione/modifica record |
| `filter` | `ict-filter-form` | Barra filtri per i report |
| `search` | `ict-search-form` | Ricerca rapida |
| `modal` | `ict-modal-form` | Form in finestra modale |
| `child` | `ict-child-form` | Form per record figli (relazione parent-child) |

**Tipi di campo supportati:**

| Tipo | Descrizione |
|------|-------------|
| `text` | Input di testo |
| `textarea` | Area di testo multilinea |
| `select` | Menu a tendina |
| `multiselect` | Selezione multipla (salva come JSON) |
| `radio` | Bottoni radio |
| `checkbox` | Checkbox |
| `date` | Selettore data |
| `number` | Input numerico |
| `email` | Input email |
| `password` | Input password |
| `file` | Upload file |
| `hidden` | Campo nascosto |
| `crypted` | Campo cifrato (salvato criptato nel DB) |

**DSL per le opzioni di select/radio:**

Le opzioni delle select possono essere definite in due modi nel campo `type_attr`:

```
# Sintassi da database:
table:options,code:code,label:label,reference:TIPO,orderBy:label,order:ASC

# Sintassi diretta (prefisso #):
#key1:Valore 1,key2:Valore 2,key3:Valore 3
```

Modificatori speciali nei filtri di select:
- `@variabile` — prende il valore dalla request
- `&valore` — valore fisso
- `#` — usa il valore di contesto passato al componente
- `EDIT` — prende l'ID dal segmento URL in modalita edit

**Validazione:**

Le regole di validazione sono definite per ogni campo nel campo `rules` di `form_fields` usando la sintassi standard Laravel. Il placeholder `#id` viene sostituito automaticamente con il `recordId` per le regole `unique`.

### 3. Action Handler — Hook CRUD personalizzabili

Il sistema di Action Handler permette di intercettare e personalizzare le operazioni CRUD senza modificare il package.

**Risoluzione automatica:**
1. Mapping esplicito in `config('ict.action_handlers')`
2. Convention: `App\Actions\{StudlyCase(tableName)}ActionHandler`

**Interfaccia `FormActionHandler`:**

```php
interface FormActionHandler
{
    // PRE-operazione: modifica i dati o aborta (return null)
    public function beforeStore(string $tableName, array $data, int $formId): ?array;
    public function beforeUpdate(string $tableName, array $data, int $formId, int $recordId): ?array;
    public function beforeDelete(string $tableName, int $recordId, string $action): bool;

    // SOSTITUZIONE: gestisci l'operazione al posto del default
    // Return valore = gestito; return null = usa il default
    public function store(string $tableName, array $data, int $formId): ?int;
    public function update(string $tableName, array $data, int $formId, int $recordId): ?bool;
    public function delete(string $tableName, int $recordId, string $action): ?bool;

    // POST-operazione
    public function afterStore(string $tableName, array $data, int $newRecordId, int $formId): void;
    public function afterUpdate(string $tableName, array $data, int $recordId, int $formId): void;
    public function afterDelete(string $tableName, int $recordId, string $action): void;
}
```

**Esempio di implementazione:**

```php
// app/Actions/BooksActionHandler.php
class BooksActionHandler extends BaseActionHandler
{
    public function beforeStore(string $tableName, array $data, int $formId): ?array
    {
        $data['slug'] = Str::slug($data['title'] ?? '');
        return $data;
    }

    public function afterDelete(string $tableName, int $recordId, string $action): void
    {
        _log()->info("Book {$recordId} deleted");
    }
}
```

### 4. Sistema allegati polimorfico

Il sistema di allegati supporta due modalita operative:

**Modalita A — Attachment (polimorfico):**

Salva il file su disco e registra un record nella tabella `attachments` con relazione polimorfica `morphMany`.

```php
// Nel model, aggiungere il trait:
use HasAttachments;

// Upload:
$attachment = app(AttachmentService::class)->store(
    $file, Book::class, $bookId, 'Descrizione', 'books'
);

// Accesso:
$book->attachments;    // Collection di Attachment
$attachment->url;      // URL pubblica
$attachment->full_path; // Path su disco
```

**Modalita B — Import:**

Salva il file su disco senza registrazione nel DB. Utile per file temporanei di importazione.

```php
$meta = app(AttachmentService::class)->storeForImport($file, 'imports');
// Restituisce: server_name, original_name, path, full_path, ext
```

**File Field Handler:**

Per eseguire logica custom dopo l'upload di un campo file specifico, implementare l'interfaccia `FileFieldHandler`:

```php
// app/Actions/BooksCoperturaHandler.php
class BooksCoperturaHandler implements FileFieldHandler
{
    public function handle(
        string $fullPath,
        array $formData,
        int $recordId,
        string $tableName,
        string $fieldName
    ): void {
        // Logica post-upload (es. generazione thumbnail)
    }
}
```

Risoluzione: `config('ict.file_handlers')` oppure convention `App\Actions\{Table}{Field}Handler`.

### 5. Export Excel/CSV

Il sistema di export e gestito da `ExcelController` e usa Maatwebsite Excel.

**Funzionalita:**
- Export di qualsiasi report applicativo con filtri attivi
- Formattazione automatica colonne (date, valute, numeri, percentuali)
- Mapping valori di lookup (da codice a label human-readable) tramite `MapExport`
- Supporto campi criptati (decriptati in fase di export)
- Colonne auto-dimensionate

**Personalizzazione:**

L'applicazione estende `ExcelController` con `ExportController` per definire le colonne da escludere:

```php
class ExportController extends ExcelController
{
    protected $skip = ['id', 'is_enabled', 'is_required', 'created_at', 'updated_at'];
}
```

Per personalizzare il mapping dei valori, estendere `MapExport` in `app/Exports/AppMapExport.php`.

### 6. Generazione PDF

Il `PDFController` genera report in formato PDF usando DomPDF, con template Blade personalizzabili nelle viste `ict::pdf.*`.

### 7. Gestione menu

I menu di navigazione sono configurati nel database (tabella `menus`). Ogni voce di menu e associata a un report. Il componente Blade `<x-ict-nav-sidebar />` renderizza automaticamente il menu laterale.

### 8. Gestione utenti e profili

- **Autenticazione custom** tramite `IctAuthController` e middleware `islogged` (`AuthIct`)
- **Profili utente** con ruoli e permessi (tabelle `profiles`, `profile_roles`, `profiles_has_users`)
- **Componente Livewire** `ict-user-profile-manager` per la gestione del profilo

### 9. Tabella Options (parametri di utilita)

La tabella `options` funge da dizionario di lookup configurabile. Ogni record ha:
- `reference` — categoria/gruppo
- `code` — codice identificativo
- `label` — etichetta visualizzata
- `icon` — classe icona opzionale
- `class` — classe CSS opzionale

Accessibile tramite l'helper globale `_option($code, $reference)`.

### 10. Multicheck (azioni massive)

Il componente `ict-multicheck-manager` permette di selezionare piu record nel report e applicare azioni di massa configurate nella tabella `multicheck_actions`.

---

## Blade Components

| Componente | Utilizzo |
|-----------|----------|
| `<x-ict-btn-create />` | Pulsante "Nuovo" |
| `<x-ict-btn-edit :id="$id" />` | Pulsante "Modifica" |
| `<x-ict-btn-delete :id="$id" />` | Pulsante "Elimina" |
| `<x-ict-btn-export />` | Pulsante "Esporta" |
| `<x-ict-nav-sidebar />` | Menu laterale navigazione |
| `<x-ict-pagination :data="$data" />` | Paginazione Bootstrap |
| `<x-ict-title-page />` | Titolo pagina |
| `<x-ict-title-form />` | Titolo form |
| `<x-ict-dynamic-field />` | Campo form dinamico |

## Componenti Livewire

| Tag | Descrizione |
|-----|-------------|
| `<livewire:ict-editable-form />` | Form di creazione/modifica |
| `<livewire:ict-filter-form />` | Barra filtri report |
| `<livewire:ict-search-form />` | Ricerca rapida |
| `<livewire:ict-modal-form />` | Form modale |
| `<livewire:ict-child-form />` | Form record figli |
| `<livewire:ict-delete-confirm />` | Conferma eliminazione |
| `<livewire:ict-attachment-modal />` | Gestione allegati |
| `<livewire:ict-user-profile-manager />` | Gestione profilo utente |
| `<livewire:ict-multicheck-manager />` | Selezione multipla + azioni |
| `<livewire:ict-bool-switch />` | Toggle booleano |

Tutti i componenti Livewire estendono la classe astratta `DynamicForm` che fornisce:
- `mountForm(formId, recordId, model)` — Inizializzazione da configurazione DB
- `populateFromModel(model)` — Popolamento campi da un model Eloquent
- `getRules()` — Regole di validazione dal DB
- `submit()` — Metodo astratto per il salvataggio

---

## Helper globali

Il file `helpers.php` fornisce funzioni globali accessibili ovunque nell'applicazione:

| Helper | Descrizione |
|--------|-------------|
| `_log($channel)` | Logger con metodi `info()`, `debug()`, `error()`, `sql()` |
| `_option($code, $ref)` | Recupera valori dalla tabella options |
| `_user()` | Utente corrente dalla sessione |
| `_is_admin()` | Verifica ruolo admin |
| `_profiles()` | Profili utente corrente |
| `_date($date)` | Formatta data in formato italiano (dd/mm/yyyy) |
| `_date_time($date)` | Formatta data/ora in formato italiano |
| `_currency($val)` | Formatta come valuta EUR |
| `_number($val)` | Formatta numero intero con separatore migliaia |
| `_float($val)` | Formatta numero decimale |
| `_percent($val)` | Formatta come percentuale |
| `_convertDateItToDb($date)` | Converte dd/mm/yyyy → yyyy-mm-dd |
| `_convertDateDbToIt($date)` | Converte yyyy-mm-dd → dd/mm/yyyy |
| `_encrypt($val)` | Cifra un valore |
| `_decrypt($val)` | Decifra un valore |
| `_commit($file, $line)` | Commit transazione con logging |
| `_rollback($file, $line)` | Rollback transazione con logging |
| `_sql($file, $line)` | Log delle query SQL eseguite |
| `_find_date($date, $days)` | Calcola una data spostata di N giorni |
| `_is_valid_date($date)` | Verifica validita di una data |
| `_select_months($name)` | Genera HTML select con i mesi |
| `ddr(...$var)` | Debug dump con rollback (solo sviluppo) |

---

## Configurazione

File `config/ict.php`:

```php
return [
    'upload_dir'       => env('UPLOAD_DIR', 'upload'),
    'upload_bill_dir'  => env('UPLOAD_BILL_DIR', 'upload/bills'),
    'upload_max_size'  => env('UPLOAD_MAX_SIZE', 10240),    // KB
    'logger_level'     => env('LOGGER_LEVEL', 1),           // 0=off, 1=info, 2=debug
    'app_url'          => env('APP_URL', 'http://localhost:8040'),
    'css_color'        => env('APP_CSS_COLOR', '#4d7496'),
    'table_users'      => env('TABLE_USERS', 'users'),
    'action_handlers'  => [],  // ['books' => BooksActionHandler::class]
    'model_map'        => [],  // ['books' => Book::class]
    'file_handlers'    => [],  // ['books.cover' => BooksCoverHandler::class]
];
```

---

## Guida rapida: aggiungere una nuova entita CRUD

1. **Migration** — Creare la tabella:
   ```bash
   php artisan make:migration create_products_table
   ```

2. **Model** — Creare in `app/Models/` estendendo `IctModel`:
   ```php
   class Product extends IctModel
   {
       use HasFactory;
   }
   ```

3. **Controller** — Creare in `app/Http/Controllers/`:
   ```php
   class ProductController extends IctController
   {
       use LivewireController;

       public function __construct()
       {
           parent::__construct();
           $this->__init();
           $this->model = new Product();
       }
   }
   ```

4. **Route** — Aggiungere in `routes/web.php`:
   ```php
   Route::resource('/products', ProductController::class);
   ```

5. **Seed DB** — Inserire record in `reports`, `report_columns`, `forms` e `form_fields`

6. **Menu** — Inserire una voce in `menus` associata al report

7. **(Opzionale) ActionHandler** — Creare `app/Actions/ProductsActionHandler.php` per logica custom

8. **(Opzionale) Allegati** — Aggiungere `use HasAttachments;` nel model

---

## Schema database di sistema

```
menus ──────────────┐
                    │ 1:N
reports ────────────┤
  │                 │
  │ 1:N             │ 1:N
  ▼                 ▼
report_columns    forms
                    │
                    │ 1:N
                    ▼
                  form_fields

profiles ──── profiles_has_users ──── users
  │
  │ 1:N
  ▼
profile_roles

options              (tabella di lookup: reference / code / label)
multicheck_actions   (azioni per selezione multipla)
attachments          (allegati polimorfici: attachable_type + attachable_id)
```

---

## Struttura del progetto

```
src/
├── app/
│   ├── Actions/                  # ActionHandler e FileFieldHandler custom
│   ├── Exports/                  # AppMapExport (mapping export)
│   ├── Http/Controllers/         # Controller applicativi
│   └── Models/                   # Model applicativi (extends IctModel)
├── packages/IctInterface/
│   ├── config/ict.php            # Configurazione package
│   ├── docs/                     # Documentazione tecnica
│   └── src/
│       ├── Contracts/            # FormActionHandler, FileFieldHandler, BaseActionHandler
│       ├── Controllers/          # IctController, ExcelController, PDFController...
│       │   └── Services/         # FormService, ReportService, MenuService, Logger
│       ├── Exports/              # MapExport, ReportExport, FilterExportController
│       ├── Livewire/             # 10 componenti Livewire + DynamicForm base
│       ├── Mail/                 # Mailable classes
│       ├── Middleware/           # AuthIct (islogged)
│       ├── Models/               # IctModel, Form, Report, Attachment, Menu...
│       ├── Providers/            # IctServiceProvider
│       ├── Services/             # DynamicFormService, ActionHandlerResolver, AttachmentService
│       ├── Traits/               # LivewireController, HasAttachments
│       ├── View/Components/      # 9 Blade components
│       ├── resources/
│       │   ├── views/            # Blade templates (layouts, livewire, components, pdf)
│       │   └── assets/           # CSS, JS, immagini
│       ├── helpers.php           # Funzioni helper globali
│       └── routes.php            # Route del package
├── config/ict.php                # Config pubblicata
├── database/migrations/          # 17 migrazioni con seed data
└── routes/web.php                # Route applicative
```

## Licenza

MIT
