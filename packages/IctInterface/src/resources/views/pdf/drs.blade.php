<!DOCTYPE html>
<html>
<head>
    <title>DRS</title>
    <style>
        body{font-family:Arial;font-size: 14px;}
        .text-right {text-align:right;}
        .text-left {text-align:left;}
        .text-center {text-align:center;}
        .float-left {float:left;}
        .float-right {float: right;}
        .clearfix {clear: both;}
        .full{width:100%;}
        .half{width:50%;}
        table {	border-collapse: collapse;}
        th {background: #000066;color:#FFF}
        td {border: 1px solid #000066;padding:3px;}
    </style>
</head>

<body>

   <div class="full" style="padding:15px 0;">
    <div class="float-left half"><img src="./images/logoICT.png" width="200" /></div>
    <div class="float-right text-right half"><strong>ICTlabs S.p.A.</strong><br />
        Sede: Viale Monza 347 - 20126 Milano<br />
        Labs: Strada dei Confini 60 – 05100 Terni<br />
        P.IVA: 05504510966<br />
        www.ictlabs.it
    </div>
   </div>
   <div class="full" style="border-bottom: 1px solid #000066;">&nbsp;</div>
    
    <div class="full clearfix text-center" style="border:1px solid #CCC;background:#FAFAFA;margin: 20px 0;"> DICHIARAZIONE DI SERVIZI RESI</div>


    <div class="full">
        <p>Indicare a quale società sarà intestata la fattura</p>
        <p>
            @if ($po['society']=='P&G srl')
                [X]
            @else
                []
            @endif 
            Procter&Gamble Srl</p>
        <p>
            @if ($po['society']=='P&G Holding')
                [X]
            @else
                []
            @endif 
            Procter&Gamble Holding Srl</p>
        <p>La Ditta ICTlabs S.p.a dichiara che a fronte del Purchase Order n. <strong>{{$po['num_order']}}</strong> del {{$po['delivered_at']}} con contatto P&G {{$po['contact']}}</p>
        <p>per i seguenti specifici ITEM:<br />
            <strong>INSERIRE STRUTTURA E INFORMAZIONI DEL PO</strong>
        </p>
    </div>

    <table class="full">
        <tr>
            <th>ITEM</th>
            <th>ITEM DESCRIPTION</th>
            <th>QUANTITY</th>
            <th>PREZZO UNITARIO</th>
            <th>TOTALE</th>
            
        </tr>
        @foreach ($items as $item)
        <tr>
            <td class="text-right">{{$item['line']}}</td>
            <td>{{$item['description']}}</td>
            <td class="text-center">{{$item['qty_processing']}}</td>
            <td class="text-center">{{$item['price']}} €</td>
            <td class="text-center">{{$item['total_item']}} €</td>
        </tr>

        @endforeach
        
    </table>
    <div class="full">
        <p>ha effettuato il servizio previsto nel mese di {{$po['nameMonth']->label}} per un totale di Euro {{$total}}  
            @if ($po['society']=='P&G srl')
            (IVA esente).
            @else
            (IVA esclusa).
            @endif
            La suddetta ditta conferma che il servizio in oggetto è stato completamente reso in data {{sprintf("%'.02d",$daysOfMonth[$po['month']])}}/{{sprintf("%'.02d",$po['month'])}}/{{$po['year']}} e che il
            servizio non è stato ancora fatturato.
        </p>
        <p>&nbsp;</p>
        <p>{{$today}}</p>
    </div>

</body>

</html>