@forelse ($data as $approval)
<tr>
    <td></td>
    <td data-label="Menu">{{ $approval->getMenu->menu_name }}</td>
    <td>
        <a href="{{ route('approvalSetup.edit', $approval->id) }}" class="editdata" id='editdata'>
            <i class="icon-table fa fa-edit fa-lg"></i>
        </a>
    </td>
</tr>
@endforeach
