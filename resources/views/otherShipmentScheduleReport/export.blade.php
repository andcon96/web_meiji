<table class="table-bordered">
    <thead>
        <tr>
            <th><b>Nama dan Paraf Operator Persiapan:</b></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>

            <th><b>Tanggal Persiapan</b></th>
            <th></th>
            <th></th>
            <th></th>

            <th><b>Tanggal Pengiriman</b></th>
            <th></th>
            <th></th>
            <th></th>

            <th><b>Distributor Tujuan</b></th>
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

            <th><b>Jumlah </b></th>
            <th></th>
            <th></th>
            <th></th>

            <th><b>Jumlah Kemasan</b></th>
            <th></th>
            <th></th>
            <th></th>

            <th><b>Jumlah Kemasan</b></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>

            <th><b>Scan 2D Barcode</b></th>
            <th></th>
            <th></th>
        </tr>

        <tr>
            <th><b>Order</b></th>
            <th><b>Item Number</b></th>
            <th><b>UM</b></th>
            <th><b>Qty Ordered</b></th>
            <th><b>Sold-To</b></th>

            <th><b>No. Lot</b></th>
            <th><b>KA</b></th>
            <th><b>per UM</b></th>
            <th><b>Kode PC</b></th>
            <th><b>No Lot</b></th>

            <th><b>KA</b></th>
            <th><b>per UM</b></th>
            <th><b>Kode PC</b></th>
            <th><b>No Lot</b></th>

            <th><b>KA</b></th>
            <th><b>per UM</b></th>
            <th><b>Kode PC</b></th>
            <th><b>Koli</b></th>
            <th><b>Paraf Opt Pemeriksa</b></th>
            <th><b>Paraf Cek Pecahan (Level Group)</b></th>

            <th><b>No. SO TTAC</b></th>
            <th><b>Paraf Scan</b></th>
            <th><b>Paraf TTAC</b></th>
        </tr>

    </thead>


    <tbody>
        @foreach($rows as $row)
        @foreach($row->get_other_shipment_schedule_location as $loc)
        <tr>

            <td>{{ $row->order }}</td>
            <td>{{ $row->ossd_part }}</td>
            <td>{{ $row->ossd_uom }}</td>
            <td>{{ $row->ossd_qty_ord }}</td>
            <td>{{ $row->sold_to }}</td>
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