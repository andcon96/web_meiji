@forelse ($menus as $menu)
    <tr>
        <td data-label="Name">{{ $menu->menu_name }}</td>
        <td data-label="Route">{{ $menu->menu_route }}</td>
        <td data-label="Action">
            <a href="{{ route('menus.edit', $menu->id) }}" class="editdata" id='editdata'>
                <i class="menu-table fa fa-edit fa-lg"></i>
            </a>
            <a href="javascript:void(0)" class="deleteMenu" data-toggle="tooltip" title="Delete Data"
                data-target="#deleteModal" data-menu="{{ $menu }}">
                <i class="menu-table fa fa-trash fa-lg"></i>
            </a>
        </td>
    </tr>
@endforeach
