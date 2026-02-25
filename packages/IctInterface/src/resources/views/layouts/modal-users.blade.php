<div class="modal fade" id="modalAddUsers" tabindex="-1" aria-labelledby="modalAddUsersLabel" aria-hidden="true">
    <div class="modal-dialog"  style="max-width:90%">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="jobItemsListTitle">Lista degli utenti</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
            <div class="modal-body">
              <form method="GET" id="modalAddUsers_form">
                @csrf
                <div class="form-group col-sm-12 clearfix">
                  <div class="col-sm-3 form-field-float">
                    <label>Utente</label>
                    <input type="text" name="name" class="form-control">
                  </div>
                  
                  
                  <div class="col-sm-3 form-field-float mt-4">
                    <input type="hidden" id="profile_id" name="profile_id" value="{{$profile_id}}">
                    <input type="hidden" id="report" name="report" value="{{request('report')}}">
                    <button type="button" class="btn btn-primary" id="searchModalAddUsers">Filtra</button>
                  </div>
                </div>
              </form>
              <form method="GET" id="formAddUsers">
                @csrf
                <input type="hidden" id="profile_id" name="profile_id" value="{{$profile_id}}">
                <input type="hidden" id="report" name="report" value="{{request('report')}}">
                <div id="containerUsersList" class="col-md-12 mt-2 mb-2 overflow-auto clearbox">
                  <table class="table table-striped" id="tableUsers">
                      <thead>
                          <tr>
                                <th scope="col"></th>
                                
                          </tr>
                      </thead>
                      <tbody>
                            <tr>
                                <td></td>
                            </tr> 
                      </tbody>
                  </table>
                  <div class="form-group col-sm-12 clearbox">
                    <button class="btn btn-success" type="submit" id="btnAddUser">Salva</button>
                  </div>
              </form>
          </div>
                
              
            </div>
            <div class="modal-footer clearbox">
              
            </div>
        
      </div>
    </div>
  </div>

  <script>
    $("document").ready(function() {
      $("#searchModalAddUsers").click(function() {
        searchUsers();
      });
      //imposta il titolo della modale
      $("#modalAddUsers").on('show.bs.modal', function() {

        searchUsers();
      });

      $("#btnAddUser").click(function() {
        var requestData = $("#formAddUsers").serialize();
        var url = "{{route('call.add.users')}}";
        $.ajax(url, {
          method: 'POST',
          data: requestData,
          complete: function(response) {
            resp = response.responseJSON;
            console.log(resp);
            alert(resp.message);
            if(resp.result == 'success') {
              location.reload();
            }
          }
        });
      })
    });

    function searchUsers() {
      var requestData = $("#modalAddUsers_form").serialize();
        var url = "{{route('call.search.users')}}";
        $.ajax(url, {
          method: 'GET',
          data: requestData, 
          complete: function(response) {
            resp = response.responseJSON;
    
            if(resp.result == 'success') {
              var checked = '';
              var thead = "<tr>\n";
              $.each(resp.cols, function(index, col) {
                thead += "<th scope=\"col\">"+col+"</th>\,";
              });
              thead += "<th scope=\"col\"><i class=\"fas fa-check\"></i></th>\,";
              thead += "</tr>\n";
              $("#tableUsers thead").html(thead);
              var tbody = '';

              $.each(resp.users, function(index, obj) {
                tbody += "<tr>\n";
                
                for(var key in obj) {
                  if(obj[key]==null || key == 'profile_id') {
                    // obj[key] = 'N/A';
                    continue;
                  }
                  tbody += "<td>"+obj[key]+"</td>\,";
                };
                
                if(resp.users[index].profile_id) {
                  checked = 'checked="checked"';
                } else {
                  checked = '';
                }
                tbody += '<td><input type="checkbox" id="userID_'+obj['id']+'" name="user_id[]" value="'+obj['id']+'" class="form_control" '+checked+'></td>';
                // tbody += '<td><button type="button" id="'+obj['id']+'-BtnJitemEdit_'+obj['activity_item_id']+'" title="Modifica" data-job_item_id="'+obj['id']+'" data-report="21" data-toggle="modal" data-target="#modalAddUsers" class="btn btn-info"><i class="fas fa-edit"></i></button></td>';
                tbody += "</tr>\n";
              });

              if(tbody.length == 0) {
                tbody = "<tr><td colspan=\"10\" style=\"text-align:center\">"+resp.message+"</td></tr>";
              }
              $("#tableUsers tbody").html(tbody);
              
              
            } else {
              alert(resp.message);
            }
        }});
    }
  </script>