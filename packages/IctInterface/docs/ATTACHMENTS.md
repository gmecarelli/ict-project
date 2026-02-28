# Sistema Attachments — Analisi e Redesign

## 1. Analisi dello stato attuale

### Problema

Il sistema attachment attuale e' **inutile, inutilizzato e inutilizzabile** nella sua forma corrente. Ecco perche':

### Codice esistente

**`AttachmentController.php`** — Unica operazione: `delete()`. Elimina un record `Attachment` + `AttachmentArchive` + file fisico in `public/storage/upload/`. Non ha metodi di creazione, lettura o listing.

**`Attachment.php`** — Model vuoto, estende `IctModel` senza alcuna configurazione (nessuna `$table`, nessuna relazione, nessun `$fillable`).

**`AttachmentArchive.php`** — Model vuoto con solo `$guarded`.

**`FormService`** — Contiene 6 metodi legacy per gestione file:
- `saveFileAttached()` — salva file singolo + registra in DB + archivia
- `saveMultiAttached()` — salva file multipli con nome tipo array
- `saveAttachArchive()` — inserisce in `attachment_archives`
- `uploadFileAttached()` / `upload()` — upload fisico via `Storage::putFileAs()`
- `saveFileName()` — UPDATE/INSERT su model generico

Questi metodi:
- Usano `request()` direttamente (incompatibili con Livewire)
- Dipendono da strutture tabella hardcoded (`attachment_archives` con colonne `reference_id`, `type_attach`, `date_reference`, `tag`, `attach`, `user`)
- Non hanno migration per le tabelle `attachments` e `attachment_archives`
- Non sono invocati da nessun componente Livewire attuale

**`EditableFormComponent.php`** — Ha una gestione file minimale e scollegata:
```php
foreach ($this->fileUploads as $fieldName => $file) {
    $fileName = time() . '_' . $file->getClientOriginalName();
    $file->storeAs($uploadDir, $fileName, 'public');
    $data[$fieldName] = $fileName;
}
```
Salva il nome file nella colonna della tabella target, ma:
- NON registra nulla in una tabella attachments
- NON conserva il nome originale del file
- NON gestisce eliminazione file fisico
- NON supporta file multipli per record

### Tabelle mancanti

Non esistono migration per `attachments` ne' per `attachment_archives`. I model esistono ma non hanno tabelle.

### Conclusione

Il sistema attuale e' un misto di codice legacy (FormService) e codice Livewire incompleto (EditableFormComponent), senza una tabella attachments funzionante e senza integrazione tra i due. Serve un redesign completo.

---

## 2. Requisiti del nuovo sistema

I campi `file` nei form devono supportare **due modalita'**:

### Caso A — Allegato (attachment)

Il file viene salvato su filesystem e registrato in una tabella dedicata.

1. Upload file su `storage/app/public/uploads/` (directory di default configurabile)
1.1 avere la possibilità di passare come parametro il nome di una cartella dove salvare i file (path tipo: storage/app/public/uploads/[nome_cartella_passata_come_parametro])
2. Registrazione su tabella `attachments` con metadati (nome originale, path, estensione, ecc.)
3. Gli altri campi del form vengono salvati normalmente sulla tabella di destinazione
4. Eliminazione file = eliminazione record DB + file su filesystem
5. Deve essere possibile aprire un form modale con un campo file per aggiungere allegati a un record esistente

### Caso B — Importazione dati

Il file viene salvato su filesystem come supporto per un'elaborazione custom.

1. Upload file su filesystem (stessa directory)
2. Gli altri campi del form (incluso il nome del file) vengono salvati sulla tabella di riferimento
3. L'applicazione deve poter definire una funzione custom per elaborare il file dopo il salvataggio (es. parsare un CSV/excel e inserire righe in tabella con maatwebsite)

### Requisito trasversale — Estensibilita'

