<?php

/**
 * DynamicFormService
 *
 * Servizio ponte tra il vecchio sistema (FormService/kris-form-builder)
 * e i nuovi componenti Livewire.
 * Legge la configurazione dei form dal DB (form, form_fields) e fornisce
 * dati pronti per essere usati dai componenti Livewire.
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Packages\IctInterface\Models\Form;
use Packages\IctInterface\Models\FormField;

class DynamicFormService
{
    /**
     * Carica le proprietà di un form dal DB
     */
    public function getFormProperties(int $formId): ?object
    {
        $form = Form::find($formId);

        return $form;
    }

    /**
     * Carica i campi di un form dal DB, ordinati per position.
     * Per ogni campo di tipo select/radio popola anche le opzioni.
     */
    public function getFormFields(int $formId): Collection
    {
        $fields = FormField::where('form_id', $formId)
            ->where('is_enabled', 1)
            ->orderBy('position')
            ->get();

        // Per ogni campo select/radio, carica le opzioni
        return $fields->map(function ($field) {
            $fieldArray = $field->toArray();

            $fieldParams = $this->parseTypeAttr($field['attr_params'] ?? '');
            foreach($fieldParams as $key => $value) {
                $fieldArray[$key] = $value;
            }
            if(count($fieldParams) > 0) {
                $fieldArray = Arr::add($fieldArray, 'attr', $fieldParams);
            }

            if (in_array($field->type, ['select', 'radio', 'multiselect']) && !empty($field->type_attr)) {
                $fieldArray['options'] = $this->getSelectOptions($field->type_attr);
            } else {
                $fieldArray['options'] = [];
            }

            return $fieldArray;
        });
    }

    /**
     * Carica il form filtro per un report
     */
    public function getFilterForm(int $reportId): ?object
    {
        return Form::where('report_id', $reportId)
            ->where('type', 'filter')
            ->first();
    }

    /**
     * Carica il form search per un report
     */
    public function getSearchForm(int $reportId): ?object
    {
        return Form::where('report_id', $reportId)
            ->where('type', 'search')
            ->first();
    }

    /**
     * Carica il form editable per un report
     */
    public function getEditableForm(int $reportId): ?object
    {
        return Form::where('report_id', $reportId)
            ->where('type', 'editable')
            ->first();
    }

    /**
     * Carica il form modale per un report
     */
    public function getModalForm(int $reportId): ?object
    {
        return Form::where('report_id', $reportId)
            ->where('type', 'modal')
            ->first();
    }

    /**
     * Genera le opzioni per una select dal type_attr.
     * Supporta il DSL esistente: table:X,code:Y,label:Z,...
     * Supporta anche la sintassi diretta con # : #key1:val1,key2:val2
     */
    public function getSelectOptions(string $typeAttr, mixed $contextValue = null): array
    {
        // Sintassi diretta: #key1:val1,key2:val2
        if (Str::startsWith($typeAttr, '#')) {
            return $this->parseDirectOptions(substr($typeAttr, 1));
        }

        // Sintassi DB: table:X,code:Y,label:Z,...
        $arrAttributes = $this->parseTypeAttr($typeAttr);

        $defaults = [
            'table' => 'options',
            'code' => 'code',
            'label' => 'label',
            'orderBy' => 'id',
            'order' => 'ASC',
        ];

        $arrAttributes = array_merge($defaults, $arrAttributes);

        // Separa le chiavi di configurazione dalle chiavi di filtro (reference)
        $reference = $arrAttributes;
        Arr::forget($reference, ['table', 'code', 'label', 'orderBy', 'order']);

        // Costruisci la query con Query Builder (no SQL concatenation)
        $query = DB::table($arrAttributes['table'])
            ->select(
                "{$arrAttributes['code']} as code",
                "{$arrAttributes['label']} as label"
            );

        // Aggiungi colonne extra per la tabella options
        if ($arrAttributes['table'] === 'options') {
            $query->addSelect('icon', 'class');
        }

        // Applica filtri reference
        if (count($reference) > 0) {
            foreach ($reference as $key => $value) {
                // @variabile = prendi il valore dalla request
                if (preg_match("/^@/", $value)) {
                    $var = substr($value, 1);
                    $value = request()->has($var) ? request()->get($var) : null;
                }

                // &valore = valore fisso
                if (preg_match("/^&/", $value)) {
                    $value = substr($value, 1);
                }

                // # = usa il valore di contesto passato
                if ($value === '#') {
                    $value = $contextValue;
                }

                // EDIT = prendi l'id dal segmento URL in modalità edit
                if ($value === 'EDIT') {
                    if (Str::contains(url()->current(), 'edit')) {
                        $value = request()->segment(2);
                    } else {
                        continue;
                    }
                }

                $query->where($key, $value);
            }
        }

        // Filtro is_enabled se la colonna esiste
        if (Schema::hasColumn($arrAttributes['table'], 'is_enabled')) {
            $query->where('is_enabled', 1);
        }

        $query->orderBy($arrAttributes['orderBy'], $arrAttributes['order']);

        $results = $query->get();

        // Componi l'array choices
        $choices = [];
        $hasExtras = ($arrAttributes['table'] === 'options');

        foreach ($results as $row) {
            $label = $row->label;

            if ($hasExtras) {
                $icon = !is_null($row->icon) ? "<i class=\"{$row->icon}\"></i>" : '';
                if (!is_null($row->class)) {
                    $label = "<span class=\"{$row->class}\">{$row->label} {$icon}</span>";
                } elseif ($icon) {
                    $label = "{$icon} {$row->label}";
                }
            }

            $choices[$row->code] = $label;
        }

        return $choices;
    }

    /**
     * Genera le regole di validazione da un set di campi del form.
     * Sostituisce #id con il recordId per le regole unique.
     */
    public function getValidationRules(int $formId, ?int $recordId = null): array
    {
        $fields = FormField::where('form_id', $formId)
            ->where('is_enabled', 1)
            ->orderBy('position')
            ->get();

        $rules = [];
        foreach ($fields as $field) {
            if (!empty($field->is_guarded)) {
                continue;
            }

            $fieldRules = $field->rules ?: 'nullable';

            if (!is_null($recordId)) {
                $fieldRules = Str::replace('#id', $recordId, $fieldRules);
            } else {
                $fieldRules = Str::replace(',#id', '', $fieldRules);
            }

            $rules[$field->name] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Verifica se un campo è criptato
     */
    public function isFieldCrypted(string $fieldName, int $formId): bool
    {
        $formField = FormField::where('form_id', $formId)
            ->where('name', $fieldName)
            ->first();

        return $formField ? (bool) $formField->is_crypted : false;
    }

    public function isFieldMultiselect(string $fieldName, int $formId): bool
    {
        $formField = FormField::where('form_id', $formId)
            ->where('name', $fieldName)
            ->first();

        return $formField ? ($formField->type === 'multiselect') : false;
    }

    /**
     * Carica le colonne del report associate a un report_id
     */
    public function getReportColumns(int $reportId): Collection
    {
        return DB::table('report_columns')
            ->where('report_id', $reportId)
            ->orderBy('position')
            ->get();
    }

    /**
     * Carica i campi child di un form
     */
    public function getChildFormFields(int $childFormId): Collection
    {
        return $this->getFormFields($childFormId);
    }

    /**
     * Genera le regole di validazione per i campi child (formato items.*.fieldname)
     */
    public function getChildValidationRules(int $childFormId): array
    {
        $fields = FormField::where('form_id', $childFormId)
            ->where('is_enabled', 1)
            ->orderBy('position')
            ->get();

        $rules = [];
        foreach ($fields as $field) {
            if (!empty($field->is_guarded)) {
                continue;
            }
            $rules['items.*.' . $field->name] = $field->rules ?: 'nullable';
        }

        return $rules;
    }

    /**
     * Parsa la stringa type_attr nel formato key:value,key:value
     */
    private function parseTypeAttr(string $str): array
    {
        $parsed = [];
        $elements = explode(",", $str);
        foreach ($elements as $element) {
            $parts = explode(":", $element, 2);
            if (count($parts) === 2) {
                $parsed[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $parsed;
    }

    /**
     * Parsa le opzioni dirette nel formato key1:val1,key2:val2
     */
    private function parseDirectOptions(string $str): array
    {
        return $this->parseTypeAttr($str);
    }
}
