<?php

/**
 * EditableFormComponent
 *
 * Componente Livewire per i form di create/edit.
 * Sostituisce AppFormsBuilder per i form editable.
 * Gestisce: create, edit, pre-filling, validazione, cifratura, upload file.
 *
 * Uso: @livewire('ict-editable-form', ['reportId' => $reportId, 'recordId' => $id])
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Livewire;

use Exception;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Packages\IctInterface\Services\DynamicFormService;

class EditableFormComponent extends DynamicForm
{
    use WithFileUploads;

    public int $reportId;
    public ?string $tableName = null;
    public ?string $redirectUrl = null;
    public bool $hasChild = false;
    public ?int $editableChildFormId = null;
    public string $pageUrl = '';

    // Proprietà per file upload
    public array $fileUploads = [];

    public function mount(int $reportId, ?int $recordId = null, ?string $tableName = null): void
    {
        $this->reportId = $reportId;

        // Salva l'URL della pagina originale durante mount()
        // (mount viene eseguito nella request della pagina, non da /livewire/update)
        $this->pageUrl = url()->current();

        $formService = app(DynamicFormService::class);
        $form = $formService->getEditableForm($reportId);

        if ($form) {
            // Il tableName può essere passato esplicitamente o ricavato dal form DB
            $this->tableName = $tableName ?? ($form->table ?? $form->name ?? null);

            $model = null;

            if ($recordId && $this->tableName) {
                // Edit: carica il record dal DB
                $model = DB::table($this->tableName)->where('id', $recordId)->first();
            }

            $this->mountForm($form->id, $recordId, $model);

            // In edit mode, decripta i campi criptati
            if ($model) {
                $this->decryptFields($formService);
            }

            // Gestione child form
            if ($form->id_child) {
                $this->hasChild = true;
                $this->editableChildFormId = $form->id_child;
            }
        }
    }

    /**
     * Decripta i valori dei campi criptati per la visualizzazione in edit
     */
    private function decryptFields(DynamicFormService $formService): void
    {
        foreach ($this->fields as $field) {
            $name = $field['name'];
            if ($formService->isFieldCrypted($name, $this->formId) && !empty($this->formData[$name])) {
                try {
                    $this->formData[$name] = Crypt::decryptString($this->formData[$name]);
                } catch (Exception $e) {
                    // Se la decifratura fallisce, lascia il valore originale
                }
            }
        }
    }

    public function submit(): void
    {
        $formService = app(DynamicFormService::class);
        $rules = $formService->getValidationRules($this->formId, $this->recordId);

        // Prefissa le regole con formData. (i dati sono in $this->formData)
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

        // Rimuovi campi guarded e file temporanei
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

        // Se ha child form e appena inserito, resta sulla pagina per aggiungere items
        if ($this->hasChild && $wasInsert) {
            return;
        }

        $redirectUrl = $this->redirectUrl
            ?? $this->pageUrl . '?report=' . $this->reportId;

        $this->redirect($redirectUrl);
    }

    public function render()
    {
        return view('ict::livewire.editable-form');
    }
}