Tutte le azioni intrinseche al package devono poter essere **personalizzate/sovrascritte** a livello di applicazione senza toccare la logica del package. Questo si integra con il sistema `ActionHandler` gia' progettato in `CUSTOMIZATION.md`.

---

## 3. Design della soluzione

### 3.1 Nuova tabella `attachments`

```
Migration: create_attachments_table
```

```php
Schema::create('attachments', function (Blueprint $table) {
    $table->id();
    $table->string('file_name_server');        // nome file su disco (rinominato)
    $table->string('file_name_original');      // nome file originale dell'utente
    $table->string('description')->nullable(); // descrizione opzionale
    $table->string('path');                    // path relativo (es. "uploads/books/")
    $table->string('ext', 20);                // estensione (pdf, jpg, xlsx, ecc.)
    $table->morphs('attachable');              // attachable_type + attachable_id (relazione polimorfica)
    $table->timestamps();
});
```

**Nota sulla relazione polimorfica:** `attachable_type` e `attachable_id` permettono di collegare un allegato a qualsiasi entita' (books, orders, users, ecc.) senza FK hardcoded. Questo e' il pattern standard Laravel per allegati generici.

### 3.2 Model `Attachment` aggiornato

```php
// packages/IctInterface/src/Models/Attachment.php
namespace Packages\IctInterface\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'file_name_server',
        'file_name_original',
        'description',
        'path',
        'ext',
        'attachable_type',
        'attachable_id',
    ];

    /**
     * Relazione polimorfica inversa
     */
    public function attachable()
    {
        return $this->morphTo();
    }

    /**
     * Path completo per accesso pubblico
     */
    public function getFullPathAttribute(): string
    {
        return $this->path . '/' . $this->file_name_server;
    }

    /**
     * URL pubblica per download
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->full_path);
    }
}
```

### 3.3 Trait `HasAttachments` per i Model applicativi

```php
// packages/IctInterface/src/Traits/HasAttachments.php
namespace Packages\IctInterface\Traits;

use Packages\IctInterface\Models\Attachment;

trait HasAttachments
{
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
```

Uso nell'applicazione:

```php
// app/Models/Book.php
class Book extends IctModel
{
    use \Packages\IctInterface\Traits\HasAttachments;
}
```

### 3.4 Servizio `AttachmentService`

```php
// packages/IctInterface/src/Services/AttachmentService.php
namespace Packages\IctInterface\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Packages\IctInterface\Models\Attachment;

class AttachmentService
{
    /**
     * Upload e registrazione allegato (Caso A)
     *
     * @param UploadedFile $file         File caricato
     * @param string       $attachableType  Classe del model (es. 'App\Models\Book')
     * @param int          $attachableId    ID del record padre
     * @param string|null  $description     Descrizione opzionale
     * @param string|null  $subDir          Sottocartella opzionale (es. 'books')
     * @return Attachment
     */
    public function store(
        UploadedFile $file,
        string $attachableType,
        int $attachableId,
        ?string $description = null,
        ?string $subDir = null
    ): Attachment {
        $baseDir = config('ict.upload_dir', 'uploads');
        $path = $subDir ? "{$baseDir}/{$subDir}" : $baseDir;

        $originalName = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();
        $serverName = $this->generateServerName($attachableId, $ext);

        // Upload fisico
        $file->storeAs("public/{$path}", $serverName);

        // Registrazione DB
        return Attachment::create([
            'file_name_server'  => $serverName,
            'file_name_original' => $originalName,
            'description'       => $description,
            'path'              => $path,
            'ext'               => $ext,
            'attachable_type'   => $attachableType,
            'attachable_id'     => $attachableId,
        ]);
    }

    /**
     * Upload senza registrazione su attachments (Caso B — importazione)
     *
     * Salva il file su filesystem e restituisce i metadati.
     * L'applicazione puo' poi elaborare il file come vuole.
     *
     * @return array{server_name: string, original_name: string, path: string, full_path: string, ext: string}
     */
    public function storeForImport(
        UploadedFile $file,
        ?string $subDir = null
    ): array {
        $baseDir = config('ict.upload_dir', 'uploads');
        $path = $subDir ? "{$baseDir}/{$subDir}" : $baseDir;

        $originalName = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();
        $serverName = time() . '_' . $originalName;

        $file->storeAs("public/{$path}", $serverName);

        return [
            'server_name'   => $serverName,
            'original_name' => $originalName,
            'path'          => $path,
            'full_path'     => "{$path}/{$serverName}",
            'ext'           => $ext,
        ];
    }

    /**
     * Elimina allegato: record DB + file fisico
     */
    public function delete(Attachment $attachment): bool
    {
        $filePath = "public/{$attachment->full_path}";

        // Elimina file fisico
        if (Storage::exists($filePath)) {
            Storage::delete($filePath);
        }

        // Elimina record DB
        return $attachment->delete();
    }

    /**
     * Elimina tutti gli allegati di un'entita'
     */
    public function deleteAllFor(string $attachableType, int $attachableId): int
    {
        $attachments = Attachment::where('attachable_type', $attachableType)
            ->where('attachable_id', $attachableId)
            ->get();

        $count = 0;
        foreach ($attachments as $attachment) {
            if ($this->delete($attachment)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Genera nome file su disco: {id}_{timestamp}.{ext}
     */
    protected function generateServerName(int $id, string $ext): string
    {
        return "{$id}_" . date('YmdHis') . ".{$ext}";
    }
}
```

