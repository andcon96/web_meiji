@forelse ($data as $approval)
    <tr>
        <td></td>
        <td data-label="User Approve">{{ $approval->getUserApprove->name }}</td>
        <td data-label="User Alt Approve">{{ $approval->getUserApproveAlt->name }}</td>
        <td data-label="Sequence">{{ $approval->ar_sequence }}</td>
    </tr>
@endforeach
