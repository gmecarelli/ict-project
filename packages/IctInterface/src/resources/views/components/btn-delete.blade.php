<td>
    @if($class === 'cancel')
        <button
            onclick="Livewire.dispatch('confirm-disable', { recordId: {{trim(strip_tags($id))}} })"
            id="BtnDel_{{trim(strip_tags($id))}}"
            title="Disabilita"
            class="btn btn-warning btn-sm"
            type="button">
                <i class="fas fa-ban"></i>
        </button>
    @else
        <button
            onclick="Livewire.dispatch('confirm-delete', { recordId: {{trim(strip_tags($id))}} })"
            id="BtnDel_{{trim(strip_tags($id))}}"
            title="Elimina"
            class="btn btn-danger btn-sm"
            type="button">
                <i class="fas fa-trash"></i>
        </button>
    @endif
</td>
