@php
/**
 * @var header
 * @var activities
 */
@endphp

<table>
    <thead>
        <tr>
            <th colspan="11"></th>
            <th style="text-align:center;font-family: Arial;font-size:9px;width:20px;padding:20px;background:#a3b38e">{{$tot_imponibile_irpef}} €</th>
            <th style="text-align:center;font-family: Arial;font-size:9px;width:20px;padding:20px;background:#a3b38e">{{$tot_irpef}} €</th>
            <th style="text-align:center;font-family: Arial;font-size:9px;width:20px;padding:20px;background:#a3b38e"></th>
            <th style="text-align:center;font-family: Arial;font-size:9px;width:20px;padding:20px;background:#a3b38e">{{$tot_imponibile_impsost}} €</th>
            <th style="text-align:center;font-family: Arial;font-size:9px;width:20px;padding:20px;background:#a3b38e">{{$tot_impsost}} €</th>
            <th colspan="6"></th>
        </tr>
        <tr>
            <th colspan="11"></th>
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;"></th>
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;">25%</th>
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;"></th>
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;"></th>
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;">20%</th>
            <th colspan="6"></th>
        </tr>
        <tr>
            <th colspan="11"></th>
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;">Irpef</th>
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;background:#ffc000">IRPEF cod.1048</th>
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;"></th>
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;">Imp.Sost.</th>
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;background:#ffc000">IMP. SOST. cod. 1672</th>
            <th colspan="6"></th>
        </tr>
    <tr>
        @foreach ($header as $key => $value)
            @if (in_array($key, [1, 7, 12, 15]))
                <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;background:#ffc000">{{$value}}</th>
            @elseif($key == 16)
                <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:25px;padding:20px;background:#ffe293">{{$value}}</th>
            @elseif($key == 20 || $key == 6 || $key == 18)
                <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:30px;padding:20px;background:#b4c7e7">{{$value}}</th>
            @else
                <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;background:#b4c7e7">{{$value}}</th>
            @endif
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($activities as $index => $activity)
        <tr>
            @foreach ($activity as $key => $data)
                @if (is_array($data))
                    <td style="height:{{$tdHeights[$index]}}px;font-family: Arial;font-size:9px;text-align:center;">
                        @foreach ($data as $item)
                            <p>{{$item}}</p>
                        @endforeach
                    </td>
                
                @elseif($key == 'tipologia_premio')
                    <td style="height:{{$tdHeights[$index]}}px;font-family: Arial;font-size:9px;text-align:center;width:300px"><p>{{$data->label}}</p><p>&nbsp;</p></td>
                @else<p>
                    <td style="height:{{$tdHeights[$index]}}px;font-family: Arial;font-size:9px;text-align:center;"><p>{{$data}}</p><p>&nbsp;</p></td>
                @endif

            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
{{-- @php dd($activities); @endphp --}}