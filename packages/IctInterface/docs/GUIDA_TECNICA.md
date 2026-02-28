# IctInterface - Guida Tecnica

Guida completa per l'installazione, configurazione e utilizzo del package **IctInterface** in un progetto Laravel.

---

## Indice

1. [Panoramica](#1-panoramica)
2. [Requisiti e Installazione](#2-requisiti-e-installazione)
3. [Configurazione](#3-configurazione)
4. [Architettura del Package](#4-architettura-del-package)
5. [Schema Database](#5-schema-database)
6. [Autenticazione e Autorizzazione](#6-autenticazione-e-autorizzazione)
7. [Il Sistema Data-Driven](#7-il-sistema-data-driven)
8. [Report (Tabelle Dati)](#8-report-tabelle-dati)
9. [Form Dinamici](#9-form-dinamici)
10. [Componenti Livewire](#10-componenti-livewire)
11. [Componenti Blade](#11-componenti-blade)
12. [Creare un Nuovo Modulo CRUD](#12-creare-un-nuovo-modulo-crud)
13. [DSL dei Parametri](#13-dsl-dei-parametri)
14. [Tipi di Dato per Colonne Report](#14-tipi-di-dato-per-colonne-report)
15. [Tipi di Campo per Form](#15-tipi-di-campo-per-form)
16. [Filtri e Ricerca](#16-filtri-e-ricerca)
17. [Sistema Multicheck (Azioni di Massa)](#17-sistema-multicheck-azioni-di-massa)
18. [Upload File e Allegati](#18-upload-file-e-allegati)
19. [Export Excel](#19-export-excel)
20. [Sistema Profili e Ruoli](#20-sistema-profili-e-ruoli)
21. [Tabella Options (Parametri di Utilita')](#21-tabella-options-parametri-di-utilita)
22. [Helper Globali](#22-helper-globali)
23. [Logger](#23-logger)
24. [Rotte del Package](#24-rotte-del-package)
25. [Personalizzare il Layout](#25-personalizzare-il-layout)

---

## 1. Panoramica

IctInterface e' un package Laravel che fornisce un'interfaccia amministrativa completa e **data-driven**. La sua caratteristica principale e' che report (tabelle dati), form e menu sono configurati interamente tramite record nel database, senza necessita' di scrivere codice per ogni nuova entita'.

**Stack tecnologico:**
- Laravel 12
- Livewire 3
- Alpine.js
- Bootstrap 5.3
- FontAwesome (icone)
- Maatwebsite/Excel (export)

**Cosa offre il package:**
- Autenticazione e autorizzazione basata su profili/ruoli
- Menu sidebar dinamico generato dal database
- Report tabulari con paginazione, ordinamento, filtri
- Form CRUD dinamici con validazione
- Form padre-figlio (master-detail)
- Form modali
- Azioni di massa (multicheck)
- Upload file e gestione allegati
- Export Excel
- Tipi di dato avanzati (enum, link, switch, stoplight, ecc.)
- Crittografia campi sensibili
- Logger configurabile

---

## 2. Requisiti e Installazione

### Requisiti

- PHP >= 8.2
- Laravel >= 11
- Livewire 3
- Alpine.js
- Bootstrap 5.3
- Maatwebsite/Excel 3.x

### Installazione

**1. Aggiungere il package come repository locale in `composer.json`:**

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/IctInterface"
        }
    ]
}
```

**2. Richiedere il package:**

```bash
composer require ict/interface
```

**3. Registrare il Service Provider** in `config/app.php` o in `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->register(\Packages\IctInterface\Providers\IctServiceProvider::class);
}
```

**4. Registrare il middleware `islogged`:**

```php
// app/Providers/AppServiceProvider.php (o bootstrap/app.php)
use Packages\IctInterface\Middleware\AuthIct;

// In AppServiceProvider::boot()
$this->app['router']->aliasMiddleware('islogged', AuthIct::class);
```

**5. Pubblicare gli asset:**

```bash
php artisan vendor:publish --tag=assets
```

Gli asset vengono copiati in `public/ict-assets/` (CSS, JS, immagini, FontAwesome).

**6. Eseguire le migration:**

```bash
php artisan migrate
```

Le migration creano le tabelle di sistema: `menus`, `reports`, `report_columns`, `forms`, `form_fields`, `profiles`, `profile_roles`, `profiles_has_users`, `options`, `multicheck_actions`. Vengono anche inseriti i dati di seed iniziali per le voci di configurazione del package stesso.

**7. Aggiungere l'autoload degli helper** in `composer.json`:

```json
{
    "autoload": {
        "files": [
            "packages/IctInterface/src/helpers.php"
        ]
    }
}
```

Poi eseguire:

```bash
composer dump-autoload
```

---

## 3. Configurazione

Il file `config/ict.php` viene automaticamente mergiato dal Service Provider. Le chiavi disponibili sono:

| Chiave | Env | Default | Descrizione |
|---|---|---|---|
| `upload_dir` | `UPLOAD_DIR` | `upload` | Directory base per gli upload |
| `upload_bill_dir` | `UPLOAD_BILL_DIR` | `upload/bills` | Directory per upload fatture |
| `logger_level` | `LOGGER_LEVEL` | `1` | Livello di log: 0=tutto, 1=debug+sql+error, 2=solo errori |
| `app_url` | `APP_URL` | `http://localhost:8040` | URL dell'applicazione |
| `css_color` | `APP_CSS_COLOR` | `#4d7496` | Colore tema CSS (variabile `--custom-bg`) |
| `table_users` | `TABLE_USERS` | `users` | Nome della tabella utenti (configurabile) |

Per sovrascrivere i valori, aggiungere le variabili nel `.env`:

```env
UPLOAD_DIR=upload
LOGGER_LEVEL=1
APP_CSS_COLOR=#336699
TABLE_USERS=users
```

---

## 4. Architettura del Package

### Struttura directory

```
packages/IctInterface/
├── config/ict.php                    # Configurazione
└── src/
    ├── Providers/IctServiceProvider.php
    ├── Controllers/
    │   ├── IctController.php         # Controller base
    │   ├── Auth/IctAuthController.php
    │   ├── Ajax/AjaxController.php
    │   ├── AttachmentController.php
    │   ├── ExcelController.php
    │   ├── MenuController.php
    │   ├── ReportController.php
    │   ├── ReportColumnController.php
    │   ├── FormController.php
    │   ├── FormFieldController.php
    │   ├── ProfileController.php
    │   ├── ProfileRoleController.php
    │   ├── OptionController.php
    │   ├── PDFController.php
    │   └── Services/
    │       ├── ApplicationService.php   # Formattazione dati + select
    │       ├── FormService.php          # Salvataggio form + upload
    │       ├── ReportService.php        # Query builder + paginazione
    │       ├── MenuService.php          # Costruzione menu sidebar
    │       ├── MulticheckController.php # Gestione azioni di massa
    │       └── Logger.php               # Logger configurabile
    ├── Services/
    │   └── DynamicFormService.php       # Bridge Livewire per form
    ├── Support/
    │   └── BaseService.php              # Classe base dei servizi
    ├── Livewire/
    │   ├── DynamicForm.php              # Classe astratta base
    │   ├── FilterFormComponent.php      # Filtri report
    │   ├── SearchFormComponent.php      # Ricerca report
    │   ├── EditableFormComponent.php    # Form CRUD completo
    │   ├── ChildFormComponent.php       # Form figlio (detail)
    │   ├── ModalFormComponent.php       # Form in modale
    │   ├── DeleteConfirmComponent.php   # Conferma eliminazione
    │   ├── MulticheckManagerComponent.php
    │   ├── BoolSwitchComponent.php      # Toggle booleano inline
    │   └── UserProfileManagerComponent.php
    ├── Models/
    │   ├── IctModel.php                 # Model base
    │   ├── Menu.php, Report.php, ReportColumn.php
    │   ├── Form.php, FormField.php
    │   ├── Profile.php, ProfileRole.php
    │   ├── Option.php, IctUser.php
    │   ├── Attachment.php, AttachmentArchive.php
    │   └── MulticheckAction.php
    ├── Traits/
    │   ├── LivewireController.php       # Trait corrente
    │   └── StandardController.php       # @deprecated
    ├── View/Components/                 # Blade components x-ict-*
    ├── Middleware/AuthIct.php
    ├── helpers.php
    ├── routes.php
    └── resources/
        ├── assets/                      # CSS, JS, immagini
        └── views/                       # Viste Blade
```

### Service Provider

`IctServiceProvider` registra:

**Singletons:**
- `FormService` - gestione salvataggio form, upload, validazione
- `ReportService` - query builder, paginazione, formattazione
- `MenuService` - costruzione menu sidebar
- `DynamicFormService` - bridge per componenti Livewire

**Blade Components** (prefisso `x-ict-`):**
- `BtnCreate`, `BtnDelete`, `BtnEdit`, `BtnExport`
- `NavSidebar`, `Pagination`, `TitleForm`, `TitlePage`
- `DynamicField`

**Livewire Components:**
- `ict-filter-form`, `ict-search-form`, `ict-editable-form`
- `ict-child-form`, `ict-modal-form`, `ict-delete-confirm`
- `ict-user-profile-manager`, `ict-multicheck-manager`, `ict-bool-switch`

### Flusso di una richiesta tipica

```
HTTP Request
    ↓
AuthIct middleware
    → verifica session('loggedUser')
    → verifica ?report=N nei parametri
    → verifica permessi profilo/ruolo
    ↓
Resource Controller (usa trait LivewireController)
    ↓
    index() → ReportService
        → makeWhereFilter()     → legge filtri da GET
        → loadReportProperties() → da tabella reports
        → loadReportColumns()    → da tabella report_columns
        → loadTableData()        → query + formattazione celle
    ↓
    view('ict::report')
        @livewire('ict-filter-form')
        @livewire('ict-delete-confirm')
        Tabella dati con paginazione
    ↓
    create()/edit() → view con flag useLivewireForm=true
        @livewire('ict-editable-form')
            → DynamicFormService::getFormFields()
            → DynamicFormService::getValidationRules()
            → render @foreach fields → <x-ict-dynamic-field>
            → submit: validate → encrypt → upload → DB insert/update
```

---

## 5. Schema Database

### Tabelle di sistema (create dal package)

#### `menus`
Voci del menu sidebar di primo livello.

| Campo | Tipo | Descrizione |
|---|---|---|
| `id` | bigint PK | |
| `title` | varchar(50) UNIQUE | Etichetta del menu |
| `tooltip` | varchar(75) | Tooltip al passaggio mouse |
| `icon` | varchar(75) | Classe FontAwesome (es. `fas fa-wrench`) |
| `position` | int | Ordinamento (crescente) |
| `is_enabled` | int | 1=attivo, 0=disattivo |

#### `reports`
Ogni report corrisponde a una pagina tabulare accessibile dal menu.

| Campo | Tipo | Descrizione |
|---|---|---|
| `id` | bigint PK | |
| `menu_id` | FK → menus | Menu padre |
| `title` | varchar | Titolo della pagina |
| `route` | varchar(150) | Nome della rotta Laravel (es. `menu`, `report`) |
| `table` | varchar(150) | Tabella DB da cui leggere i dati |
| `blade` | varchar(150) | Template Blade (default: `report`) |
| `sum` | varchar | Campi per somme nel footer (DSL) |
| `position` | int | Ordinamento nel sottomenu |
| `where_condition` | varchar(100) | Clausola WHERE SQL aggiuntiva |
| `group_by` | varchar(150) | GROUP BY SQL |
| `href_url` | varchar | URL base per le azioni |
| `has_create_button` | int | 0=no, 1=si (pulsante "Nuovo") |
| `has_edit_button` | int | 0=no, 1=si (pulsanti modifica/elimina) |
| `class_delete_button` | varchar | `destroy` (elimina) o `cancel` (disabilita) |
| `multicheck_reference` | varchar | Reference per azioni multicheck (nullable) |
| `is_enabled` | bool | Abilitato |
| `is_show_menu` | bool | Visibile nel menu sidebar |
| `is_editable` | bool | Modificabile |

#### `report_columns`
Colonne visibili nella tabella di un report.

| Campo | Tipo | Descrizione |
|---|---|---|
| `id` | bigint PK | |
| `report_id` | FK → reports | Report di appartenenza |
| `label` | varchar(150) | Intestazione colonna |
| `field` | varchar(150) | Nome campo nella tabella DB |
| `type` | varchar(150) | Tipo di dato (vedi [sezione 14](#14-tipi-di-dato-per-colonne-report)) |
| `type_params` | varchar | Parametri del tipo (DSL) |
| `position` | int | Ordinamento colonna |
| `is_enabled` | int | 1=visibile, 0=nascosta |
| `is_crypted` | int | 1=campo crittografato |

#### `forms`
Definizione dei form associati ai report.

| Campo | Tipo | Descrizione |
|---|---|---|
| `id` | bigint PK | |
| `report_id` | FK → reports | Report associato |
| `name` | varchar(50) | Nome/prefisso rotta (es. `menu`, `report`) |
| `title` | varchar(45) | Titolo del form |
| `table` | varchar(50) | Tabella di salvataggio |
| `clean_at` | int | N. campi per riga nel form |
| `id_modal` | varchar(100) | ID modale HTML (null = pagina intera) |
| `modal_width` | varchar(10) | Larghezza modale (es. `80%`) |
| `type` | varchar(10) | Tipo: `editable`, `filter`, `search`, `modal` |
| `data` | varchar | Template Blade. Prefisso `view:` per path personalizzato |
| `id_child` | FK → forms | ID del form figlio (master-detail) |
| `is_enabled` | bool | |

**Tipi di form:**
- `editable` - form di creazione/modifica record
- `filter` - form filtro nella pagina report
- `search` - form ricerca avanzata
- `modal` - form aperto in modale

#### `form_fields`
Campi di un form.

| Campo | Tipo | Descrizione |
|---|---|---|
| `id` | bigint PK | |
| `form_id` | FK → forms | Form di appartenenza |
| `label` | varchar(150) | Etichetta del campo |
| `name` | varchar(150) | Nome del campo (corrisponde alla colonna DB) |
| `type` | varchar(50) | Tipo HTML del campo (vedi [sezione 15](#15-tipi-di-campo-per-form)) |
| `type_attr` | varchar(255) | Parametri per select/radio (DSL opzioni) |
| `position` | int | Ordinamento |
| `bootstrap_cols` | int | Colonne Bootstrap (1-12) |
| `attr_params` | varchar(150) | Attributi HTML del campo (DSL) |
| `default_value` | varchar(50) | Valore di default |
| `rules` | varchar | Regole di validazione Laravel |
| `is_guarded` | bool | 1=campo escluso dal salvataggio |
| `is_available` | bool | 1=disponibile, 0=non mostrato |
| `is_enabled` | bool | 1=attivo |
| `is_crypted` | int | 1=valore crittografato con `Crypt::encryptString()` |

#### `profiles`

| Campo | Tipo | Descrizione |
|---|---|---|
| `id` | bigint PK | |
| `name` | varchar(40) UNIQUE | Nome profilo |
| `is_enabled` | int | |

#### `profile_roles`
Permessi per profilo su ciascun report.

| Campo | Tipo | Descrizione |
|---|---|---|
| `id` | bigint PK | |
| `profile_id` | varchar(40) | ID profilo |
| `report_id` | int | ID report |
| `has_create_button` | bool | Puo' creare nuovi record |
| `has_edit_button` | bool | Puo' modificare record |
| `is_all_owner` | bool | Vede tutti i record o solo i propri |
| `fields_disabled` | mediumText | JSON con campi disabilitati per form |
| `is_enabled` | bool | |

#### `profiles_has_users`
Tabella pivot utente-profilo.

| Campo | Tipo | Descrizione |
|---|---|---|
| `key_id` | varchar PK | Chiave generata (`{random}-p{profileId}u{userId}`) |
| `user_id` | FK → users | |
| `profile_id` | FK → profiles | |

#### `options`
Tabella chiave-valore per parametri, enumerazioni, lookup.

| Campo | Tipo | Descrizione |
|---|---|---|
| `id` | bigint PK | |
| `code` | varchar(50) | Codice/chiave |
| `label` | varchar(75) | Etichetta visualizzata |
| `reference` | varchar(50) | Raggruppamento (es. `YN`, `ED`, `MONTH`, `TYPEFORM`) |
| `icon` | varchar(50) | Classe FontAwesome |
| `class` | varchar(75) | Classe CSS |
| `is_enabled` | bool | |

Indice UNIQUE su `(code, reference)` e su `(label, reference)`.

#### `multicheck_actions`
Azioni disponibili per il sistema multicheck.

| Campo | Tipo | Descrizione |
|---|---|---|
| `id` | bigint PK | |
| `reference` | varchar | Reference del report |
| `label` | varchar | Etichetta dell'azione nel dropdown |
| `set` | text | Operazione UPDATE (DSL `campo:valore`) |
| `where` | text | Condizioni WHERE aggiuntive (DSL) |
| `table` | varchar | Tabella target (se diversa dal report) |
| `route` | varchar | Rotta di redirect (alternativa a UPDATE) |

---

## 6. Autenticazione e Autorizzazione

### Login

Il package fornisce un sistema di autenticazione proprio tramite `IctAuthController`.

**Rotta di login:** `GET /login` (o `GET /`)
**Rotta di verifica:** `POST /check`
**Rotta di logout:** `ANY /logout`

Al login vengono settate le seguenti variabili di sessione:

| Sessione | Contenuto |
|---|---|
| `loggedUser` | Oggetto utente |
| `profiles` | Array di ID profili assegnati |
| `roles` | Collezione di `profile_roles` per i profili dell'utente |
| `is_admin` | `1` se l'utente e' admin, `0` altrimenti |
| `roles_checker` | Array associativo `[report_id => role_object]` |

### Middleware `islogged` (AuthIct)

Tutte le rotte protette usano il middleware `islogged`. Questo:

1. Verifica che `session('loggedUser')` esista, altrimenti redirect a `/login`
2. Per ogni rotta diversa da `/dashboard`, richiede il parametro `?report=N` nell'URL
3. Salva `report_id` in sessione
4. Controlla i permessi: l'admin ha sempre accesso; per gli altri utenti verifica che il profilo abbia un `profile_roles` per quel `report_id`
5. Per le rotte contenenti `create`, verifica `has_create_button == 1`

**Importante:** ogni URL dell'applicazione (tranne login/logout/dashboard) DEVE contenere `?report=N` come parametro. Questo e' il meccanismo di autorizzazione: il `report_id` identifica la pagina/risorsa a cui si accede.

### Utente admin

Un utente con `is_admin=1` nella tabella users bypassa tutti i controlli di autorizzazione.

---

## 7. Il Sistema Data-Driven

Il cuore di IctInterface e' il pattern **data-driven**: tutta la configurazione di report, form e menu risiede nel database. Per aggiungere una nuova entita' CRUD non serve scrivere codice nei form o nella vista — basta inserire i record appropriati nelle tabelle di configurazione.

### Flusso di configurazione

```
1. Creare la tabella dati (migration)
2. Creare il Model (estende IctModel)
3. Inserire un record in `menus` (voce di menu)
4. Inserire un record in `reports` (definizione report)
5. Inserire record in `report_columns` (colonne visibili)
6. Inserire record in `forms` (form editable + filter)
7. Inserire record in `form_fields` (campi del form)
8. Creare il Controller (estende IctController, usa LivewireController)
9. Aggiungere la rotta
```

I passi 3-7 sono **pura configurazione DB** — nessun file Blade o PHP da scrivere.

---

## 8. Report (Tabelle Dati)

### Come funziona

`ReportService` e' il servizio singleton che gestisce il caricamento e la visualizzazione dei dati tabulari.

1. `loadReportProperties($id)` — carica la configurazione del report da DB
2. `loadReportColumns($id)` — carica le colonne visibili
3. `makeWhereFilter($form_id)` — costruisce i filtri dalla query string
4. `loadTableData($model, $cols, $form_id, $whereFilters)` — esegue la query, formatta le celle

### Configurazione report (tabella `reports`)

Esempio di inserimento:

```sql
INSERT INTO reports (menu_id, title, route, `table`, blade, has_create_button,
    has_edit_button, class_delete_button, href_url, is_enabled, is_show_menu)
VALUES (3, 'Elenco Libri', 'books', 'books', 'report', 1, 1, 'destroy', '/books', 1, 1);
-- Supponiamo che l'ID assegnato sia 10
```

### Configurazione colonne (tabella `report_columns`)

```sql
INSERT INTO report_columns (report_id, label, field, type, type_params, position, is_enabled) VALUES
(10, '#',           'id',         'int',    NULL, 1, 1),
(10, 'Titolo',      'title',      'string', NULL, 10, 1),
(10, 'Autore',      'author_id',  'enum',   'table:authors,code:id,label:name', 20, 1),
(10, 'Prezzo',      'price',      'currency', NULL, 30, 1),
(10, 'Pubblicato',  'published_at','date',   NULL, 40, 1),
(10, 'Attivo',      'is_enabled', 'switch',  NULL, 50, 1);
```

### Ordinamento colonne

Le colonne sono automaticamente ordinabili cliccando sull'intestazione. L'ordinamento viene gestito tramite i parametri GET `ob` (order by field) e `ot` (order type: `asc`/`desc`).

### Paginazione

Di default 20 record per pagina. Modificabile con `ReportService::setPaginate($value)`.

### Somme nel footer

Il campo `sum` del report accetta una stringa DSL per calcolare somme nel footer:

```
field_name:alias,field_name2:alias2
```

---

## 9. Form Dinamici

### Come funzionano

I form sono gestiti dal `DynamicFormService` e renderizzati dai componenti Livewire. La configurazione risiede nelle tabelle `forms` e `form_fields`.

### Form Editable (creazione/modifica)

Ogni report deve avere almeno un form di tipo `editable`. Esempio:

```sql
INSERT INTO forms (report_id, name, title, `table`, type, data, clean_at, is_enabled)
VALUES (10, 'books', 'Gestione Libro', 'books', 'editable', 'view:ict::forms.builder', 6, 1);
-- Supponiamo che l'ID sia 20
```

### Campi del form

```sql
INSERT INTO form_fields (form_id, label, name, type, type_attr, position,
    bootstrap_cols, attr_params, rules, is_enabled) VALUES
(20, '',          'id',           'hidden', NULL, 1,  12, 'class:form-control', NULL, 1),
(20, 'Titolo',    'title',        'text',   NULL, 10, 6,  'class:form-control', 'required|max:255', 1),
(20, 'Autore',    'author_id',    'select', 'table:authors,code:id,label:name', 20, 6, 'class:form-control', 'required', 1),
(20, 'Prezzo',    'price',        'number', NULL, 30, 4,  'class:form-control,step:0.01', 'required|numeric', 1),
(20, 'Data pub.', 'published_at', 'date',   NULL, 40, 4,  'class:form-control', 'nullable|date', 1),
(20, 'Note',      'notes',        'textarea', NULL, 50, 12, 'class:form-control,rows:3', 'nullable', 1);
```

### Form Filter

Un form di tipo `filter` associato allo stesso report aggiunge automaticamente una barra filtri sopra la tabella.

```sql
INSERT INTO forms (report_id, name, title, `table`, type, data, clean_at, is_enabled)
VALUES (10, 'books', 'Filtro libri', 'books', 'filter', 'view:ict::forms.builder', 4, 1);
-- ID: 21

INSERT INTO form_fields (form_id, label, name, type, type_attr, position,
    bootstrap_cols, attr_params, is_enabled) VALUES
(21, 'Titolo', 'title', 'text', NULL, 10, 4, 'class:form-control', 1),
(21, 'Autore', 'author_id', 'select', 'table:authors,code:id,label:name', 20, 4, 'class:form-control', 1);
```

### Form Master-Detail (padre-figlio)

Per creare un form padre con un sotto-form figlio (es. ordine + righe ordine):

1. Creare il form padre (editable) con `id_child` che punta al form figlio
2. Creare il form figlio (editable) con i suoi `form_fields`
3. Il `ChildFormComponent` infere automaticamente la foreign key dal nome della tabella padre (`singolare_id`)

```sql
-- Form padre (ordine)
INSERT INTO forms (report_id, name, title, `table`, type, data, id_child, is_enabled)
VALUES (10, 'orders', 'Ordine', 'orders', 'editable', 'view:ict::forms.builder', 25, 1);
-- ID: 24

-- Form figlio (righe ordine)
INSERT INTO forms (report_id, name, title, `table`, type, data, is_enabled)
VALUES (11, 'order_items', 'Righe Ordine', 'order_items', 'editable', 'view:ict::forms.builder', 1);
-- ID: 25
```

Il campo nascosto `order_id` nel form figlio verra' automaticamente popolato con l'ID del record padre.

### Template Blade personalizzato

Il campo `data` nella tabella `forms` determina quale template Blade viene usato:

- `view:ict::forms.builder` — template generico del package
- `view:ict::forms.menu` — template specializzato per menu
- `view:books.edit` — template personalizzato dell'applicazione

Se il valore inizia con `view:`, viene usato come path Blade diretto.

---

## 10. Componenti Livewire

### `ict-filter-form` — Filtro Report

Renderizza il form filtro sopra la tabella report.

```blade
@livewire('ict-filter-form', ['reportId' => $reportId])
```

- Carica automaticamente il form di tipo `filter` per il report
- Pre-popola i campi dai parametri GET correnti
- Pulsanti: "Filtra" (applica), "Reset" (pulisci filtri)
- Submit: redirige con i filtri come query string `?report=N&campo=valore&filter=Y`

### `ict-search-form` — Ricerca

Come il filtro, ma aggiunge `search=on` ai parametri.

```blade
@livewire('ict-search-form', ['reportId' => $reportId])
```

### `ict-editable-form` — Form CRUD

Il componente principale per creazione e modifica record.

```blade
@livewire('ict-editable-form', [
    'reportId' => $reportId,
    'recordId' => $recordId,      // null per creazione, ID per modifica
    'tableName' => $tableName     // opzionale, inferito dal form
])
```

Funzionalita':
- Carica campi e regole di validazione dal DB
- Gestisce crittografia campi (`is_crypted`)
- Gestisce multiselect (salva come JSON)
- Gestisce upload file (`WithFileUploads`)
- INSERT o UPDATE in transazione DB
- Se ha un form figlio (`id_child`), dopo il salvataggio resta sulla pagina e mostra il `ChildFormComponent`

### `ict-child-form` — Form Figlio (Detail)

Gestisce i record figli in un form master-detail.

```blade
@livewire('ict-child-form', [
    'parentFormId' => $parentFormId,
    'childFormId' => $childFormId,
    'parentRecordId' => $parentRecordId
])
```

- Mostra una tabella con i record figli esistenti
- Pulsante "Aggiungi riga" per aggiungere un nuovo record
- Inferisce la foreign key: cerca campi hidden `*_id` o deriva da `singular(tabella_padre)_id`
- Salvataggio bulk in transazione
- Ascolta eventi: `record-saved`, `record-deleted` per ricaricare i dati

### `ict-modal-form` — Form Modale

Form aperto in una modale Bootstrap 5.

```blade
@livewire('ict-modal-form', ['reportId' => $reportId])
{{-- oppure --}}
@livewire('ict-modal-form', ['formId' => $formId])
```

- Ascolta l'evento `open-modal-form` per aprirsi
- Supporta creazione e modifica (passa `recordId` nell'evento)
- Chiude e dispatcha `record-saved` al salvataggio

Apertura via JavaScript:

```javascript
Livewire.dispatch('open-modal-form', { recordId: 5 })  // modifica
Livewire.dispatch('open-modal-form', { recordId: null }) // creazione
```

### `ict-delete-confirm` — Conferma Eliminazione

Modale di conferma per eliminazione o disabilitazione record.

```blade
@livewire('ict-delete-confirm', ['routePrefix' => $route])
```

- Ascolta `confirm-delete` per eliminazione (DELETE)
- Ascolta `confirm-disable` per disabilitazione (`is_enabled=0`)
- Dopo l'operazione ricarica la pagina

Attivazione dai pulsanti della tabella report:

```javascript
Livewire.dispatch('confirm-delete', { recordId: 5 })
Livewire.dispatch('confirm-disable', { recordId: 5 })
```

### `ict-multicheck-manager` — Azioni di Massa

Gestisce le operazioni bulk sui record selezionati.

```blade
@livewire('ict-multicheck-manager', ['reportId' => $reportId])
```

- Legge gli ID selezionati da `session('multicheck_ids')`
- Esegue UPDATE bulk o redirect verso una rotta configurata
- Configurazione tramite tabella `multicheck_actions`

### `ict-bool-switch` — Toggle Booleano

Switch on/off inline nella tabella report.

```blade
@livewire('ict-bool-switch')
```

Attivato automaticamente dalle colonne di tipo `switch` nel report. Aggiorna il valore nel DB con una singola chiamata Livewire.

### `ict-user-profile-manager` — Gestione Utenti Profilo

Gestisce l'associazione utenti-profilo.

```blade
@livewire('ict-user-profile-manager', ['profileId' => $profileId])
```

- Modale con lista utenti ricercabili
- Checkbox per selezionare/deselezionare utenti
- Salvataggio nella tabella pivot `profiles_has_users`

---

## 11. Componenti Blade

Tutti i componenti Blade usano il prefisso `x-ict-`.

### `<x-ict-title-page>`
Titolo della pagina con breadcrumb.

### `<x-ict-title-form>`
Titolo del form.

### `<x-ict-nav-sidebar>`
Menu laterale di navigazione. Generato automaticamente da `MenuService::getNavSidebar()`.

### `<x-ict-btn-create>`
Pulsante "Nuovo" per la creazione record.

### `<x-ict-btn-edit>`
Pulsante "Modifica" per ogni riga del report.

### `<x-ict-btn-delete>`
Pulsante "Elimina" o "Disabilita" per ogni riga. Dispatcha l'evento Livewire `confirm-delete` o `confirm-disable` in base a `class_delete_button` del report.

### `<x-ict-btn-export>`
Pulsante per export Excel.

### `<x-ict-pagination>`
Navigazione pagine del report.

### `<x-ict-dynamic-field>`
Renderizza un singolo campo di form in base al suo `type`. Supporta 13 tipi di campo (vedi [sezione 15](#15-tipi-di-campo-per-form)).

---

## 12. Creare un Nuovo Modulo CRUD

Esempio completo: gestione di una tabella `books`.

### Passo 1: Migration

```php
Schema::create('books', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->foreignId('author_id')->constrained();
    $table->decimal('price', 8, 2);
    $table->date('published_at')->nullable();
    $table->text('notes')->nullable();
    $table->boolean('is_enabled')->default(true);
    $table->timestamps();
});
```

### Passo 2: Model

```php
// app/Models/Book.php
namespace App\Models;

use Packages\IctInterface\Models\IctModel;

class Book extends IctModel
{
    protected $table = 'books';
}
```

**Importante:** estendere `IctModel` (non `Model` di Laravel) per il supporto a `setTable()`, `$where[]` e `$guarded`.

### Passo 3: Controller

```php
// app/Http/Controllers/BookController.php
namespace App\Http\Controllers;

use App\Models\Book;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Traits\LivewireController;

class BookController extends IctController
{
    use LivewireController;

    public function __construct()
    {
        $this->model = new Book();
        $this->__init(); // Inizializza ReportService, FormService, Logger
    }
}
```

Il trait `LivewireController` fornisce automaticamente: `index()`, `create()`, `edit()`, `destroy()`, `disabled()`.

### Passo 4: Rotta

```php
// routes/web.php
Route::middleware(['web', 'islogged'])->group(function () {
    Route::resource('/books', \App\Http\Controllers\BookController::class);
});
```

### Passo 5: Configurazione DB

Inserire i record in `menus`, `reports`, `report_columns`, `forms`, `form_fields` come mostrato nelle sezioni 8 e 9. Si possono inserire via migration, seeder, o direttamente dal pannello di configurazione del package.

### Passo 6: Assegnare i permessi

```sql
INSERT INTO profile_roles (profile_id, report_id, has_create_button, has_edit_button, is_enabled)
VALUES (1, 10, 1, 1, 1);  -- profilo Admin ha accesso completo al report 10
```

A questo punto, navigando a `/books?report=10` si vedra' la tabella dati, e i pulsanti Nuovo/Modifica/Elimina saranno funzionanti senza aver scritto alcun template Blade.

---

## 13. DSL dei Parametri

IctInterface usa un mini-DSL (Domain Specific Language) nelle colonne `type_params`, `attr_params`, `type_attr` per configurare il comportamento tramite stringhe.

### Formato generale

```
chiave:valore,chiave2:valore2
```

Viene parsato dalla funzione `_parser()` / `stringToArray()` in un array associativo:

```php
_parser('table:authors,code:id,label:name')
// → ['table' => 'authors', 'code' => 'id', 'label' => 'name']
```

### DSL per `attr_params` (attributi HTML campo)

```
class:form-control,readonly:readonly,placeholder:Inserisci...,step:0.01
```

Genera gli attributi HTML corrispondenti sul tag `<input>`.

### DSL per `type_attr` (opzioni select/radio)

**Da tabella DB:**
```
table:authors,code:id,label:name
```
Genera una SELECT con `<option value="id">name</option>` dalla tabella `authors`.

**Con filtro:**
```
table:authors,code:id,label:name,reference:active
```
Aggiunge `WHERE reference = 'active'`.

**Da tabella `options`:**
```
reference:YN
```
Carica le opzioni dalla tabella `options` dove `reference = 'YN'`.

**Valori speciali:**
- `@var` — sostituito con il valore corrente del campo
- `&value` — filtro dinamico basato su un altro campo
- `#` — valore dell'ID del record corrente
- `EDIT` — indica che le opzioni devono cambiare in base al contesto edit/create

### DSL per `type_params` (parametri colonne report)

**Tipo `enum`:**
```
table:menus,code:id,label:title
```
Risolve il valore numerico nel campo alla label corrispondente dalla tabella.

**Tipo `enum` con options:**
```
reference:YN
```

**Tipo `link`:**
```
route:reportcol,filter:report_id,title:Vedi colonne
```
Genera un link interno alla rotta indicata con filtro preimpostato.

**Tipo `relations`:**
```
model:App\Models\Author,function:books,field:title,filter:is_enabled
```
Risolve una relazione Eloquent.

**Tipo `stoplight`:**
```
limit:50,field:target_value
```

**Tipo `match`:**
```
down:25,medium:50,high:75,above:100
```

---

## 14. Tipi di Dato per Colonne Report

I tipi di dato determinano come viene formattato e visualizzato il valore nella cella della tabella.

| Tipo | Descrizione | `type_params` |
|---|---|---|
| `string` | Testo semplice | — |
| `int` | Intero con separatore migliaia | — |
| `integer` | Alias di `int` | Opzionale: numero decimali |
| `float` | Decimale (2 cifre, formato IT) | — |
| `currency` | Valuta (`€ 1.234,56`) | — |
| `percent` | Percentuale (`75%`) | — |
| `date` | Data formato italiano (`dd/mm/yyyy`) | — |
| `dateTime` | Data e ora formato italiano | — |
| `enum` | Lookup da tabella | `table:X,code:Y,label:Z` oppure `reference:REF` |
| `array` | Enum multi-valore (comma-separated) | Come `enum` |
| `relations` | Relazione Eloquent | `model:X,function:Y,field:Z` |
| `switch` | Toggle booleano (Bootstrap switch) | — |
| `stoplight` | Semaforo colorato per soglie | `limit:N,field:campo` |
| `match` | Percentuale con colori a fasce | `down:N,medium:N,high:N,above:N` |
| `alert` | Confronto aggregati con colore | Parametri specifici |
| `link` | Link interno | `route:X,filter:Y,title:Z` |
| `directlink` | Link esterno `<a href>` | — |
| `thumb` | Miniatura immagine `<img>` | — |
| `decrypt` | Decrittografia campo | — |
| `encrypt` | Crittografia campo | — |

---

## 15. Tipi di Campo per Form

I tipi determinano quale elemento HTML viene renderizzato dal componente `<x-ict-dynamic-field>`.

| Tipo | Elemento HTML | Note |
|---|---|---|
| `text` | `<input type="text">` | Campo testo standard |
| `number` | `<input type="number">` | Numerico |
| `email` | `<input type="email">` | Email |
| `password` | `<input type="password">` | Password |
| `date` | `<input type="date">` | Selettore data nativo |
| `hidden` | `<input type="hidden">` | Campo nascosto |
| `textarea` | `<textarea>` | Area di testo |
| `select` | `<select>` | Dropdown, opzioni da `type_attr` |
| `radio` | `<input type="radio">` | Gruppo radio, opzioni da `type_attr` |
| `checkbox` | `<input type="checkbox">` | Singolo checkbox |
| `multiselect` | Custom Alpine.js | Multi-selezione con chip e ricerca |
| `file` | `<input type="file">` | Upload file (Livewire `WithFileUploads`) |
| `finder` | Custom Alpine.js | Autocomplete con ricerca API via fetch |
| `daterange` | Custom Alpine.js | Selettore intervallo date con preset |

### Campo `select` — Configurazione opzioni

Nel campo `type_attr` si specifica la sorgente delle opzioni:

```
table:authors,code:id,label:name
```

Oppure da tabella `options`:

```
reference:CATEGORY
```

Oppure opzioni inline:

```
#1:Opzione1,#2:Opzione2,#3:Opzione3
```

### Campo `multiselect`

Salva i valori selezionati come array JSON nel DB. Usa il componente Alpine.js `multiSelectField` con ricerca testuale e pulsante "Seleziona tutti".

### Campo `finder`

Autocomplete basato su una chiamata API. Configurato tramite `type_attr`:

```
url:/search/endpoint,map:id:name,field:related_field
```

### Campo `daterange`

Selettore intervallo date puro Alpine.js con preset (Oggi, Ultima settimana, Ultimo mese, ecc.). Sostituisce il vecchio daterangepicker jQuery.

---

## 16. Filtri e Ricerca

### Come funzionano i filtri

Quando un form di tipo `filter` e' associato a un report, il `FilterFormComponent` renderizza automaticamente i campi sopra la tabella. Al submit, i valori diventano parametri GET.

`ReportService::makeWhereFilter()` legge i parametri GET e costruisce le clausole WHERE:

| Logica | Sintassi GET | Clausola Eloquent |
|---|---|---|
| Uguale | `?title=valore` | `where('title', '=', 'valore')` |
| LIKE | `?title=valore` (per campi text) | `where('title', 'LIKE', '%valore%')` |
| Mese | `?created_at_month=03` | `whereMonth('created_at', 3)` |
| Anno | `?created_at_year=2025` | `whereYear('created_at', 2025)` |
| Intervallo | `?date_from=X&date_to=Y` | `whereBetween('date', [X, Y])` |
| IN | `?status[]=A&status[]=B` | `whereIn('status', ['A', 'B'])` |
| OR | Campi con prefisso `or_` | `orWhere(...)` |

### Operatori di confronto

Nel DSL dei filtri si possono usare operatori:

| Codice | Operatore SQL |
|---|---|
| `eq` | `=` |
| `ne` | `!=` |
| `gt` | `>` |
| `lt` | `<` |
| `ge` | `>=` |
| `le` | `<=` |

### Filtro su report con `where_condition`

Il campo `where_condition` nella tabella `reports` aggiunge una clausola WHERE fissa:

```sql
-- Report che mostra solo libri attivi
UPDATE reports SET where_condition = 'is_enabled = 1' WHERE id = 10;
```

---

## 17. Sistema Multicheck (Azioni di Massa)

### Configurazione

1. Impostare `multicheck_reference` nel record report:

```sql
UPDATE reports SET multicheck_reference = 'BOOKS_ACTIONS' WHERE id = 10;
```

2. Inserire le azioni nella tabella `multicheck_actions`:

```sql
INSERT INTO multicheck_actions (reference, label, `set`, `where`, `table`, route) VALUES
('BOOKS_ACTIONS', 'Disabilita selezionati', 'is_enabled:0', NULL, NULL, NULL),
('BOOKS_ACTIONS', 'Abilita selezionati',    'is_enabled:1', NULL, NULL, NULL),
('BOOKS_ACTIONS', 'Esporta selezionati',    NULL, NULL, NULL, '/export/books');
```

### Funzionamento

1. L'utente seleziona i checkbox nella tabella report
2. Gli ID selezionati vengono sincronizzati in sessione via `POST /session_ids` (fetch API)
3. L'utente sceglie un'azione dal dropdown
4. `MulticheckManagerComponent` legge `session('multicheck_ids')` e:
   - Se `set` e' valorizzato: esegue `DB::table()->whereIn('id', $ids)->update($set)`
   - Se `route` e' valorizzato: redirige verso la rotta indicata
5. La pagina viene ricaricata

---

## 18. Upload File e Allegati

### Configurazione campo file

```sql
INSERT INTO form_fields (form_id, label, name, type, position, bootstrap_cols, attr_params, is_enabled)
VALUES (20, 'Copertina', 'cover_image', 'file', 60, 6, 'class:form-control', 1);
```

### Funzionamento

`EditableFormComponent` usa il trait Livewire `WithFileUploads`. Al submit:

1. Il file viene caricato temporaneamente da Livewire
2. Viene spostato in `storage/app/public/upload/{prefix}/` tramite `storeAs()`
3. Il path del file viene salvato nel campo della tabella

### Directory di upload

Configurabili via `.env`:

```env
UPLOAD_DIR=upload
UPLOAD_BILL_DIR=upload/bills
```

### Gestione allegati (tabelle dedicate)

Per gestione avanzata degli allegati, il package offre le tabelle `attachments` e `attachment_archives` con il `FormService`:

- `saveFileAttached()` — salva file + record allegato + archivio
- `saveMultiAttached()` — multipli allegati
- `saveAttachArchive()` — archivia versione precedente

L'`AttachmentController` gestisce l'eliminazione degli allegati con rimozione del file fisico.

---

## 19. Export Excel

Il package include `ExcelController` per l'export dei dati in formato Excel tramite Maatwebsite/Excel.

### Rotte di export

| Rotta | Descrizione |
|---|---|
| `GET /export/report` | Esporta i dati del report |
| `GET /export/reportcol` | Esporta le colonne del report |
| `GET /export/form` | Esporta la configurazione form |
| `GET /export/formfield` | Esporta i campi del form |
| `GET /export/roles` | Esporta i ruoli profilo |

### Utilizzo

Il pulsante `<x-ict-btn-export>` nel template report genera automaticamente il link di download. L'export rispetta i filtri attivi nella query string.

---

## 20. Sistema Profili e Ruoli

### Struttura

```
Profilo (profiles)
  ├── ha molti Utenti (profiles_has_users)
  └── ha molti Ruoli (profile_roles)
       └── ogni ruolo definisce i permessi su un Report
```

### Creare un profilo

```sql
INSERT INTO profiles (name, is_enabled) VALUES ('Operatore', 1);
-- ID: 3
```

### Assegnare permessi

```sql
INSERT INTO profile_roles (profile_id, report_id, has_create_button, has_edit_button, is_enabled)
VALUES
(3, 10, 1, 1, 1),  -- Puo' vedere, creare e modificare nel report 10
(3, 11, 0, 1, 1);  -- Puo' solo vedere e modificare nel report 11
```

### Associare utenti al profilo

L'associazione avviene tramite `UserProfileManagerComponent` nell'interfaccia, oppure direttamente:

```sql
INSERT INTO profiles_has_users (key_id, user_id, profile_id)
VALUES ('abc123-p3u5', 5, 3);
```

### Campo `fields_disabled`

Per disabilitare specifici campi del form per un profilo:

```json
{
    "20": ["price", "published_at"],
    "21": "all"
}
```

- Chiave: ID del form
- Valore: array di nomi campi da disabilitare, oppure `"all"` per disabilitare tutto

---

## 21. Tabella Options (Parametri di Utilita')

La tabella `options` e' un registro chiave-valore usato per enumerazioni, lookup e parametri di configurazione.

### Gruppi standard (reference)

| Reference | Descrizione | Valori tipici |
|---|---|---|
| `YN` | Si/No | `1=Si`, `0=No` |
| `ED` | Enabled/Disabled | `1=Abilitato`, `0=Disabilitato` |
| `MONTH` | Mesi dell'anno | `01=Gennaio`, ..., `12=Dicembre` |
| `TYPEFORM` | Tipi di form | `editable`, `filter`, `search`, `modal` |

### Creare un nuovo gruppo

```sql
INSERT INTO options (code, label, reference, icon, class, is_enabled) VALUES
('DRAFT',     'Bozza',      'BOOK_STATUS', NULL, 'text-secondary', 1),
('PUBLISHED', 'Pubblicato', 'BOOK_STATUS', NULL, 'text-success', 1),
('ARCHIVED',  'Archiviato', 'BOOK_STATUS', NULL, 'text-muted', 1);
```

### Usare in una colonna report

```sql
-- type_params nella colonna report
'reference:BOOK_STATUS'
```

### Usare in un campo form

```sql
-- type_attr nel campo form
'reference:BOOK_STATUS'
```

### Helper `_option()`

```php
// Tutti i valori di un reference
$statuses = _option(null, 'BOOK_STATUS'); // Collection di Option

// Singolo valore
$status = _option('PUBLISHED', 'BOOK_STATUS'); // Oggetto Option
echo $status->label; // "Pubblicato"
```

---

## 22. Helper Globali

Il file `helpers.php` fornisce funzioni globali utilizzabili ovunque nell'applicazione.

### Formattazione dati

| Funzione | Descrizione | Esempio |
|---|---|---|
| `_currency($val)` | Formato valuta italiana | `_currency(1234.5)` → `€ 1.234,50` |
| `_number($val)` | Intero con separatore migliaia | `_number(1234567)` → `1.234.567` |
| `_float($val)` | Decimale con 2 cifre | `_float(1234.5)` → `1.234,50` |
| `_int($val)` | Formato intero | `_int(1234)` → `1.234` |
| `_percent($val)` | Percentuale | `_percent(75)` → `75%` |

### Date

| Funzione | Descrizione | Esempio |
|---|---|---|
| `_date($date)` | Formato italiano | `_date('2025-03-15')` → `15/03/2025` |
| `_date_time($date)` | Formato italiano con ora | `_date_time('2025-03-15 14:30')` → `15/03/2025 14:30` |
| `_convertDateItToDb($d)` | IT → DB | `_convertDateItToDb('15/03/2025')` → `2025-03-15` |
| `_convertDateDbToIt($d)` | DB → IT | `_convertDateDbToIt('2025-03-15')` → `15/03/2025` |
| `_is_valid_date($d, $fmt)` | Validazione data | `_is_valid_date('2025-03-15')` → `true` |
| `_day($d)` | Giorno | `_day('2025-03-15')` → `15` |
| `_month($d)` | Mese | `_month('2025-03-15')` → `03` |
| `_year($d)` | Anno | `_year('2025-03-15')` → `2025` |
| `_find_date($d, $days)` | Data + N giorni | `_find_date('2025-03-15', 7)` → `2025-03-22` |

### Sessione e utente

| Funzione | Descrizione |
|---|---|
| `_user()` | Restituisce l'oggetto `IctUser` corrente |
| `_is_admin()` | `1` se admin, `0` altrimenti |
| `_profiles()` | Array degli ID profili dell'utente |

### Crittografia

| Funzione | Descrizione |
|---|---|
| `_encrypt($val)` | Crittografa con `Crypt::encryptString()` |
| `_decrypt($val)` | Decrittografa con `Crypt::decryptString()` |

### Database e transazioni

| Funzione | Descrizione |
|---|---|
| `_commit($file, $line)` | Commit di tutte le transazioni aperte + log |
| `_rollback($file, $line)` | Rollback di tutte le transazioni aperte + log |
| `_sql($file, $line)` | Log dell'ultima query SQL eseguita |

### Debug

| Funzione | Descrizione |
|---|---|
| `ddr(...$var)` | `dd()` con rollback automatico della transazione |

### Altro

| Funzione | Descrizione |
|---|---|
| `_parser($val)` | Parsa stringa DSL `chiave:valore,chiave2:valore2` → array |
| `_option($code, $ref)` | Lookup nella tabella `options` |
| `_log($channel)` | Restituisce istanza Logger con canale impostato |
| `_select_months($name, $req)` | Genera HTML `<select>` con i mesi dell'anno |
| `time_start()` / `time_end($s)` | Misurazione performance |
| `callback_clean($data)` | Wrapper per `addslashes()` |

---

## 23. Logger

Il `Logger` e' un wrapper configurabile del sistema di log di Laravel.

### Livelli di log

Configurati tramite `config('ict.logger_level')`:

| Livello | Cosa viene loggato |
|---|---|
| `0` | Tutto: info, debug, sql, error, rollback, commit |
| `1` | debug, sql, error, rollback, commit |
| `2` | Solo sql, error, rollback, commit |

### Utilizzo

```php
// Tramite helper
_log()->debug('Messaggio debug', __FILE__, __LINE__);
_log()->info('Info', __FILE__, __LINE__);
_log()->error('Errore critico', __FILE__, __LINE__);

// Log query SQL
_sql(__FILE__, __LINE__);

// Con canale personalizzato
_log('custom_channel')->debug('Messaggio', __FILE__, __LINE__);
```

### Nei controller

```php
// I controller che usano LivewireController hanno $this->log
$this->log->info("Inizio operazione", __FILE__, __LINE__);
$this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);
```

---

## 24. Rotte del Package

### Rotte pubbliche (middleware `web`)

| Metodo | URI | Descrizione |
|---|---|---|
| GET | `/` | Pagina di login |
| GET | `/login` | Pagina di login |
| POST | `/check` | Verifica credenziali |
| ANY | `/logout` | Logout |

### Rotte protette (middleware `web` + `islogged`)

| Metodo | URI | Descrizione |
|---|---|---|
| GET | `/dashboard` | Dashboard |
| Resource | `/menu` | CRUD menu |
| Resource | `/report` | CRUD report |
| Resource | `/reportcol` | CRUD colonne report |
| Resource | `/form` | CRUD form |
| Resource | `/formfield` | CRUD campi form |
| Resource | `/profiles` | CRUD profili |
| Resource | `/roles` | CRUD ruoli |
| Resource | `/options` | CRUD opzioni |
| POST | `/session_ids` | Sincronizza ID multicheck in sessione |
| GET | `/deleteattach` | Elimina allegato |
| GET | `/search/users` | Ricerca utenti (AJAX) |
| POST | `/modal/savecol` | Salva colonna report (modale) |
| POST | `/modal/saveformitem` | Salva campo form (modale) |
| POST | `/modal/saverole` | Salva ruolo (modale) |
| POST | `/modal/addusers` | Associa utenti a profilo |
| GET | `/export/report` | Export Excel report |
| GET | `/export/reportcol` | Export Excel colonne |
| GET | `/export/form` | Export Excel form |
| GET | `/export/formfield` | Export Excel campi |
| GET | `/export/roles` | Export Excel ruoli |

**Nota:** tutte le rotte protette (tranne `/dashboard`) richiedono `?report=N` come parametro.

---

## 25. Personalizzare il Layout

### Layout principale

Il layout base e' `ict::layouts.app`. Include:

- Sidebar di navigazione (`<x-ict-nav-sidebar>`)
- Area contenuto principale
- Bootstrap 5.3 + Alpine.js
- Livewire scripts
- FontAwesome
- CSS personalizzabile via `config('ict.css_color')`

### Template Blade personalizzato per un form

Per usare un template diverso dal generico `ict::forms.builder`:

1. Creare il file Blade nella propria applicazione (es. `resources/views/books/edit.blade.php`)
2. Impostare `data = 'view:books.edit'` nel record `forms`
3. Nel template, usare il componente Livewire:

```blade
@extends('ict::layouts.app')

@section('content')
    <x-ict-title-form :title="'Gestione Libro'" />

    @if($useLivewireForm)
        @livewire('ict-editable-form', [
            'reportId' => $reportId,
            'recordId' => $recordId
        ])
    @endif

    {{-- Contenuto personalizzato --}}
    <div class="mt-3">
        <h5>Sezione personalizzata</h5>
        {{-- ... --}}
    </div>
@endsection
```

### Template Blade personalizzato per un report

Se `blade != 'report'` nel record `reports`, il package cerchera' il template specificato nel campo `blade`:

```sql
UPDATE reports SET blade = 'books.report-custom' WHERE id = 10;
```

Il template ricevera' le stesse variabili del report standard: `$data`, `$cols`, `$report`, `$route`, `$pages`, `$count`, `$reportId`, ecc.

### Colore tema

```env
APP_CSS_COLOR=#336699
```

Il valore viene applicato come variabile CSS `--custom-bg` nel layout.

### Alpine.js Components

Il layout registra tre componenti Alpine.js globali in `alpine:init`:

- **`finderField(wireModel, searchUrl, mapStr, fieldName)`** — autocomplete via fetch API
- **`dateRangeField(wireModel)`** — selettore intervallo date con preset
- **`multiSelectField(entangled, options)`** — multi-select con chip, ricerca e "Seleziona tutti"

Questi vengono automaticamente utilizzati dal componente `<x-ict-dynamic-field>` quando il tipo di campo corrisponde.