### 3.5 Contratto `FileFieldHandler` (estensibilita' Caso B)

Per permettere all'applicazione di definire logica custom post-upload (importazione dati):

```php
// packages/IctInterface/src/Contracts/FileFieldHandler.php
namespace Packages\IctInterface\Contracts;

interface FileFieldHandler
{
    /**
     * Viene invocato dopo il salvataggio del file su filesystem.
     *
     * @param string $fullPath    Path completo del file salvato (relativo a storage/app)
     * @param array  $formData    Tutti i dati del form (inclusi altri campi)
     * @param int    $recordId    ID del record appena salvato/aggiornato
     * @param string $tableName   Tabella di destinazione
     * @param string $fieldName   Nome del campo file nel form
     * @return void
     */
    public function handle(
        string $fullPath,
        array $formData,
        int $recordId,
        string $tableName,
        string $fieldName
    ): void;
}
```

Esempio di implementazione a livello applicazione:

```php
// app/Actions/ImportCsvHandler.php
namespace App\Actions;

use Packages\IctInterface\Contracts\FileFieldHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportCsvHandler implements FileFieldHandler
{
    public function handle(
        string $fullPath,
        array $formData,
        int $recordId,
        string $tableName,
        string $fieldName
    ): void {
        $content = Storage::get($fullPath);
        $rows = array_map('str_getcsv', explode("\n", $content));
        $headers = array_shift($rows);

        foreach ($rows as $row) {
            if (count($row) === count($headers)) {
                DB::table('imported_data')->insert(
                    array_combine($headers, $row)
                );
            }
        }
    }
}
```

### 3.6 Risoluzione del FileFieldHandler

La risoluzione segue lo stesso pattern dell'`ActionHandlerResolver`:

**In `config/ict.php`:**

```php
'file_handlers' => [
    // 'nome_campo_file' => HandlerClass   (per campo specifico)
    // 'tabella.nome_campo' => HandlerClass (per tabella+campo specifico)
],
```

**In `AttachmentService`:**

