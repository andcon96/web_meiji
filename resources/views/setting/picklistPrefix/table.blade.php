@forelse ($prefixes as $prefix)
<tr>
    <td data-label="Schedule Prefix">{{ $prefix->prefix_wo }}</td>
    <td data-label="Sequence">{{ $prefix->prefix_year_wo }}</td>
    <td data-label="Sequence">{{ $prefix->prefix_month_wo }}</td>
    <td data-label="Sequence">{{ $prefix->running_nbr_wo }}</td>
    
    <td data-label="Action">
        <a href="{{ route('PicklistPrefix.edit', $prefix->id) }}" class="editdata" id='editdata'>
            <i class="icon-table fa fa-edit fa-lg"></i>
        </a>
        <a href="javascript:void(0)" class="deletedata" data-toggle="tooltip" title="Delete Data"
            data-target="#deleteModal" data-prefix="{{ $prefix }}">
            <i class="icon-table fa fa-trash fa-lg"></i>
        </a>
    </td>
</tr>
@endforeach
