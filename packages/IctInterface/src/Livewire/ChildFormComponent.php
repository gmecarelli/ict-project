<?php

/**
 * ChildFormComponent
 *
 * Componente Livewire per i form child (items/righe figlie).
 * Visualizza i record esistenti usando le colonne del report configurate sul DB.
 * Permette di aggiungere nuovi record, modificarli via modale ed eliminarli.
 *
 * Uso: @livewire('ict-child-form', [
 *     'parentFormId' => $formId,
 *     'parentRecordId' => $recordId,
 *     'childFormId' => $childFormId,
 *     'foreignKey' => 'order_id', // opzionale, viene inferito se non passato
 * ])
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Livewire;

use Exception;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Packages\IctInterface\Services\DynamicFormService;

class ChildFormComponent extends Component
{
    public int $childFormId;
    public ?int $parentRecordId = null;
    public int $parentFormId;
    public string $childTableName = '';
    public string $foreignKey = '';
    public array $items = [];
    public array $childFields = [];
    public array $existingItems = [];
    public ?string $childFormTitle = null;

    // Report columns per la visualizzazione tabella
    public ?int $childReportId = null;
    public array $reportColumns = [];

    protected $listeners = [
        'record-saved' => 'reloadItems',
        'record-deleted' => 'reloadItems',
    ];

    public function mount(int $parentFormId, int $childFormId, ?int $parentRecordId = null, ?string $foreignKey = null): void
    {
        $this->parentFormId = $parentFormId;
        $this->parentRecordId = $parentRecordId;
        $this->childFormId = $childFormId;

        $formService = app(DynamicFormService::class);

        // Carica proprietà del child form
        $childFormProps = $formService->getFormProperties($childFormId);
        if ($childFormProps) {
            $this->childTableName = $childFormProps->table ?? $childFormProps->name ?? '';
            $this->childFormTitle = $childFormProps->title ?? null;
            $this->childReportId = $childFormProps->report_id ?? null;
        }

        // Foreign key: usa il parametro esplicito, oppure inferisci dai campi child
        if ($foreignKey) {
            $this->foreignKey = $foreignKey;
        }

        // Carica i campi del child form (servono per il form di aggiunta nuovi items)
        $fields = $formService->getChildFormFields($childFormId);
        $this->childFields = $fields->toArray();

        // Se la foreignKey non è stata passata, prova a inferirla dai campi hidden *_id
        if (empty($this->foreignKey)) {
            $this->foreignKey = $this->inferForeignKey();
        }

        // Carica le colonne del report per la visualizzazione tabella
        if ($this->childReportId) {
            $cols = $formService->getReportColumns($this->childReportId);
            $this->reportColumns = $cols->map(fn($col) => (array) $col)->toArray();
        }

        // Se in edit mode, carica gli items esistenti dal DB
        if ($parentRecordId && $this->childTableName && $this->foreignKey) {
            $this->loadExistingItems();
        }
    }

    /**
     * Inferisce la foreign key:
     * 1. Cerca tra i campi hidden del child form quelli che terminano con _id
     * 2. Se non trovata, deriva dal nome della tabella parent (reports → report_id)
     */
    private function inferForeignKey(): string
    {
        // 1. Cerca dai campi hidden del child form
        foreach ($this->childFields as $field) {
            if ($field['type'] === 'hidden' && preg_match('/_id$/', $field['name'])) {
                return $field['name'];
            }
        }

        // 2. Deriva dal nome della tabella parent: singular(table_name) + _id
        $formService = app(DynamicFormService::class);
        $parentFormProps = $formService->getFormProperties($this->parentFormId);
        if ($parentFormProps) {
            $parentTable = $parentFormProps->table ?? $parentFormProps->name ?? '';
            if ($parentTable) {
                $fk = Str::singular($parentTable) . '_id';
                if (Schema::hasColumn($this->childTableName, $fk)) {
                    return $fk;
                }
            }
        }

        return '';
    }

    /**
     * Carica gli items esistenti dal DB per il record parent.
     * Seleziona solo i campi definiti nelle report columns + id.
     */
    private function loadExistingItems(): void
    {
        $query = DB::table($this->childTableName)
            ->where($this->foreignKey, $this->parentRecordId);

        if (Schema::hasColumn($this->childTableName, 'is_enabled')) {
            $query->where('is_enabled', 1);
        }

        // Seleziona solo i campi delle report columns + id
        if (!empty($this->reportColumns)) {
            $selectFields = ['id'];
            foreach ($this->reportColumns as $col) {
                if (!in_array($col['field'], $selectFields)) {
                    $selectFields[] = $col['field'];
                }
            }
            $query->select($selectFields);
        }

        if (Schema::hasColumn($this->childTableName, 'position')) {
            $query->orderBy('position');
        } else {
            $query->orderBy('id');
        }

        $records = $query->get();

        $this->existingItems = $records->map(function ($record) {
            return (array) $record;
        })->toArray();
    }

    /**
     * Ricarica la lista degli items esistenti (chiamato da listener eventi)
     */
    public function reloadItems(): void
    {
        if ($this->parentRecordId && $this->childTableName && $this->foreignKey) {
            $this->loadExistingItems();
        }
    }

    /**
     * Aggiunge una riga vuota al form child
     */
    public function addItem(): void
    {
        $newItem = [];
        foreach ($this->childFields as $field) {
            if ($field['name'] === $this->foreignKey) {
                $newItem[$field['name']] = $this->parentRecordId;
            } else {
                $newItem[$field['name']] = $field['default_value'] ?? null;
            }
        }

        // Ensure FK is always set even if not among child form fields
        if ($this->foreignKey && !isset($newItem[$this->foreignKey])) {
            $newItem[$this->foreignKey] = $this->parentRecordId;
        }

        $this->items[] = $newItem;
    }

    /**
     * Rimuove una riga nuova (non ancora salvata)
     */
    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /**
     * Salva tutti i nuovi items
     */
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

        DB::beginTransaction();

        try {
            foreach ($this->items as $item) {
                // Imposta la foreign key
                $item[$this->foreignKey] = $this->parentRecordId;

                // Rimuovi campi guarded
                foreach ($this->childFields as $field) {
                    if (!empty($field['is_guarded'])) {
                        unset($item[$field['name']]);
                    }
                }

                DB::table($this->childTableName)->insert($item);
            }

            DB::commit();

            // Svuota i nuovi items e ricarica quelli esistenti
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

    public function render()
    {
        return view('ict::livewire.child-form');
    }
}
