# Sistema Action Handler per personalizzazione CRUD senza modificare il package

## Context
I componenti Livewire del package (`EditableFormComponent`, `ModalFormComponent`, `ChildFormComponent`, `DeleteConfirmComponent`) eseguono store/update/delete direttamente con `DB::table()`. Non esiste modo di personalizzare il comportamento CRUD a livello applicazione senza fare override dei componenti Livewire. L'utente vuole poter customizzare le 5 azioni (form create, form update, store, update, delete) tramite classi PHP plain a livello app, senza conoscere Livewire.

## Architettura

**Due livelli di intervento:**
- **Hook (before/after):** modificano i dati o eseguono side-effect, il componente Livewire continua a fare il DB write
- **Action replacement (store/update/delete):** sostituiscono completamente l'operazione DB, il componente Livewire delega al handler

**Risoluzione per table name:** `books` → `App\Actions\BooksActionHandler` (convention) oppure mapping esplicito in config.

---

## File da creare (package)

### 1. `packages/IctInterface/src/Contracts/FormActionHandler.php` — Interface

```php
<?php
namespace Packages\IctInterface\Contracts;

interface FormActionHandler
{
    // Hook: modifica $data prima dell'insert. Return null = abort.
    public function beforeStore(string $tableName, array $data, int $formId): ?array;
    // Hook: modifica $data prima dell'update. Return null = abort.
    public function beforeUpdate(string $tableName, array $data, int $formId, int $recordId): ?array;
    // Hook: return false = abort delete.
    public function beforeDelete(string $tableName, int $recordId, string $action): bool;

    // Replacement: return int (new ID) = handled. Return null = usa default DB::table().
    public function store(string $tableName, array $data, int $formId): ?int;
    // Replacement: return true = handled. Return null = usa default.
    public function update(string $tableName, array $data, int $formId, int $recordId): ?bool;
    // Replacement: $action = 'delete'|'disable'. Return true = handled. Return null = usa default.
    public function delete(string $tableName, int $recordId, string $action): ?bool;

    // Hook post-operazione
    public function afterStore(string $tableName, array $data, int $newRecordId, int $formId): void;
    public function afterUpdate(string $tableName, array $data, int $recordId, int $formId): void;
    public function afterDelete(string $tableName, int $recordId, string $action): void;
}
```

### 2. `packages/IctInterface/src/Contracts/BaseActionHandler.php` — Classe base astratta

Implementa tutti i metodi con comportamento no-op (pass-through). Lo sviluppatore estende questa e fa override solo dei metodi che serve.

```php
<?php
namespace Packages\IctInterface\Contracts;

abstract class BaseActionHandler implements FormActionHandler
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

    public function store(string $tableName, array $data, int $formId): ?int
    {
        return null;
    }

    public function update(string $tableName, array $data, int $formId, int $recordId): ?bool
    {
        return null;
    }

    public function delete(string $tableName, int $recordId, string $action): ?bool
    {
        return null;
    }

    public function afterStore(string $tableName, array $data, int $newRecordId, int $formId): void {}
    public function afterUpdate(string $tableName, array $data, int $recordId, int $formId): void {}
    public function afterDelete(string $tableName, int $recordId, string $action): void {}
}
```

### 3. `packages/IctInterface/src/Services/ActionHandlerResolver.php` — Resolver

```php
<?php
namespace Packages\IctInterface\Services;

use Illuminate\Support\Str;
use Packages\IctInterface\Contracts\FormActionHandler;

class ActionHandlerResolver
{
    public function resolve(?string $tableName): ?FormActionHandler
    {
        if (!$tableName) return null;

        // 1. Config esplicita: config('ict.action_handlers')['books'] => BookHandler::class
        $handlers = config('ict.action_handlers', []);
        if (isset($handlers[$tableName])) {
            return app($handlers[$tableName]);
        }

        // 2. Convention: App\Actions\{StudlyCase(tableName)}ActionHandler
        $class = 'App\\Actions\\' . Str::studly($tableName) . 'ActionHandler';
        if (class_exists($class)) {
            return app($class);
        }

        return null; // nessun handler → comportamento default
    }
}
```

---

## File da modificare (package)

### 4. `packages/IctInterface/config/ict.php`

Aggiungere chiave:

```php
'action_handlers' => [],
```

### 5. `packages/IctInterface/src/Providers/IctServiceProvider.php`

In `register()`, aggiungere:

```php
$this->app->singleton(
    \Packages\IctInterface\Services\ActionHandlerResolver::class
);
```

### 6. `packages/IctInterface/src/Livewire/EditableFormComponent.php` — `submit()`

