<script>
    /**
     * Elimina il record nel database
     */
    $("document").ready(function() {
      $(".destroy").click(function(e) {
        e.preventDefault();
        var url = $(this).attr('data-route').replace(/\?report=\d+/, '');
        if(confirm("Sei sicuro di eliminare il record ["+$(this).attr('data-route').replace(/\/\w+\//, '')+"]")) {
          $.ajax(url, {
            method: 'DELETE',
            data: {
              "_token": '{{csrf_token()}}',
              "report": {{request('report')}}
            },
            complete: function(response) {
              console.log(response);
              if(response.responseText == 1) {
                alert("Il record è stato eliminato");
                location.reload(true);
              } else if(response.responseJSON.result == 'success') {
                alert(response.responseJSON.message);
                location.reload(true);
              } else {
                var message = response.responseJSON.message.replace(/\(.+$/,'');
                alert(message);
              }
            }
          });
        }
      });

      $(".cancel").click(function(e) {
        e.preventDefault();
        var url = $(this).attr('data-route').replace(/\?report=\d+/, '');
        if(confirm("Sei sicuro di disabilitare il record ["+$(this).attr('data-route').replace(/\/\w+\//, '')+"]")) {
          $.ajax(url, {
            method: 'PUT',
            data: {
              "_token": '{{csrf_token()}}',
              "report": {{request('report')}},
              "cancel_action": 1
            },
            complete: function(response) {
              console.log(response);
              if(response.responseText == 1 || response.statusText == 'OK') {
                alert('Il record (ed eventuali items) è stato disabilitato ma non eliminato dal database');
                location.reload(true);
              } else {
                var message = response.responseJSON.message.replace(/\(.+$/,'');
                alert(message);
              }
            }
          });
        }
      });
    });
    /** FINE Elimina record nel database **/
  </script>