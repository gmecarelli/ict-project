<?php

namespace Packages\IctInterface\Controllers;

use Illuminate\Support\Arr;
use App\Models\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Packages\IctInterface\Models\Form;
use Packages\IctInterface\Models\Report;
use Packages\IctInterface\Controllers\Services\Logger;

class IctController extends Controller {

    protected $filters;
    protected $report;
    protected $form;
    public $model;
    public $log;
    public $response;
   
    public function __construct()
    {
        // parent::__construct();
        $this->log = new Logger();
        DB::enableQueryLog();
      
    }
    
    /**
     * isAdmin
     * Controlla se l'utente loggato è amministratore
     * @return void
     */
    public function isAdmin() {
        if(session()->has('is_admin') && session('is_admin') == 1) {
            return true;
        }
        return false;
    }

    public function getFormId($report_id, $type='editable') {
        if(request()->has('form_id') && request()->filled('form_id')) {
            return request('form_id');
        }

        $form = Form::where('report_id', $report_id)
            ->where('type', $type)
            ->first();

        return is_null($form) ? null : $form->id;
    }

    /**
     * loadDataById
     * Restituisce i dati del record caricati dal Model con l'ID
     * @param  mixed $model
     * @param  mixed $id
     * @return stdClass
     */
    public function loadDataById($model, $id) {
        return $model->where('id',$id)
            ->first();
    }

    /**
     * stringToArray
     * Parsa la stringa dei parametri scritta nel db ( con formato nome_chiave_1:valore_1,nome_chiave_2:valore_2) e la trasforma in un array associativo
     * @param  string $str
     * @return array
     */
    public function stringToArray($str) {
        // dd($parsed);
        $parsed = [];
        // $this->log->debug("*Parso la string [$str]*",__FILE__, __LINE__);
        
        $elements = explode(",",$str);
        foreach($elements as $element) {
            list($key, $value) = explode(":",$element);
            $parsed[trim($key)] = trim($value);
        }
        
        return $parsed;
    }


    /**
     * checkPeriod
     * Controlla che un periodo (mese/anno) non sia superiore al periodo di riferimento
     * @param  array $period
     * @param  array $periodRef
     * @return bool
     */
    public function checkPeriod($period, $periodRef) {
        $this->log->debug("*Periodo RDP* mese[{$period['m']}] anno[{$period['y']}]",__FILE__,__LINE__);
        $this->log->debug("*Periodo RIF* mese[{$periodRef['month']}] anno[{$periodRef['year']}]",__FILE__,__LINE__);
        if($period['y'] < $periodRef['year']) {
            //se anno rif è superiore ad anno in esame
            return true;
        }
        if($period['y'] > $periodRef['year']) {
            //se anno rif è inferiore ad anno in esame
            return false;
        }
        if($period['m'] <= $periodRef['month']) {
            //se mese in esame minore o uguale a mese rif (qui anno sicuramente lo stesso)
            return true;
        } else {
            return false;
        }
    }

        /**
     * saveModalForm
     * Salva i dati di una modale per le chiamate AJAX
     * @param  mixed $Model
     * @param  mixed $formId
     * @param  mixed $id [id del record da aggiornare. Se null fa INSERT]
     * @param  mixed $trans [indica se la DB::transection è già aperta o meno]
     * @param  mixed $data [array da salvare e quindi da non prendere dalla request]
     * @return void
     */
    public function saveModalForm($Model, $formId, $id = null, $trans = false, $data = null) {
        // Se passo trans=true allora non istanzia DB::beginTransaction() perchè già fatto sulla function genitore
        
        // $trans == true ?: DB::beginTransaction();
        if(is_null($data) && !is_null($formId)) {
            $data = request()->validate($this->form->getDataToSave($formId, $id));
        }

        // $this->log->debug("*DATA RDP TOTALS* ".print_r($data, true),__FILE__,__LINE__);
        if(is_null($id)) {
            $res = $Model->create($data);
            $logNewId = $res->id;
            $this->response = Arr::add($this->response, 'insert_id', $logNewId);
        } else {
            $res = $Model->find($id)->update($data);
            $logNewId = $id;
        }

        $this->log->sql(DB::getQueryLog(),__FILE__,__LINE__,"ID: {$logNewId}");

        if(!$res) {
            $this->response['result'] = 'fail';
            $this->response['message'] = "Il record [ID={$logNewId}] non è stato aggiornato";
            $this->log->rollback(__FILE__,__LINE__);
            DB::rollBack();
            return $this->response;
        }
        if($trans == false) {
            $this->log->commit(__FILE__,__LINE__);
            DB::commit();
            
        }
       
        return $this->response;
    }


    /**
     * setFlashMessages
     * Scrive in sessione (flash) i messaggi di errore
     * @param  mixed $message
     * @param  mixed $alert
     * @return void
     */
    public function setFlashMessages($message, $alert) {
        session()->flash('message', $message);
        session()->flash('alert', $alert);
    }
    
    /**
     * loadFormIdReport
     * Restituisce il form_id del report di riferimento
     * @return void
     */
    protected function loadFormIdReport($report_id = null) {
        $report_id = is_null($report_id) ? request('report') : $report_id;

        $form = Form::where('report_id', $report_id)
                    ->where('type', 'editable')
                    ->first();
// dd(DB::getQueryLog());
        return $form->id;
    }
    
    /**
     * replaceTags
     * Sostituisce da una stringa dei tags predefiniti con un valore dinamico
     * @param  mixed $str
     * @return void
     */
    public function replaceTags($str) {
        return str_replace(['[now]'],[date("d/m/Y")], $str);
    }
    
    /**
     * updateSwitch
     * Aggiorna lo stato di un switch
     * @return void
     */
    public function updateSwitch() {
        DB::table(request('table'))->where('id', request('id'))->update([request('field') => request('value')]);
        $this->log->sql(DB::getQueryLog(),__FILE__,__LINE__);
        return [
            'result' => 'success',
            'message' => request('value') == 1 ? '<span class="text-success">On</span>' : '<span class="text-danger">Off</span>',
            'id' => request('id')
        ];
    }
    
    /**
     * dashboard
     * Visualizzazione della dashboard
     * @return void
     */
    public function dashboard()
    {
        if(!session()->has('loggedUser')) {
            return redirect('/logout');
        }

        if(method_exists(parent::class, 'dashboard')) {
            //richiama la dashboard del genitore che potrà essere sovrascritta nel controller genitore dell'applicazione
            //se non esiste, allora la dashboard di default di ICT
            return parent::dashboard();
        
            if(!empty($dashboard['notifications'])) {
                $params = [
                    'notifications' => $dashboard['notifications'],
                ];
            } else {
                $notifications = ['Accesso consentito, benvenuto! Le notifiche sono disattivate'];
                $params = [
                    'notifications' => $notifications,
                ];
            }

            return view(is_null($dashboard['view']) ? 'ict::dashboard' : $dashboard['view'], $params);
        }

        if(Schema::hasTable('notifications')) {
            
            $notifications = Notification::where('is_enabled', 1)
                                        ->whereDate('created_at', '>=', Carbon::now()->subDays(2))
                                        ->orderBy('id', 'desc')
                                        ->get();
            if($notifications->isEmpty()) {
                $notifications = ['Accesso consentito, benvenuto! Non ci sono nuove notifiche'];
            }
        } else {
            $notifications = ['Accesso consentito, benvenuto! Le notifiche sono disattivate'];
        }

        return view('ict::dashboard', [
            'notifications' => $notifications,
        ]);
    }
 
}