@forelse ($data->getListItem as $datas)
    <tr>
        <td data-label="Site">{{ $datas->getItem->im_item_part }}</td>
        <td data-label="Site">{{ $datas->getItem->im_item_desc }}</td>
        <td data-label="Site">{{ $datas->getItem->im_item_um }}</td>
        <td data-label="Site">{{ $datas->getItem->im_item_price }}</td>
        <td data-label="Action">
            <form action="{{ route('itemlocation.destroy', $datas->id) }}" method="POST" class="formDelete">
                @csrf
                @method('delete')
                <a href="#" class="delete deleteBtn">
                    <i class="icon-table fa fa-trash fa-lg"></i>
                </a>
            </form>
        </td>
    </tr>
@endforeach
