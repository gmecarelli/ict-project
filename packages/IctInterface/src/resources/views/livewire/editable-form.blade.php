<div>
    @if(session()->has('message'))
        <div class="alert alert-{{ session('alert', 'info') }}">
            {{ session('message') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form wire:submit="submit">
        <div class="row">
            @foreach($fields as $field)
                @if($field['type'] === 'hidden')
                    <input type="hidden"
                        wire:model="formData.{{ $field['name'] }}"
                        value="{{ $formData[$field['name']] ?? '' }}"
                    >
                @elseif($field['type'] === 'file')
                    <div class="col-sm-{{ $field['bootstrap_cols'] ?? 3 }} mb-3">
                        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
                        <input type="file"
                            class="form-control @error('fileUploads.' . $field['name']) is-invalid @enderror"
                            wire:model="fileUploads.{{ $field['name'] }}"
                            id="{{ $field['name'] }}"
                        >
                        @if(isset($formData[$field['name']]) && $formData[$field['name']])
                            <small class="text-muted">File attuale: {{ $formData[$field['name']] }}</small>
                        @endif
                        @error('fileUploads.' . $field['name'])
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
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
            @if($redirectUrl)
                <a href="{{ $redirectUrl }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Annulla
                </a>
            @endif
        </div>
    </form>

    @if($hasChild && $recordId)
        @livewire('ict-child-form', [
            'parentFormId' => $formId,
            'parentRecordId' => $recordId,
            'childFormId' => $editableChildFormId,
        ])
    @endif
</div>
