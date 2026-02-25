<div class="btn-group mb-3">
      <button class="btn btn-light border border-dark p-2 mb-2" title="Seleziona/Deseleziona tutto" id="toggleCheck"><i class="fas fa-check-square"></i> Seleziona tutto</button>
</div>

<div class="btn-group mb-3">
            <button class="btn btn-secondary p-2 dropdown-toggle mb-2" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-expanded="false">
              Azioni massive
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                @if(!is_null($dropdown))
                    @foreach ($dropdown->dropItems as $i => $action)
                        @if(is_null($action['route']))
                            <a id="dropdownitem_{{$i}}" class="dropdown-item do-action" data-report="{{$report['id']}}">{{$action['label']}}</a>
                        @elseif(preg_match("/^MODAL/",$action['route']))
                            <a title="{{$action['label']}}" class="dropdown-item" id="dropdownitem_{{$i}}" data-toggle="modal" data-target="#{{substr($action['route'],6)}}">{{$action['label']}}</a>
                        @else
                            <a id="actionitem_{{$i}}" class="dropdown-item" href="{{route($action['route'])}}?report={{$report['id']}}">{{$action['label']}}</a>
                        @endif
                    @endforeach
                @endif
            </div>
</div>
<script>
    $('.dropdown-toggle').dropdown();
</script>