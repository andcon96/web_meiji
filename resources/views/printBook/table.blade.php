@forelse ($data as $datas)
    <tr>
        <td></td>
        <td data-label="Nomor Buku">{{ $datas->rd_nomor_buku }}</td>
        <td data-label="Nomor Receipt">{{ $datas->getMaster->getPurchaseOrderMaster->po_nbr }}</td>
        <td data-label="Nomor Receipt">{{ $datas->getMaster->rm_rn_number }}</td>
        <td data-label="Item">{{ $datas->getPurchaseOrderDetail->pod_part }}</td>
        <td data-label="Lot">{{ $datas->rd_batch }}</td>
        <td data-label="Action">
            <a href="{{ route('printBook', $datas->id) }}" class="editdata" id='editdata' target="_blank">
                <i class="fa-solid fa-file-pdf"></i>
            </a>
        </td>
    </tr>
@endforeach
