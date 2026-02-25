<?php

/**
 * MulticheckManagerComponent
 *
 * Componente Livewire per l'esecuzione delle azioni bulk sulla selezione multipla
 * tramite checkbox nella tabella report.
 * Sostituisce multiselect-js.blade.php (jQuery AJAX) e dropdown.blade.php (jQuery dropdown).
 *
 * La gestione dello stato dei checkbox avviene lato client con Alpine.js.
 * Questo componente riceve gli ID selezionati e esegue l'azione bulk server-side.
 *
 * Uso in report.blade.php:
 *   @livewire('ict-multicheck-manager', ['reportId' => $report['id']])
 *
 * Dispatch da Alpine.js:
 *   Livewire.dispatch('execute-multicheck-action', { actionIndex: 0, selectedIds: [1,2,3] })
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Livewire;

use Exception;
use Livewire\Component;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Packages\IctInterface\Controllers\Services\Logger;

class MulticheckManagerComponent extends Component
{
    public int $reportId;

    protected $listeners = [
        'execute-multicheck-action' => 'executeAction',
    ];

    public function mount(int $reportId): void
    {
        $this->reportId = $reportId;
    }

    /**
     * Esegue un'azione bulk sugli ID selezionati.
     * Chiamato tramite Livewire.dispatch da Alpine.js.
     */
    public function executeAction(int $actionIndex, array $selectedIds): void
    {
        $log = new Logger();

        if (empty($selectedIds)) {
            session()->flash('message', 'Non ci sono righe selezionate');
            session()->flash('alert', 'warning');
            $this->js('window.location.reload()');
            return;
        }

        $dropItems = session()->get('drop_items', []);
        $dropItem = Arr::get($dropItems, $actionIndex);

        if (!$dropItem) {
            return;
        }

        // Se l'azione ha una route, redirect
        $route = Arr::get($dropItem, 'route');
        if (!is_null($route)) {
            session()->forget('drop_items');
            $this->redirect(route($route) . '?report=' . $this->reportId);
            return;
        }

        // Esegui update bulk
        $table = Arr::get($dropItem, 'table');
        $set = (array) json_decode(Arr::get($dropItem, 'set', '{}'));
        $where = json_decode(Arr::get($dropItem, 'where', 'null'));
        $where = is_null($where) ? null : (array) $where;

        if (!$table || empty($set)) {
            return;
        }

        DB::beginTransaction();

        try {
            $query = DB::table($table)->whereIn('id', $selectedIds);

            if (!is_null($where)) {
                foreach ($where as $field => $value) {
                    if (preg_match('/,/', $value)) {
                        $query->whereIn($field, explode(',', $value));
                    } elseif ($value === 'null') {
                        $query->whereNull($field);
                    } elseif ($value === 'not null') {
                        $query->whereNotNull($field);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }

            $counter = $query->update($set);
            DB::commit();

            $log->info("MULTICHECK bulk update: {$counter} record aggiornati su [{$table}]", __FILE__, __LINE__);

            session()->forget('drop_items');

            session()->flash('message', "Aggiornate {$counter} record con successo");
            session()->flash('alert', 'success');
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('message', 'Errore: ' . $e->getMessage());
            session()->flash('alert', 'danger');
        }

        $this->js('window.location.reload()');
    }

    public function render()
    {
        return <<<'HTML'
        <div></div>
        HTML;
    }
}
