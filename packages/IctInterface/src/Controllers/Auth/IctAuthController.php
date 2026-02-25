<?php

namespace Packages\IctInterface\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth as auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Controllers\Services\Logger;
use Packages\IctInterface\Models\IctUser;
use Packages\IctInterface\Models\ProfileRole;

class IctAuthController extends IctController
{
    public $log;

    public function __construct()
    {
        $this->log = new Logger();
    }

    /**
     * login
     * Visualizza il form di login
     * @return void
     */
    public function login()
    {
        if(session()->has('loggedUser')) {
            return redirect('dashboard');
        }
        return view('ict::auth.loginIct');
    }

    /**
     * check
     * Esegue il controllo dei dati di accesso
     * @param  mixed $request
     * @return void
     */
    public function check(Request $request)
    {
        DB::enableQueryLog();
        // Validate request
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        $user = new IctUser();
        $user->setTable(config('ict.table_users', 'users'));
        if($user->getTable() == 'vw_users') {
            $version = DB::select("SELECT VERSION() as version")[0];
            $funcCryptPassword = Str::contains($version->version,["MariaDB"]) ? "PASSWORD" : "MD5";
            $user = $user->where('email', $request->get('email'))
                    ->whereRaw("`password` = '{$funcCryptPassword}('" . $request->get('password') . "')")
                    ->where('is_enabled', '1')
                    ->first();
        } else {
            if(auth::attempt(['email' => $request->email, 'password' => $request->password, 'is_enabled' => 1])) {
                $user = $user->where('email', $request->email)->first();
            } else {
                $user = null;
            }
        }

        if (!is_null($user)) {
            $profiles = $this->getProfile($user->id);
            $request->session()->put('loggedUser', $user);
            $profiles = $this->setProfiles($user->profiles);
            $request->session()->put('profiles', $profiles);
            $request->session()->put('roles', $this->setRoles($profiles));
            $this->log->info("Utente loggato, scrivo in sessione", __FILE__, __LINE__);

            // dd($request->session()->get('loggedUser'));
            return redirect('dashboard');
        }

        return back()->withErrors('Il nome utente sbagliato o disabilitato');
    }


    /**
     * setProfiles
     * Imposta i profili in sessione
     * @param  mixed $profiles
     * @return void
     */
    public function setProfiles($profiles)
    {
        $sessProfiles = [];
        foreach (session('loggedUser')->profiles as $profile) {
            $sessProfiles[] = $profile->id;
            if ($profile->id == 1) {
                session()->put('is_admin', 1);
            }
        }
        return $sessProfiles;
    }

    /**
     * setRoles
     * Imposta i ruoli in sessione
     * @param  mixed $profiles
     * @return void
     */
    private function setRoles($profiles)
    {
        $roles = [];
        $rolesData = ProfileRole::whereIn('profile_id', $profiles)
            ->where('is_enabled', 1)
            ->get();
        
        foreach($rolesData as $role) {
            $roles = Arr::add($roles, $role->report_id.'.has_create_button', $role->has_create_button);
            $roles = Arr::add($roles, $role->report_id.'.has_edit_button', $role->has_edit_button);
            $roles = Arr::add($roles, $role->report_id.'.is_all_owner', $role->is_all_owner);
            $roles = Arr::add($roles, $role->report_id.'.fields_disabled', json_decode($role->fields_disabled, true));
            if($role->has_edit_button == 0){
                $roles = Arr::add($roles, $role->report_id.'.multicheck_reference', null);
            }
        }
        session()->put('roles_checker', $roles);
        return $rolesData;
    }

    

    public function logout()
    {
        session()->flush();
        return redirect('/');
    }

    public function getProfile($user_id)
    {
        $profile = [];
        $utenti = DB::table('profiles_has_users')->where('user_id', $user_id)->get();
        foreach ($utenti as  $utente) {
            $profile[] = $utente->profile_id;
        }
        //dd($profile);
        return $profile;
    }
}
