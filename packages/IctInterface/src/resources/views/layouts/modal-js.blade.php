/** 
     * Carica form a Modale per modifica child
     */
    
     $("document").ready(function() {
              $('#{{$itemFormData->id_modal}}').on("show.bs.modal", function(e){
                var _token = '{{csrf_token()}}';
                var url = '{{route($itemFormData->url_load)}}';
                var formItemChildID = {{$itemFormData->id}};
                console.log("FORM ID: ["+formItemChildID+"]");

                var clickedButton = e.relatedTarget;
                var id = clickedButton.id.replace('BtnEdit_','');
                
                console.log("ID ITEM EDIT: ["+id+"]");
                
                var reportId = $('#'+clickedButton.id).data('report');
                var foreignName = $('#'+clickedButton.id).data('foreign');
                var foreignVal = $('#'+clickedButton.id).data('foreign-val');
                console.log("REPORT ID: ["+reportId+"]");
                console.log("FOREIGN VAL: ["+foreignVal+"]");
                console.log("FOREIGN Name: ["+foreignName+"]");
                
                var req = {
                        'report':reportId,
                        'formId': formItemChildID,
                        '_token': _token,
                        'id': id
                        };

                console.log(req);
                $.ajax(url, {
                    method: 'GET',
                    data: req, 
                    complete: function(response){
                        if(response.responseJSON.result == 'success') {
                        $("#{{$itemFormData->id_modal}} .modal-body").empty();
                        $("#{{$itemFormData->id_modal}} .modal-body").html(response.responseJSON.html);
                        } else {
                        $("#{{$itemFormData->id_modal}} .modal-body").html("Non è possibile caricare il form");
                        }
                    }
                });
              });
      
     /** FINE Aggiungi form a Modal **/

     /** 
     * Salva dati da Modale
     */
              $("#{{$itemFormData->id_modal}} #saveModalData").on("click", function(){
                var url = '{{route($itemFormData->url_save)}}';
                var req = $("#{{$itemFormData->id_modal}}_form").serialize();
                console.log(req);
                $.ajax(url, {
                        method: 'POST',
                        data: req, 
                        complete: function(response){
                                console.log(response);
                                alert(response.responseJSON.message);
                                
                                if(response.responseJSON.result == 'success') {
                                        location.reload();
                                } else if(response.responseJSON.result == 'close') {
                                        $("#{{$itemFormData->id_modal}} #saveModalData").empty();
                                        $("#{{$itemFormData->id_modal}} #saveModalData").hide();
                                }
                        }
                });
              });
      });
     /** FINE Salva dati da Modale **/