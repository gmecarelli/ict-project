# Analisi Refactoring Package `IctInterface` (fase analisi, nessuna modifica codice)

Data analisi: 2026-02-19

## 1) Obiettivo e vincoli

Obiettivo richiesto:
- eliminare `kris/laravel-form-builder`;
- sostituire jQuery;
- valutare se mantenere Bootstrap (aggiornandolo) o sostituire parti UI;
- pianificare il refactoring del package `packages/IctInterface` con upgrade Laravel 12.

Vincoli funzionali dichiarati:
- mantenere funzionalita` attuali (report, form dinamici da DB, profili/ruoli, auth, upload, export Excel);
- in questa fase non toccare il codice applicativo;
- codice legacy: modificare solo dove necessario;
- nuovo codice di integrazione: su convenzioni Laravel 12.

## 2) Stato attuale reale (rilievo sul codice)

### 2.1 Dipendenze e framework attuali

- Laravel progetto: `^10.10` (`src/composer.json`).
- Form builder attuale: `kris/laravel-form-builder` (`src/composer.json`).
- Export: `maatwebsite/excel` (`src/composer.json`).
- PDF: `barryvdh/laravel-dompdf` (`src/composer.json`).

### 2.2 Frontend effettivo del package

Nel package **non risulta Bootstrap 5**, ma Bootstrap 4.6:
- `src/packages/IctInterface/src/resources/assets/css/bootstrap.min.css` (header: `Bootstrap v4.6.0`)
- `src/packages/IctInterface/src/resources/assets/js/bootstrap.bundle.min.js` (header: `Bootstrap v4.6.0`, con dipendenza jQuery)
- `src/packages/IctInterface/src/resources/views/layouts/app.blade.php` carica jQuery slim CDN + bootstrap bundle del package.

### 2.3 Coupling con `laravel-form-builder`

Il package e` fortemente accoppiato al form-builder:
- Trait centrale: `src/packages/IctInterface/src/Traits/StandardController.php`
- Service centrale: `src/packages/IctInterface/src/Controllers/Services/FormService.php`
- Classi form dinamiche: `src/packages/IctInterface/src/Forms/*.php`
- Blade helpers del package: `form()`, `form_start()`, `form_until()`, `form_end()` in:
  - `src/packages/IctInterface/src/resources/views/forms/builder.blade.php`
  - `src/packages/IctInterface/src/resources/views/forms/profile.blade.php`
  - `src/packages/IctInterface/src/resources/views/forms/item-child.blade.php`
  - `src/packages/IctInterface/src/resources/views/forms/fattura.blade.php`
  - `src/packages/IctInterface/src/resources/views/report.blade.php`

### 2.4 Coupling con jQuery/AJAX inline

Logica AJAX inline in molte blade:
- modali CRUD e form child (`layouts/modal-js.blade.php`, `layouts/form-child-js.blade.php`)
- delete/cancel (`layouts/delete-js.blade.php`)
- multiselect (`multiselect-js.blade.php`, `multiselect/multiselect-js.blade.php`)
- utenti profilo (`layouts/modal-users.blade.php`)
- allegati (`layouts/modal-attachlist.blade.php`)
- report switch bool (`report.blade.php`)
- picker date range e script globali (`layouts/app.blade.php`).

### 2.5 Architettura dominio/funzioni chiave

