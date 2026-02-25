<div  class="mb-0 display-4  text-center" style="padding: 30px 0">
    <h3>{{$titlePage}}</h3>
    @if (!empty($subTitle))
        <h5>{{$subTitle}}</h5>
    @endif
    @if (!is_null($count))
        @if (is_array($count))
            @foreach ($count as $label => $sum)
                <h6>{!! $label !!}: {!! $sum !!}</h6>
            @endforeach
            
        @else
            <h6>Totale righe: {{_number($count)}}</h6>
        @endif
    @endif
    
</div>