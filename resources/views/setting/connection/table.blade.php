@forelse ($connections as $connection)
    <tr>
        <td></td>
        <td data-label="WSA URL">{{ $connection->wsa_url }}</td>
        <td data-label="WSA Path">{{ $connection->wsa_path }}</td>
        <td data-label="QX URL">{{ $connection->qx_url }}</td>
        <td data-label="Action">
            <a href="{{ route('connections.edit', $connection->id) }}" class="editdata" id='editdata'>
                <i class="domain-table fa fa-edit fa-lg"></i>
            </a>
            <a href="javascript:void(0)" class="deleteConnection" data-toggle="tooltip" title="Delete Data"
                data-target="#deleteModal" data-connection="{{ $connection }}">
                <i class="domain-table fa fa-trash fa-lg"></i>
            </a>
        </td>
    </tr>
@endforeach
