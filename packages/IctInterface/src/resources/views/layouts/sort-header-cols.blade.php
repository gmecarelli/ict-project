@foreach ($cols as $col)

              <th scope="col"><a href="{{$col['order_link']}}" class="text-dark">{{$col['label']}}
              @if (($col['field'] == request('ob') && request('ot') == 'ASC'))
                <i class="fas fa-sort-down"></i>
              @elseif($col['field'] == request('ob') && request('ot') == 'DESC')
                <i class="fas fa-sort-up"></i>
              @else
                <i class="fas fa-sort"></i>
              @endif</a></th>
@endforeach