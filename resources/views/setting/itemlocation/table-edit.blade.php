@forelse ($data->getDetailLocation as $datas)
    <tr>
        <td data-label="Site">{{ $datas->ld_lot_serial }}</td>
        <td data-label="Site">{{ $datas->ld_building }}</td>
        <td data-label="Site">{{ $datas->ld_rak }}</td>
        <td data-label="Site">{{ $datas->ld_bin }}</td>
        <td data-label="Action">
            <a href="{{ route('itemLocationDetail', ['id' => $datas->id]) }}" class="editdata" id='editdata'>
                <i class="icon-table fa fa-edit fa-lg"></i>
            </a>
        </td>
    </tr>
@endforeach
