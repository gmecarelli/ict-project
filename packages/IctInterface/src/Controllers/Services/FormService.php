<?php

/**
 * Classe che contiene tutte funzioni di servizio per la gestione dei form.
 *
 * I metodi di rendering FormBuilder (renderField, childRenderField, getForm, etc.)
 * sono stati rimossi con la migrazione a Livewire 3.
 * Il rendering dei form è ora gestito dai componenti Livewire e dal DynamicFormService.
 *
 * Metodi preservati: childSaveForm, loadFormFilters, loadFormProperties,
 * getDataToSave, isCrypted.
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Controllers\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Controllers\Services\Logger;
use Packages\IctInterface\Controllers\Services\ApplicationService;
use Packages\IctInterface\Models\Form as ModelsForm;
use Packages\IctInterface\Models\FormField;

class FormService extends ApplicationService
{
    public $form_properties;
    public $clearfix;
    public $formUrl;
    public $childFormId = null;
    public $actionResponse = [];
    public $childArrFieldName = 'items';

    public function setChildArrFieldName($val)
    {
        $this->childArrFieldName = $val;
    }

    public function getChildArrFieldName()
    {
        return $this->childArrFieldName;
    }

    /**
     * checkPeriod
     * Controlla che un periodo (mese/anno) non sia superiore al periodo di riferimento
     */
    public function checkPeriod($period, $periodRef)
    {
        return parent::checkPeriod($period, $periodRef);
    }

    /**
     * childSaveForm
     * Salva i dati di un form Child. Il salvataggio dei child è sempre una INSERT
     */
    public function childSaveForm($Model, $referenceId, $data, $fieldReference = 'report_id')
    {
        if (!$data['items']) {
            return null;
        }
        foreach ($data['items'] as $key => $item) {
            $data['items'][$key][$fieldReference] = $referenceId;
            $items = Arr::add($data['items'], $key . '.' . $fieldReference, $referenceId);
        }

        foreach ($items as $i => $item) {
            $resultModel = $Model->create($item);
            $id_item = $resultModel->id;
            $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);
            $this->log->info("*INSERT ID ITEM* [{$id_item}]", __FILE__, __LINE__);
            $res = $id_item ? $id_item : null;
            if (is_null($res)) {
                return null;
            }
            $items[$i]['id'] = $id_item;
        }
        return $items;
    }

    /**
     * loadModalFormProperties
     * Carica le proprietà di un form da visualizzare in una modale customizzata
     */
    public function loadModalFormProperties($report_id, $type = 'modal')
    {
        $modalForm = DB::table('forms')
            ->where('report_id', $report_id)
            ->where('type', $type)
            ->get()
            ->toArray();

        if (is_null($modalForm)) {
            return null;
        }

        return Arr::get($modalForm, 0);
    }

    /**
     * loadFormFilters
     * Carica i campi per creare il form dei filtri
     */
    public function loadFormFilters($id_report, $type = 'filter')
    {
        $formFilter = DB::table('forms')
            ->where('report_id', '=', $id_report)
            ->where('type', '=', $type)
            ->get();
        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);
        if (count($formFilter) == 0) {
            return;
        }

        return $formFilter->get(0);
    }

    /**
     * loadFormByType
     * Carica un form dal report e tipo di form.
     */
    public function loadFormByType($id_report, $type)
    {
        return $this->loadFormFilters($id_report, $type);
    }

    /**
     * loadFormFields
     * Restituisce un array con le proprietà di tutti i campi del form
     */
    public function loadFormFields($form_id, $fieldName = 'form_id')
    {
        return $this->loadFormFieldsData($form_id, $fieldName);
    }

    /**
     * getForm_properties
     * Restituisce le proprietà (lette dal db) del form
     */
    public function getForm_properties()
    {
        return $this->form_properties;
    }

    /**
     * loadFormProperties
     * Carica tutte le proprietà del form
     */
    public function loadFormProperties($form_id)
    {
        $this->log->info("*Carico dati form* id[$form_id]", __FILE__, __LINE__);
        $arr = DB::table('forms')
            ->where('id', '=', $form_id)
            ->get()
            ->toArray();

        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);
        if (!$arr || is_null($form_id)) {
            $this->log->debug("*NUM ROWS* [null] form_id[$form_id]", __FILE__, __LINE__);
            return null;
        }

        $this->childFormId = $arr[0]->id_child;
        $this->log->debug("*NUM ROWS* [" . count($arr) . "] child_form_id [{$this->childFormId}]", __FILE__, __LINE__);

        return Arr::get($arr, 0);
    }

    /**
     * cancelRecord
     * Esegue la cancellazione del record (disabilitazione is_enabled = 0)
     */
    public function cancelRecord($params)
    {
        foreach ($params as $table => $where) {
            $res = DB::table($table)
                ->where($where)
                ->update(['is_enabled' => 0]);
            $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__, $res);
        }
        return true;
    }

    /**
     * getDataToSave
     * Restituisce un array di regole di validazione per un form
     */
    public function getDataToSave($formId, $id = null)
    {
        $rules = [];
        $fields = $this->loadFormFields($formId);

        foreach ($fields as $field) {
            if (!empty($field->is_guarded)) {
                continue;
            }

            if (!is_null($id)) {
                $field->rules = Str::replace('#id', $id, $field->rules);
            } else {
                $field->rules = Str::replace(',#id', '', $field->rules);
            }
            $rules[$field->name] = $field->rules ?: 'nullable';
        }

        if ($this->childFormId) {
            $rulesChild = $this->childGetDataToSave($this->childFormId);
            $rules = array_merge($rules, $rulesChild);
        }

        return $rules;
    }

    /**
     * childGetDataToSave
     * Restituisce un array di regole di validazione per i campi child
     */
    public function childGetDataToSave($formId)
    {
        $fields = $this->loadFormFields($formId);
        $rules = [];
        foreach ($fields as $field) {
            if (!empty($field->is_guarded)) {
                continue;
            }
            $rules['items.*.' . $field->name] = $field->rules ?: 'nullable';
        }
        $this->log->info("*RULES* " . print_r($rules, true), __FILE__, __LINE__);
        return $rules;
    }

    /**
     * isCrypted
     * Verifica se un campo è criptato
     */
    public function isCrypted($fieldName, $form_id)
    {
        $formField = FormField::where('form_id', $form_id)->where('name', $fieldName)->first();
        return $formField->is_crypted;
    }
}
