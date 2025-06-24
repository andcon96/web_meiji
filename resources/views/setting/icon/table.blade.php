@forelse ($icons as $icon)
    <tr>
        <td data-label="Name">{{ $icon->icon_name }}</td>
        <td data-label="Desc">{{ $icon->icon_desc }}</td>
        <td data-label="Value"><i class="{{ $icon->icon_value }}"></i></td>
        <td data-label="Action">
            <a href="{{route('icons.edit', $icon->id)}}" class="editdata" id='editdata'>
                <i class="icon-table fa fa-edit fa-lg"></i>
            </a>
            <a href="javascript:void(0)" class="deleteIcon" data-toggle="tooltip" title="Delete Data"
                data-target="#deleteModal" data-icon="{{$icon}}">
                <i class="icon-table fa fa-trash fa-lg"></i>
            </a>
        </td>
    </tr>
@endforeach