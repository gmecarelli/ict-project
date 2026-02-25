<?php

/**
 * Classe base per tutti i Service del package IctInterface.
 * Contiene i metodi utility condivisi che precedentemente erano in IctController.
 * I Service NON devono essere controller: questa classe fornisce le utility
 * senza estendere Illuminate\Routing\Controller.
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Support;

use Illuminate\Support\Facades\DB;
use Packages\IctInterface\Controllers\Services\Logger;
use Packages\IctInterface\Models\Form;

class BaseService
{
    public $log;

    public function __construct()
    {
        $this->log = new Logger();
        DB::enableQueryLog();
    }

    /**
     * isAdmin
     * Controlla se l'utente loggato è amministratore
     */
    public function isAdmin(): bool
    {
        if (session()->has('is_admin') && session('is_admin') == 1) {
            return true;
        }
        return false;
    }

    /**
     * getFormId
     * Restituisce il form_id dal report_id e dal tipo
     */
    public function getFormId($report_id, $type = 'editable')
    {
        if (request()->has('form_id') && request()->filled('form_id')) {
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
     */
    public function loadDataById($model, $id)
    {
        return $model->where('id', $id)->first();
    }

    /**
     * stringToArray
     * Parsa la stringa dei parametri scritta nel db
     * (formato nome_chiave_1:valore_1,nome_chiave_2:valore_2)
     * e la trasforma in un array associativo
     */
    public function stringToArray($str)
    {
        $parsed = [];
        $elements = explode(",", $str);
        foreach ($elements as $element) {
            list($key, $value) = explode(":", $element);
            $parsed[trim($key)] = trim($value);
        }
        return $parsed;
    }

    /**
     * checkPeriod
     * Controlla che un periodo (mese/anno) non sia superiore al periodo di riferimento
     */
    public function checkPeriod($period, $periodRef)
    {
        $this->log->debug("*Periodo RDP* mese[{$period['m']}] anno[{$period['y']}]", __FILE__, __LINE__);
        $this->log->debug("*Periodo RIF* mese[{$periodRef['month']}] anno[{$periodRef['year']}]", __FILE__, __LINE__);
        if ($period['y'] < $periodRef['year']) {
            return true;
        }
        if ($period['y'] > $periodRef['year']) {
            return false;
        }
        if ($period['m'] <= $periodRef['month']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * setFlashMessages
     * Scrive in sessione (flash) i messaggi di errore
     */
    public function setFlashMessages($message, $alert)
    {
        session()->flash('message', $message);
        session()->flash('alert', $alert);
    }

    /**
     * loadFormIdReport
     * Restituisce il form_id del report di riferimento
     */
    protected function loadFormIdReport($report_id = null)
    {
        $report_id = is_null($report_id) ? request('report') : $report_id;

        $form = Form::where('report_id', $report_id)
            ->where('type', 'editable')
            ->first();

        return $form->id;
    }

    /**
     * replaceTags
     * Sostituisce da una stringa dei tags predefiniti con un valore dinamico
     */
    public function replaceTags($str)
    {
        return str_replace(['[now]'], [date("d/m/Y")], $str);
    }
}
