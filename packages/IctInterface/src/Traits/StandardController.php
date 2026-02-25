<?php

/**
 * TRAIT DOVE SONO DEFINITE LE FUNZIONI STANDARD DEI CONTROLLERS DEI REPORT DELL'APPLICAZIONE
 * QUESTO DEVE ESSERE IMPORTATO NELLA CLASSE CONTROLLER
 *
 * @deprecated Sostituito da Packages\IctInterface\Traits\LivewireController
 * @see \Packages\IctInterface\Traits\LivewireController
 */

namespace Packages\IctInterface\Traits;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Packages\IctInterface\Models\MulticheckAction;
use Packages\IctInterface\Controllers\Services\Logger;
use Packages\IctInterface\Controllers\Services\FormService;
use Packages\IctInterface\Controllers\Services\ReportService;
use Packages\IctInterface\Controllers\Services\MulticheckController;

trait StandardController
{
    protected $reportData;
    protected $report;
    protected $form;
    public $log;
    protected $_formId; //id del form di riferimento per questo controller
    public $model;
    public $modelChild;
    public $insert_id;
    public $foreignKey; //nome della chiave esterna da impostare sul controller padre per il salvataggio dei child
    public $propForm; //proprietà del form padre
    public $propFormChild; //proprietà del form child
    public $rolesChecker;

    public $errorMessage; //messaggio di errore

    public function filterData($data, $encrypt = null)
    {
        return $data;
    }

    /**
     * __init
     * Inizializza le variabili del controller. E' praticamente il costruttore del trait e va richiamato nel costruttore della classe controller
     * @return void
     */
    public function __init()
    {
        $this->report = new ReportService();
        $this->form = new FormService();
        $this->log = new Logger();
        $this->modelChild = null;
        $this->insert_id = null;

        DB::enableQueryLog();
    }

    /**
     * getIndex
     * Restituisce i parametri utili alla visualizzazione del report
     * @param  mixed $formBuilder
     * @param  mixed $request
     * @return mixed
     */
    public function getIndex($formBuilder, Request $request)
    {

        $this->_formId = $this->getFormId(request('report'));


        $whereFilters =  $this->report->makeWhereFilter($this->_formId);

        $this->reportData = $this->report->loadReportProperties(request('report'));

        $this->model->setTable($this->reportData['table']);

        $this->log->info("#INIZIO REPORT MENU#", __FILE__, __LINE__);
        $cols = $this->report->loadReportColumns(request('report'));
        $data = $this->report->loadTableData($this->model, $cols, $this->_formId, $whereFilters);

        if (!is_null($this->reportData['sum'])) {
            $this->report->setCounterRows($this->reportData['sum'], $this->model, $whereFilters);
        }

        session()->put('reportData', $this->reportData);
        $params = [
            'data' => $data,
            'cols' => $cols,
            'report' => $this->reportData,
            'route' => $this->reportData['route'],
            'filters' => null,
            'reportId' => request('report'),
            'useNewFilters' => true,
            'dropdown' => $this->setDropMultiSelect(),
            'pages' => $this->report->linkPages,
            'count' => $this->report->countRows,
        ];
        $formFilter = $this->form->loadFormFilters(request('report'));
        if (!is_null($formFilter)) {
            $formFilterId = $formFilter->id;
            // @deprecated - FormBuilder rimosso, i filtri sono gestiti dal componente Livewire ict-filter-form
            // $this->form->setClassForm(\Packages\IctInterface\Forms\FilterForm::class);
            // $filters = $this->form->getForm($formBuilder, $formFilterId, $this->model);
            // $params['filters'] = $filters;
            // $params = Arr::add($params, 'multiple', $this->setMultipleFields($filters));
        }


        session()->put('urlLastReport', $request->fullUrl());

        return $params;
    }

    /**
     * index
     * Esegue la visualizzazione della lista dei record della tabella
     * @param  mixed $formBuilder
     * @param  mixed $request
     * @return void
     */
    public function index($formBuilder, Request $request)
    {

        $params = $this->getIndex($formBuilder, $request);
        // $params['report']->has_create_button = session('loggedUser')->roles;

        return view("ict::{$this->reportData['blade']}", $params);
    }

