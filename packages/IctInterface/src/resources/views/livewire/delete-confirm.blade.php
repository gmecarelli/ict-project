<div>
    {{-- Bootstrap 5.3 Modal di conferma eliminazione/disabilitazione --}}
    <div
        x-data="{ show: @entangle('showConfirm') }"
        x-show="show"
        x-transition.opacity
        class="modal fade"
        :class="{ 'show d-block': show }"
        tabindex="-1"
        @keydown.escape.window="$wire.cancel()"
        style="display: none;"
    >
        <div class="modal-dialog {{ $modalSize }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if($action === 'delete')
                            <i class="fas fa-exclamation-triangle text-danger"></i> Conferma eliminazione
                        @else
                            <i class="fas fa-exclamation-triangle text-warning"></i> Conferma disabilitazione
                        @endif
                    </h5>
                    <button type="button" class="btn-close" wire:click="cancel" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($action === 'delete')
                        <p>Sei sicuro di voler <strong>eliminare</strong> il record <strong>[ID: {{ $recordId }}]</strong>?</p>
                        <p class="text-danger"><small>Questa azione non e' reversibile.</small></p>
                    @else
                        <p>Sei sicuro di voler <strong>disabilitare</strong> il record <strong>[ID: {{ $recordId }}]</strong>?</p>
                        <p class="text-muted"><small>Il record non sara' eliminato dal database.</small></p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="cancel">Annulla</button>
                    <button type="button"
                        class="btn {{ $action === 'delete' ? 'btn-danger' : 'btn-warning' }} btn-sm"
                        wire:click="execute"
                    >
                        @if($action === 'delete')
                            <i class="fas fa-trash"></i> Elimina
                        @else
                            <i class="fas fa-ban"></i> Disabilita
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Backdrop --}}
    <div
        x-data="{ show: @entangle('showConfirm') }"
        x-show="show"
        x-transition.opacity
        class="modal-backdrop fade"
        :class="{ 'show': show }"
        style="display: none;"
        @click="$wire.cancel()"
    ></div>
</div>
