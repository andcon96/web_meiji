@forelse ($users as $user)
    <tr>
        <td></td>
        <td data-label="Username">{{ $user->username }}</td>
        <td data-label="Name">{{ $user->name }}</td>
        <td data-label="Email">{{ $user->email }}</td>
        <td data-label="Role">{{ $user->getRole->role_code }}</td>
        <td data-label="Department">{{ $user->getDepartment == null ? ' - ' : $user->getDepartment->department_code }}
        </td>
        <td data-label="Super User">{{ $user->is_super_user }}</td>
        <td data-label="Action">
            <a href="{{ route('users.edit', $user->id) }}" class="editdata" id='editdata'>
                <i class="icon-table fa fa-edit fa-lg"></i>
            </a>
            <a href="javascript:void(0)" class="deleteUser" data-toggle="tooltip" title="Delete Data"
                data-target="#deleteModal" data-user="{{ $user }}">
                <i class="icon-table fa fa-trash fa-lg"></i>
            </a>
            {{-- <a href="javascript:void(0)" class="resetPasswordUser" data-toggle="tooltip" title="Reset password"
                data-target="#resetPasswordModal" data-user="{{$user}}">
                <i class="fa-solid fa-key"></i>
            </a> --}}
        </td>
    </tr>
@endforeach