Dopo preparazione dati (encrypt, upload, guarded removal), prima della transazione DB:
- Resolve handler per `$this->tableName`
- Chiama `beforeStore()`/`beforeUpdate()` → se return null, abort
- Dentro la transazione: prova `handler->store()`/`handler->update()` → se return null, usa `DB::table()` default
- Dopo commit: chiama `afterStore()`/`afterUpdate()`

**submit() modificato:**

```php
public function submit(): void
{
    $formService = app(DynamicFormService::class);
    $rules = $formService->getValidationRules($this->formId, $this->recordId);

    $prefixedRules = [];
    foreach ($rules as $field => $rule) {
        $prefixedRules["formData.{$field}"] = $rule;
    }
    $this->validate($prefixedRules);

    $data = $this->formData;

    // Gestione cifratura
    foreach ($data as $key => $value) {
        if ($formService->isFieldCrypted($key, $this->formId) && !empty($value)) {
            $data[$key] = Crypt::encryptString($value);
        }
        if ($formService->isFieldMultiselect($key, $this->formId) && !empty($value)) {
            $data[$key] = json_encode($value);
        }
    }

    // Gestione upload file
    foreach ($this->fileUploads as $fieldName => $file) {
        if ($file) {
            $uploadDir = config('ict.upload_dir', 'upload');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->storeAs($uploadDir, $fileName, 'public');
            $data[$fieldName] = $fileName;
        }
    }

    // Rimuovi campi guarded
    foreach ($this->fields as $field) {
        if (!empty($field['is_guarded'])) {
            unset($data[$field['name']]);
        }
    }

    // --- ACTION HANDLER ---
    $resolver = app(\Packages\IctInterface\Services\ActionHandlerResolver::class);
    $handler = $resolver->resolve($this->tableName);

    $wasInsert = !$this->recordId;

    // --- BEFORE HOOKS ---
    if ($handler) {
        if ($this->recordId) {
            $data = $handler->beforeUpdate($this->tableName, $data, $this->formId, $this->recordId);
        } else {
            $data = $handler->beforeStore($this->tableName, $data, $this->formId);
        }
        if ($data === null) {
            session()->flash('message', 'Operazione annullata dal handler');
            session()->flash('alert', 'warning');
            return;
        }
    }

    DB::beginTransaction();

    try {
        if ($this->recordId) {
            // --- UPDATE ---
            $handled = $handler ? $handler->update($this->tableName, $data, $this->formId, $this->recordId) : null;
            if ($handled === null) {
                DB::table($this->tableName)->where('id', $this->recordId)->update($data);
            }
            session()->flash('message', 'Record aggiornato con successo');
            session()->flash('alert', 'success');
        } else {
            // --- INSERT ---
            $newId = $handler ? $handler->store($this->tableName, $data, $this->formId) : null;
            if ($newId === null) {
                $newId = DB::table($this->tableName)->insertGetId($data);
            }
            $this->recordId = $newId;
            session()->flash('message', "Record [ID: {$newId}] creato con successo");
            session()->flash('alert', 'success');
        }

        DB::commit();

        // --- AFTER HOOKS ---
        if ($handler) {
            if ($wasInsert) {
                $handler->afterStore($this->tableName, $data, $this->recordId, $this->formId);
            } else {
                $handler->afterUpdate($this->tableName, $data, $this->recordId, $this->formId);
            }
        }
    } catch (Exception $e) {
        DB::rollBack();
        session()->flash('message', 'Errore nel salvataggio: ' . $e->getMessage());
        session()->flash('alert', 'danger');
        return;
    }

    if ($this->hasChild && $wasInsert) {
        return;
    }

    $redirectUrl = $this->redirectUrl ?? $this->pageUrl . '?report=' . $this->reportId;
    $this->redirect($redirectUrl);
}
```

### 7. `packages/IctInterface/src/Livewire/ModalFormComponent.php` — `submit()`

Stesso pattern di EditableFormComponent. Dopo preparazione dati e prima della transazione:

```php
public function submit(): void
{
    if (!$this->formId) {
        return;
    }

    $formService = app(DynamicFormService::class);
    $rules = $formService->getValidationRules($this->formId, $this->recordId);

    $prefixedRules = [];
    foreach ($rules as $field => $rule) {
        $prefixedRules["formData.{$field}"] = $rule;
    }
    $this->validate($prefixedRules);

    $data = $this->formData;

    // Gestione cifratura
    foreach ($data as $key => $value) {
        if ($formService->isFieldCrypted($key, $this->formId) && !empty($value)) {
            $data[$key] = Crypt::encryptString($value);
        }
        if ($formService->isFieldMultiselect($key, $this->formId) && !empty($value)) {
            $data[$key] = json_encode($value);
        }
    }

    // Rimuovi campi guarded
    foreach ($this->fields as $field) {
        if (!empty($field['is_guarded'])) {
            unset($data[$field['name']]);
        }
    }

    // --- ACTION HANDLER ---
    $resolver = app(\Packages\IctInterface\Services\ActionHandlerResolver::class);
    $handler = $resolver->resolve($this->tableName);

    $wasInsert = !$this->recordId;

    if ($handler) {
        if ($this->recordId) {
            $data = $handler->beforeUpdate($this->tableName, $data, $this->formId, $this->recordId);
        } else {
            $data = $handler->beforeStore($this->tableName, $data, $this->formId);
        }
        if ($data === null) {
            session()->flash('modal_error', 'Operazione annullata dal handler');
            return;
        }
    }

    DB::beginTransaction();

    try {
        if ($this->recordId) {
            $handled = $handler ? $handler->update($this->tableName, $data, $this->formId, $this->recordId) : null;
            if ($handled === null) {
                DB::table($this->tableName)->where('id', $this->recordId)->update($data);
            }
        } else {
            $newId = $handler ? $handler->store($this->tableName, $data, $this->formId) : null;
            if ($newId === null) {
                $newId = DB::table($this->tableName)->insertGetId($data);
            }
            $this->recordId = $newId;
        }

        DB::commit();

        if ($handler) {
            if ($wasInsert) {
                $handler->afterStore($this->tableName, $data, $this->recordId, $this->formId);
            } else {
                $handler->afterUpdate($this->tableName, $data, $this->recordId, $this->formId);
            }
        }

        $this->closeModal();
        $this->dispatch('record-saved');
    } catch (Exception $e) {
        DB::rollBack();
        session()->flash('modal_error', 'Errore nel salvataggio: ' . $e->getMessage());
    }
}
```

### 8. `packages/IctInterface/src/Livewire/ChildFormComponent.php` — `saveItems()`

Stesso pattern, ma per-item nel loop. Cambio `insert()` → `insertGetId()` per avere l'ID:

```php
public function saveItems(): void
{
    if (empty($this->items)) {
        return;
    }

    $formService = app(DynamicFormService::class);
    $rules = $formService->getChildValidationRules($this->childFormId);

    if (!empty($rules)) {
        $this->validate($rules);
    }

    // --- ACTION HANDLER ---
    $resolver = app(\Packages\IctInterface\Services\ActionHandlerResolver::class);
    $handler = $resolver->resolve($this->childTableName);

    DB::beginTransaction();

    try {
        foreach ($this->items as $item) {
            $item[$this->foreignKey] = $this->parentRecordId;

            foreach ($this->childFields as $field) {
                if ($field['type'] === 'crypted' && !empty($item[$field['name']])) {
                    $item[$field['name']] = _encrypt($item[$field['name']]);
                }
            }
            foreach ($this->childFields as $field) {
                if (!empty($field['is_guarded'])) {
                    unset($item[$field['name']]);
                }
            }

            // Before hook
            if ($handler) {
                $item = $handler->beforeStore($this->childTableName, $item, $this->childFormId);
                if ($item === null) {
                    continue; // skip questo item
                }
            }

            // Store action
            $newId = $handler ? $handler->store($this->childTableName, $item, $this->childFormId) : null;
            if ($newId === null) {
                $newId = DB::table($this->childTableName)->insertGetId($item);
            }

            // After hook
            if ($handler) {
                $handler->afterStore($this->childTableName, $item, $newId, $this->childFormId);
            }
        }
        DB::commit();

        $this->items = [];
        $this->loadExistingItems();

        session()->flash('child_message', 'Items salvati con successo');
        session()->flash('child_alert', 'success');
    } catch (Exception $e) {
        DB::rollBack();
        session()->flash('child_message', 'Errore salvataggio items: ' . $e->getMessage());
        session()->flash('child_alert', 'danger');
    }
}
```

### 9. `packages/IctInterface/src/Livewire/DeleteConfirmComponent.php` — `execute()`

