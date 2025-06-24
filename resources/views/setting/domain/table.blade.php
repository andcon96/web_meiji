@forelse ($domains as $domain)
    <tr>
        <td data-label="Code">{{ $domain->domain }}</td>
        <td data-label="Desc">{{ $domain->domain_desc }}</td>
        <td data-label="Action">
            <a href="{{route('domains.edit', $domain->id)}}" class="editdata" id='editdata'>
                <i class="domain-table fa fa-edit fa-lg"></i>
            </a>
            <a href="javascript:void(0)" class="deleteDomain" data-toggle="tooltip" title="Delete Data"
                data-target="#deleteModal" data-domain="{{$domain}}">
                <i class="domain-table fa fa-trash fa-lg"></i>
            </a>
        </td>
    </tr>
@endforeach