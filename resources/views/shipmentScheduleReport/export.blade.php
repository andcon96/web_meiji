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
        @forelse($rows as $row)
        <tr>
            {{-- <td>{{ $row['ssd_sod_nbr'] }}</td>
            <td>{{ $row['ssd_sod_shipto'] }}</td>
            <td>{{ $row['ssd_sod_part'] }}</td>
            <td>{{ $row['ssd_uom'] }}</td>
            <td>{{ $row['ssd_sod_qty_ord'] }}</td>
            <td>{{ $row['ssd_sod_lot'] }}</td> --}}

            <td>{{ $row->ssd_sod_nbr }}</td>
            <td>{{ $row->sold_to }}</td>
            <td>{{ $row->ssd_sod_part }}</td>
            <td>{{ $row->ssd_uom }}</td>
            <td>{{ $row->ssd_sod_qty_ord }}</td>
            <td>{{ $row->ssd_sod_lot }}</td>

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
    </tbody>
</table>