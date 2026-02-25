@php
/**
 * Vista per il form item-child (fatturazione ordini/DRS).
 * Include tabella items con pulsanti modali per modifica e fatturazione.
 *
 * Supporta due modalità:
 * - $useLivewireForm = true  → usa il componente Livewire ict-editable-form
 * - $useLivewireForm = false → fallback legacy (richiede kris/laravel-form-builder)
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
                                </div>
                        @elseif(class_exists('Kris\LaravelFormBuilder\Form'))
                                {{-- Legacy: kris/laravel-form-builder (solo se il package è ancora installato) --}}
                                <div class="card-body">
                                        @php $id_child = $form->formService->form_properties->id_child; @endphp

                                        {!! form_start($form) !!}
                                        {!! form_until($form, 'id') !!}


                                        @if($id_child)
                                        <div class="clearbox card-header">
                                                <button class="btn bth-light text-success" id="addChildForm" type="button">
                                                        <i class="fas fa-plus-circle"></i> <span class="text-dark"><strong>Aggiungi
                                                                @if (session()->get('reportData')['route']=='report')
                                                                        Colonna
                                                                @else
                                                                        Item
                                                                @endif</strong></span>
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
                                                                        @if (!is_null($itemChildFormData))
                                                                                <th><i class="fas fa-coins"></i></th>
                                                                        @endif
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
                                                                                <button type="button" id="BtnEdit_{{trim(strip_tags($id_item))}}" title="Modifica" data-report="{{$itemFormData->report_id}}" data-form_id="{{$itemFormData->id_child}}" data-bs-toggle="modal" data-bs-target="#{{$itemFormData->id_modal}}" class="btn btn-primary"><i class="fa fa-edit"></i></button>
                                                                        </td>
                                                                        @if (!is_null($itemChildFormData))
                                                                        <td>
                                                                                @if (trim(strip_tags($record['status']))=='Fatturabile')
                                                                                <button type="button" id="BtnAdd_{{trim(strip_tags($id_item))}}" title="Inserisci nel DRS - Da fatturare" data-report="{{$itemChildFormData->report_id}}" data-bs-toggle="modal" data-bs-target="#{{$itemChildFormData->id_modal}}" class="btn btn-success btnAdd"><i class="fa fa-coins"></i></button>
                                                                                @elseif(trim(strip_tags($record['status']))=='Parziale')
                                                                                <button type="button" id="BtnAdd_{{trim(strip_tags($id_item))}}" title="Inserisci nel DRS - Parzialmente fatturato" data-report="{{$itemChildFormData->report_id}}" data-bs-toggle="modal" data-bs-target="#{{$itemChildFormData->id_modal}}" class="btn btn-warning btnAdd"><i class="fa fa-coins"></i></button>
                                                                                @endif

                                                                        </td>
                                                                        @endif
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
    <!-- HTML della model degli item -->
    @include('ict::layouts.modal-item')

      <script>
        {{-- @include('ict::layouts.form-child-js') --}} {{-- Sostituito da Livewire ChildFormComponent --}}

         {{-- @include('ict::layouts.modal-js') --}} {{-- Sostituito da Livewire ModalFormComponent --}}

      </script>
    @endif

    @if (isset($itemChildFormData) && $itemChildFormData)
        <!-- html della modale dell'item-child -->
        @include('ict::layouts.modal')

      <script>
        /**
         * Carica form a Modale per itemChild
         */

         $("document").ready(function() {

                  var _token = '{{csrf_token()}}';
                  var url = '{{route($itemChildFormData->url_load)}}';
                  var formItemChildID = {{$itemChildFormData->id}};

                  $('#{{$itemChildFormData->id_modal}}').on("show.bs.modal", function(e){
                    var clickedButton = e.relatedTarget;
                    var idItemParent = clickedButton.id.replace('BtnAdd_','');
                    console.log("ID ITEM PARENT: ["+idItemParent+"]");

                    var reportId = $('#'+clickedButton.id).data('report');
                    console.log("REPORT ID: ["+reportId+"]");

                    var req = { 'report':reportId, 'formId': formItemChildID, '_token': _token, 'idItemParent': idItemParent };

                    $.ajax(url, {
                        method: 'GET',
                        data: req,
                        complete: function(response){

                                console.log(response.responseJSON.result);
                                if(response.responseJSON.result == 'success') {
                                    $("#{{$itemChildFormData->id_modal}} .modal-body").empty();
                                    $("#{{$itemChildFormData->id_modal}} .modal-body").html(response.responseJSON.html);
                                } else {
                                    $("#{{$itemChildFormData->id_modal}} .modal-body").html("Non è possibile caricare il form");
                                }
                        }
                    });
                  });

         /** FINE Aggiungi form a Modal **/

         /**
         * Salva dati da Modale per itemChild
         */
                  $("#{{$itemChildFormData->id_modal}} #saveModalData").on("click", function(){
                            var url = '{{route($itemChildFormData->url_save)}}';
                            var req = {
                                    'month': $("#month").val(),
                                    'year': $("#year").val(),
                                    'quantity': $("#quantity").val(),
                                    'price_unit': $("#price_unit").val(),
                                    'report': $("input[name='report']").val(),
                                    'order_item_id': $("input[name='order_item_id']").val(),
                                    'drs_id': $("input[name='drs_id']").val(),
                                    '_token': '{{csrf_token()}}',
                                    'form_id': $("input[name='form_id']").val()
                            };
                            console.log(req);
                          $.ajax(url, {
                                  method: 'POST',
                                  data: req,
                                  complete: function(response){
                                    console.log(response);
                                    alert(response.responseJSON.message);

                                    if(response.responseJSON.result == 'success') {
                                            location.reload();
                                    }
                                  }
                          });
                  });
          });
         /** FINE Salva dati da Modale **/
      </script>
    @endif
  @endif
@endsection
