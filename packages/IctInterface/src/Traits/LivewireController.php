<?php

/**
 * LivewireController
 *
 * Trait alternativo a StandardController per i controller che usano
 * i nuovi componenti Livewire al posto di kris/laravel-form-builder.
 *
 * Differenze rispetto a StandardController:
 * - index(), create(), edit() NON richiedono FormBuilder come parametro
 * - create() e edit() passano alla view solo reportId/recordId/tableName
 *   + il flag useLivewireForm=true (il form è gestito dal componente Livewire)
 * - store() e update() NON sono necessari (Livewire gestisce il submit),
 *   ma sono mantenuti come fallback per eventuali form non ancora migrati
 * - destroy() e disabled() restano identici a StandardController
 *
 * Migrazione graduale: per migrare un controller basta sostituire
 *   use StandardController;
 * con
 *   use LivewireController;
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Traits;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Packages\IctInterface\Controllers\Services\FormService;
use Packages\IctInterface\Controllers\Services\Logger;
use Packages\IctInterface\Controllers\Services\MulticheckController;
use Packages\IctInterface\Controllers\Services\ReportService;
use Packages\IctInterface\Models\Form;
use Packages\IctInterface\Models\MulticheckAction;

trait LivewireController
{
    protected $reportData;
    protected $report;
    protected $form;
    public $log;
    protected $_formId;
    public $model;
    public $modelChild;
    public $insert_id;
    public $foreignKey;
    public $rolesChecker;
    public $errorMessage;

    public function filterData($data, $encrypt = null)
    {
        return $data;
    }

    /**
     * Inizializza le variabili del controller.
     * Va richiamato nel costruttore della classe controller.
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
     * Restituisce i parametri per la visualizzazione del report.
     * NON richiede FormBuilder: i filtri sono gestiti dal componente Livewire.
     */
    public function getIndex(Request $request)
    {
        $this->_formId = $this->getFormId(request('report'));

        $whereFilters = $this->report->makeWhereFilter($this->_formId);

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

        session()->put('urlLastReport', $request->fullUrl());

        return $params;
    }

    /**
     * index
     * Visualizza la lista dei record (report).
     * Filtri gestiti dal componente Livewire ict-filter-form.
     */
    public function index(Request $request)
    {
        $params = $this->getIndex($request);

        return view($this->reportData['blade'] == 'report' ? 'ict::'.$this->reportData['blade'] : $this->reportData['blade'], $params);
    }

    /**
     * getCreate
     * Restituisce i parametri per la creazione del form.
     * Il form è gestito dal componente Livewire ict-editable-form.
     */
    public function getCreate()
    {
        $this->_formId = $this->getFormId(request('report'));
        $this->reportData = $this->report->loadReportProperties(request('report'));

        return [
            'useLivewireForm' => true,
            'reportId' => (int) request('report'),
            'recordId' => null,
            'tableName' => $this->reportData['table'] ?? null,
            'form' => Form::find($this->_formId), // Passo anche i dati del form per eventuali personalizzazioni nel componente Livewire
        ];
    }

    /**
     * create
     * Visualizza il form di creazione.
     */
    public function create()
    {
        $params = $this->getCreate();
        $view = $this->setViewTemplate($params['form']->data);
        return view($view, $params);
    }

    protected function setViewTemplate($data)
    {
        return Str::startsWith($data, 'view:') ? Str::after($data, 'view:') : $data;
    }

    /**
     * getEdit
     * Restituisce i parametri per il form di edit.
     * Il form è gestito dal componente Livewire ict-editable-form.
     */
    public function getEdit($id)
    {
        $this->_formId = $this->getFormId(request('report'));
        $this->reportData = $this->report->loadReportProperties(request('report'));

        return [
            'useLivewireForm' => true,
            'reportId' => (int) request('report'),
            'recordId' => (int) $id,
            'tableName' => $this->reportData['table'] ?? null,
            'form' => Form::find($this->_formId), // Passo anche i dati del form per eventuali personalizzazioni nel componente Livewire
        ];
    }

    /**
     * edit
     * Visualizza il form di edit.
     */
    public function edit($id)
    {
        $params = $this->getEdit($id);
        $view = $this->setViewTemplate($params['form']->data);
        return view($view, $params);
    }

    /**
     * destroy
     * Elimina il record.
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
     */
    public function referer()
    {
        return redirect(url()->previous());
    }

    /**
     * setDropMultiSelect
     * Imposta il menù a tendina delle azioni del multicheck.
     * Se $reference non è passato, legge automaticamente da $this->reportData['multicheck_reference'].
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
     * Funzione hook per forzare la definizione dell'id del form
     */
    public function hook_FormId($report = null, $type = null)
    {
        return is_null($report) && is_null($type) ? $this->getFormId(request('report')) : $this->getFormId($report, $type);
    }

    /**
     * removeExpiredIds
     * Rimuove gli ID scaduti dalla lista.
     */
    public function removeExpiredIds(&$ids)
    {
        $currentTime = now();
        $ids = array_filter($ids, function ($item) use ($currentTime) {
            return $currentTime->lt($item['expires_at']);
        });
        $ids = array_values($ids);
    }
}
