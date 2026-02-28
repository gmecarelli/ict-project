# Piano di Rimozione jQuery e AJAX Inline

> **Obiettivo**: Eliminare completamente jQuery e tutte le chiamate AJAX inline, sostituendole con componenti Livewire ed eventi browser nativi (Alpine.js incluso in Livewire).
>
> **Stato attuale**: 18 chiamate `$.ajax()` distribuite in 12 file, 1 CDN jQuery, 1 bundle compilato con jQuery, 1 plugin jQuery (daterangepicker).

---

## Indice

- [Inventario completo dipendenze jQuery](#inventario-completo-dipendenze-jquery)
- [PROCESSO 1 — Eliminazione delete-js.blade.php](#processo-1--eliminazione-delete-jsbladephp)
- [PROCESSO 2 — Eliminazione form-child-js.blade.php](#processo-2--eliminazione-form-child-jsbladephp)
- [PROCESSO 3 — Eliminazione modal-js.blade.php](#processo-3--eliminazione-modal-jsbladephp)
- [PROCESSO 4 — Eliminazione modal-attachlist.blade.php (jQuery)](#processo-4--eliminazione-modal-attachlistbladephp-jquery)
- [PROCESSO 5 — Eliminazione modal-users.blade.php (jQuery)](#processo-5--eliminazione-modal-usersbladephp-jquery)
- [PROCESSO 6 — Eliminazione multiselect-js.blade.php](#processo-6--eliminazione-multiselect-jsbladephp)
- [PROCESSO 7 — Eliminazione finder.blade.php (jQuery)](#processo-7--eliminazione-finderbladephp-jquery)
- [PROCESSO 8 — Eliminazione jQuery inline da report.blade.php](#processo-8--eliminazione-jquery-inline-da-reportbladephp)
- [PROCESSO 9 — Eliminazione jQuery inline da app.blade.php](#processo-9--eliminazione-jquery-inline-da-appbladephp)
- [PROCESSO 10 — Eliminazione common.js](#processo-10--eliminazione-commonjs)
- [PROCESSO 11 — Eliminazione dropdown.blade.php (jQuery)](#processo-11--eliminazione-dropdownbladephp-jquery)
- [PROCESSO 12 — Sostituzione daterangepicker jQuery](#processo-12--sostituzione-daterangepicker-jquery)
- [PROCESSO 13 — Rimozione jQuery CDN e bundle](#processo-13--rimozione-jquery-cdn-e-bundle)
- [PROCESSO 14 — Aggiornamento dei file che includono i JS rimossi](#processo-14--aggiornamento-dei-file-che-includono-i-js-rimossi)
- [PROCESSO 15 — Pulizia finale e validazione](#processo-15--pulizia-finale-e-validazione)
- [Riepilogo file da eliminare](#riepilogo-file-da-eliminare)
- [Riepilogo componenti Livewire da creare](#riepilogo-componenti-livewire-da-creare)

---

## Inventario completo dipendenze jQuery

### Chiamate `$.ajax()` attive (16 + 2 inattive)

| # | File | Metodo HTTP | Scopo | Categoria |
|---|------|-------------|-------|-----------|
| 1 | `layouts/delete-js.blade.php:10` | DELETE | Cancellazione record da DB | Conferma eliminazione |
| 2 | `layouts/delete-js.blade.php:37` | PUT | Soft-disable record (cancel) | Conferma eliminazione |
| 3 | `layouts/form-child-js.blade.php:26` | POST | Aggiunta riga child form dinamica | Child forms |
| 4 | `layouts/modal-js.blade.php:32` | GET | Caricamento HTML form in modale | Modali |
| 5 | `layouts/modal-js.blade.php:55` | POST | Salvataggio dati form modale | Modali |
| 6 | `layouts/app.blade.php:129` | GET | Auto-popolamento campo code da reference | Form submissions |
| 7 | `layouts/modal-attachlist.blade.php:80` | GET | Ricerca allegati per mese/anno | Modali (filtro) |
| 8 | `layouts/modal-attachlist.blade.php:118` | GET | Eliminazione allegato | Modali (delete) |
| 9 | `layouts/modal-users.blade.php:76` | POST | Salvataggio associazioni utente-profilo | Modali (form) |
| 10 | `layouts/modal-users.blade.php:94` | GET | Ricerca utenti per profilo | Modali (filtro) |
| 11 | `multiselect/multiselect-js.blade.php:58` | GET | Esecuzione azione bulk su selezionati | Multiselect |
| 12 | `multiselect/multiselect-js.blade.php:105` | GET | Memorizzazione ID selezionati in sessione | Multiselect |
| 13 | `report.blade.php:119` | PUT | Aggiornamento campo boolean (switch) | Form submissions |
| 14 | `forms/item-child.blade.php:170` | GET | Caricamento form item-child in modale | Modali |
| 15 | `forms/item-child.blade.php:205` | POST | Salvataggio dati item-child (DRS/fattura) | Modali |
| 16 | `finder.blade.php:34` | GET | Risoluzione URL finder per autocomplete | Filtering |
| 17 | `finder.blade.php:79` | GET | Ricerca/autocomplete risultati | Filtering |
| 18 | `multiselect-js.blade.php:82` | GET | (vecchia copia - inattiva) | Multiselect |

### Altro codice jQuery (non-AJAX)

| File | Riga | Scopo |
|------|------|-------|
| `assets/js/common.js:3` | `$(".btnDel").click()` | Rimozione riga child form dal DOM |
| `multiselect/dropdown.blade.php:23` | `$('.dropdown-toggle').dropdown()` | Init Bootstrap dropdown |
| `app.blade.php:145` | `$('button.btn, a.btn').attr('disabled')` | Disabilitazione bottoni senza permessi edit |
| `app.blade.php:196` | `$("document").find("div.tox-promotion").remove()` | Rimozione badge promo TinyMCE |
| `multiselect/multiselect-js.blade.php:4` | `$("#toggleCheck").click()` | Toggle tutti i checkbox multiselect |

### Include jQuery CDN / Bundle

| File | Riga | Risorsa |
|------|------|---------|
| `layouts/app.blade.php:15` | CDN | `jquery-3.6.0.slim.min.js` |
| `layouts/app.blade.php:31` | Bundle | `app.js` (contiene jQuery compilato) |
| `layouts/app.blade.php:32` | Script | `common.js` |
| `layouts/app.blade.php:37` | CDN | `moment.min.js` |
| `layouts/app.blade.php:38` | CDN | `daterangepicker.min.js` (plugin jQuery) |

---

## PROCESSO 1 — Eliminazione delete-js.blade.php

**File sorgente**: `views/layouts/delete-js.blade.php`
**Incluso da**: `report.blade.php`, `builder.blade.php`, `profile.blade.php`
**Chiamate AJAX**: 2 (`DELETE` per cancellazione, `PUT` per soft-disable)

### Cosa fa attualmente
- Click su `.destroy` → `confirm()` → `$.ajax({ method: 'DELETE' })` → `location.reload()`
- Click su `.cancel` → `confirm()` → `$.ajax({ method: 'PUT', cancel_action: 1 })` → `location.reload()`

### Sostituzione con Livewire

**Opzione A — Componente Livewire `DeleteConfirm`** (se non esiste gia):

```php
// Packages\IctInterface\Livewire\DeleteConfirm.php
class DeleteConfirm extends Component
{
    public int $recordId;
    public string $deleteUrl;
    public bool $showConfirm = false;
    public string $action = 'delete'; // 'delete' o 'cancel'

    public function confirmDelete(int $id, string $action = 'delete'): void
    {
        $this->recordId = $id;
        $this->action = $action;
        $this->showConfirm = true;
    }

    public function executeAction(): void
    {
        if ($this->action === 'delete') {
            // DELETE request via controller
        } else {
            // PUT con cancel_action=1
        }
        $this->showConfirm = false;
        $this->dispatch('record-deleted');
    }

    public function render() { ... }
}
```

**Opzione B — Alpine.js puro con `fetch()` nativo** (piu leggero):

```blade
<div x-data="{ showConfirm: false, recordId: null, action: 'delete' }">
    {{-- Trigger button (dentro il loop della tabella report) --}}
    <button @click="recordId = {{ $record->id }}; action = 'delete'; showConfirm = true"
            class="btn btn-danger btn-sm">
        <i class="fas fa-trash"></i>
    </button>

    {{-- Modal conferma con Bootstrap 5 nativo --}}
    <template x-if="showConfirm">
        <div class="modal show d-block" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body">Confermi l'eliminazione?</div>
                    <div class="modal-footer">
                        <button @click="showConfirm = false" class="btn btn-secondary">Annulla</button>
                        <button @click="
                            fetch('/your-route/' + recordId, {
                                method: action === 'delete' ? 'DELETE' : 'PUT',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Content-Type': 'application/json'
                                },
                                body: action === 'cancel' ? JSON.stringify({cancel_action: 1}) : null
                            }).then(() => location.reload())
                        " class="btn btn-danger">Conferma</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
```

### Azioni

1. Creare componente Livewire `DeleteConfirm` oppure snippet Alpine.js con `fetch()` nativo
2. Nella vista Blade del componente usare Bootstrap 5.3 Modal per la conferma (no `confirm()`)
3. Sostituire le chiamate `$.ajax DELETE/PUT` con:
   - `wire:click="delete(id)"` (Livewire) oppure
   - `@click + fetch()` (Alpine.js nativo)
4. Rimuovere `@include('ict::layouts.delete-js')` da `report.blade.php`, `builder.blade.php`, `profile.blade.php`
5. Eliminare il file `delete-js.blade.php`

---

## PROCESSO 2 — Eliminazione form-child-js.blade.php

**File sorgente**: `views/layouts/form-child-js.blade.php`
**Incluso da**: `item-child.blade.php`, `builder.blade.php`, `fattura.blade.php`, `profile.blade.php`
**Chiamate AJAX**: 1 (`POST` per aggiungere riga child)

### Cosa fa attualmente
- Click su `#addChildForm` / `#addChildFormBottom` → `$.ajax({ method: 'POST' })` → append HTML della nuova riga child in `#childContainer` → aggiorna i campi hidden `item_id`

### Sostituzione con Livewire

Questo deve essere gestito dal componente Livewire `ChildFormComponent` (gia previsto nella strategia di refactoring FASE 6).

```php
// Nel ChildFormComponent Livewire
public function addItem(): void
{
    $newItem = [];
    foreach ($this->childFields as $field) {
        $newItem[$field['name']] = $field['default_value'] ?? null;
    }
    $this->items[] = $newItem;
}
```

Nella vista Blade:
```blade
<button type="button" wire:click="addItem" class="btn btn-primary btn-sm">
    <i class="fas fa-plus-circle"></i> Aggiungi riga
</button>

@foreach($items as $index => $item)
    <div wire:key="child-{{ $index }}">
        {{-- campi del child --}}
        @foreach($childFields as $field)
            <x-ict-dynamic-field :field="$field" wire:model="items.{{ $index }}.{{ $field['name'] }}" />
        @endforeach
        <button wire:click="removeItem({{ $index }})" class="btn btn-danger btn-sm">
            <i class="fas fa-minus-circle"></i>
        </button>
    </div>
@endforeach
```

### Azioni

1. Assicurarsi che il componente Livewire `ChildFormComponent` gestisca l'aggiunta/rimozione righe dinamicamente via `wire:click`
2. La logica di aggiunta riga NON richiede piu chiamate AJAX: Livewire aggiorna il DOM reattivamente
3. Il contatore `item_id` e gestito come proprieta del componente Livewire
4. Rimuovere `@include('ict::layouts.form-child-js')` da tutti i file che lo includono
5. Eliminare il file `form-child-js.blade.php`
6. Eliminare anche la funzione correlata in `common.js` (click su `.btnDel` per rimozione riga)

---

## PROCESSO 3 — Eliminazione modal-js.blade.php

**File sorgente**: `views/layouts/modal-js.blade.php`
**Incluso da**: `item-child.blade.php`, `builder.blade.php`, `fattura.blade.php`, `profile.blade.php`
**Chiamate AJAX**: 2 (`GET` per caricare form in modale, `POST` per salvare)

### Cosa fa attualmente
- `$('#modal').on("show.bs.modal")` → estrae ID record dal bottone → `$.ajax({ method: 'GET' })` → inietta HTML del form nel modal body
- `$("#saveModalData").click()` → serializza il form → `$.ajax({ method: 'POST' })` → `alert()` + `location.reload()`

### Sostituzione con Livewire

Componente Livewire `ModalFormComponent` (gia previsto in FASE 7):

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
    }

    public function submit(): void
    {
        $validated = $this->validate($this->getRules());
        // ... logica salvataggio ...
        $this->closeModal();
        $this->dispatch('record-saved');
    }
}
```

Vista Blade con Alpine.js + Bootstrap 5.3 Modal:
```blade
<div x-data="{ show: @entangle('showModal') }"
     x-show="show"
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
                            <x-ict-dynamic-field :field="$field"
                                wire:model="formData.{{ $field['name'] }}" />
                        </div>
                    @endforeach
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" wire:click="closeModal">Annulla</button>
                <button class="btn btn-primary" wire:click="submit">Salva</button>
            </div>
        </div>
    </div>
</div>
<div x-show="show" class="modal-backdrop fade show"></div>
```

### Azioni

1. Il componente Livewire `ModalFormComponent` carica i dati del form via `mount()` o `openModal()`, non via AJAX GET
2. Il salvataggio avviene via `wire:submit` o `wire:click="submit"`, non via AJAX POST
3. La modale Bootstrap si apre/chiude tramite Alpine.js `x-show` + `@entangle`, non via jQuery `.modal('show')`
4. I messaggi di successo usano `session()->flash()` o `$this->dispatch('notify')`, non `alert()`
5. Rimuovere `@include('ict::layouts.modal-js')` da tutti i file
6. Eliminare il file `modal-js.blade.php`

---

## PROCESSO 4 — Eliminazione modal-attachlist.blade.php (jQuery)

**File sorgente**: `views/layouts/modal-attachlist.blade.php` (righe 74-133)
**Chiamate AJAX**: 2 (`GET` per ricerca allegati, `GET` per eliminazione allegato)

### Cosa fa attualmente
- `$("#searchModalAttach").click()` → serializza form → `$.ajax GET` a route `call.search.attach` → ricostruisce `<tbody>` con `$.each()`
- `$(".btnCancelAttach").click()` → `$.ajax GET` a route `delete.attachments` → `location.reload()`

### Sostituzione con Livewire

Creare un componente Livewire `AttachmentList`:

```php
class AttachmentList extends Component
{
    public int $recordId;
    public ?string $filterMonth = null;
    public ?string $filterYear = null;
    public Collection $attachments;

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;
        $this->loadAttachments();
    }

    public function search(): void
    {
        $this->loadAttachments();
    }

    public function deleteAttachment(int $attachId): void
    {
        // logica eliminazione
        $this->loadAttachments();
        $this->dispatch('notify', message: 'Allegato eliminato');
    }

    private function loadAttachments(): void
    {
        $query = Attachment::where('record_id', $this->recordId);
        if ($this->filterMonth) $query->whereMonth('created_at', $this->filterMonth);
        if ($this->filterYear) $query->whereYear('created_at', $this->filterYear);
        $this->attachments = $query->get();
    }
}
```

### Azioni

1. Creare componente Livewire `AttachmentList` che gestisce ricerca e cancellazione allegati
2. La tabella degli allegati si aggiorna reattivamente via Livewire (no ricostruzione DOM con jQuery)
3. Sostituire lo script jQuery (righe 74-133) con `wire:click="search"` e `wire:click="deleteAttachment(id)"`
4. Mantenere la struttura HTML Bootstrap della modale, aggiungendo solo le direttive `wire:`
5. Eliminare il blocco `<script>` jQuery dal file

---

## PROCESSO 5 — Eliminazione modal-users.blade.php (jQuery)

**File sorgente**: `views/layouts/modal-users.blade.php` (righe 62-143)
**Incluso da**: `profile.blade.php`
**Chiamate AJAX**: 2 (`GET` per ricerca utenti, `POST` per aggiunta utenti a profilo)

### Cosa fa attualmente
- `searchUsers()` → serializza form → `$.ajax GET` a route `call.search.users` → costruisce `<thead>` e `<tbody>` con `$.each()` inclusi checkbox
- `$("#btnAddUser").click()` → serializza form con checkbox → `$.ajax POST` a route `call.add.users` → `location.reload()`

### Sostituzione con Livewire

Creare un componente Livewire `UserProfileManager`:

```php
class UserProfileManager extends Component
{
    public int $profileId;
    public string $searchTerm = '';
    public array $selectedUsers = [];
    public Collection $searchResults;

    public function search(): void
    {
        $this->searchResults = User::where('name', 'like', "%{$this->searchTerm}%")
            ->orWhere('email', 'like', "%{$this->searchTerm}%")
            ->get();
    }

    public function addUsers(): void
    {
        // logica aggiunta utenti al profilo
        foreach ($this->selectedUsers as $userId) {
            // associa utente al profilo
        }
        $this->dispatch('users-added');
        $this->reset(['selectedUsers', 'searchTerm']);
    }
}
```

### Azioni

1. Creare componente Livewire `UserProfileManager`
2. La ricerca utenti usa `wire:model.live="searchTerm"` con debounce per ricerca real-time
3. I checkbox usano `wire:model="selectedUsers"` come array
4. Il salvataggio usa `wire:click="addUsers"`, no AJAX POST
5. La tabella risultati si aggiorna reattivamente via Livewire
6. Rimuovere lo script jQuery (righe 62-143) dal file
7. Rimuovere `@include('ict::layouts.modal-users')` da `profile.blade.php` e sostituire con `@livewire('user-profile-manager')`

---

## PROCESSO 6 — Eliminazione multiselect-js.blade.php

**File sorgente**: `views/multiselect/multiselect-js.blade.php` (attivo), `views/multiselect-js.blade.php` (vecchia copia)
**Incluso da**: `app.blade.php` (condizionale su `$report['has_multiselect']`)
**Chiamate AJAX**: 3 (`GET` per azione bulk, `GET` per salvataggio selezione in sessione, `GET` vecchia copia)

### Cosa fa attualmente
- `$("#toggleCheck").click()` → toglie/mette tutti i checkbox `.multicheck`
- `$(".multicheck").click()` → gestisce selezione individuale
- `$(".do-action").click()` → `$.ajax GET` a route `call.do_multiselect` → esegue azione bulk → reload
- `setChecked()` → `$.ajax GET` a route `call.multiselect` → salva ID selezionati in sessione server

### Sostituzione con Livewire

Creare un componente Livewire `MultiselectManager`:

```php
class MultiselectManager extends Component
{
    public array $selectedIds = [];
    public bool $selectAll = false;
    public int $reportId;
    public string $selectedAction = '';

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedIds = $this->getAllVisibleIds();
        } else {
            $this->selectedIds = [];
        }
    }

    public function executeAction(): void
    {
        if (empty($this->selectedAction) || empty($this->selectedIds)) return;

        // esegui azione bulk
        // ...

        $this->selectedIds = [];
        $this->selectAll = false;
    }
}
```

Vista Blade:
```blade
{{-- Toggle all --}}
<input type="checkbox" wire:model.live="selectAll" class="form-check-input">

{{-- Singolo checkbox per ogni riga --}}
@foreach($records as $record)
    <input type="checkbox" wire:model.live="selectedIds" value="{{ $record->id }}"
           class="form-check-input">
@endforeach

{{-- Azione bulk --}}
<select wire:model="selectedAction" class="form-select">
    <option value="">- Seleziona azione -</option>
    {{-- opzioni --}}
</select>
<button wire:click="executeAction" class="btn btn-primary btn-sm">Esegui</button>
```

### Azioni

1. Creare componente Livewire `MultiselectManager` (o integrare nel `ReportTable` se esiste)
2. I checkbox usano `wire:model.live="selectedIds"` — Livewire traccia lo stato, non serve salvarlo in sessione via AJAX
3. Il toggle "seleziona tutti" usa `wire:model.live="selectAll"` con hook `updatedSelectAll()`
4. L'azione bulk usa `wire:click="executeAction"` — no AJAX GET
5. Rimuovere `@include('ict::multiselect.multiselect-js')` da `app.blade.php`
6. Eliminare il file `multiselect/multiselect-js.blade.php`
7. Eliminare il file `multiselect-js.blade.php` (vecchia copia)

---

## PROCESSO 7 — Eliminazione finder.blade.php (jQuery)

**File sorgente**: `views/finder.blade.php`
**Stato**: Attualmente commentato in `app.blade.php` (riga 241)
**Chiamate AJAX**: 2 (`GET` per risoluzione URL, `GET` per ricerca autocomplete)

### Cosa fa attualmente
- `$(document).on('focus', '.finder')` → `$.ajax GET` a route `get.finder.route` → risolve URL dati per il campo
- `$(document).on('keyup', '.finder')` → quando input > 2 caratteri → `$.ajax GET` → costruisce risultati ricerca con `$.each()`

### Sostituzione con Livewire

Se il finder viene riattivato, creare un componente Livewire `FinderField`:

```php
class FinderField extends Component
{
    public string $query = '';
    public array $results = [];
    public string $finderRoute;
    public bool $showResults = false;

    public function updatedQuery(): void
    {
        if (strlen($this->query) < 3) {
            $this->results = [];
            $this->showResults = false;
            return;
        }

        // Ricerca diretta, no risoluzione URL intermedia
        $this->results = $this->searchRecords($this->query);
        $this->showResults = true;
    }

    public function selectResult(int $id, string $label): void
    {
        $this->query = $label;
        $this->showResults = false;
        $this->dispatch('finder-selected', id: $id, label: $label);
    }
}
```

### Azioni

1. Se il finder e necessario: creare componente Livewire `FinderField` con ricerca real-time via `wire:model.live.debounce.300ms`
2. Se non e necessario: eliminare direttamente il file
3. La ricerca usa la logica server-side direttamente nel componente Livewire, senza risolvere URL via AJAX separata
4. I risultati si mostrano con `x-show` (Alpine.js) e si aggiornano reattivamente
5. Eliminare il file `finder.blade.php`
6. Rimuovere il commento `{{-- @include('js.finder') --}}` da `app.blade.php`

---

## PROCESSO 8 — Eliminazione jQuery inline da report.blade.php

**File sorgente**: `views/report.blade.php`
**Chiamate AJAX**: 1 (`PUT` per aggiornamento boolean switch)

### 8A — Date auto-copy (righe 83-92)

**Cosa fa**: `$("#whereDate-ue_billing_from").on('change')` → copia valore nel campo "billing to"

**Sostituzione con Alpine.js**:
```blade
<div x-data="{ billingFrom: '', billingTo: '' }">
    <input type="date" x-model="billingFrom"
           @change="if (!billingTo) billingTo = billingFrom"
           name="whereDate-ue_billing_from">
    <input type="date" x-model="billingTo"
           name="whereDate-ue_billing_to">
</div>
```

### 8B — Boolean switch (righe 107-140)

**Cosa fa**: `$('.boolswitch').on('change')` → `$.ajax({ type: 'PUT' })` a route `switch.update` → mostra messaggio successo temporaneo

**Sostituzione con Livewire**:

Creare un componente Livewire `BooleanSwitch`:

```php
class BooleanSwitch extends Component
{
    public int $recordId;
    public string $field;
    public bool $value;
    public int $reportId;

    public function toggle(): void
    {
        $this->value = !$this->value;
        // Aggiorna DB
        DB::table($this->getTable())
            ->where('id', $this->recordId)
            ->update([$this->field => $this->value]);

        $this->dispatch('notify', message: 'Aggiornato con successo');
    }
}
```

Vista:
```blade
<div class="form-check form-switch">
    <input type="checkbox" class="form-check-input"
           wire:click="toggle"
           @checked($value)>
</div>
```

### Azioni

1. Sostituire lo script jQuery date auto-copy con Alpine.js `x-data` + `@change`
2. Creare componente Livewire `BooleanSwitch` per lo switch inline
3. Rimuovere i blocchi `<script>` jQuery (righe 83-92 e 107-140) da `report.blade.php`
4. Aggiornare il rendering dello switch nella tabella per usare `@livewire('boolean-switch', [...])`

---

## PROCESSO 9 — Eliminazione jQuery inline da app.blade.php

**File sorgente**: `views/layouts/app.blade.php`
**Chiamate AJAX**: 1 (`GET` per auto-popolamento campo reference)

### 9A — Auto-popolamento reference → code (righe 123-141)

**Cosa fa**: `$("select#reference").on('change')` → `$.ajax GET` a route `ref_numeric` → popola il campo `#code`

**Sostituzione con Livewire**:

Questa logica va integrata nel componente `EditableFormComponent`:

```php
// Nel componente Livewire del form
public function updatedFormDataReference($value): void
{
    if (!empty($value)) {
        // Logica che attualmente e nella route ref_numeric
        $code = $this->resolveReferenceCode($value);
        $this->formData['code'] = $code;
    }
}
```

Oppure con Alpine.js + `fetch()` nativo:
```blade
<select x-data @change="
    fetch('/route/ref_numeric?reference=' + $event.target.value, {
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(data => document.getElementById('code').value = data.code)
" name="reference" id="reference" class="form-select">
```

### 9B — Disabilitazione bottoni senza permessi (riga 145-146)

**Cosa fa**: `$('button.btn, a.btn').attr('disabled', true)` quando utente non ha permessi edit

**Sostituzione con Blade/PHP puro**:
```blade
@unless($canEdit)
    <style>
        .btn-edit, .btn-delete { pointer-events: none; opacity: 0.5; }
    </style>
@endunless
```

Oppure direttamente nel rendering dei bottoni:
```blade
<button class="btn btn-primary" @disabled(!$canEdit)>Modifica</button>
```

### 9C — Rimozione badge TinyMCE promo (riga 196)

**Cosa fa**: `$("document").find("div.tox-promotion").remove()`

**Sostituzione con CSS puro**:
```css
div.tox-promotion { display: none !important; }
```

Oppure con vanilla JS:
```javascript
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('div.tox-promotion').forEach(el => el.remove());
});
```

### Azioni

1. Integrare la logica reference → code nel componente Livewire del form (hook `updated`) o usare Alpine.js + `fetch()`
2. Sostituire la disabilitazione jQuery dei bottoni con condizioni Blade `@disabled` o CSS
3. Sostituire la rimozione promo TinyMCE con CSS `display: none !important`
4. Rimuovere tutti i blocchi `<script>` jQuery da `app.blade.php`
5. Mantenere solo le inclusioni Alpine.js/Livewire

---

## PROCESSO 10 — Eliminazione common.js

**File sorgente**: `assets/js/common.js` e `public/ict-assets/js/common.js`
**Scopo**: `$(".btnDel").click()` → rimuove riga child form dal DOM

### Cosa fa attualmente
- `$(document).on('click', ".btnDel")` → legge `data-form` → rimuove il div della riga child

### Sostituzione
Questa funzionalita e gia coperta dal componente Livewire `ChildFormComponent`:
```blade
<button wire:click="removeItem({{ $index }})" class="btn btn-danger btn-sm">
    <i class="fas fa-minus-circle"></i>
</button>
```

### Azioni

1. Verificare che `ChildFormComponent` gestisca la rimozione righe via `wire:click`
2. Rimuovere `<script src="common.js">` da `app.blade.php`
3. Eliminare `assets/js/common.js`
4. Eliminare `public/ict-assets/js/common.js`

---

## PROCESSO 11 — Eliminazione dropdown.blade.php (jQuery)

**File sorgente**: `views/multiselect/dropdown.blade.php` (riga 23)
**Scopo**: `$('.dropdown-toggle').dropdown()` — inizializzazione Bootstrap dropdown

### Sostituzione con Bootstrap 5 nativo

Bootstrap 5 inizializza i dropdown automaticamente tramite `data-bs-toggle="dropdown"`. Non serve piu JavaScript.

```blade
<div class="dropdown">
    <button class="btn btn-secondary dropdown-toggle" type="button"
            data-bs-toggle="dropdown" aria-expanded="false">
        Azioni
    </button>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#">Azione 1</a></li>
    </ul>
</div>
```

### Azioni

1. Verificare che i dropdown usino `data-bs-toggle="dropdown"` (attributo BS5 nativo)
2. Rimuovere la riga `$('.dropdown-toggle').dropdown()` dal file
3. Se il file ha solo quella riga jQuery, eliminare il blocco `<script>` interamente

---

## PROCESSO 12 — Sostituzione daterangepicker jQuery

**File sorgente**: `views/layouts/app.blade.php` (righe 149-172)
**Dipendenze CDN**: `moment.min.js`, `daterangepicker.min.js`
**Scopo**: Inizializza il plugin jQuery daterangepicker su tutti gli input `.datapicker`

### Cosa fa attualmente
- `$('.datapicker').daterangepicker({...})` con configurazione locale italiana
- Handler `apply.daterangepicker` per formattare il valore selezionato
- Handler `cancel.daterangepicker` per pulire il campo

### Opzioni di sostituzione

**Opzione A — Componente Livewire `DateRangeField`**:

```php
class DateRangeField extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $fieldName;

    public function render()
    {
        return view('ict::livewire.date-range-field');
    }
}
```

Vista con input HTML5 nativi:
```blade
<div class="input-group">
    <input type="date" wire:model="dateFrom" class="form-control" placeholder="Da">
    <span class="input-group-text">-</span>
    <input type="date" wire:model="dateTo" class="form-control" placeholder="A">
</div>
```

**Opzione B — Alpine.js con Flatpickr** (no jQuery, libreria leggera):

```blade
<div x-data="dateRange()" x-init="init()">
    <input type="text" x-ref="dateInput" class="form-control" readonly>
</div>

<script>
function dateRange() {
    return {
        init() {
            flatpickr(this.$refs.dateInput, {
                mode: 'range',
                dateFormat: 'd/m/Y',
                locale: 'it'
            });
        }
    }
}
</script>
```

**Opzione C — Input HTML5 nativi `type="date"`** (zero dipendenze):
```blade
<input type="date" name="date_from" class="form-control">
<input type="date" name="date_to" class="form-control">
```

### Azioni

1. Scegliere l'approccio (Livewire, Flatpickr, o HTML5 nativo)
2. Rimuovere il CDN `moment.min.js` da `app.blade.php`
3. Rimuovere il CDN `daterangepicker.min.js` da `app.blade.php`
4. Rimuovere il blocco di inizializzazione jQuery daterangepicker (righe 149-172)
5. Aggiornare tutte le viste che usano `.datapicker` class con il nuovo componente

---

## PROCESSO 13 — Rimozione jQuery CDN e bundle

**File sorgente**: `views/layouts/app.blade.php`

### Azioni (da eseguire DOPO tutti i processi precedenti)

1. **Rimuovere CDN jQuery** (riga 15):
   ```html
   <!-- ELIMINARE -->
   <script src="https://code.jquery.com/jquery-3.6.0.slim.min.js"></script>
   ```

2. **Ricompilare app.js SENZA jQuery**:
   - Modificare `resources/js/app.js` (sorgente webpack/vite) per rimuovere l'import jQuery
   - Ricompilare con `npm run build`
   - Oppure eliminare completamente il bundle `app.js` se contiene solo jQuery e axios (Livewire gestisce le XHR)

3. **Rimuovere moment.js CDN** (riga 37) — usato solo da daterangepicker

4. **Rimuovere daterangepicker CDN** (riga 38) — plugin jQuery

5. **Rimuovere inclusione common.js** (riga 32) — gia gestito nel PROCESSO 10

6. **Verificare** che Bootstrap JS bundle (riga 16) NON dipenda da jQuery — Bootstrap 5.x e jQuery-free

---

## PROCESSO 14 — Aggiornamento dei file che includono i JS rimossi

I seguenti file contengono `@include` dei file JS jQuery eliminati e devono essere aggiornati:

### `views/report.blade.php`

```blade
{{-- RIMUOVERE nella sezione @section('footer') --}}
@include('ict::layouts.delete-js')
```

### `views/forms/builder.blade.php`

```blade
{{-- RIMUOVERE nella sezione @section('footer') --}}
@include('ict::layouts.modal-item')     {{-- se contiene jQuery --}}
@include('ict::layouts.modal')          {{-- se contiene jQuery --}}
@include('ict::layouts.delete-js')
@include('ict::layouts.form-child-js')
@include('ict::layouts.modal-js')
```

### `views/forms/profile.blade.php`

```blade
{{-- RIMUOVERE --}}
@include('ict::layouts.modal-users')    {{-- sostituito da Livewire --}}
@include('ict::layouts.delete-js')
@include('ict::layouts.form-child-js')
@include('ict::layouts.modal-js')
```

### `views/forms/item-child.blade.php`

```blade
{{-- RIMUOVERE nella sezione @section('footer') --}}
@include('ict::layouts.form-child-js')
@include('ict::layouts.modal-js')
{{-- RIMUOVERE anche il blocco <script> custom righe 149-220 --}}
```

### `views/forms/fattura.blade.php`

```blade
{{-- RIMUOVERE nella sezione @section('footer') --}}
@include('ict::layouts.modal-item')
@include('ict::layouts.modal')
@include('ict::layouts.form-child-js')
@include('ict::layouts.modal-js')
```

### `views/layouts/app.blade.php`

```blade
{{-- RIMUOVERE --}}
@include('ict::multiselect.multiselect-js')
{{-- @include('js.finder') --}}          {{-- gia commentato, rimuovere il commento --}}
{{-- RIMUOVERE tutti i blocchi <script> con codice jQuery --}}
```

### Azioni

1. Per ogni file sopra elencato, rimuovere le righe `@include` dei file JS eliminati
2. Sostituire con i corrispondenti `@livewire(...)` dove necessario
3. Verificare che la sezione `@section('footer')` non contenga piu codice jQuery
4. Se la sezione `@section('footer')` diventa vuota, rimuoverla

---

## PROCESSO 15 — Pulizia finale e validazione

### 15.1 — Grep completo per residui jQuery

```bash
grep -r "\\\$(" src/packages/ --include="*.blade.php" --include="*.js" \
  --exclude-dir=vendor --exclude-dir=tinymce --exclude-dir=node_modules
grep -r "jQuery" src/packages/ --include="*.blade.php" --include="*.js" \
  --exclude-dir=vendor --exclude-dir=tinymce --exclude-dir=node_modules
grep -r "\\\$.ajax" src/packages/ --include="*.blade.php" --include="*.js" \
  --exclude-dir=vendor --exclude-dir=tinymce
grep -r "daterangepicker" src/packages/ --include="*.blade.php" \
  --exclude-dir=vendor
```

### 15.2 — Verificare che nessuna pagina carichi jQuery

- Ispezionare il `<head>` e la fine del `<body>` in `app.blade.php`
- Verificare con DevTools del browser che jQuery non sia caricato
- Testare: `typeof jQuery` in console browser deve restituire `"undefined"`

### 15.3 — Test funzionale completo

Checklist di test per ogni funzionalita migrata:

- [ ] **Delete**: click elimina → modale conferma → record eliminato → lista aggiornata
- [ ] **Cancel/Disable**: click disabilita → modale conferma → record disabilitato
- [ ] **Child forms**: aggiungi riga → compila campi → salva parent+children → verifica DB
- [ ] **Rimuovi riga child**: click rimuovi → riga scompare → salva → verifica DB
- [ ] **Modali form**: click apri modale → form compilato → salva → modale chiude → lista aggiornata
- [ ] **Modali allegati**: ricerca per mese/anno → risultati filtrati → elimina allegato → lista aggiornata
- [ ] **Modali utenti**: ricerca utenti → seleziona checkbox → aggiungi → associazione salvata
- [ ] **Multiselect**: seleziona tutto → deseleziona singolo → esegui azione bulk → risultato corretto
- [ ] **Boolean switch**: toggle → valore aggiornato in DB → messaggio successo
- [ ] **Reference auto-fill**: cambio select reference → campo code auto-popolato
- [ ] **Date range**: selezione range date → filtro applicato correttamente
- [ ] **Finder** (se riattivato): digitazione → risultati autocomplete → selezione → campo popolato
- [ ] **Permessi edit**: utente senza permessi → bottoni disabilitati
- [ ] **TinyMCE**: editor funzionante → badge promo nascosto

### 15.4 — Eliminazione file asset jQuery

```bash
# File da eliminare
rm src/packages/IctInterface/src/resources/views/layouts/delete-js.blade.php
rm src/packages/IctInterface/src/resources/views/layouts/form-child-js.blade.php
rm src/packages/IctInterface/src/resources/views/layouts/modal-js.blade.php
rm src/packages/IctInterface/src/resources/views/multiselect-js.blade.php
rm src/packages/IctInterface/src/resources/views/multiselect/multiselect-js.blade.php
rm src/packages/IctInterface/src/resources/views/finder.blade.php
rm src/packages/IctInterface/src/resources/assets/js/common.js
rm src/public/ict-assets/js/common.js
# Valutare se eliminare anche app.js (bundle compilato) e ricompilarlo senza jQuery
```

---

## Riepilogo file da eliminare

| File | Motivo |
|------|--------|
| `views/layouts/delete-js.blade.php` | Sostituito da Livewire `DeleteConfirm` / Alpine.js |
| `views/layouts/form-child-js.blade.php` | Sostituito da Livewire `ChildFormComponent` |
| `views/layouts/modal-js.blade.php` | Sostituito da Livewire `ModalFormComponent` |
| `views/multiselect/multiselect-js.blade.php` | Sostituito da Livewire `MultiselectManager` |
| `views/multiselect-js.blade.php` | Vecchia copia, inattiva |
| `views/finder.blade.php` | Sostituito da Livewire `FinderField` (o eliminato) |
| `assets/js/common.js` | Logica coperta da Livewire `ChildFormComponent` |
| `public/ict-assets/js/common.js` | Copia pubblicata di `common.js` |

## Riepilogo componenti Livewire da creare/aggiornare

| Componente | Sostituisce | AJAX rimpiazzate |
|------------|-------------|------------------|
| `DeleteConfirm` | `delete-js.blade.php` | 2 (DELETE, PUT) |
| `ChildFormComponent` | `form-child-js.blade.php` + `common.js` | 1 (POST) |
| `ModalFormComponent` | `modal-js.blade.php` | 2 (GET, POST) |
| `AttachmentList` | jQuery in `modal-attachlist.blade.php` | 2 (GET, GET) |
| `UserProfileManager` | jQuery in `modal-users.blade.php` | 2 (GET, POST) |
| `MultiselectManager` | `multiselect-js.blade.php` | 3 (GET, GET, GET) |
| `FinderField` | `finder.blade.php` | 2 (GET, GET) |
| `BooleanSwitch` | jQuery in `report.blade.php` | 1 (PUT) |
| `DateRangeField` | jQuery daterangepicker in `app.blade.php` | 0 (init plugin) |
| `EditableFormComponent` (update) | jQuery reference auto-fill in `app.blade.php` | 1 (GET) |

**Totale AJAX eliminate: 16 attive + 2 inattive = 18**

---

## Ordine di esecuzione consigliato

```
PROCESSO  1 → DeleteConfirm (2 AJAX)
PROCESSO  2 → ChildFormComponent (1 AJAX + common.js)
PROCESSO  3 → ModalFormComponent (2 AJAX)
PROCESSO  4 → AttachmentList (2 AJAX)
PROCESSO  5 → UserProfileManager (2 AJAX)
PROCESSO  6 → MultiselectManager (3 AJAX)
PROCESSO  7 → FinderField (2 AJAX) — opzionale se commentato
PROCESSO  8 → report.blade.php inline (1 AJAX + date JS)
PROCESSO  9 → app.blade.php inline (1 AJAX + 3 jQuery snippets)
PROCESSO 10 → common.js (1 jQuery handler)
PROCESSO 11 → dropdown.blade.php (1 jQuery init)
PROCESSO 12 → daterangepicker (plugin + 2 CDN)
─────────────────────────────────────────────
PROCESSO 13 → Rimozione jQuery CDN e bundle (ULTIMO)
PROCESSO 14 → Aggiornamento @include nei file
PROCESSO 15 → Pulizia finale e test
```

> **Nota**: I processi 1-12 possono essere eseguiti in parallelo. I processi 13-15 devono essere eseguiti per ultimi, dopo aver verificato che tutti i precedenti siano completati con successo.
