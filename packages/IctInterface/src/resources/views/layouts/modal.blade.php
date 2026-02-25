<div class="modal fade" id="{{$itemChildFormData->id_modal}}" tabindex="-1" aria-labelledby="{{$itemChildFormData->id_modal}}Label" aria-hidden="true">
    <div class="modal-dialog"  style="max-width:{{$itemChildFormData->modal_width}}">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="{{$itemChildFormData->name}}">{{$itemChildFormData->title}}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form method="{{$itemChildFormData->method}}" id="{{$itemChildFormData->id_modal}}_form">
            @csrf
            <div class="modal-body">
            </div>
            <div class="modal-footer clearbox">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
            <button type="button" class="btn btn-success" id="saveModalData">Salva</button>
            </div>
        </form>
      </div>
    </div>
  </div>