```php
/**
 * Risolve il handler per un campo file.
 * Cerca: config esplicita → convention App\Actions\{Studly(table)}{Studly(field)}Handler
 */
public function resolveFileHandler(string $tableName, string $fieldName): ?FileFieldHandler
{
    $handlers = config('ict.file_handlers', []);

    // 1. Mapping esplicito tabella.campo
    if (isset($handlers["{$tableName}.{$fieldName}"])) {
        return app($handlers["{$tableName}.{$fieldName}"]);
    }

    // 2. Mapping esplicito solo campo
    if (isset($handlers[$fieldName])) {
        return app($handlers[$fieldName]);
    }

    // 3. Convention: App\Actions\{Table}{Field}Handler
    $class = 'App\\Actions\\' . Str::studly($tableName) . Str::studly($fieldName) . 'Handler';
    if (class_exists($class)) {
        return app($class);
    }

    return null;
}
```

### 3.7 Distinguere Caso A da Caso B nei form_fields

Il campo `type_attr` del `form_field` determina il comportamento:

| `type` | `type_attr` | Comportamento |
|---|---|---|
| `file` | `mode:attachment` (o null/vuoto) | **Caso A** — allegato: salva file + registra in `attachments` |
| `file` | `mode:import` | **Caso B** — importazione: salva file, salva path nella tabella target, invoca `FileFieldHandler` se esiste |
| `file` | `mode:import,handler:App\Actions\MioHandler` | **Caso B** con handler esplicito |

### 3.8 Modifica `EditableFormComponent::submit()`

La sezione upload file attuale:

```php
// ATTUALE (da sostituire)
foreach ($this->fileUploads as $fieldName => $file) {
    if ($file) {
        $uploadDir = config('ict.upload_dir', 'upload');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->storeAs($uploadDir, $fileName, 'public');
        $data[$fieldName] = $fileName;
    }
}
```

Diventa:

```php
// NUOVO
$attachmentService = app(AttachmentService::class);
$pendingAttachments = []; // allegati da registrare dopo l'insert (serve l'ID)
$pendingImports = [];     // file da processare dopo il salvataggio

foreach ($this->fileUploads as $fieldName => $file) {
    if (!$file) continue;

    $fieldConfig = collect($this->fields)->firstWhere('name', $fieldName);
    $mode = $this->getFileMode($fieldConfig);

    if ($mode === 'attachment') {
        // Caso A: salva file ora, registra in attachments dopo (serve recordId)
        $uploadResult = $attachmentService->storeForImport($file, $this->tableName);
        $pendingAttachments[] = [
            'file'       => $uploadResult,
            'fieldName'  => $fieldName,
            'description' => $data['description'] ?? null,
        ];
        // NON mettere il file nella colonna della tabella target
        unset($data[$fieldName]);
    } else {
        // Caso B: salva file su filesystem, metti il path nella colonna
        $uploadResult = $attachmentService->storeForImport($file, $this->tableName);
        $data[$fieldName] = $uploadResult['server_name'];
        $pendingImports[] = [
            'fullPath'  => "public/{$uploadResult['full_path']}",
            'fieldName' => $fieldName,
            'fieldConfig' => $fieldConfig,
        ];
    }
}
```

Dopo il commit DB (dove otteniamo `$this->recordId`):

```php
// Registra allegati in tabella attachments (Caso A)
foreach ($pendingAttachments as $pa) {
    Attachment::create([
        'file_name_server'   => $pa['file']['server_name'],
        'file_name_original' => $pa['file']['original_name'],
        'description'        => $pa['description'],
        'path'               => $pa['file']['path'],
        'ext'                => $pa['file']['ext'],
        'attachable_type'    => $this->getAttachableType(),
        'attachable_id'      => $this->recordId,
    ]);
}

// Invoca FileFieldHandler per import (Caso B)
foreach ($pendingImports as $pi) {
    $handler = $attachmentService->resolveFileHandler($this->tableName, $pi['fieldName']);
    if ($handler) {
        $handler->handle(
            $pi['fullPath'],
            $data,
            $this->recordId,
            $this->tableName,
            $pi['fieldName']
        );
    }
}
```

Helper privato:

