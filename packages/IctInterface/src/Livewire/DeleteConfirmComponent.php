<?php

/**
 * DeleteConfirmComponent
 *
 * Componente Livewire per la conferma eliminazione/disabilitazione record.
 * Sostituisce delete-js.blade.php (jQuery AJAX) per le pagine che usano LivewireController.
 *
 * Uso nella view report:
 *   @livewire('ict-delete-confirm', ['routePrefix' => $report['route']])
 *
 * Il componente ascolta eventi dispatch da pulsanti della tabella.
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Livewire;

use Exception;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Packages\IctInterface\Controllers\Services\Logger;

class DeleteConfirmComponent extends Component
{
    public string $routePrefix = '';
    public ?int $recordId = null;
    public string $action = ''; // 'delete' o 'disable'
    public bool $showConfirm = false;
    public string $modalSize = ''; // '', 'modal-sm', 'modal-lg', 'modal-xl'

    protected $listeners = [
        'confirm-delete' => 'confirmDelete',
        'confirm-disable' => 'confirmDisable',
        'confirm-child-delete' => 'confirmDelete',
    ];

    public function confirmDelete(int $recordId): void
    {
        $this->recordId = $recordId;
        $this->action = 'delete';
        $this->showConfirm = true;
    }

    public function confirmDisable(int $recordId): void
    {
        $this->recordId = $recordId;
        $this->action = 'disable';
        $this->showConfirm = true;
    }

    public function cancel(): void
    {
        $this->showConfirm = false;
        $this->recordId = null;
        $this->action = '';
    }

    public function execute(): void
    {
        if (!$this->recordId) {
            return;
        }

        $log = new Logger();

        // --- ACTION HANDLER ---
        $resolver = app(\Packages\IctInterface\Services\ActionHandlerResolver::class);
        $handler = $resolver->resolve($this->routePrefix);

        if ($handler) {
            $allowed = $handler->beforeDelete($this->routePrefix, $this->recordId, $this->action);
            if (!$allowed) {
                session()->flash('message', 'Operazione annullata dal handler');
                session()->flash('alert', 'warning');
                $this->cancel();
                $this->js('window.location.reload()');
                return;
            }
        }

        try {
            DB::beginTransaction();

            $handled = $handler ? $handler->delete($this->routePrefix, $this->recordId, $this->action) : null;

            if ($handled === null) {
                // Default behavior
                if ($this->action === 'delete') {
                    DB::table($this->routePrefix)->where('id', $this->recordId)->delete();
                    $log->info("*DELETE* ID [{$this->recordId}] da [{$this->routePrefix}]", __FILE__, __LINE__);
                    session()->flash('message', "Record [ID: {$this->recordId}] eliminato con successo");
                    session()->flash('alert', 'success');
                } elseif ($this->action === 'disable') {
                    DB::table($this->routePrefix)->where('id', $this->recordId)->update(['is_enabled' => 0]);
                    $log->info("*DISABLE* ID [{$this->recordId}] da [{$this->routePrefix}]", __FILE__, __LINE__);
                    session()->flash('message', "Record [ID: {$this->recordId}] disabilitato con successo");
                    session()->flash('alert', 'success');
                }
            } else {
                $actionLabel = $this->action === 'delete' ? 'eliminato' : 'disabilitato';
                session()->flash('message', "Record [ID: {$this->recordId}] {$actionLabel} con successo");
                session()->flash('alert', 'success');
            }

            DB::commit();

            if ($handler) {
                $handler->afterDelete($this->routePrefix, $this->recordId, $this->action);
            }
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('message', 'Errore: ' . $e->getMessage());
            session()->flash('alert', 'danger');
        }

        $this->cancel();
        $this->js('window.location.reload()');
    }

    public function render()
    {
        return view('ict::livewire.delete-confirm');
    }
}
