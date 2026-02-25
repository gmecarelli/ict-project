<div>
    {{-- Bootstrap 5.3 Modal controllato da Alpine.js (incluso in Livewire 3) --}}
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
        <div class="modal-dialog" @if($modalWidth) style="max-width: {{ $modalWidth }}" @endif>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $modalTitle ?? 'Form' }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                </div>

                <form wire:submit="submit">
                    <div class="modal-body">
                        @if(session()->has('modal_error'))
                            <div class="alert alert-danger">
                                {{ session('modal_error') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger">
                                @foreach($errors->all() as $error)
                                    <p class="mb-0">{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        <div class="row">
                            @foreach($fields as $field)
                                @if($field['type'] === 'hidden')
                                    <input type="hidden"
                                        wire:model="formData.{{ $field['name'] }}"
                                        value="{{ $formData[$field['name']] ?? '' }}"
                                    >
                                @else
                                    <div class="col-sm-{{ $field['bootstrap_cols'] ?? 3 }} mb-3">
                                        <x-ict-dynamic-field
                                            :field="$field"
                                            :value="$formData[$field['name']] ?? null"
                                            :wireModel="'formData.' . $field['name']"
                                        />
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Annulla</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Salva
                        </button>
                    </div>
                </form>
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
