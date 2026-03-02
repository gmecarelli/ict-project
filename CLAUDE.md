# CLAUDE.md — IctInterface Project

## Panoramica progetto

Applicazione Laravel 12 con un package custom `IctInterface` in `packages/IctInterface/`.
Il package fornisce un sistema dinamico di interfacce CRUD (report, form, menu) configurate via database.

**Stack tecnologico:** Laravel 12, Livewire 3, Bootstrap 5.3, Maatwebsite Excel 3.1, DomPDF 3.1, PHP 8.2+

## Struttura del progetto

```
src/
├── app/                          # Codice applicativo
│   ├── Actions/                  # ActionHandler custom (es. BooksActionHandler)
│   ├── Exports/                  # AppMapExport (mapping export applicativo)
│   ├── Http/Controllers/         # Controller app (estendono IctController)
│   └── Models/                   # Model app (estendono IctModel)
├── packages/IctInterface/        # Package core
│   ├── config/ict.php            # Configurazione package
│   ├── docs/                     # Documentazione tecnica
│   └── src/
│       ├── Contracts/            # Interfacce (FormActionHandler, FileFieldHandler)
│       ├── Controllers/          # Controller package + Services/
│       ├── Exports/              # MapExport, ReportExport, FilterExportController
│       ├── Livewire/             # Componenti Livewire (10 componenti)
│       ├── Models/               # Model package (IctModel, Form, Report, Attachment...)
│       ├── Providers/            # IctServiceProvider
│       ├── Services/             # DynamicFormService, ActionHandlerResolver, AttachmentService
│       ├── Traits/               # LivewireController, HasAttachments
│       ├── View/Components/      # Blade components (btn-*, nav-sidebar, pagination...)
│       ├── resources/views/      # Viste Blade + Livewire
│       ├── helpers.php           # Helper globali (_log, _date, _currency, _option...)
│       └── routes.php            # Route del package
├── config/ict.php                # Config pubblicata
└── database/migrations/          # 17 migrazioni (con seed data per report/form)
```

## Namespace e autoloading

- `App\` → `app/`
- `Packages\IctInterface\` → `packages/IctInterface/src/`
- Helper globali: `packages/IctInterface/src/helpers.php` (autoloaded via composer)

## Architettura e pattern chiave

### Controller applicativi
I controller estendono `IctController` e usano il trait `LivewireController`:
```php
class BookController extends IctController {
    use LivewireController;
    public function __construct() {
        parent::__construct();
        $this->__init();
        $this->model = new Book();
    }
}
```

### Model applicativi
I model estendono `IctModel` (guarded: `form_id`, `report`, `id`).
Per gli allegati, aggiungere il trait `HasAttachments`.

### ActionHandler (hook CRUD)
Per personalizzare le operazioni CRUD, creare `App\Actions\{StudlyTable}ActionHandler` che estende `BaseActionHandler`.
Metodi disponibili: `beforeStore`, `store`, `afterStore`, `beforeUpdate`, `update`, `afterUpdate`, `beforeDelete`, `delete`, `afterDelete`.

### Sistema allegati
Due modalità:
- **Attachment** (polimorfico): `AttachmentService::store()` — salva file + record in `attachments`
- **Import**: `AttachmentService::storeForImport()` — salva solo file, restituisce metadati

### Export Excel/CSV
`ExcelController` gestisce gli export. L'app estende con `ExportController`.
`MapExport` mappa i valori di lookup (reference → label) nell'export.
Per personalizzare il mapping, estendere `MapExport` in `app/Exports/AppMapExport.php`.

### Form dinamici
I form sono configurati nel DB (tabelle `forms` e `form_fields`).
`DynamicFormService` carica proprietà, campi e regole di validazione.
Tipi di form: `editable`, `filter`, `search`, `modal`, `child`.

### Tipi di campo supportati
`text`, `textarea`, `select`, `multiselect`, `radio`, `checkbox`, `date`, `number`, `email`, `password`, `file`, `hidden`, `crypted`

### Select options DSL
- Da DB: `table:options,code:code,label:label,reference:TIPO`
- Diretto: `#key1:val1,key2:val2`

## Convenzioni di codice

- I controller applicativi vanno in `app/Http/Controllers/`
- Gli ActionHandler vanno in `app/Actions/` con naming `{StudlyTable}ActionHandler`
- I FileFieldHandler vanno in `app/Actions/` con naming `{StudlyTable}{StudlyField}Handler`
- I model applicativi vanno in `app/Models/` e estendono `IctModel`
- La configurazione custom va in `config/ict.php` (action_handlers, model_map, file_handlers)

## Componenti Livewire registrati

| Tag | Componente |
|-----|-----------|
| `ict-editable-form` | EditableFormComponent |
| `ict-filter-form` | FilterFormComponent |
| `ict-search-form` | SearchFormComponent |
| `ict-child-form` | ChildFormComponent |
| `ict-modal-form` | ModalFormComponent |
| `ict-delete-confirm` | DeleteConfirmComponent |
| `ict-attachment-modal` | AttachmentModalComponent |
| `ict-user-profile-manager` | UserProfileManagerComponent |
| `ict-multicheck-manager` | MulticheckManagerComponent |
| `ict-bool-switch` | BoolSwitchComponent |

## Blade components

Prefisso `ict::`: `btn-create`, `btn-delete`, `btn-edit`, `btn-export`, `nav-sidebar`, `pagination`, `title-form`, `title-page`, `dynamic-field`

## Configurazione (`config/ict.php`)

| Chiave | Default | Descrizione |
|--------|---------|-------------|
| `upload_dir` | `upload` | Directory base upload |
| `upload_max_size` | `10240` | Dimensione max upload (KB) |
| `logger_level` | `1` | Livello di logging |
| `css_color` | `#4d7496` | Colore tema |
| `table_users` | `users` | Tabella utenti |
| `action_handlers` | `[]` | Mapping tabella → ActionHandler class |
| `model_map` | `[]` | Mapping model → tabella |
| `file_handlers` | `[]` | Mapping campo → FileFieldHandler class |

## Helper globali principali

- `_log($channel)` — Logger (info, debug, error, sql)
- `_option($code, $reference)` — Recupera valori dalla tabella options
- `_user()` / `_is_admin()` / `_profiles()` — Info utente corrente
- `_date()` / `_date_time()` / `_currency()` / `_number()` / `_float()` — Formattazione
- `_convertDateItToDb()` / `_convertDateDbToIt()` — Conversione date IT↔DB
- `_encrypt()` / `_decrypt()` — Cifratura campi
- `_commit()` / `_rollback()` — Gestione transazioni con logging
- `ddr()` — Debug con rollback (solo sviluppo)

## Middleware

- `islogged` (alias di `AuthIct`) — Verifica autenticazione custom ICT

## Database

Le tabelle di sistema del package sono: `menus`, `reports`, `report_columns`, `forms`, `form_fields`, `profiles`, `profile_roles`, `profiles_has_users`, `options`, `multicheck_actions`, `attachments`.
Le migrazioni includono seed data per i report e form di configurazione del package.

## Note per lo sviluppo

- NON modificare la struttura delle tabelle di sistema del package
- Per aggiungere una nuova entita CRUD: creare migration, model (extends IctModel), controller (extends IctController, use LivewireController), e configurare report/form via seed o UI
- Il package usa Bootstrap 5.3 per il frontend — non usare Tailwind
- jQuery e' stato rimosso — tutta la logica reattiva e' gestita da Livewire
- Le viste del package usano il namespace `ict::` (es. `ict::report`, `ict::livewire.editable-form`)
