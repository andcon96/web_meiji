@forelse ($data as $datas)
    <tr>
        <td></td>
        <td data-label="Site">{{ $datas->location_site }}</td>
        <td data-label="Location">{{ $datas->location_code }}</td>
        <td data-label="Action">
            <a href="{{ route('itemlocation.edit', $datas->id) }}" class="editdata" id='editdata'>
                <i class="icon-table fa fa-edit fa-lg"></i>
            </a>
        </td>
    </tr>
@endforeach
