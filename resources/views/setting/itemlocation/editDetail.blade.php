@extends('layout.layout')
@section('title', 'Detail Item Location')
@section('content')
    <!-- Responsive Datatable -->
    <div class="card">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="dataTables_wrapper dt-bootstrap5 no-footer">
                <div class="card-datatable table-responsive">
                    <h5 class="card-header"><span>Item Location</span>
                        <div class="card-header-elements ms-auto">
                            <div class="btn-group">
                                <a href="{{ route('itemlocation.index') }}" class="btn btn-warning">Back</a>
                                &nbsp;
                                <a href="{{ route('createItemLocationDetail', ['id' => $data->id]) }}"
                                    class="btn btn-primary">New
                                    Data</a>
                            </div>
                        </div>
                    </h5>
                    <div class="mb-3 row">
                        <label for="locationSite" class="col-md-2 col-form-label">
                            Site
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $data->getMaster->location_site }}"
                                id="locationSite" name="locationSite" disabled />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="locationDesc" class="col-md-2 col-form-label">
                            Location
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $data->getMaster->location_desc }}"
                                id="locationDesc" name="locationDesc" disabled />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="lotSerial" class="col-md-2 col-form-label">
                            Lot Serial
                        </label>
                        <div class="col-md-4">
                            <input class="form-control" type="text" value="{{ $data->ld_lot_serial }}" id="lotSerial"
                                name="lotSerial" disabled />
                        </div>
                        <label for="building" class="col-md-2 col-form-label">
                            Building
                        </label>
                        <div class="col-md-4">
                            <input class="form-control" type="text" value="{{ $data->ld_building }}" id="building"
                                name="building" disabled />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="level" class="col-md-2 col-form-label">
                            Level
                        </label>
                        <div class="col-md-4">
                            <input class="form-control" type="text" value="{{ $data->ld_rak }}" id="level"
                                name="level" disabled />
                        </div>
                        <label for="bin" class="col-md-2 col-form-label">
                            Bin
                        </label>
                        <div class="col-md-4">
                            <input class="form-control" type="text" value="{{ $data->ld_bin }}" id="bin"
                                name="bin" disabled />
                        </div>
                    </div>
                    <table id="itemTable" class="dt-responsive table border-top">
                        <thead>
                            <tr>
                                <th>Item Part</th>
                                <th>Item Desc</th>
                                <th>UM</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('setting.itemlocation.table-editDetail')
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!--/ Responsive Datatable -->
@endsection

@section('scripts')
    <script>
        $('.deleteBtn').on('click', function() {
            Swal.fire({
                title: "Confirm Delete Data ?",
                text: "Data Will be Deleted.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Confirm",
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    const parent = $(this).parent();
                    parent.submit();
                }
            });
        });
    </script>
@endsection