    /**
     * getCreate
     * Restituisce i parametri per la creazione del form di creazione
     * @param  mixed $formBuilder
     * @return void
     */
    public function getCreate($formBuilder)
    {
        $this->_formId = $this->getFormId(request('report'));
        empty(request('id')) ? $edit_id = null : $edit_id = request('id');
        // @deprecated - FormBuilder rimosso, i form sono gestiti dal componente Livewire ict-editable-form
        // $this->form->setClassForm(\Packages\IctInterface\Forms\AppFormsBuilder::class);
        // $form = $this->form->getForm($formBuilder, $this->_formId, $this->model, $edit_id);

        $this->_childFormProperties = is_null($this->form->form_properties->id_child) ? null : $this->form->loadFormProperties($this->form->form_properties->id_child);

        return [
            'form' => null,
            'itemsList' => null,
            'itemFormData' => $this->_childFormProperties
        ];
    }

    /**
     * create
     * Visualizza il form di creazione del record
     * @param  mixed $formBuilder
     * @return void
     */
    public function create($formBuilder)
    {
        $params = $this->getCreate($formBuilder);

        $view = Str::after($this->form->form_properties->data, 'view:');
        return view($view, $params);
    }

    /**
     * store
     * Esegue la insert
     * @param  mixed $request
     * @return void
     */
    public function store(Request $request)
    {
        $res = $this->getStore($request);

        $this->resultStore($res);
        return redirect(url()->current() . "?report=" . request('report'));
    }

    public function getStore(Request $request)
    {
        $this->_formId = $this->getFormId(request('report'));

        //carico le proprietà del form child
        $this->_childFormProperties = $this->form->loadFormProperties($this->_formId);

        $data = $request->validate($this->form->getDataToSave($this->_formId));

        $data = $this->filterData($data, true);
        foreach ($data as $key => $value) {
            $isCrypted = $this->form->isCrypted($key, $this->_formId);
            if ($isCrypted) {
                $data[$key] = Crypt::encryptString($value);
            }
        }
        $this->log->info("*STORE IN* " . __CLASS__, __FILE__, __LINE__);
        // dd($data, $this->_formId);
        $id = null;
        try {
            $this->insert_id = $id = $this->model->create($data)->id;
            $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__, $id);
        } catch (Exception $e) {
            $this->catchCode($e, __FILE__, __LINE__);
            return [
                'result' => 'fail',
                'message' => $e->getMessage(),
            ];
        }

        $res = true; //assumo per dafault che $res=true perchè se nn ci fossero campi children da salvare darebbe errore

        if ($id && !is_null($this->_childFormProperties) && $request->has('items')) {
            //salvo il form child
            $res = $this->form->childSaveForm($this->modelChild, $id, $data, $this->foreignKey);
        }

        if (!$id) {
            return null;
        } elseif (!$res) {
            return false;
        }

