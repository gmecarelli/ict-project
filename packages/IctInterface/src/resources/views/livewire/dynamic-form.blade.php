<div>
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

        <div class="mt-3">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> {{ $submitLabel }}
            </button>
        </div>
    </form>
</div>
