<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gestione Allegati</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    @if(session('attach_message'))
                        <div class="alert alert-success alert-sm">{{ session('attach_message') }}</div>
                    @endif

                    {{-- Form upload --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="file" wire:model="file" class="form-control">
                            @error('file') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-4">
                            <input type="text" wire:model="description" class="form-control"
                                   placeholder="Descrizione (opzionale)">
                        </div>
                        <div class="col-md-2">
                            <button wire:click="upload" class="btn btn-primary w-100"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="upload">Carica</span>
                                <span wire:loading wire:target="upload">...</span>
                            </button>
                        </div>
                    </div>

                    {{-- Lista allegati --}}
                    @if(count($attachments) > 0)
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Nome file</th>
                                <th>Descrizione</th>
                                <th>Ext</th>
                                <th>Data</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attachments as $att)
                            <tr>
                                <td>
                                    <a href="{{ asset('storage/' . $att['path'] . '/' . $att['file_name_server']) }}"
                                       target="_blank">
                                        {{ $att['file_name_original'] }}
                                    </a>
                                </td>
                                <td>{{ $att['description'] ?? '-' }}</td>
                                <td>{{ $att['ext'] }}</td>
                                <td>{{ \Carbon\Carbon::parse($att['created_at'])->format('d/m/Y H:i') }}</td>
                                <td>
                                    <button wire:click="deleteAttachment({{ $att['id'] }})"
                                            wire:confirm="Eliminare questo allegato?"
                                            class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-muted">Nessun allegato presente.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
