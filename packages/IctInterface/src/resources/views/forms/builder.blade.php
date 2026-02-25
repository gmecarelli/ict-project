@php
/**
 * Vista per la creazione dei form standard su pagina.
 * Comprende il form stesso e lista dei record di eventuali child table.
 *
 * Supporta due modalità:
 * - $useLivewireForm = true  → usa il componente Livewire ict-editable-form (default)
 * - $useLivewireForm = false → fallback legacy (richiede kris/laravel-form-builder)
 */
@endphp

@extends('ict::layouts.app')


@section('content')
<div class="row col-md-10">
        <div class="col-md-12">
                <div class="card" style="margin-top: 30px;" id="app">
                        <div class="card-header"><x-ict-title-form /></div>
                        @if(isset($warning) && $warning)
                                <div class="alert alert-warning">
                                        <span class="p-2 font-weight-bolder"><i class="fas fa-exclamation-triangle"></i> {{ $warning }}</span>
                                </div>
                        @endif

                        @if(isset($useLivewireForm) && $useLivewireForm)
                                {{-- Nuovo: Livewire editable form --}}
                                <div class="card-body">
                                        @livewire('ict-editable-form', [
                                                'reportId' => $reportId,
                                                'recordId' => $recordId ?? null,
                                                'tableName' => $tableName ?? null,
                                        ])
                                </div>
                        
                        @else
                                {{-- Nessun sistema form disponibile --}}
                                <div class="card-body">
                                        <div class="alert alert-danger">
                                                Form non disponibile. Utilizzare la modalità Livewire.
                                        </div>
                                </div>
                        @endif
                </div>


        </div>
</div>

@endsection

@section('footer')
  @parent
  @if(!isset($useLivewireForm) || !$useLivewireForm)
    @if(isset($id_child) && $id_child)
        @include('ict::layouts.modal-item')
        @if(isset($itemChildFormData))
                @include('ict::layouts.modal')
        @endif
    @endif
  @endif

@endsection
