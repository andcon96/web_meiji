@forelse ($prefixes as $prefix)
    <tr>
        <td data-label="Receipt Nbr">{{ $prefix->prefix_receipt }}{{ $prefix->running_nbr_receipt }}</td>
        <td data-label="Buku Penerimaan Nbr">
            {{ $prefix->prefix_buku_penerimaan }}{{ $prefix->running_nbr_buku_penerimaan }}</td>
        <td data-label="Action">
            <a href="{{ route('prefix.edit', $prefix->id) }}" class="editdata" id='editdata'>
                <i class="icon-table fa fa-edit fa-lg"></i>
            </a>
            <a href="javascript:void(0)" class="deletedata" data-toggle="tooltip" title="Delete Data"
                data-target="#deleteModal" data-prefix="{{ $prefix }}">
                <i class="icon-table fa fa-trash fa-lg"></i>
            </a>
        </td>
    </tr>
@endforeach
