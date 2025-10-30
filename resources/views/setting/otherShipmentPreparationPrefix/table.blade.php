@forelse ($prefixes as $prefix)
<tr>
    <td data-label="Preparation Prefix">{{ $prefix->other_shipment_preparation_prefix }}</td>
    <td data-label="Sequence">{{ $prefix->other_shipment_preparation_running_nbr }}</td>
    <td data-label="Action">
        <a href="{{ route('otherShipmentPreparationPrefix.edit', $prefix->id) }}" class="editdata" id='editdata'>
            <i class="icon-table fa fa-edit fa-lg"></i>
        </a>
        <a href="javascript:void(0)" class="deletedata" data-toggle="tooltip" title="Delete Data"
            data-target="#deleteModal" data-prefix="{{ $prefix }}">
            <i class="icon-table fa fa-trash fa-lg"></i>
        </a>
    </td>
</tr>
@endforeach