```php
private function getFileMode(?array $fieldConfig): string
{
    if (!$fieldConfig) return 'attachment';
    $attrs = $fieldConfig['type_attr'] ?? '';
    $parsed = _parser($attrs);
    return $parsed['mode'] ?? 'attachment';
}

private function getAttachableType(): string
{
    // Convention: App\Models\{StudlyCase(tableName)}
    // Configurabile via config('ict.model_map')
    $map = config('ict.model_map', []);
    if (isset($map[$this->tableName])) {
        return $map[$this->tableName];
    }
    return 'App\\Models\\' . \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($this->tableName));
}
```

### 3.9 Componente Livewire `ict-attachment-modal`

Form modale per aggiungere allegati a un record esistente (requisito caso A punto 4).

```php
// packages/IctInterface/src/Livewire/AttachmentModalComponent.php
namespace Packages\IctInterface\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Packages\IctInterface\Models\Attachment;
use Packages\IctInterface\Services\AttachmentService;

class AttachmentModalComponent extends Component
{
    use WithFileUploads;

    public bool $showModal = false;
    public ?string $attachableType = null;
    public ?int $attachableId = null;
    public $file = null;
    public string $description = '';
    public array $attachments = [];

    protected $listeners = ['open-attachment-modal' => 'openModal'];

    public function openModal(string $attachableType, int $attachableId): void
    {
        $this->attachableType = $attachableType;
        $this->attachableId = $attachableId;
        $this->showModal = true;
        $this->loadAttachments();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['file', 'description']);
        $this->resetValidation();
    }

    public function loadAttachments(): void
    {
        $this->attachments = Attachment::where('attachable_type', $this->attachableType)
            ->where('attachable_id', $this->attachableId)
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function upload(): void
    {
        $this->validate([
            'file' => 'required|file|max:10240', // 10MB default
        ]);

        $service = app(AttachmentService::class);
        $service->store(
            $this->file,
            $this->attachableType,
            $this->attachableId,
            $this->description ?: null,
            // subDir derivata dal type
            \Illuminate\Support\Str::snake(class_basename($this->attachableType))
        );

        $this->reset(['file', 'description']);
        $this->loadAttachments();
        $this->dispatch('attachment-saved');

        session()->flash('attach_message', 'Allegato caricato con successo');
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $attachment = Attachment::findOrFail($attachmentId);
        $service = app(AttachmentService::class);
        $service->delete($attachment);

        $this->loadAttachments();
        $this->dispatch('attachment-deleted');

        session()->flash('attach_message', 'Allegato eliminato');
    }

    public function render()
    {
        return view('ict::livewire.attachment-modal');
    }
}
```

Registrazione in `IctServiceProvider::boot()`:

```php
Livewire::component('ict-attachment-modal', AttachmentModalComponent::class);
```

### 3.10 Vista `attachment-modal.blade.php`

```blade
{{-- packages/IctInterface/src/resources/views/livewire/attachment-modal.blade.php --}}
<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gestione Allegati</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    @if(session('attach_message'))
                        <div class="alert alert-success alert-sm">{{ session('attach_message') }}</div>
                    @endif

                    {{-- Form upload --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="file" wire:model="file" class="form-control">
                            @error('file') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-4">
                            <input type="text" wire:model="description" class="form-control"
                                   placeholder="Descrizione (opzionale)">
                        </div>
                        <div class="col-md-2">
                            <button wire:click="upload" class="btn btn-primary w-100"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="upload">Carica</span>
                                <span wire:loading wire:target="upload">...</span>
                            </button>
                        </div>
                    </div>

                    {{-- Lista allegati --}}
                    @if(count($attachments) > 0)
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Nome file</th>
                                <th>Descrizione</th>
                                <th>Ext</th>
                                <th>Data</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attachments as $att)
                            <tr>
                                <td>
                                    <a href="{{ asset('storage/' . $att['path'] . '/' . $att['file_name_server']) }}"
                                       target="_blank">
                                        {{ $att['file_name_original'] }}
                                    </a>
                                </td>
                                <td>{{ $att['description'] ?? '-' }}</td>
                                <td>{{ $att['ext'] }}</td>
                                <td>{{ \Carbon\Carbon::parse($att['created_at'])->format('d/m/Y H:i') }}</td>
                                <td>
                                    <button wire:click="deleteAttachment({{ $att['id'] }})"
                                            wire:confirm="Eliminare questo allegato?"
                                            class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-muted">Nessun allegato presente.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
```

