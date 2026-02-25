<div class="mt-3">
    @if($childFormTitle)
        <h6 class="border-bottom pb-2 mb-3">{{ $childFormTitle }}</h6>
    @endif

    @if(session()->has('child_message'))
        <div class="alert alert-{{ session('child_alert', 'info') }} alert-dismissible fade show" role="alert">
            {{ session('child_message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tabella items esistenti (usa report columns per le intestazioni) --}}
    @if(count($existingItems) > 0)
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    @if(!empty($reportColumns))
                        @foreach($reportColumns as $col)
                            <th>{{ $col['label'] ?? $col['field'] }}</th>
                        @endforeach
                    @else
                        @foreach($childFields as $field)
                            @if($field['type'] !== 'hidden')
                                <th>{{ $field['label'] }}</th>
                            @endif
                        @endforeach
                    @endif
                    <th style="width: 50px;"><i class="fas fa-edit"></i></th>
                    <th style="width: 50px;"><i class="fas fa-trash"></i></th>
                </tr>
            </thead>
            <tbody>
                @foreach($existingItems as $item)
                    <tr>
                        @if(!empty($reportColumns))
                            @foreach($reportColumns as $col)
                                <td>{!! $item[$col['field']] ?? '' !!}</td>
                            @endforeach
                        @else
                            @foreach($childFields as $field)
                                @if($field['type'] !== 'hidden')
                                    <td>{{ $item[$field['name']] ?? '' }}</td>
                                @endif
                            @endforeach
                        @endif
                        <td>
                            <button type="button"
                                class="btn btn-primary btn-sm"
                                wire:click="$dispatch('open-child-modal', { recordId: {{ $item['id'] }} })"
                                title="Modifica"
                            >
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                        <td>
                            <button type="button"
                                class="btn btn-danger btn-sm"
                                wire:click="$dispatch('confirm-child-delete', { recordId: {{ $item['id'] }} })"
                                title="Elimina"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Nuovi items da aggiungere --}}
    @if(count($items) > 0)
        <div class="border rounded p-3 mb-3 bg-light">
            @foreach($items as $index => $item)
                <div class="row mb-2 align-items-end border-bottom pb-2">
                    @foreach($childFields as $field)
                        @if($field['type'] === 'hidden')
                            <input type="hidden"
                                wire:model="items.{{ $index }}.{{ $field['name'] }}"
                            >
                        @else
                            <div class="col-sm-{{ $field['bootstrap_cols'] ?? 3 }} mb-2">
                                <x-ict-dynamic-field
                                    :field="$field"
                                    :value="$items[$index][$field['name']] ?? null"
                                    :wireModel="'items.' . $index . '.' . $field['name']"
                                />
                            </div>
                        @endif
                    @endforeach
                    <div class="col-sm-1 mb-2">
                        <button type="button"
                            class="btn btn-outline-danger btn-sm"
                            wire:click="removeItem({{ $index }})"
                            title="Rimuovi riga"
                        >
                            <i class="fas fa-minus-circle"></i>
                        </button>
                    </div>
                </div>
            @endforeach

            <div class="mt-2">
                <button type="button" class="btn btn-success btn-sm" wire:click="saveItems">
                    <i class="fas fa-save"></i> Salva items
                </button>
            </div>
        </div>
    @endif

    {{-- Pulsante aggiungi --}}
    @if($parentRecordId)
        <button type="button" class="btn btn-light text-success border btn-sm" wire:click="addItem">
            <i class="fas fa-plus-circle"></i>
            <span class="text-dark"><strong>Aggiungi nuovo</strong></span>
        </button>
    @else
        <div class="alert alert-info mt-2">
            <small><i class="fas fa-info-circle"></i> Salva prima il record principale per poter aggiungere items.</small>
        </div>
    @endif

    {{-- Modale edit per i record child --}}
    @if($childFormId)
        @livewire('ict-modal-form', ['formId' => $childFormId], key('child-modal-' . $childFormId))
    @endif

    {{-- Modale conferma eliminazione --}}
    @if($childTableName)
        @livewire('ict-delete-confirm', ['routePrefix' => $childTableName], key('child-delete-' . $childTableName))
    @endif
</div>
