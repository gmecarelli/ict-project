<?php

/**
 * AjaxController
 *
 * Controller per le chiamate AJAX residue.
 * Metodi save (saveFormRole, saveFormItemsForm, saveReportColsForm)
 * funzionanti tramite saveModalForm() di IctController.
 * searchUsers per la ricerca utenti nei profili.
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Controllers\Ajax;

use Packages\IctInterface\Models\FormField;
use Illuminate\Support\Arr;
use Packages\IctInterface\Models\ReportColumn;
use Illuminate\Support\Facades\DB;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Controllers\Services\Logger;
use Packages\IctInterface\Controllers\Services\FormService;
use Packages\IctInterface\Controllers\Services\ReportService;
use Packages\IctInterface\Models\ProfileRole;

class AjaxController extends IctController
{
    public $report;
    public $form;
    public $Model;
    public $response;
    public $job_id;

    public function __construct()
    {
        parent::__construct();
        $this->report = new ReportService();
        $this->form = new FormService();
        $this->log = new Logger();
        DB::enableQueryLog();

        $this->job_id = null;

        $this->response = [
            'result' => 'success',
            'html' => '',
            'message' => 'Dati aggiornati con successo',
        ];
    }

    /**
     * saveFormRole
     * Salva il form dei form_items tramite saveModalForm() di IctController
     */
    public function saveFormRole()
    {
        $this->log->info("AGGIORNO da MODALE " . __FUNCTION__, __FILE__, __LINE__);
        $Model = new ProfileRole();
        if (request('id') && request()->has('id')) {
            return $this->saveModalForm($Model, request('form_id'), request('id'));
        } else {
            return $this->saveModalForm($Model, request('form_id'));
        }
    }

    /**
     * saveFormItemsForm
     * Salva il form dei form_items tramite saveModalForm() di IctController
     */
    public function saveFormItemsForm()
    {
        $this->log->info("AGGIORNO da MODALE " . __FUNCTION__, __FILE__, __LINE__);
        $Model = new FormField();
        if (request('id') && request()->has('id')) {
            return $this->saveModalForm($Model, request('form_id'), request('id'));
        } else {
            return $this->saveModalForm($Model, request('form_id'));
        }
    }

    /**
     * saveReportColsForm
     * Salva i dati del form delle colonne del report tramite saveModalForm() di IctController
     */
    public function saveReportColsForm()
    {
        $this->log->info("AGGIORNO da MODALE " . __FUNCTION__, __FILE__, __LINE__);
        $model = new ReportColumn();

        if (request('id') && request()->has('id')) {
            return $this->saveModalForm($model, request('form_id'), request('id'), true);
        } else {
            return $this->saveModalForm($model, request('form_id'));
        }
    }

    /**
     * searchUsers
     * Ricerca utenti per l'assegnazione ai profili
     */
    public function searchUsers()
    {
        $this->response['users'] = [];
        $this->response['cols'] = [
            '#',
            'Nome',
            'Username',
        ];

        $profiles = DB::table('profiles_has_users')
            ->select(['id', 'name', 'email'])
            ->leftJoin(config('ict.table_users'), config('ict.table_users').'.id', '=', 'profiles_has_users.user_id')
            ->where('profile_id', request('profile_id'));

        $users = DB::table(config('ict.table_users'))
            ->select(['id', 'name', 'email'])
            ->where('is_enabled', 1);
        if (request()->filled('name') && request()->filled('name')) {
            $users = $users->where('name', 'like', "%" . request('name') . "%");
        }

        $users = $users->union($profiles)->get()->toArray();

        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);

        if (is_null($users)) {
            $this->response['result'] = 'fail';
            $this->response['message'] = 'Errore nella ricerca degli utenti';
            return $this->response;
        }
        $this->response['result'] = 'success';
        foreach ($users as $key => $user) {
            $has_profile = Arr::get(DB::table('profiles_has_users')
                ->where('profile_id', request('profile_id'))
                ->where('user_id', $user->id)
                ->get()
                ->toArray(), 0);

            $this->response['users'][$key]['id'] = $user->id;
            $this->response['users'][$key]['name'] = $user->name;
            $this->response['users'][$key]['username'] = $user->email;
            $this->response['users'][$key]['profile_id'] = isset($has_profile->profile_id) ? request('profile_id') : null;
        }
        if(empty($this->response['users'])) {
            $this->response['message'] = 'Nessun utente trovato';
        }
        return $this->response;
    }
}
