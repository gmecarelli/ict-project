<?php

/**
 * UserProfileManagerComponent
 *
 * Componente Livewire per la gestione utenti-profilo.
 * Sostituisce modal-users.blade.php (jQuery AJAX) con ricerca e assegnazione
 * utenti al profilo tramite Livewire.
 *
 * Uso:
 *   @livewire('ict-user-profile-manager', ['profileId' => $profile_id])
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Livewire;

use Exception;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UserProfileManagerComponent extends Component
{
    public int $profileId;
    public string $searchName = '';
    public array $users = [];
    public array $selectedUserIds = [];
    public bool $showModal = false;

    public function mount(int $profileId): void
    {
        $this->profileId = $profileId;
    }

    /**
     * Apre la modale e carica gli utenti
     */
    public function openModal(): void
    {
        $this->showModal = true;
        $this->searchUsers();
    }

    /**
     * Chiude la modale e resetta
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->searchName = '';
        $this->users = [];
        $this->selectedUserIds = [];
    }

    /**
     * Ricerca utenti (unione tra utenti abilitati e utenti già nel profilo)
     */
    public function searchUsers(): void
    {
        $usersTable = config('ict.table_users', 'users');

        // Utenti già associati al profilo
        $profileUserIds = DB::table('profiles_has_users')
            ->where('profile_id', $this->profileId)
            ->pluck('user_id')
            ->toArray();

        // Utenti abilitati (filtrati per nome se presente)
        $query = DB::table($usersTable)
            ->select(['id', 'name', 'email'])
            ->where('is_enabled', 1);

        if (!empty($this->searchName)) {
            $query->where('name', 'like', '%' . $this->searchName . '%');
        }

        $allUsers = $query->orderBy('name')->get();

        $this->users = $allUsers->map(function ($user) use ($profileUserIds) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'has_profile' => in_array($user->id, $profileUserIds),
            ];
        })->toArray();

        // Pre-seleziona gli utenti già associati
        $this->selectedUserIds = $profileUserIds;
    }

    /**
     * Salva le associazioni utente-profilo
     */
    public function saveUsers(): void
    {
        DB::beginTransaction();

        try {
            // Rimuovi tutte le associazioni esistenti
            DB::table('profiles_has_users')
                ->where('profile_id', $this->profileId)
                ->delete();

            // Inserisci le nuove associazioni
            foreach ($this->selectedUserIds as $userId) {
                $keyId = Str::random(6) . '-p' . $this->profileId . 'u' . $userId;
                DB::table('profiles_has_users')->insert([
                    'key_id' => $keyId,
                    'profile_id' => $this->profileId,
                    'user_id' => $userId,
                    'created_at' => now(),
                ]);
            }

            DB::commit();

            $this->closeModal();
            $this->js('window.location.reload()');
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('modal_error', 'Errore: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('ict::livewire.user-profile-manager');
    }
}
