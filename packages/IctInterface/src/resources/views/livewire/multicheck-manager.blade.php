{{--
    DEPRECATO: questa vista non è più utilizzata.

    Il componente MulticheckManagerComponent usa render inline (<div></div>)
    e funziona come listener-only per l'evento 'execute-multicheck-action'.

    La toolbar (Seleziona tutto + Dropdown azioni) è ora gestita direttamente
    in report.blade.php tramite Alpine.js.

    I checkbox nella tabella report sono gestiti da Alpine.js (x-model="selectedIds").
    L'azione bulk viene inviata al componente Livewire via:
        Livewire.dispatch('execute-multicheck-action', { actionIndex, selectedIds })
--}}
