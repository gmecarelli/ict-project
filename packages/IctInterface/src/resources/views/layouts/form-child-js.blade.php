
/** 
* Aggiungi form child dal pulsante "+"
* Per abilitare questo codice nel controller devono essere presente un parametro
* @param $addChildRoute = Indica la route della funzione ajax per caricare il form child di addItem
*/
      
       $("document").ready(function() {
                var report_id = {{request()->input('report')}};
                var id_child = {{$id_child}};
                var _token = '{{csrf_token()}}';
                var url = '{{route($addChildRoute)}}';
                var item_id = $('[name="id"]').val();
                var _cont = 1;

                if($("#childContainer .progressive").length > 0) {
                        $("#childContainer .progressive").each(function(index, value) {
                                _cont++;
                        })
                }
                
                var req = { 'report':report_id, 'id_child': id_child, '_token': _token, '_cont': _cont };

                $("#addChildForm, #addChildFormBottom").on("click", function(){
                        
                        $.ajax(url, {
                                method: 'POST',
                                data: req, 
                                complete: function(response){
                                        console.log(req);
                                        console.log(response.responseJSON);
                                        if(response.responseJSON.result == 'success') {
                                                $("#childContainer").append(response.responseJSON.html);
                                                var i = 1;
                                                $('input[type="hidden"]').each(function(index){
                                                        
                                                        {{-- if($(this).attr('type') == 'hidden') { --}}
                                                              if($(this).attr('name') == 'items['+i+'][item_id]') {
                                                                        $(this).val(item_id);
                                                                        i++;
                                                                }  
                                                        {{-- } else {

                                                                $(this).attr('id', "_"+item_id);
                                                        } --}}
                                                        
                                                });
                                                $("label[for='items\\[" + i + "\\]\\[variante_2\\]']").hide();
                                                $("label[for='items\\[" + i + "\\]\\[variante_1\\]']").hide();
                                                req._cont++;
                                        } else {
                                                $("#childContainer").html("Non è possibile caricare il form");
                                        }
                                }
                        });
                });
        });
       /** FINE Aggiungi form child **/