        return true;
    }

    /**
     * resultStore
     * Elabora il riscontro della scrittura nei dati del db e restituisce il risultato facendo il rollback o commit
     * @param  mixed $res [null,false,true]
     * @return bool
     */
    public function resultStore($res, $error = null)
    {
        if (is_null($res)) {
            is_null($error) ? $this->report->setFlashMessages('Errore nel salvataggio dei dati', 'danger') : $this->report->setFlashMessages('Errore nel salvataggio dei dati [' . $error . ']', 'danger');
            $this->log->rollback(__FILE__, __LINE__);
            DB::rollBack();
            return false;
        } elseif ($res == false) {
            is_null($error) ? $this->report->setFlashMessages("Errore nell'inserimento dei dati figli", 'danger') : $this->report->setFlashMessages("Errore nell'inserimento dei dati figli [" . $error . "]", 'danger');
            $this->log->rollback(__FILE__, __LINE__);
            DB::rollBack();
            return false;
        } elseif (is_array($res) && $res['result'] == 'fail') {
            $this->report->setFlashMessages("Errore salvataggio dati [{$res['message']}]", 'danger');
            $this->log->rollback(__FILE__, __LINE__);
            DB::rollBack();
            return false;
        }

        $this->log->commit(__FILE__, __LINE__);
        DB::commit();
        $this->report->setAlertMessage($res, $this->insert_id);
        return $this->insert_id;
    }

    /**
     * getEdit
     * Restituisce i parametri per la creazione del form di edit
     * @param  mixed $formBuilder
     * @param  mixed $id
     * @return void
     */
    public function getEdit($formBuilder, $id)
    {

        $this->_formId = $this->getFormId(request('report'));
        $model = $this->model->find($id);

        $edit_id = $id;
        // @deprecated - FormBuilder rimosso, i form sono gestiti dal componente Livewire ict-editable-form
        // $this->form->setClassForm(\Packages\IctInterface\Forms\AppFormsBuilder::class);
        // $form = $this->form->getForm($formBuilder, $this->_formId, $model, $edit_id);

        $this->_childFormProperties = is_null($this->form->form_properties->id_child) ? null : $this->form->loadFormProperties($this->form->form_properties->id_child);

        $itemsList = is_null($this->_childFormProperties) ? null : $this->form->loadItemsList($this->modelChild, $edit_id, $this->_childFormProperties->report_id, $this->foreignKey, ['*']);

        return [
            'form' => null,
            'itemsList' => $itemsList,
            'itemFormData' => $this->_childFormProperties,
            'itemChildFormData' => $this->_childFormProperties,
            'roles_checker' => isset($this->rolesChecker[request('report')]) ? $this->rolesChecker[request('report')] : null
        ];
    }

    /**
     * edit
     * Visualizza il form di edit
     * @param  mixed $formBuilder
     * @param  mixed $id
     * @return void
     */
    public function edit($formBuilder, $id)
    {
        $params = $this->getEdit($formBuilder, $id);

        $params = $this->setEditChildParams($params);

        return $this->view($params);
    }

    /**
     * setEditChildParams
     * Imposta i parametri del form child se esiste
     * @param  mixed $params
     * @return void
     */
    public function setEditChildParams($params)
    {
        if (!is_null($params['itemChildFormData'])) {
            $params = Arr::add($params, 'addChildRoute', 'call.child.addformchild');
            $params = Arr::add($params, 'id_child', $params['itemChildFormData']->id);
        }
        return $params;
    }

    /**
     * view
     * Prende la vista blade configurata nel form e la visualizza
     * @param  mixed $params
     * @return void
     */
    public function view($params)
    {
        $view = Str::after($this->form->form_properties->data, 'view:');
        return view($view, $params);
    }

    /**
     * update
     * Aggiorna il record
     * @param  mixed $request
     * @param  mixed $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        if (request()->has('cancel_action') && request('cancel_action') == 1) {
            return $this->disabled($id);
        }

        $res = $this->getUpdate($request, $id);
        $this->resultUpdate($res, $id);
        return $this->referer();
    }

    /**
     * getUpdate
     * Esegue l'aggiornamento nel DB
     * @param  mixed $request
     * @param  mixed $id
     * @return void
     */
    public function getUpdate(Request $request, $id)
    {
        $this->_formId = $this->getFormId(request('report'));
        $this->log->info("*UPDATE IN* " . __CLASS__, __FILE__, __LINE__);
        $dataForm = $request->validate($this->form->getDataToSave($this->_formId, $id));
        foreach ($dataForm as $key => $value) {
            $isCrypted = $this->form->isCrypted($key, $this->_formId);
            if ($isCrypted) {
                $dataForm[$key] = Crypt::encryptString($value);
            }
        }
        $resItems = true; // imposto a true perchè potrebbe non salvare alcun item

        if ($request->has('items')) {
            $resItems = $this->form->childSaveForm($this->modelChild, $id, $request->all(), $this->foreignKey);
        }

        if ($resItems) {
            try {
                $data = $dataForm;
                if (Arr::has($dataForm, 'items')) {
                    //tolgo i dati degli eventuali items
                    Arr::forget($data, 'items');
                }

                $data = $this->filterData($data, true);

                $res = $this->model->where('id', '=', $id)->update($data);

                $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__, $res);

                return true;
            } catch (Exception $e) {
                $this->report->setAlertMessage(false, $id);
                $this->catchCode($e, __FILE__, __LINE__);
                $this->errorMessage = $e->getMessage();
                return false;
            }
        } else {

            return false;
        }
        return true;
    }

    /**
     * resultUpdate
     * Elabora il riscontro della scrittura nei dati del db e restituisce il risultato facendo il rollback o commit
     * @param  mixed $res
     * @param  mixed $id
     * @return bool
     */
    public function resultUpdate($res, $id)
    {
        if ($res == true) {
            DB::commit();
            $this->log->commit(__FILE__, __LINE__);
            $this->report->setAlertMessage(true, $id);
        } else {
            $this->report->setFlashMessages($this->errorMessage, 'danger');
            $this->log->rollback(__FILE__, __LINE__);
            DB::rollBack();
        }
        return $res;
    }

    /**
     * destroy
     * Elimina il record
     * @param  mixed $id
     * @return void
     */
    public function destroy($id)
    {
        $this->_formId = $this->getFormId(request('report'));
        $res = $this->execDestroy($id);
        if ($res == false) {
            return false;
        }
        DB::commit();
        $this->log->commit(__FILE__, __LINE__);

        return $res;
    }

    public function execDestroy($id)
    {

        $this->log->info("*ELIMINO IN* " . __CLASS__, __FILE__, __LINE__);
        try {
            $res = $this->model->findOrFail($id)->delete();
            $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);
            $this->report->setAlertMessage($res, $id, 'D');
        } catch (Exception $e) {
            $this->report->setAlertMessage(false, $id);
            $this->catchCode($e, __FILE__, __LINE__);
            $res = false;
        }
        return $res;
    }

    /**
     * disabled
     * Disabilita un record (is_enabled = 0)
     * @param  mixed $id
     * @return void
     */
    public function disabled($id)
    {
        $this->_formId = $this->getFormId(request('report'));
        $this->log->info("*DISABILTO IN* " . __CLASS__, __FILE__, __LINE__);

        try {
            $res = $this->model->find($id)->update(['is_enabled' => 0]);
            $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);
            DB::commit();
            $this->log->commit(__FILE__, __LINE__);
            $this->report->setAlertMessage($res, $id, 'C');
        } catch (Exception $e) {
            $this->report->setAlertMessage(false, $id, 'C');
            $this->catchCode($e, __FILE__, __LINE__);
            return false;
        }
        return $res;
    }

    public function catchCode($e, $file, $line, $db = null)
    {
        is_null($db) ? DB::rollBack() : $db->rollBack();
        $this->log->rollback($file, $line);
        if (count(DB::getQueryLog()) > 0) {
            $this->log->sql(DB::getQueryLog(), $file, $line);
        }
        $this->log->error($e->getMessage() . "[" . basename($file) . ", $line]", $file, $line);
    }

    /**
     * referer
     * Redirige alla url precedente
     * @return void
     */
    public function referer()
    {
        return redirect(url()->previous());
        // return redirect(request()->headers->get('referer'));
    }


    /**
     * setDropMultiSelect
     * Imposta il menù a tendina delle azioni del multicheck.
     * Se $reference non è passato, legge automaticamente da $this->reportData['multicheck_reference'].
     * @param  mixed $reference
     * @return void
     */
    public function setDropMultiSelect($reference = null)
    {
        if (is_null($reference)) {
            $reference = $this->reportData['multicheck_reference'] ?? null;
        }

        if (is_null($reference)) {
            return null;
        }

        $items = MulticheckAction::where('reference', $reference)
            ->get()
            ->toArray();
        $multicheck = new MulticheckController();
        return count($items) > 0 ? $multicheck->init($items) : null;
    }

    /**
     * hook_FormId
     * Funzione hook che deve essere sovrascritta sul parent qualora si voglia forzare la definizione dell'id del form
     * @param  mixed $report
     * @param  mixed $type
     * @return void
     */
    public function hook_FormId($report = null, $type = null)
    {
        return is_null($report) && is_null($type) ? $this->getFormId(request('report')) : $this->getFormId($report, $type);
    }

    public function disabledForm($form, $reportId)
    {
        if ($form->formService->form_properties->type == 'editable') {
            if (session()->has('roles_checker')) {
                $this->rolesChecker = session()->get('roles_checker');
            }
            if (isset($this->rolesChecker[$reportId]) && $this->rolesChecker[$reportId]['has_edit_button'] == 0) {
                // Disabilita tutti i campi del form
                $form->disableFields();
                return true;
            } elseif (isset($this->rolesChecker[$reportId]) && !empty($this->rolesChecker[$reportId]['fields_disabled'])) {
                foreach ($this->rolesChecker[$reportId]['fields_disabled'] as $fields) {
                    foreach ($fields as $field => $value) {
                        $form->modify($field, $form->getField($field)->gettype(), ['attr' => [key($value) => $value[key($value)]]]);
                    }
                }
                return true;
            }
        }
        return false;
    }

    public function setMultipleFields($form)
    {
        $multiple = [];
        foreach ($form->getFields() as $field) {
            if ($field->getOption('multiple')) {
                $multiple[] = $field->getOption('attr.id');
            }
        }
        return $multiple;
    }



    /**
     * Rimuove gli ID scaduti dalla lista.
     *
     * @param array $ids
     */
    public function removeExpiredIds(&$ids)
    {
        $currentTime = now();

        // Filtra gli ID per rimuovere quelli scaduti
        $ids = array_filter($ids, function ($item) use ($currentTime) {
            return $currentTime->lt($item['expires_at']);
        });

        // Riordina gli array dopo il filtro per evitare buchi
        $ids = array_values($ids);
    }
}
