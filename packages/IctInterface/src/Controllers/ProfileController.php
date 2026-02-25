<?php

namespace Packages\IctInterface\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Packages\IctInterface\Models\Profile;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Traits\LivewireController;

class ProfileController extends IctController
{
    use LivewireController;

    public $response;

    public function __construct()
    {
        parent::__construct();
        $this->__init();
        $this->model = new Profile();
        $this->foreignKey = 'profile_id';
        $this->response = [
            'result' => 'success',
            'message' => 'Utenti aggiunti al profilo con successo',
        ];
    }

    /**
     * edit
     * Override per includere il profile_id nella vista profilo.
     * La vista profile.blade.php ha funzionalità specifiche (Aggiungi utenti al profilo)
     * che non sono gestite dal builder.blade.php standard.
     */
    public function edit($id)
    {
        $params = $this->getEdit($id);
        $params['profile_id'] = $id;

        return view('ict::forms.profile', $params);
    }

    /**
     * addUsers
     * Ajax call: aggiunge gli utenti selezionati al profilo
     */
    public function addUsers()
    {
        $res = DB::table('profiles_has_users')
            ->where('profile_id', request('profile_id'))
            ->delete();
        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);

        if (is_null($res)) {
            $this->response['result'] = 'fail';
            $this->response['message'] = "Errore nel reset del profilo";
            DB::rollBack();
            $this->log->rollback(__FILE__, __LINE__);
            return $this->response;
        }

        $usersId = request()->filled('user_id') ? request('user_id') : [];
        foreach ($usersId as $user_id) {
            $keyId = Str::random(6) . "-p" . request('profile_id') . "u" . $user_id;
            $res = DB::table('profiles_has_users')
                ->insert([
                    'key_id' => $keyId,
                    'profile_id' => request('profile_id'),
                    'user_id' => $user_id,
                    'created_at' => now(),
                ]);
            $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);

            if (is_null($res)) {
                $this->response['result'] = 'fail';
                $this->response['message'] = "Errore nel nel salvataggio dell'utente ID[{$user_id}]";
                DB::rollBack();
                $this->log->rollback(__FILE__, __LINE__);
                return $this->response;
            }
        }

        return $this->response;
    }
}
