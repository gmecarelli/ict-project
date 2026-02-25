<script>
$("document").ready(function() {
    var checked = true;
    var session_ids = [];
    $("#toggleCheck").click(function(e) {

        if (checked == true) {
            // seleziono tutti i check
            $(".multicheck").prop("checked", true);
            checked = false;
            $("#toggleCheck").html("<i class=\"far fa-check-square\"></i> Deseleziona tutto");
        } else {
            // deseleziono tutti i check
            $(".multicheck").prop("checked", false);
            checked = true;
            $("#toggleCheck").html("<i class=\"fas fa-check-square\"></i> Seleziona tutto");
        }
        setChecked(checked)
    });

    $(".multicheck").click(function(e) {

        var check = e.currentTarget;
        console.log($("#" + check.id).prop('checked'));

        if ($("#" + check.id).prop('checked') == true) {
            // seleziono un check specifico
            checked = false;
            $("#toggleCheck").html("<i class=\"far fa-check-square\"></i> Deseleziona tutto");
            setChecked($("#" + check.id).val());
        } else {
            // deseleziono un check specifico
            var flag = false;
            $(".multicheck").each(function() {
                // controllo se ci sono check selezionati
                if ($(this).prop('checked') == true) {
                    checked = false;
                    flag = true;
                }
            });

            if (flag == false) {
                // se non ci sono altri check selezionati reimposta il valore del pulsante massivo
                $("#toggleCheck").html("<i class=\"fas fa-check-square\"></i> Seleziona tutto");
            }
            // chiamo la funzione che setta la sessione dei check
            setChecked($("#" + check.id).val(), false);
        }
    });

    $(".do-action").click(function(e) {
        var itemClicked = e.currentTarget;
        var report = $("#"+itemClicked.id).data("report");
        var i_item = itemClicked.id.replace('dropdownitem_','');

        var url = "{{route('call.do_multiselect')}}";
        console.log("indice dell'azione: "+i_item);
        $.ajax(url, {
            type: 'GET',
            data: {
                "i_item": i_item,
                "report": report,
            },
            headers: {'X-CSRF-TOKEN': '{{csrf_token()}}'},
            success:function(response){
                alert(response.message);
                if(response.result == 'success') {
                    location.reload(true);
                }
            }
        });
    });

    /**
     * Popola la sessione con gli id dei checkboxes selezionati
     * @param {bool | int} val 
     * @param { bool} check 
     */
    function setChecked(val, check = true) {

        if (val == true) {

            //eliminare tutti in sessione
            while (session_ids.length) {
                session_ids.pop();
            }
        } else if (val == false) {
            //aggiungere tutti in sessione
            $(".multicheck").each(function() {
                session_ids.push($(this).val());
            });
        } else {
            if (check == true) {
                session_ids.push(val);
            } else {
                var filteredArray;
                filteredArray = session_ids.filter(function(e) { return e !== val })
                session_ids = filteredArray;
            }

        }
        console.log(session_ids);
        var url = '{{route('call.multiselect')}}?report={{$report["id"]}}'

        $.ajax(url, {
            type: 'GET',
            data: {
                "sess": session_ids,
                // "_token": {{csrf_token()}},
            },
            headers: {'X-CSRF-TOKEN': '{{csrf_token()}}'},
            success:function(response){
                if(response.result == 'success') {
                    console.log("id commesse inseriti in sessione");
                }
            }
        });
    }

});
</script>