@forelse ($roles as $role)
    <tr>
        <td></td>
        <td data-label="Role">{{ $role->role_desc }}</td>
        <td data-label="Access">
        @foreach ($role->getMenuAccess as $access)
        {{ $access->getMenu->menu_name .','}}
        @endforeach
        </td>
        <td data-label="Action">
            <a href="javascript:void(0)" class="editRoleAcc" data-toggle="tooltip" title="Delete Data"
                data-target="#editModal" data-role="{{ $role->role_desc }}" data-roleId="{{ $role->id }}"
                data-roleAccess="{{ $role->role_android_acc }}">
                <i class="icon-table fa fa-edit fa-lg"></i>
            </a>
        </td>
    </tr>
@endforeach
