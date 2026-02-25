@php
/**
 * @var header
 * @var activities
 */
@endphp

<table>
    <thead>
    <tr>
        @foreach ($header as $key => $value)
            @if ($key < 14)
                <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;background:#CCCCCC">{{$value}}</th>
            @elseif($key > 13 && $key < 26)
                <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;background:#ffe293">{{$value}}</th>
            @else
                <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;background:#fff178">{{$value}}</th>
            @endif
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($activities as $activity)
        
            @foreach ($activity as $key => $data)
            
                @if ($key=='referente_pg')
                    @if (is_array($activity['premi']) && count($activity['premi']) > 0)
                        <tr height="{{count($activity['premi'])*25}}">
                    @else
                        <tr height="40">
                    @endif
                        
                @endif

                @if (is_array($data))
                    <td style="font-family: Arial;font-size:9px;">
                        @foreach ($data as $item)
                            {{$item}}<br>
                        @endforeach
                    </td>
                @else
                    <td style="font-family: Arial;font-size:9px;" valign="middle">{{$data}}</td>
                @endif
                
                
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
{{-- @php dd($activities); @endphp --}}