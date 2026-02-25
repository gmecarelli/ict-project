@php
/**
 * @var header
 * @var activities
 */
@endphp

<table>
    <thead>
        <tr>
            <th colspan="5"></th>
        </tr>
    <tr>
        <th>&nbsp;</th>
        @foreach ($header as $key => $value)
                <th style="text-align:center;font-family: Arial;font-weight:bold;font-size:9px;width:20px;padding:20px;background:#b4c7e7">{{$value}}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td style="font-family: Arial;font-size:9px;"></td>
            @foreach ($row as $key => $data)
                @if($key == 3)
                    <td style="font-family: Arial;font-size:9px;text-align:right;"><p>{{$data}} €</p></td>
                @else<p>
                    <td style="font-family: Arial;font-size:9px;"><p>{{$data}}</p></td>
                @endif

            @endforeach
        </tr>
    @endforeach
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td style="font-family: Arial;font-size:9px;text-align:right;"><p>{{$total}} €</p></td>
        </tr>
    </tbody>
</table>
{{-- @php dd($activities); @endphp --}}