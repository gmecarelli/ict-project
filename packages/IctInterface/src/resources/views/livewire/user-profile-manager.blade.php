<div>
    {{-- Pulsante apertura modale --}}
    <button type="button" wire:click="openModal" class="btn btn-warning p-2 border">
        <i class="fas fa-coins"></i> <strong>Aggiungi utenti al profilo</strong>
    </button>

    {{-- Modale Bootstrap 5 controllata da Alpine.js --}}
    <div
        x-data="{ show: @entangle('showModal') }"
        x-show="show"
        x-transition.opacity
        class="modal fade"
        :class="{ 'show d-block': show }"
        tabindex="-1"
        @keydown.escape.window="$wire.closeModal()"
        style="display: none;"
    >
        <div class="modal-dialog" style="max-width:90%">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Lista degli utenti</h5>
                    <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if(session()->has('modal_error'))
                        <div class="alert alert-danger">{{ session('modal_error') }}</div>
                    @endif

                    {{-- Filtro ricerca --}}
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <label class="form-label">Utente</label>
                            <input type="text"
                                wire:model="searchName"
                                wire:keydown.enter="searchUsers"
                                class="form-control"
                                placeholder="Cerca per nome...">
                        </div>
                        <div class="col-sm-3 d-flex align-items-end">
                            <button type="button" wire:click="searchUsers" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filtra
                            </button>
                        </div>
                    </div>

                    {{-- Tabella utenti --}}
                    @if(count($users) > 0)
                        <div class="overflow-auto" style="max-height: 400px;">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nome</th>
                                        <th>Username</th>
                                        <th style="width: 50px;"><i class="fas fa-check"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>{{ $user['id'] }}</td>
                                            <td>{{ $user['name'] }}</td>
                                            <td>{{ $user['email'] }}</td>
                                            <td>
                                                <input type="checkbox"
                                                    wire:model="selectedUserIds"
                                                    value="{{ $user['id'] }}"
                                                    class="form-check-input">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">Nessun utente trovato.</p>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Annulla</button>
                    <button type="button" class="btn btn-success" wire:click="saveUsers">
                        <i class="fas fa-save"></i> Salva
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Backdrop --}}
    <div
        x-data="{ show: @entangle('showModal') }"
        x-show="show"
        x-transition.opacity
        class="modal-backdrop fade"
        :class="{ 'show': show }"
        style="display: none;"
        @click="$wire.closeModal()"
    ></div>
</div>
