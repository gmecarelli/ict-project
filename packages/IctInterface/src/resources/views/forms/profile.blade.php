@php
/**
 * Vista per il form del profilo.
 * Include funzionalità specifica: "Aggiungi utenti al profilo"
 *
 * Supporta due modalità:
 * - $useLivewireForm = true  → usa il componente Livewire ict-editable-form
 * - $useLivewireForm = false → usa il vecchio kris/laravel-form-builder (legacy)
 */
@endphp

@extends('ict::layouts.app')


@section('content')
<div class="row col-md-10">
        <div class="col-md-12">
                <div class="card" style="margin-top: 30px;">
                        <div class="card-header"><x-ict-title-form /></div>
                        @if(session()->has('errors'))
                                <div class="alert alert-danger">
                                @foreach (session()->get('errors')->all() as $error)
                                        <p>{{ str_replace('items.','',$error) }}</p>
                                @endforeach
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

                                        {{-- Aggiungi utenti al profilo (Livewire) --}}
                                        @if(isset($profile_id) && $profile_id)
                                        <div class="clearbox card-header mt-3">
                                                @livewire('ict-user-profile-manager', ['profileId' => $profile_id])
                                        </div>
                                        @endif
                                </div>
                        @else
                                {{-- Legacy: kris/laravel-form-builder --}}
                                @if(class_exists('Kris\LaravelFormBuilder\Form'))
                                <div class="card-body">
                                        @php $id_child = $form->formService->form_properties->id_child; @endphp

                                        {!! form_start($form) !!}
                                        {!! form_until($form, 'id') !!}


                                        @if($id_child)
                                        <div class="clearbox card-header">
                                                <button class="btn bth-light text-success" id="addChildForm" type="button">
                                                        <i class="fas fa-plus-circle"></i> <span class="text-dark"><strong>Aggiungi
                                                                permesso profilo</strong></span>
                                                </button>

                                                <button type="button" id="btnAddUsers" data-bs-target="#modalAddUsers" data-bs-toggle="modal" class="btn btn-warning p-2 border">
                                                        <i class="fas fa-coins"></i> <strong>Aggiungi utenti al profilo</strong>
                                                </button>
                                        </div>


                                        <div id="childContainer" class="col-md-12 mt-2 mb-2 p-0">
                                                @if($itemsList)

                                                <table class="table table-striped">
                                                        <thead>
                                                                <tr>
                                                                        @foreach ($itemsList['cols'] as $col)
                                                                                <th>{{$col}}</th>
                                                                        @endforeach
                                                                        <th><i class="fas fa-edit"></i></th>
                                                                        <th><i class="fas fa-ban"></i></th>
                                                                </tr>

                                                        </thead>
                                                        <tbody>
                                                                @foreach ($itemsList['records'] as $record)
                                                                <tr>

                                                                        @foreach ($record as $key => $data)
                                                                        @if($key == 'id')
                                                                                @php $id_item = $data @endphp
                                                                        @endif
                                                                                <td>{!! $data !!}</td>
                                                                        @endforeach
                                                                        @if(isset($itemFormData))
                                                                        <td>
                                                                                @if (!is_null($itemFormData->id_modal))
                                                                                        <button type="button" id="BtnEdit_{{trim(strip_tags($id_item))}}" title="Modifica" data-report="{{$itemFormData->report_id}}" data-form_id="{{$itemFormData->id}}" data-bs-toggle="modal" data-bs-target="#{{$itemFormData->id_modal}}" class="btn btn-primary"><i class="fa fa-edit"></i></button>
                                                                                @else
                                                                                        <a title="Modifica" href="/{{$itemFormData->name}}/{{trim(strip_tags($id_item))}}/edit?report={{$itemFormData->report_id}}" class="btn btn-primary"><i class="fa fa-edit"></i></a>
                                                                                @endif

                                                                        </td>
                                                                        <td>
                                                                                <button
                                                                                    data-route="/{{ $itemFormData->name }}/{{ trim(strip_tags($id_item)) }}"
                                                                                    id="BtnDelRole_{{ trim(strip_tags($id_item)) }}" title="Elimina"
                                                                                    class="btn btn-danger destroy" type="button"><i
                                                                                        class="fa fa-ban"></i></button>
                                                                            </td>
                                                                        @endif
                                                                </tr>
                                                                @endforeach
                                                        </tbody>
                                                </table>

                                                @endif

                                        </div>
                                        @endif
                                        {!! form_end($form, true) !!}

                                </div>
                                @endif
                        @endif
                </div>


        </div>
</div>

@endsection

@section('footer')
  @parent
  {{-- @include("ict::layouts.modal-users") --}} {{-- Sostituito da Livewire ict-user-profile-manager --}}

  @if(!isset($useLivewireForm) || !$useLivewireForm)
    @if(isset($id_child) && $id_child)
        @include('ict::layouts.modal-item')
        @if(isset($itemChildFormData))
                @include('ict::layouts.modal')
                {{-- @include('ict::layouts.delete-js') --}} {{-- Sostituito da Livewire ict-delete-confirm --}}
        @endif

        <script>
            {{-- @if(isset($addChildRoute))
                    @include('ict::layouts.form-child-js')
            @endif --}} {{-- Sostituito da Livewire ChildFormComponent --}}

            {{-- @include('ict::layouts.modal-js') --}} {{-- Sostituito da Livewire ModalFormComponent --}}
        </script>
    @endif
  @endif
@endsection
