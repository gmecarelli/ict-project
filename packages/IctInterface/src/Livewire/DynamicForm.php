<?php

/**
 * DynamicForm
 *
 * Componente Livewire astratto che legge la configurazione dei form dal DB
 * e renderizza un form Bootstrap 5.3.
 * Tutti i tipi di form (filter, search, editable, modal, child) estendono questa classe.
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Livewire;

use Livewire\Component;
use Packages\IctInterface\Services\DynamicFormService;

abstract class DynamicForm extends Component
{
    public int $formId;
    public ?int $recordId = null;
    public array $formData = [];
    public array $fields = [];
    public ?string $formName = null;
    public ?string $formType = null;
    public ?int $childFormId = null;
    public string $submitLabel = 'Salva';

    public function mountForm(int $formId, ?int $recordId = null, ?object $model = null): void
    {
        $this->formId = $formId;
        $this->recordId = $recordId;

        $formService = app(DynamicFormService::class);

        $formProperties = $formService->getFormProperties($formId);
        if ($formProperties) {
            $this->formName = $formProperties->name ?? null;
            $this->formType = $formProperties->type ?? null;
            $this->childFormId = $formProperties->id_child ?? null;
        }

        $this->fields = $formService->getFormFields($formId)->toArray();

        // Inizializza formData con valori di default
        foreach ($this->fields as $field) {
            if ($field['type'] === 'multiselect') {
                $this->formData[$field['name']] = [];
            } else {
                $this->formData[$field['name']] = $field['default_value'] ?? null;
            }
        }

        // Se c'è un model, popola i campi con i valori del record
        if ($model) {
            $this->populateFromModel($model);
        }
    }

    protected function populateFromModel(object $model): void
    {
        foreach ($this->fields as $field) {
            $name = $field['name'];
            if (isset($model->$name)) {
                if ($field['type'] === 'multiselect' && is_string($model->$name)) {
                    $this->formData[$name] = json_decode($model->$name, true) ?? [];
                } elseif($field['type'] === 'crypted') {
                    $this->formData[$name] = _decrypt($model->$name) ?? null; // Non precompilare i campi criptati
                } else {
                    $this->formData[$name] = $model->$name;
                }
            }
        }
    }

    public function getRules(): array
    {
        $formService = app(DynamicFormService::class);
        return $formService->getValidationRules($this->formId, $this->recordId);
    }

    abstract public function submit(): void;

    abstract public function render();
}
