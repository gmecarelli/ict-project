@extends('ict::layouts.app')

@section('content')

  @if(!empty($report['multicheck_reference']))
  <script>
      document.addEventListener('alpine:init', () => {
          Alpine.data('multicheckState', () => ({
              selectedIds: [],
              allIds: {!! json_encode(collect($data)->pluck('id')->map(fn($id) => trim(strip_tags((string) $id)))->values()->toArray()) !!},
              get allChecked() { return this.allIds.length > 0 && this.selectedIds.length === this.allIds.length },
              toggleAll() { this.allChecked ? this.selectedIds = [] : this.selectedIds = [...this.allIds] },
              executeAction(index) { Livewire.dispatch('execute-multicheck-action', { actionIndex: index, selectedIds: this.selectedIds }) }
          }));
      });
  </script>
  @endif

  <div class="col-md-10" @if(!empty($report['multicheck_reference'])) x-data="multicheckState()" @endif>
    <x-ict-title-page :count=$count titlePage="{{$report['title']}}" />
    @if(Session::has('message'))
      <div class="alert alert-{{session()->get('alert')}}">{!! session()->get('message') !!}</div>
    @endif

    @if(!empty($report['multicheck_reference']))
      {{-- Toolbar multicheck: Seleziona tutto + Dropdown azioni (Alpine.js) --}}
      {{-- Sostituisce @include('ict::multiselect.dropdown') e multiselect-js.blade.php (jQuery AJAX) --}}
      <div class="d-flex gap-2 mb-3">
          <button class="btn btn-light border border-dark p-2" title="Seleziona/Deseleziona tutto" @click="toggleAll()">
              <template x-if="allChecked">
                  <span><i class="far fa-check-square"></i> Deseleziona tutto</span>
              </template>
              <template x-if="!allChecked">
                  <span><i class="fas fa-check-square"></i> Seleziona tutto</span>
              </template>
          </button>

          @if(isset($dropdown) && $dropdown)
              <div class="btn-group">
                  <button class="btn btn-secondary p-2 dropdown-toggle" type="button"
                          data-bs-toggle="dropdown" aria-expanded="false">
                      Azioni massive
                  </button>
                  <div class="dropdown-menu">
                      @foreach($dropdown->dropItems as $i => $action)
                          @if(is_null($action['route']))
                              <a class="dropdown-item" href="#"
                                 @click.prevent="executeAction({{ $i }})">
                                  {{ $action['label'] }}
                              </a>
                          @elseif(preg_match('/^MODAL/', $action['route']))
                              <a class="dropdown-item"
                                 data-bs-toggle="modal"
                                 data-bs-target="#{{ substr($action['route'], 6) }}">
                                  {{ $action['label'] }}
                              </a>
                          @else
                              <a class="dropdown-item"
                                 href="{{ route($action['route']) }}?report={{ $report['id'] }}">
                                  {{ $action['label'] }}
                              </a>
                          @endif
                      @endforeach
                  </div>
              </div>
          @endif
      </div>
    @endif
    
    @if($report['has_create_button'])
      <div class="col-sm-12">
        <x-ict-btn-create  label="Inserisci nuovo" has="{{$report['has_create_button']}}" route="{{$report['route']}}"/>
      </div>
    @endif
    
      <table class="table table-striped table-hover @if(count($cols) > 12) table-responsive table-max-content @endif">
          <thead>
            <tr>
              @if(!empty($report['multicheck_reference']))
                  <th><i class="fa fa-check"></i></th>
              @endif
                @if($report['is_editable'] == 1)
                  <th><i class="fa fa-edit"></i></th>
                  
    
                  @if($report['has_edit_button'] )
                
                    <th><i class="fas fa-ban"></i></th>
                  @endif
                @endif
              @include('ict::layouts.sort-header-cols')
            </tr>
          </thead>
          <tbody>
          
              @foreach($data as $record)
                  <tr>
                    @if(!empty($report['multicheck_reference']))
                        <td style="display: inline-block">
                            {{-- Checkbox multicheck gestito da Alpine.js (sostituisce <x-ict-multi-checkbox>) --}}
                            <input type="checkbox"
                                value="{{ trim(strip_tags($record['id'])) }}"
                                x-model="selectedIds"
                                class="form-check-input m-auto" />
                        </td>
                    @endif
                    @if($report['is_editable'] == 1)
                    <x-ict-btn-edit label="" has="{{$report['has_edit_button']}}" route="{{$report['route']}}/{{trim(strip_tags($record['id']))}}/edit"  id="{{trim(strip_tags($record['id']))}}"/>
                    @if($report['has_edit_button'] )
                      <x-ict-btn-delete label="" has="{{$report['has_edit_button']}}" route="{{$report['route']}}" id="{{trim(strip_tags($record['id']))}}" class="{{$report['class_delete_button']}}" />
                    @endif
                    @endif
                    @foreach ($record as $value)
                    <td><span title="{!! trim(strip_tags($value)) !!}">@if(Str::length(trim(strip_tags($value))) > 40) {!! Str::of(strip_tags($value))->words(4, '...') !!} @else {!! $value !!} @endif</span></td>
                    @endforeach
                  </tr> 
              @endforeach
          </tbody>
        </table>

    <div class="col-md-12 clearbox d-flex d-flex-row justify-content-end mt-4 mb-4">
      <x-ict-pagination :pages="$pages" />
    </div>
    <div class="col-md-12 clearbox d-flex d-flex-row bg-light border p-0">
      <div class="btn-group mr-1">
        <x-ict-btn-export  label="Esporta in XLSX" format="xlsx" route="{{$report['route']}}" />
         <!--<a title="Esporta in XLSX" href="/order/create?report=4" class="btn btn-light p-2 border"><i class="fas fa-download"></i> Esporta in XLSX</a>-->
      </div> 
      @if($report['has_create_button'])
      
        <div class="btn-group mr-1">
          <x-ict-btn-create  label="Inserisci nuovo" has="{{$report['has_create_button']}}" route="{{$report['route']}}"/>
        </div>      
      @endif
    </div>
    <div class="col-md-12 clearbox" id="contentFilters">
      @if(isset($useNewFilters) && $useNewFilters && isset($reportId))
        @livewire('ict-filter-form', ['reportId' => $reportId])
      @elseif($filters)
        {!! form($filters) !!}

        {{-- Auto-copy data fatturazione: vanilla JS (sostituisce jQuery) --}}
        <script>
          document.addEventListener('DOMContentLoaded', function() {
              var fromField = document.getElementById('whereDate-ue_billing_from');
              if (fromField) {
                  fromField.addEventListener('change', function() {
                      var toField = document.getElementById('whereDate-de_billing_to');
                      if (toField) toField.value = fromField.value;
                  });
              }
          });
        </script>
      @endif
    </div>

  </div>

  {{-- Livewire: modale conferma eliminazione/disabilitazione --}}
  @if($report['has_edit_button'])
    @livewire('ict-delete-confirm', ['routePrefix' => $report['route'], 'modalSize' => 'modal-lg'], key('delete-confirm-' . $report['route']))
  @endif

  {{-- Livewire: gestore azioni massive multicheck (sostituisce multiselect-js.blade.php jQuery AJAX) --}}
  @if(!empty($report['multicheck_reference']))
    @livewire('ict-multicheck-manager', ['reportId' => $report['id']], key('multicheck-manager-' . $report['id']))
  @endif

  {{-- Livewire: gestore switch boolean inline (sostituisce jQuery .boolswitch + $.ajax PUT switch.update) --}}
  @livewire('ict-bool-switch', [], key('bool-switch-' . $report['id']))

@endsection

{{-- Footer jQuery boolswitch rimosso — sostituito da BoolSwitchComponent Livewire (toggle-bool-switch) --}}