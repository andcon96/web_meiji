<table class="table-bordered">
    <thead>
        <tr>
            <th>Nama dan Paraf Operator Persiapan:</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>

            <th>Tanggal Persiapan</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>

            <th>Tanggal Pengiriman</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
        </tr>

        <tr></tr>
        <tr></tr>

        <tr>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>

            <th>Jumlah Kemasan</th>
            <th></th>
            <th></th>
            <th></th>

            <th>Jumlah Kemasan</th>
            <th></th>
            <th></th>
            <th></th>

            <th>Jumlah Kemasan</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>

            <th>Scan 2D Barcode</th>
            <th></th>
            <th></th>
        </tr>

        <tr>
            <th>Order</th>
            <th>Sold-To</th>
            <th>Item Number</th>
            <th>UM</th>
            <th>Qty Ordered</th>
            <th>No. Lot</th>

            <th>KA</th>
            <th>per UM</th>
            <th>Kode PC</th>
            <th>No Lot</th>

            <th>KA</th>
            <th>per UM</th>
            <th>Kode PC</th>
            <th>No Lot</th>

            <th>KA</th>
            <th>per UM</th>
            <th>Kode PC</th>
            <th>Koli</th>
            <th>Paraf Opt Pemeriksa</th>
            <th>Paraf Cek Pecahan (Level Group)</th>

            <th>No. SO TTAC</th>
            <th>Paraf Scan</th>
            <th>Paraf TTAC</th>
        </tr>
    </thead>
   

    <tbody>
        @foreach($rows as $row)
            @foreach($row->get_other_shipment_schedule_location as $loc)
            <tr>

                <td>{{ $row->order }}</td>
                <td>{{ $row->sold_to }}</td>
                <td>{{ $row->ossd_part }}</td>
                <td>{{ $row->ossd_uom }}</td>
                <td>{{ $row->ossd_qty_ord }}</td>
                <td>{{ $loc->ossl_lotserial }}</td>

                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @endforeach
        @endforeach
    </tbody>
</table>