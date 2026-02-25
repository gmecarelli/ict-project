<div>
    @if(count($fields) > 0)
    <div class="card mb-0 col-sm-12">
        <div class="card-body">
            <form wire:submit="submit">
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

                <div class="mt-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter"></i> {{ $submitLabel }}
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="resetFilters">
                        <i class="fas fa-times"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
