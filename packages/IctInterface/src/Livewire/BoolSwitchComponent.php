<?php

namespace Packages\IctInterface\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

/**
 * BoolSwitchComponent — Componente Livewire listener-only per switch boolean nei report.
 * Sostituisce il jQuery inline (.boolswitch on change → $.ajax PUT switch.update).
 *
 * Riceve l'evento globale 'toggle-bool-switch' (da onchange in ApplicationService::_switch()),
 * aggiorna il DB e mostra un feedback temporaneo nel browser.
 */
class BoolSwitchComponent extends Component
{
    #[On('toggle-bool-switch')]
    public function toggle(int $id, string $field, string $table, int $value): void
    {
        DB::table($table)->where('id', $id)->update([$field => $value]);

        $cssClass = $value ? 'text-success' : 'text-danger';
        $label = $value ? 'On' : 'Off';

        $this->js(<<<JS
            (function() {
                var el = document.getElementById('switch-{$id}');
                if (el) {
                    var msg = document.createElement('div');
                    msg.className = 'text-muted';
                    msg.innerHTML = '<span class="{$cssClass}">{$label}</span>';
                    el.appendChild(msg);
                    setTimeout(function() { msg.remove(); }, 1500);
                }
            })();
        JS);
    }

    public function render()
    {
        return <<<'HTML'
        <div></div>
        HTML;
    }
}