Invocazione dalla vista report o form:

```blade
@livewire('ict-attachment-modal')

{{-- Apertura da un pulsante --}}
<button onclick="Livewire.dispatch('open-attachment-modal', {
    attachableType: 'App\\Models\\Book',
    attachableId: {{ $record->id }}
})">
    <i class="fas fa-paperclip"></i> Allegati
</button>
```

---

## 4. Registrazioni nel Service Provider

Aggiunte necessarie a `IctServiceProvider`:

```php
// register()
$this->app->singleton(
    \Packages\IctInterface\Services\AttachmentService::class
);

// boot()
Livewire::component('ict-attachment-modal', AttachmentModalComponent::class);
```

---

## 5. Configurazione

Aggiunte a `config/ict.php`:

```php
// Directory base upload (gia' esistente, default cambiato)
'upload_dir' => env('UPLOAD_DIR', 'uploads'),

// Mapping tabella → model class (opzionale, per relazione polimorfica)
'model_map' => [],

// Handler per file di importazione (Caso B)
'file_handlers' => [],

// Dimensione massima upload in KB (default 10MB)
'upload_max_size' => env('UPLOAD_MAX_SIZE', 10240),
```

---

## 6. Estensibilita' a livello applicazione

### Sovrascrivere il servizio `AttachmentService`

```php
// app/Providers/AppServiceProvider.php
$this->app->singleton(
    \Packages\IctInterface\Services\AttachmentService::class,
    \App\Services\CustomAttachmentService::class
);
```

### Sovrascrivere il componente modale

```php
// app/Providers/AppServiceProvider.php boot()
Livewire::component('ict-attachment-modal', \App\Livewire\CustomAttachmentModal::class);
```

### Aggiungere handler per elaborazione file (Caso B)

**Per convention:**

```
tabella: imports
campo file: csv_file
→ App\Actions\ImportsCsvFileHandler (implements FileFieldHandler)
```

**Per config esplicita:**

```php
// config/ict.php
'file_handlers' => [
    'imports.csv_file' => \App\Actions\MyCsvImporter::class,
],
```

### Integrazione con ActionHandler (CUSTOMIZATION.md)

Il sistema attachment si integra con gli `ActionHandler` gia' progettati:

- `beforeStore()` puo' modificare i dati del form (incluso rimuovere/aggiungere campi file)
- `afterStore()` puo' essere usato per aggiungere allegati programmaticamente dopo il salvataggio
- `afterDelete()` puo' essere usato per pulire allegati orfani

Esempio:

```php
class BooksActionHandler extends BaseActionHandler
{
    public function afterStore(string $tableName, array $data, int $newRecordId, int $formId): void
    {
        // Dopo aver creato il libro, genera un PDF copertina e lo allega
        $pdfPath = $this->generateCoverPdf($data);
        $service = app(AttachmentService::class);
        $service->store(
            new \Illuminate\Http\UploadedFile($pdfPath, 'copertina.pdf'),
            'App\Models\Book',
            $newRecordId,
            'Copertina generata automaticamente'
        );
    }

    public function afterDelete(string $tableName, int $recordId, string $action): void
    {
        // Elimina tutti gli allegati quando il libro viene cancellato
        if ($action === 'delete') {
            $service = app(AttachmentService::class);
            $service->deleteAllFor('App\Models\Book', $recordId);
        }
    }
}
```

---

## 7. Pulizia codice legacy

### Da rimuovere

- `Models/AttachmentArchive.php` — sostituito dalla nuova tabella `attachments`
- `AttachmentController::delete()` — sostituito da `AttachmentService::delete()` e `AttachmentModalComponent::deleteAttachment()`
- Rotta `GET /deleteattach`

