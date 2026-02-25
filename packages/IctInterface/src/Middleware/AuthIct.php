<?php

namespace Packages\IctInterface\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthIct
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Session::has('loggedUser')) {
            return redirect('login')->withErrors('Non hai i permessi di accesso');
        }

        if (url()->current() != route('dashboard')) {
            if (!request()->input('report')) {
                return $this->_redirection("Nella precedente richiesta non era valorizzato il dato passato `report`");
            }
            //imposto la variabile report_id in sessione per il successivo controllo dei permessi
            session()->put('report_id', request('report'));

            if ($this->_roles() == false) {
                $url =  url()->current();
                if (session()->has('_previous')) {
                    return redirect(url()->previous())->withErrors("Non hai i permessi di accesso alla pagina richiesta [$url]");
                }
                return $this->_redirection("Non hai i permessi di accesso alla pagina richiesta [$url]");
            }
        }
        return $next($request);
    }

    private function _redirection($message)
    {
        session()->flush();
        return redirect('login')->withErrors($message);
    }

    /**
     * _roles
     * Controlla se l'utente ha i permessi di accesso. Al primo accesso setta le sessioni
     * @return void
     */
    private function _roles()
    {
        if (session()->has('roles')) {
            //controllo se l'utente ha i permessi di accesso
            $roles = session()->get('roles');
        } else {
            return false;
        }
        return $this->checkRoles($roles);
    }

    /**
     * checkRoles
     * Controlla se si ha accesso al report
     * @param  mixed $roles
     * @return void
     */
    protected function checkRoles($roles)
    {
        if (session()->has('is_admin') && session()->get('is_admin') == 1) {
            return true;
        }

        foreach ($roles as $role) {
            if ($role->report_id == session()->get('report_id')) {
                $url = url()->current();

                if (Str::contains($url, 'create')) {
                    if ($role->has_create_button == 0) {
                        return false;
                    }
                }
                // if (Str::contains($url, 'edit')) {
                //     if ($role->has_edit_button == 0) {
                //         return false;
                //     }
                // }
                return true;
            }
        }
        return false;
    }
}
