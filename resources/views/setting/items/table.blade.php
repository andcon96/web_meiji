@forelse ($items as $item)
    <tr>
        <td></td>
        <td data-label="Code">{{ $item->im_item_part }}</td>
        <td data-label="Desc">{{ $item->im_item_desc }}</td>
        <td data-label="UM">{{ $item->im_item_um }}</td>
        <td data-label="Prod Line">{{ $item->im_item_prod_line }}</td>
        <td data-label="Group">{{ $item->im_item_group }}</td>
        <td data-label="Type">{{ $item->im_item_type }}</td>
        <td data-label="Price">{{ $item->im_item_price }}</td>
        <td data-label="Loaded By">{{ $item->getLoadedBy->name }}</td>
        <td data-label="Updated By">{{ $item->getUpdatedBy != null ? $item->getUpdatedBy->name : '' }}</td>
    </tr>
@endforeach
