<?php

/**
 * Classe che contiene tutte funzioni di servizio per la gestione dei form.
 *
 * I metodi di rendering FormBuilder (renderField, childRenderField, getForm, etc.)
 * sono stati rimossi con la migrazione a Livewire 3.
 * Il rendering dei form è ora gestito dai componenti Livewire e dal DynamicFormService.
 *
 * Metodi preservati: childSaveForm, loadFormFilters, loadFormProperties,
 * getDataToSave, isCrypted, saveFileAttached, upload, etc.
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Controllers\Services;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
    public $attach_po_code = null;
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
     * saveFileAttached
     * Esegue le fasi del salvataggio di un file allegato al form
     *
     * @deprecated Usa AttachmentService::store() o storeForImport() con EditableFormComponent.
     */
    public function saveFileAttached($model, $id, $prefix = null, $fieldNames = ['attach'])
    {
        foreach ($fieldNames as $fieldName) {
            if (request()->hasFile($fieldName) && $id) {
                $fileName = $this->uploadFileAttached($id, $prefix, $fieldName);
                if ($fileName == false) {
                    $this->log->debug("*UPLOAD FILE FALLITO, NON ESEGUO SCRITTURA SU DB* [{$fileName}]", __FILE__, __LINE__);
                    return false;
                }
                $fileNameToSave = is_null($prefix) ? $fileName : $prefix . '/' . $fileName;
                if (empty($this->saveFileName($model, $id, [$fieldName => $fileNameToSave]))) {
                    return false;
                }
                if (empty($this->saveAttachArchive($id, $prefix, $fileName))) {
                    $this->log->debug("*IMPOSSIBILE ARCHIVIARE IL FILE NEL DB* [{$fileName}]", __FILE__, __LINE__);
                    return false;
                }
                $this->log->debug("*FILE ARCHIVIATO NEL DB* [{$fileName}]", __FILE__, __LINE__);
            }
        }

        return true;
    }

    /**
     * saveMultiAttached
     * Esegue l'upload dei file di un form con nome del tipo array (attach[])
     *
     * @deprecated Usa AttachmentService::store() con AttachmentModalComponent.
     */
    public function saveMultiAttached($model, $id_record, $fileFields = [], $prefix = null, $fk_key = 'activity_id')
    {
        if (!$fileFields) {
            return true;
        }

        foreach ($fileFields as $i => $file) {
            if (is_null($file)) {
                continue;
            }
            $fileName = $file->getClientOriginalName();
            if ($fileName && is_null($prefix)) {
                $prefix = substr($fileName, 0, 3);
            }
            if ($fileName) {
                if (!is_null($this->attach_po_code)) {
                    $oldPoNum = Str::afterLast($fileName, '_');
                    $oldPoNum = Str::before($oldPoNum, ')');
                    $fileName = Str::replace($oldPoNum, $this->attach_po_code, $fileName);
                    $this->attach_po_code = null;
                }

                if ($this->upload($file, $fileName, $prefix) == false) {
                    return false;
                }
                $dataFileToSave = [
                    $fk_key => $id_record,
                    'tag' => $prefix,
                    'attach' => is_null($prefix) ? $fileName : $prefix . '/' . $fileName,
                    'user' => session()->get('loggedUser')->email
                ];
                if (empty($this->saveFileName($model, null, $dataFileToSave))) {
                    return false;
                }
                $this->log->debug("*SALVATAGGIO DEL FILE NEL DB ESEGUITO* [{$fileName}] id[{$id_record}]", __FILE__, __LINE__);

                if (empty($this->saveAttachArchive($id_record, $prefix, $fileName))) {
                    $this->log->debug("*IMPOSSIBILE ARCHIVIARE IL FILE NEL DB* [{$fileName}]", __FILE__, __LINE__);
                    return false;
                }
                $this->log->debug("*FILE ARCHIVIATO NEL DB* [{$fileName}]", __FILE__, __LINE__);
            }
        }
        return true;
    }

    /**
     * saveAttachArchive
     * Salva i file nella tabella di archivio files
     *
     * @deprecated Usa Attachment model con relazione polimorfica.
     */
    public function saveAttachArchive($id_ref, $tag, $fileName, $file = null)
    {
        if (is_null($file)) {
            $date_reference = $type_attach = null;
        } else {
            $date_reference = $file['date_reference'];
            $type_attach = $file['type_attach'];
        }
        $res = DB::table('attachment_archives')
            ->insert([
                'reference_id' => $id_ref,
                'type_attach' => $type_attach,
                'date_reference' => $date_reference,
                'tag' => $tag,
                'attach' => $fileName,
                'user' => session()->get('loggedUser')->email,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__, $res);
        return $res;
    }

    /**
     * uploadFileAttached
     * Esegue l'upload del file
     *
     * @deprecated Usa AttachmentService::store() o storeForImport().
     */
    public function uploadFileAttached($id, $prefix = null, $fieldName = 'attach')
    {
        $file = request()->file($fieldName);
        $fileName = $this->_setFileName($id, $file, $prefix);
        if ($this->upload($file, $fileName, $prefix) == false) {
            return;
        }
        return $fileName;
    }

    /**
     * upload
     * Esegue l'upload del file
     *
     * @deprecated Usa AttachmentService::store() o storeForImport().
     */
    public function upload($file, $fileName, $prefix)
    {
        if (is_null($file) || is_null($fileName)) {
            $this->log->warning("*UPLOAD FILE NULL* [{$fileName}]", __FILE__, __LINE__);
            return false;
        }

        $filePath = "{$prefix}/";

        try {
            $upload = Storage::putFileAs("public/upload/{$filePath}", $file, $fileName);

            if (!$upload) {
                $this->log->debug("*UPLOAD FILE FALLITO!!!* [{$fileName}]", __FILE__, __LINE__);
                return false;
            }
        } catch (\Exception $e) {
            $this->log->error("*UPLOAD FILE FALLITO ECCEZIONE!!!* [{$fileName}] [{$e->getMessage()}]", __FILE__, __LINE__);
            return false;
        }

        return true;
    }

    public function setUploadDir($prefix = null)
    {
        return is_null($prefix) ? config('ict.upload_dir') . '/' : config('ict.upload_dir') . '/' . $prefix;
    }

    /**
     * saveFileName
     * UPDATE: aggiorna il valore del campo del file con il nome_file convenzionale
     *
     * @deprecated Usa AttachmentService per la gestione dei nomi file.
     */
    public function saveFileName($model, $id, $fieldsToSave = [])
    {
        if (is_null($id)) {
            $res = $model->create($fieldsToSave);
        } else {
            $res = $model->where('id', '=', $id)->update($fieldsToSave);
        }

        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__, $res);
        return $res;
    }

    /**
     * _setFileName
     * Rinomina il file come da convenzione
     *
     * @deprecated Usa AttachmentService per la gestione dei nomi file.
     */
    private function _setFileName($id, $file, $prefix = null)
    {
        $fileName = $prefix . "_" . $id . "_" . date("YmdHis") . "." . $file->extension();
        $this->log->debug("*FILE RINOMINATO* [{$fileName}]", __FILE__, __LINE__);
        return $fileName;
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
