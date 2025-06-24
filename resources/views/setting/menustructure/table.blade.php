@forelse ($menusStructure as $key => $menuStructure)
    <tr>
        <td data-label="Menu sequence">{{ $menuStructure->menu_sequence }}</td>
        <td data-label="Icon">{!! $menuStructure->getIcon == NULL ? '-' : '<i class="' . $menuStructure->getIcon->icon_value . '"></i>' !!}</td>
        <td data-label="Menu name">{{ $menuStructure->getMenu == NULL ? '-' : $menuStructure->getMenu->menu_name }}</td>
        <td data-label="Menu parent">{{ $menuStructure->getMenuParent == NULL ? '-' : $menuStructure->getMenuParent->menu_name }}</td>
        <td data-label="Action">
            <a href="{{route('menuStructure.edit', $menuStructure->id)}}" class="editdata" id='editdata'>
                <i class="menuStructure-table fa fa-edit fa-lg"></i>
            </a>
            <a href="javascript:void(0)" class="deleteMenuStructure" data-toggle="tooltip" title="Delete Data"
                data-target="#deleteModal" data-menustructure="{{$menuStructure}}">
                <i class="menuStructure-table fa fa-trash fa-lg"></i>
            </a>
            @if ($key > 0)
                <a href="javascript:void(0)" class="moveUpMenuStructure" data-toggle="tooltip" title="Move Up"
                    data-target="#moveUp" data-id="{{$menuStructure->id}}">
                    <i class="menuStructure-table fa-solid fa-circle-up fa-lg"></i>
                </a>
            @endif
            @if (!$loop->last)
                <a href="javascript:void(0)" class="moveDownMenuStructure" data-toggle="tooltip" title="Move Down"
                    data-target="#moveDown" data-id="{{$menuStructure->id}}">
                    <i class="menuStructure-table fa-solid fa-circle-down fa-lg"></i>
                </a>
            @endif
        </td>
    </tr>
@endforeach