### Da deprecare

In `FormService`:
- `saveFileAttached()` — deprecato, usare `AttachmentService::store()`
- `saveMultiAttached()` — deprecato, usare `AttachmentService::store()` in loop
- `saveAttachArchive()` — deprecato, non serve piu'
- `uploadFileAttached()` — deprecato, usare `AttachmentService::storeForImport()`
- `upload()` — deprecato, logica spostata in `AttachmentService`
- `saveFileName()` — deprecato
- `_setFileName()` — deprecato

Questi metodi vanno marcati `@deprecated` ma mantenuti per eventuali applicazioni legacy che li usano direttamente.

---

## 8. Migration di dati esistenti

Se esistono applicazioni con allegati nella vecchia struttura `attachment_archives`, prevedere una migration per convertire i dati:

```php
// Esempio di migration di conversione (opzionale, a livello applicazione)
$oldRecords = DB::table('attachment_archives')->get();
foreach ($oldRecords as $old) {
    Attachment::create([
        'file_name_server'   => basename($old->attach),
        'file_name_original' => basename($old->attach),
        'description'        => $old->tag,
        'path'               => dirname($old->attach) ?: config('ict.upload_dir'),
        'ext'                => pathinfo($old->attach, PATHINFO_EXTENSION),
        'attachable_type'    => 'legacy', // da mappare manualmente
        'attachable_id'      => $old->reference_id,
        'created_at'         => $old->created_at,
        'updated_at'         => $old->updated_at,
    ]);
}
```

---

## 9. Riepilogo file da creare/modificare

### File da CREARE

| File | Descrizione |
|---|---|
| `database/migrations/xxxx_create_attachments_table.php` | Nuova tabella `attachments` |
| `src/Services/AttachmentService.php` | Servizio upload/registrazione/eliminazione |
| `src/Contracts/FileFieldHandler.php` | Interface per handler importazione (Caso B) |
| `src/Traits/HasAttachments.php` | Trait relazione polimorfica per model app |
| `src/Livewire/AttachmentModalComponent.php` | Componente modale gestione allegati |
| `src/resources/views/livewire/attachment-modal.blade.php` | Vista modale allegati |

### File da MODIFICARE

| File | Modifica |
|---|---|
| `src/Models/Attachment.php` | Aggiungere `$fillable`, relazione `morphTo`, accessors |
| `src/Livewire/EditableFormComponent.php` | Riscrivere sezione upload con Caso A/B |
| `src/Providers/IctServiceProvider.php` | Registrare `AttachmentService` singleton + componente Livewire |
| `config/ict.php` | Aggiungere chiavi `model_map`, `file_handlers`, `upload_max_size` |

### File da DEPRECARE (non rimuovere)

| File | Note |
|---|---|
| `src/Models/AttachmentArchive.php` | `@deprecated` — mantenere per retrocompatibilita' |
| `src/Controllers/AttachmentController.php` | `@deprecated` — sostituito da AttachmentService |
| Metodi upload in `FormService` | `@deprecated` su 6 metodi |

---

## 10. Ordine di implementazione

1. Creare migration `attachments`
2. Aggiornare `Attachment.php` model
3. Creare `AttachmentService`
4. Creare `FileFieldHandler` interface
5. Creare `HasAttachments` trait
6. Registrare in `IctServiceProvider` (singleton + config)
7. Aggiornare `config/ict.php`
8. Modificare `EditableFormComponent::submit()` (Caso A + Caso B)
9. Creare `AttachmentModalComponent` + vista
10. Registrare componente Livewire in `IctServiceProvider`
11. Deprecare codice legacy (FormService, AttachmentController, AttachmentArchive)
12. Test: form con campo file `mode:attachment`
13. Test: form con campo file `mode:import` + handler custom
14. Test: modale allegati (upload, listing, eliminazione)
