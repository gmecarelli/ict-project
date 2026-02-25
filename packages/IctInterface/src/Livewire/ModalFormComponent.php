<?php

/**
 * ModalFormComponent
 *
 * Componente Livewire per i form in modale Bootstrap 5.3.
 * Sostituisce il sistema jQuery AJAX di caricamento form in modali
 * (ModalForms + AjaxController::loadModalForm/saveModalForm + modal-js.blade.php).
 *
 * Uso nel parent component o blade:
 *   @livewire('ict-modal-form', ['reportId' => $reportId])
 *   @livewire('ict-modal-form', ['formId' => $formId])  // mount diretto con formId
 *
 * Apertura modale da un pulsante:
 *   <button wire:click="$dispatch('open-modal-form', { recordId: 123 })">Modifica</button>
 *   <button wire:click="$dispatch('open-child-modal', { recordId: 123 })">Modifica child</button>
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Livewire;

use Exception;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Packages\IctInterface\Services\DynamicFormService;

class ModalFormComponent extends Component
{
    public ?int $reportId = null;
    public ?int $formId = null;
    public ?int $recordId = null;
    public bool $showModal = false;
    public array $formData = [];
    public array $fields = [];
    public ?string $modalTitle = null;
    public ?string $modalWidth = null;
    public ?string $tableName = null;

    protected $listeners = [
        'open-modal-form' => 'openModal',
        'open-child-modal' => 'openModal',
    ];

    public function mount(?int $reportId = null, ?int $formId = null): void
    {
        $formService = app(DynamicFormService::class);
        $modalForm = null;

        if ($formId) {
            // Mount diretto con formId (usato da ChildFormComponent)
            $modalForm = $formService->getFormProperties($formId);
            $this->reportId = $modalForm->report_id ?? 0;
        } elseif ($reportId) {
            // Mount tramite reportId (cerca form type=modal)
            $this->reportId = $reportId;
            $modalForm = $formService->getModalForm($reportId);
        }

        if ($modalForm) {
            $this->formId = $modalForm->id;
            $this->modalTitle = $modalForm->title ?? 'Form';
            $this->modalWidth = $modalForm->modal_width ?? null;
            $this->tableName = $modalForm->table ?? $modalForm->name ?? null;

            // Carica i campi
            $fields = $formService->getFormFields($modalForm->id);
            $this->fields = $fields->toArray();

            // Inizializza formData vuoto
            $this->resetFormData();
        }
    }

    /**
     * Apre la modale, opzionalmente con un recordId per edit
     */
    public function openModal(?int $recordId = null): void
    {
        $this->recordId = $recordId;

        if ($recordId && $this->tableName) {
            // Edit: carica il record dal DB
            $model = DB::table($this->tableName)->where('id', $recordId)->first();
            if ($model) {
                $this->populateFromModel($model);
            }
        } else {
            $this->resetFormData();
        }

        $this->showModal = true;
        $this->dispatch('modal-opened');
    }

    /**
     * Chiude la modale e resetta i dati
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->recordId = null;
        $this->resetFormData();
        $this->resetValidation();
    }

    /**
     * Popola formData dal record DB
     */
    private function populateFromModel(object $model): void
    {
        $modelArray = (array) $model;
        $formService = app(DynamicFormService::class);

        foreach ($this->fields as $field) {
            $name = $field['name'];
            $value = $modelArray[$name] ?? null;

            // Decripta i campi criptati
            if ($formService->isFieldCrypted($name, $this->formId) && !empty($value)) {
                try {
                    $value = Crypt::decryptString($value);
                } catch (Exception $e) {
                    // Lascia il valore originale se la decifratura fallisce
                }
            }
            if ($formService->isFieldMultiselect($name, $this->formId) && is_string($value)) {
                $value = json_decode($value, true) ?? [];
            }

            $this->formData[$name] = $value;
        }
    }

    /**
     * Resetta formData con valori vuoti/default
     */
    private function resetFormData(): void
    {
        $this->formData = [];
        foreach ($this->fields as $field) {
            if ($field['type'] === 'multiselect') {
                $this->formData[$field['name']] = [];
            } else {
                $this->formData[$field['name']] = $field['default_value'] ?? null;
            }
        }
    }

    /**
     * Salva il record (INSERT o UPDATE)
     */
    public function submit(): void
    {
        if (!$this->formId) {
            return;
        }

        $formService = app(DynamicFormService::class);
        $rules = $formService->getValidationRules($this->formId, $this->recordId);

        // Prefissa le regole con formData.
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

        DB::beginTransaction();

        try {
            if ($this->recordId) {
                // UPDATE
                DB::table($this->tableName)
                    ->where('id', $this->recordId)
                    ->update($data);
            } else {
                // INSERT
                $this->recordId = DB::table($this->tableName)->insertGetId($data);
            }

            DB::commit();

            $this->closeModal();
            $this->dispatch('record-saved');
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('modal_error', 'Errore nel salvataggio: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('ict::livewire.modal-form');
    }
}