Il package e` meta-driven da DB (tabelle `forms`, `form_fields`, `reports`, `report_columns`, ecc.):
- report dinamici + filtri + ordinamento + paginazione: `ReportService`
- form dinamici da DB: `FormService` + classi `Forms/*`
- auth/profilazione custom session-based: `AuthIct`, `IctAuthController`
- upload allegati: `FormService` + `AttachmentController`
- export Excel: `ExcelController` + `ReportExport`

Conclusione tecnica: l’eliminazione di `formBuilder` non e` una sostituzione “a pacchetto”, ma richiede un nuovo renderer dinamico dei campi.

## 3) Compatibilita` ecosistema (aggiornata)

Riferimenti principali (al 2026-02-19):
- Laravel 12 upgrade guide: https://laravel.com/docs/12.x/upgrade
- Livewire (Packagist): https://packagist.org/packages/livewire/livewire
- Filament (Packagist): https://packagist.org/packages/filament/filament
- Filament support (dipendenze): https://packagist.org/packages/filament/support
- Flux docs requirements: https://fluxui.dev/docs/installation
- Bootstrap 5 migration (drop jQuery): https://getbootstrap.com/docs/5.3/migration/

Nota importante:
- Bootstrap 5 non richiede jQuery (allineato al tuo obiettivo di rimozione jQuery).
- Filament nelle versioni recenti richiede stack Livewire/Tailwind moderno; e` ottimo come admin framework, ma va valutato rispetto al vincolo di mantenere layout Bootstrap e logica package-driven da DB.

## 4) Framework candidati: pro/contro per questo package

### Opzione A (raccomandata): Livewire + Bootstrap 5 (+ Alpine minimo)

Pro:
- Migrazione progressiva senza riscrivere tutto in un colpo;
- massimo controllo su renderer dinamico dei form da DB;
- compatibile con mantenimento layout Bootstrap;
- elimina jQuery usando azioni Livewire/eventi browser nativi.

Contro:
- serve costruire componenti custom (non “plug and play” come Filament);
- effort iniziale maggiore sul motore form dinamici.

Quando usarla:
- quando vuoi preservare al massimo il comportamento attuale e la riusabilita` package.

### Opzione B: Filament (panels/forms/tables)

Pro:
- velocizza CRUD standard, tabelle, filtri, azioni, policy;
- ottimo per backoffice amministrativo.

Contro:
- rischio di conflitto con UI Bootstrap-only (Filament ha stack UI proprio);
- migrare il tuo motore form/report dinamico DB-driven dentro schema Filament puo` essere non lineare;
- puo` ridurre la neutralita` del package verso app host con frontend diverso.

Quando usarla:
- come **modulo admin interno** (configurazione report/form/colonne), non necessariamente per tutta la UI runtime.

### Opzione C: Livewire + Flux

Pro:
- UI component-based avanzata su Livewire.

Contro:
- introduce fortemente stack UI specifico (tailwind-oriented), meno allineato al vincolo Bootstrap-first;
- puo` aumentare complessita` se vuoi mantenere anche Bootstrap 5.

Quando usarla:
- se decidi esplicitamente di standardizzare la UI su Flux per nuove schermate.

## 5) Scelta consigliata

Scelta consigliata per il tuo scenario:
1. **Core runtime**: Livewire + Bootstrap 5 (niente jQuery).
2. **Filament opzionale**: solo per pannello amministrativo “interno” di configurazione (forms/report columns/options), se vuoi accelerare.
3. **Flux opzionale**: valutare solo se accetti stack UI parallelo o migrazione UI completa.

Motivo: minimizza regressioni sul motore dinamico da DB e rispetta il vincolo di processo a step.

## 6) Strategia di refactoring a step (ordine richiesto)

## Step 0 - Baseline e rete di sicurezza

- inventario funzionale completo per report/form/auth/upload/export;
- smoke test manuali + test automatici minimi (feature tests) su flussi critici;
- congelamento comportamento attuale (golden dataset / screenshot / output export).

Output:
- checklist regressioni + test matrix.

## Step 1 - Sostituzione `formBuilder` (priorita` massima)

Obiettivo:
- introdurre un nuovo strato `FormRenderer` indipendente da `Kris\LaravelFormBuilder`.

Approccio:
- creare adapter/interfaccia (`FormRendererInterface`) con due implementazioni temporanee:
  - `LegacyFormBuilderRenderer` (attuale)
  - `LivewireDynamicFormRenderer` (nuovo)
- instradare `StandardController` / `FormService` verso interfaccia, non verso package esterno.

Migrazione verticale consigliata:
1. Filter form
2. Editable form
3. Child form
4. Modal form

Output:
- form dinamici renderizzati da Livewire senza helper `form_*`.

## Step 2 - Rimozione jQuery e AJAX inline

Obiettivo:
- sostituire endpoint AJAX e script inline con azioni Livewire.

Mappatura tipica:
- `$.ajax(...)` load modal -> `wire:click` + stato componente
- submit modal -> `wire:submit`
- multiselect session -> stato Livewire + azioni bulk
- delete/cancel confirm -> action Livewire + browser event
- daterangepicker jQuery -> componente date range Livewire.

Output:
- nessuna dipendenza jQuery nel package.

## Step 3 - Bootstrap: mantenere e aggiornare

Obiettivo:
- tenere Bootstrap per layout, ma aggiornare a 5.x (latest stabile).

Attivita`:
- sostituzione attributi `data-toggle/data-target/data-dismiss` -> `data-bs-*`;
- aggiornamento classi/utilita` deprecate;
- rimozione JS bootstrap v4 locale dipendente da jQuery;
- allineamento componenti modale/collapse/dropdown alla sintassi BS5.

Output:
- UI Bootstrap 5 senza jQuery.

## Step 4 - Auth/profilazione (parita` funzionale)

Obiettivo:
- mantenere stessa logica attuale (`report` obbligatorio eccetto dashboard, session keys `loggedUser`, `is_admin`, `roles`, `profiles`).

Approccio:
- mantenere middleware semantico equivalente ad `AuthIct`;
- migrare login/logout su flusso Laravel 12 compatibile, preservando payload sessione legacy;
- introdurre test di autorizzazione per report/form actions.

Output:
- comportamento auth invariato lato business.

## Step 5 - Upload ed export

- upload: mantenere schema DB e naming file, consolidare su `Storage` (gia` presente in `FormService`);
- export excel: mantenere backend `maatwebsite/excel`, migrare solo trigger frontend in Livewire;
- verifiche su filtri esportazione e formattazioni.

## Step 6 - Upgrade Laravel 12 e integrazione

- aggiornare app a Laravel 12 (sequenza ufficiale upgrade);
- mantenere codice legacy dove non necessario toccare;
- scrivere nuovo codice di integrazione con convenzioni Laravel 12 (middleware/bootstrapping/routing secondo struttura target app).

## 7) Cosa cambiera` concretamente nel package

Componenti target suggeriti (nuovi):
- `Livewire/Reports/ReportTable.php`
- `Livewire/Forms/DynamicForm.php`
- `Livewire/Forms/ChildRepeater.php`
- `Livewire/Modals/DynamicModal.php`
- `Livewire/Bulk/MultiSelectActions.php`
- `Services/FormSchemaResolver.php` (da tabelle `forms`/`form_fields`)
- `Services/FieldTypeMapper.php` (text/select/date/file/checkbox/...)

Codice legacy da disaccoppiare progressivamente:
- `src/packages/IctInterface/src/Controllers/Services/FormService.php`
- `src/packages/IctInterface/src/Traits/StandardController.php`
- `src/packages/IctInterface/src/resources/views/forms/*.blade.php`
- `src/packages/IctInterface/src/resources/views/layouts/*-js.blade.php`

## 8) Rischi principali e mitigazioni

1. Regressioni su form dinamici complessi (type_attr, select dinamiche, readonly/ruoli)
- Mitigazione: migrazione per verticale + snapshot test HTML/validazione.

2. Regressioni su permessi report/profili
- Mitigazione: test feature su middleware e session payload.

3. Regressioni su query dinamiche filtri (`whereMonth`, `whereBetween`, ecc.)
- Mitigazione: test dedicati su `ReportService` / nuovo query layer.

4. Doppio stack UI temporaneo durante transizione
- Mitigazione: feature flag per report/form migrati.

## 9) Stima di effort (ordine di grandezza)

- Step 0: 2-4 gg
- Step 1 (sostituzione formbuilder): 8-15 gg
- Step 2 (rimozione jQuery): 5-10 gg
- Step 3 (Bootstrap 5): 3-6 gg
- Step 4 (auth/profili): 3-6 gg
- Step 5 (upload/export): 2-4 gg
- Step 6 (upgrade/integrazione L12): 3-7 gg

Totale indicativo: 26-52 gg uomo (dipende da numero report/form personalizzati e copertura test).

## 10) Checklist di uscita (Definition of Done)

- `kris/laravel-form-builder` rimosso da `composer.json`;
- nessun riferimento `form_*` helper nelle blade del package;
- nessun `$.ajax` / jQuery nel package;
- bootstrap assets aggiornati a 5.x;
- tutte le funzionalita` core preservate (report/form/profili/auth/upload/export);
- test feature verdi su scenari critici;
- documentazione package aggiornata (installazione, publish assets, middleware, routes).

## 11) Domande aperte (necessarie prima dell’implementazione)

1. Confermi che vuoi **mantenere Bootstrap come UI principale** anche dopo introduzione Livewire?
2. Filament va usato:
- solo per pannello admin di configurazione,
- oppure come base UI completa del package?
3. Flux e` obbligatorio in questo refactoring o opzionale?
4. Vuoi mantenere al 100% le stesse route attuali del package (path + name), anche internamente con Livewire?
5. Confermi priorita` iniziale su un sottoinsieme pilota (es. `report` + `form` + `formfield`) prima di migrare tutto?

---

## Allegato A - Evidenze file principali analizzati

- `src/composer.json`
- `src/packages/IctInterface/src/Providers/IctServiceProvider.php`
- `src/packages/IctInterface/src/routes.php`
- `src/packages/IctInterface/src/Traits/StandardController.php`
- `src/packages/IctInterface/src/Controllers/Services/FormService.php`
- `src/packages/IctInterface/src/Controllers/Auth/IctAuthController.php`
- `src/packages/IctInterface/src/Middleware/AuthIct.php`
- `src/packages/IctInterface/src/resources/views/layouts/app.blade.php`
- `src/packages/IctInterface/src/resources/views/report.blade.php`
- `src/packages/IctInterface/src/resources/views/forms/builder.blade.php`
- `src/packages/IctInterface/src/resources/views/layouts/modal-js.blade.php`
- `src/packages/IctInterface/src/resources/views/layouts/form-child-js.blade.php`
- `src/packages/IctInterface/src/resources/views/layouts/delete-js.blade.php`
- `src/packages/IctInterface/src/resources/views/multiselect/*.blade.php`
