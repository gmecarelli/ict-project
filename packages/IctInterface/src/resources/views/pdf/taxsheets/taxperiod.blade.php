@php
/**
 * @var header
 * @var rowsIrpef
 * @var rowsImpSost
 */
@endphp

<table>
    <thead>
        <tr>
            <th style="width:20px;"></th>
            <th style="width:20px;"></th>
            <th style="width:20px;"></th>
            <th style="width:20px;"></th>
            <th style="text-align:center;font-family: Arial;font-size:9px;width:20px;background:#99ccff">IRPEF COD. 1048</th>
            <th style="width:20px;"></th>
            <th style="width:20px;"></th>
            <th style="width:20px;"></th>
            <th style="text-align:center;font-family: Arial;font-size:9px;width:10px;background:#99ccff">&nbsp;</th>
            <th style="width:20px;"></th>
            <th style="width:20px;"></th>
            <th style="width:20px;"></th>
            <th style="width:20px;"></th>
            <th style="text-align:center;font-family: Arial;font-size:9px;width:20px;background:#99ccff">IMP. SOST. COD 1672</th>
            <th style="width:20px;"></th>
            <th style="width:20px;"></th>
            <th style="width:20px;"></th>
        </tr>
    <tr>
        @foreach ($header as $key => $value)
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:10px;width:20px;color:#0033cc">{{$value}}</th>
        @endforeach
        <th style="text-align:center;font-family: Arial;font-size:9px;width:10px;background:#99ccff">&nbsp;</th>
        @foreach ($header as $key => $value)
            <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:10px;width:20px;color:#0033cc">{{$value}}</th>
        @endforeach
    </tr>
    <tr>
        @foreach ($header as $key => $value)
        <th style="text-align:center;font-family: Arial;font-size:9px;width:20px;background:#99ccff">&nbsp;</th>
        @endforeach
        <th style="text-align:center;font-family: Arial;font-size:9px;width:10px;background:#99ccff">&nbsp;</th>
        @foreach ($header as $key => $value)
        <th style="text-align:center;font-family: Arial;font-size:9px;width:20px;background:#99ccff">&nbsp;</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
        @for ($i = 0; $i < $maxRows; $i++)
            <tr>
                @foreach ($rowsIrpef[$i] as $key => $data)
                    <td style="font-family: Arial;font-size:9px;text-align:center;"><p>{{$data}}</p></td>
                @endforeach
                <td style="text-align:center;font-family: Arial;font-size:9px;width:10px;background:#99ccff">&nbsp;</td>
                @foreach ($rowsImpSost[$i] as $key => $data)
                    <td style="font-family: Arial;font-size:9px;text-align:center;"><p>{{$data}}</p></td>
                @endforeach
            </tr>
        @endfor
        <tr>
            <td colspan="8"></td>
            <td style="background:#99ccff"></td>
            <td colspan="8"></td>
        </tr>
        <tr>
            <td colspan="8"></td>
            <td style="background:#99ccff"></td>
            <td colspan="8"></td>
        </tr>
        <tr>
            <td colspan="8"></td>
            <td style="background:#99ccff"></td>
            <td colspan="8"></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td style="color: #FF0000;font-weight:bold;">{{$total_irpef}}</td>
            <td></td>
            <td></td>
            <td></td>
            <td  style="background:#99ccff"></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td style="color: #FF0000;font-weight:bold;">{{$total_imp_sost}}</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="8"></td>
            <td style="background:#99ccff"></td>
            <td colspan="8"></td>
        </tr>
        <tr>
            <td colspan="8"></td>
            <td style="background:#99ccff"></td>
            <td colspan="8"></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td style="font-weight:bold;">TOT. GENERALE</td>
            <td style="font-weight:bold;">{{$total}}</td>
            <td></td>
            <td></td>
            <td></td>
            <td  style="background:#99ccff"></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </tbody>
</table>