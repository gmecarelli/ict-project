
<table>
    
    <tbody>
    
        <tr>
            <td style="font-family: Arial;font-weight:bold;width:20px;color:#FFFFFF;background:#4D7496">Brand</td>
            <td style="font-family: Arial;width:20px;background:#FAFAFA;border:1px solid #CCCCCC;">{{$brand}}</td>
        </tr>
        <tr>
            <td style="font-family: Arial;font-weight:bold;width:20px;color:#FFFFFF;background:#4D7496">Iniziativa</td>
            <td style="font-family: Arial;width:20px;background:#FAFAFA;border:1px solid #CCCCCC;">{{$po['title']}}</td>
        </tr>
        <tr>
            <td style="font-family: Arial;font-weight:bold;width:20px;color:#FFFFFF;background:#4D7496">Agenzia</td>
            <td style="font-family: Arial;width:20px;background:#FAFAFA;border:1px solid #CCCCCC;">ICT</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td style="font-family: Arial;font-weight:bold;width:20px;">Numero PO</td>
            <td style="font-family: Arial;width:20px;background:#FAFAFA">{{Str::after($po['num_order'],'G4P-')}}</td>
        </tr>
        <tr>
            <td style="font-family: Arial;font-weight:bold;width:20px;">Ordine emesso da:</td>
            <td style="font-family: Arial;width:20px;">{{$po['contact']}}</td>
        </tr>
        <tr>
            <td style="font-family: Arial;font-weight:bold;width:20px;">Data Emissione</td>
            <td style="font-family: Arial;width:20px;">{{$po['delivered_at']}}</td>
        </tr>

    </tbody>
</table>

<table>
    <thead>
    <tr>
        <th style="text-align:center;font-family: Arial;font-weight:bold;width:20px;color:#FFFFFF;background:#4D7496">ID ITEM</th>
        <th style="text-align:center;font-family: Arial;font-weight:bold;width:20px;color:#FFFFFF;background:#4D7496">LINE ITEM N°</th>
        <th style="text-align:center;font-family: Arial;font-weight:bold;width:20px;color:#FFFFFF;background:#4D7496">DESCRIZIONE ITEM</th>
        <th style="text-align:center;font-family: Arial;font-weight:bold;width:20px;color:#FFFFFF;background:#4D7496">DETTAGLIO</th>
        <th style="text-align:center;font-family: Arial;font-weight:bold;width:20px;color:#FFFFFF;background:#4D7496">QNT</th>
        <th style="text-align:center;font-family: Arial;font-weight:bold;width:20px;color:#FFFFFF;background:#4D7496">COSTO UNITARIO</th>

        <th style="text-align:center;font-family: Arial;font-weight:bold;width:20px;background:#FFFF99">TOTALE PO</th>
        <th style="text-align:center;font-family: Arial;font-weight:bold;width:20px;background:#d5dce6">TOTALE FATTURATO</th>
        <th style="text-align:center;font-family: Arial;font-weight:bold;width:20px;background:#d0ebd9">TOTALE RESIDUO</th>

        @foreach ($drs['head'] as $m_y => $val)
            <th style="text-align:center;font-family: Arial;color:#FFFFFF;background:#4D7496">{{$m_y}}</th>
        @endforeach
        
    </tr>
    </thead>
    <tbody>
    @foreach($poItems as $item)
        <tr>
            <td style="font-family: Arial;">{{Str::after($item->num_order,'G4P-')}}_{{$item->line}}</td>
            <td style="font-family: Arial;">{{$item->line}}</td>
            <td style="font-family: Arial;">{{$item->description}}</td>
            <td style="font-family: Arial;"></td>
            <td style="font-family: Arial;">{{$item->quantity}}</td>
            <td style="font-family: Arial;">{{number_format($item->price,2,',','.')}}</td>

            <td style="font-family: Arial;background:#f5f5cf;border:1px solid #CCCCCC;">{{number_format($item->total_item,2,',','.')}}</td>
            @if (Arr::has($drs,'total.'.$item->id))
                <td style="font-family: Arial;background:#e8edf5;border:1px solid #CCCCCC;">{{number_format($drs['total'][$item->id],2,',','.')}}</td>
            @else
                <td style="font-family: Arial;background:#e8edf5;border:1px solid #CCCCCC;">-</td>
            @endif

            @if (Arr::has($drs,'total.'.$item->id))
                <td style="font-family: Arial;background:#def1e5;border:1px solid #CCCCCC;">{{number_format(($item->total_item) - $drs['total'][$item->id],2,',','.')}}</td>
            @else
                <td style="font-family: Arial;background:#def1e5;border:1px solid #CCCCCC;">{{number_format(($item->total_item),2,',','.')}}</td>
            @endif

            @foreach ($drs['head'] as $m_y => $val)
                @if (Arr::has($drs,"items.{$item->id}.{$m_y}"))
                    <td>{{$drs['items'][$item->id][$m_y]}}</td>
                @else
                    <td> - </td>
                @endif
            @endforeach
            
        </tr>
        

    @endforeach
        <tr>
            <td></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>

            <td style="font-family: Arial;background:#f5f5cf;border:1px solid #CCCCCC;">{{number_format($total_po,2,',','.')}}</td>
            <td style="font-family: Arial;background:#e8edf5;border:1px solid #CCCCCC;">{{number_format($total_drs,2,',','.')}}</td>
            <td style="font-family: Arial;background:#def1e5;border:1px solid #CCCCCC;">{{number_format($total_po - $total_drs,2,',','.')}}</td>
            @foreach ($drs['totmonth'] as $totalmonth)
            <td style="font-family: Arial;background:#def1e5;border:1px solid #CCCCCC;text-align:right">{{number_format($totalmonth,2,',','.')}}</td>
            @endforeach
        </tr>
    </tbody>
</table>
