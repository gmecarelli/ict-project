@php
/**
 * @var $header
 * @var $colMonths
 * @var $rows
 */
@endphp

<table>
    <thead>
        <tr>
            <th style="width:20px;background:#ff9900">IRPEF COD. 1048</th>
            <th style="width:20px;"></th>
            <th style="width:20px;"></th>
            @foreach ($colMonths as $label)
                <th style="width:20px;"></th>
            @endforeach
            <th style="text-align:center;font-family: Arial;font-size:9px;width:10px;background:#99ccff">&nbsp;</th>
            <th style="width:20px;background:#ff9900">IMP. SOST. COD 1672</th>
            @foreach ($colMonths as $label)
                <th style="width:20px;"></th>
            @endforeach
        </tr>
        <tr>
            <th style="width:20px;"></th>
            <th style="width:20px;"></th>
            <th style="width:20px;"></th>
            @foreach ($colMonths as $label)
                <th style="width:20px;"></th>
            @endforeach
            <th style="width:10px;background:#99ccff">&nbsp;</th>
            @foreach ($colMonths as $label)
                <th style="width:20px;"></th>
            @endforeach
        </tr>
    <tr>
        @foreach ($header as $key => $value)
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:10px;width:20px;color:#003399">{{$value}}</th>
        @endforeach
        @foreach ($colMonths as $label)
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:10px;width:20px;color:#003399">{{$label}}</th>
        @endforeach

        <th style="text-align:center;font-family: Arial;font-size:9px;width:10px;background:#99ccff">&nbsp;</th>
        
        @foreach ($colMonths as $label)
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:10px;width:20px;color:#003399">{{$label}}</th>
        @endforeach
    </tr>
    <tr>
        @foreach ($header as $value)
            <th style="text-align:center;font-family: Arial;font-size:9px;width:20px;background:#99ccff">&nbsp;</th>
        @endforeach
        @foreach ($colMonths as $value)
            <th style="text-align:center;font-family: Arial;font-size:9px;width:20px;background:#99ccff">&nbsp;</th>
        @endforeach

        <th style="text-align:center;font-family: Arial;font-size:9px;width:10px;background:#99ccff">&nbsp;</th>
        
        @foreach ($colMonths as $value)
            <th style="text-align:center;font-family: Arial;font-size:9px;width:20px;background:#99ccff">&nbsp;</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
        @foreach ($rows as $charge => $arr)
        <tr>
            @foreach ($arr as $key => $data)
                @if ($key == 'irpef')
                    @foreach ($data as $total)
                        <td style="font-family: Arial;font-size:9px;text-align:right;"><p>{{$total}} €</p></td>
                    @endforeach
                @elseif ($key == 'imp_sost')
                    <td style="text-align:center;font-family: Arial;font-size:9px;width:10px;background:#99ccff">&nbsp;</td>
                    @foreach ($data as $total)
                        <td style="font-family: Arial;font-size:9px;text-align:right;"><p>{{$total}} €</p></td>
                    @endforeach
                @else
                    <td style="font-family: Arial;font-size:9px;text-align:center;"><p>{{$data}}</p></td>
                @endif
            @endforeach        
        </tr>
        @endforeach
    </tbody>
</table>