```php
public function execute(): void
{
    if (!$this->recordId) {
        return;
    }

    $log = new Logger();

    // --- ACTION HANDLER ---
    $resolver = app(\Packages\IctInterface\Services\ActionHandlerResolver::class);
    $handler = $resolver->resolve($this->routePrefix);

    if ($handler) {
        $allowed = $handler->beforeDelete($this->routePrefix, $this->recordId, $this->action);
        if (!$allowed) {
            session()->flash('message', 'Operazione annullata dal handler');
            session()->flash('alert', 'warning');
            $this->cancel();
            $this->js('window.location.reload()');
            return;
        }
    }

    try {
        DB::beginTransaction();

        $handled = $handler ? $handler->delete($this->routePrefix, $this->recordId, $this->action) : null;

        if ($handled === null) {
            // Default behavior
            if ($this->action === 'delete') {
                DB::table($this->routePrefix)->where('id', $this->recordId)->delete();
                $log->info("*DELETE* ID [{$this->recordId}] da [{$this->routePrefix}]", __FILE__, __LINE__);
                session()->flash('message', "Record [ID: {$this->recordId}] eliminato con successo");
                session()->flash('alert', 'success');
            } elseif ($this->action === 'disable') {
                DB::table($this->routePrefix)->where('id', $this->recordId)->update(['is_enabled' => 0]);
                $log->info("*DISABLE* ID [{$this->recordId}] da [{$this->routePrefix}]", __FILE__, __LINE__);
                session()->flash('message', "Record [ID: {$this->recordId}] disabilitato con successo");
                session()->flash('alert', 'success');
            }
        } else {
            $actionLabel = $this->action === 'delete' ? 'eliminato' : 'disabilitato';
            session()->flash('message', "Record [ID: {$this->recordId}] {$actionLabel} con successo");
            session()->flash('alert', 'success');
        }

        DB::commit();

        if ($handler) {
            $handler->afterDelete($this->routePrefix, $this->recordId, $this->action);
        }
    } catch (Exception $e) {
        DB::rollBack();
        session()->flash('message', 'Errore: ' . $e->getMessage());
        session()->flash('alert', 'danger');
    }

    $this->cancel();
    $this->js('window.location.reload()');
}
```

---

## File da creare (applicazione — esempio)

### 10. `app/Actions/BooksActionHandler.php`

```php
<?php
namespace App\Actions;

use Illuminate\Support\Str;
use Packages\IctInterface\Contracts\BaseActionHandler;

class BooksActionHandler extends BaseActionHandler
{
    // Esempio: hook before store per aggiungere campo calcolato
    public function beforeStore(string $tableName, array $data, int $formId): ?array
    {
        $data['slug'] = Str::slug($data['title'] ?? '');
        return $data;
    }

    // Esempio: replacement completo dello store con Eloquent
    public function store(string $tableName, array $data, int $formId): ?int
    {
        $book = \App\Models\Book::create($data);
        return $book->id;
    }

    // Esempio: after hook per logging
    public function afterDelete(string $tableName, int $recordId, string $action): void
    {
        \Log::info("Book {$recordId} {$action}d");
    }
}
```

Si registra automaticamente per convention (`books` → `BooksActionHandler`), oppure esplicitamente in `config/ict.php`:

```php
'action_handlers' => [
    'books' => \App\Actions\BooksActionHandler::class,
],
```

---

## Note implementative

- **Transazione:** i `before*` hook girano prima di `DB::beginTransaction()`. I metodi replacement (`store`/`update`/`delete`) girano dentro la transazione del componente. I `after*` hook girano dopo `DB::commit()`. Il handler NON deve fare commit/rollback autonomo.
- **Retrocompatibilità:** form senza handler → zero cambiamenti di comportamento. Il pattern `CustomBooks extends EditableFormComponent` continua a funzionare (il submit() overriddato non raggiunge mai gli hook del package).
- **No migration DB:** il resolver usa `$this->tableName` che è già disponibile su tutti i componenti.
- `app/Livewire/CustomBooks.php` può essere rimosso e sostituito da `app/Actions/BooksActionHandler.php`.

---

## Ordine di implementazione

1. Creare `FormActionHandler` interface
2. Creare `BaseActionHandler` abstract class
3. Creare `ActionHandlerResolver` service
4. Aggiornare `config/ict.php`
5. Registrare singleton in `IctServiceProvider`
6. Modificare `EditableFormComponent::submit()`
7. Modificare `ModalFormComponent::submit()`
8. Modificare `ChildFormComponent::saveItems()`
9. Modificare `DeleteConfirmComponent::execute()`
10. Creare esempio `app/Actions/BooksActionHandler.php`

## Verifica

- Form senza handler (es. menus, reports): devono funzionare esattamente come prima
- Form con handler solo hook (beforeStore): verificare che i dati vengano modificati prima del salvataggio
- Form con handler replacement (store): verificare che il DB write passi per il handler e non per DB::table()
- Delete con handler: verificare beforeDelete (abort) e delete replacement
