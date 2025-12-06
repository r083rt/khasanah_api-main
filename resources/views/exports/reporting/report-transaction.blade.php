<table>
    <thead>
        <tr>
            <th></th>
            <th></th>
            <th></th>
            @foreach($branch_names as $branch)
            <th>Toko</th>
            <th></th>
            @endforeach
        </tr>
        <tr>
            <th></th>
            <th></th>
            <th></th>
            @foreach($branch_names as $branch)
            <th>{{$branch}}</th>
            <th></th>
            @endforeach
        </tr>
        <tr>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>Jenis</th>
            @foreach($branch_names as $branch)
            <th>Qty</th>
            <th>Harga</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($data as $item)
        <tr>
            <td>{{$item['date']}}</td>
            <td>{{$item['start_time']}} - {{$item['end_time']}}</td>
            <td>{{$item['product_category_name']}}</td>
            @foreach($item['transactions'] as $transactions)
            <td>{{$transactions['qty']}}</td>
            <td>{{$transactions['total_price'] !== null ? ('Rp '.number_format($transactions['total_price'],2,',','.')) : ''}}</td>
            @endforeach
        </tr>

        @endforeach
    </tbody>
</table>