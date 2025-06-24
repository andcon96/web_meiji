@forelse ($roles as $role)
    <tr>
        <td data-label="Code">{{ $role->role_code }}</td>
        <td data-label="Desc">{{ $role->role_desc }}</td>
        <td data-label="Action">
            <a href="{{route('roles.edit', $role->id)}}" class="editdata" id='editdata'>
                <i class="icon-table fa fa-edit fa-lg"></i>
            </a>
            <a href="javascript:void(0)" class="deleteRole" data-toggle="tooltip" title="Delete Data"
                data-target="#deleteModal" data-role="{{$role}}">
                <i class="icon-table fa fa-trash fa-lg"></i>
            </a>
        </td>
    </tr>
@endforeach