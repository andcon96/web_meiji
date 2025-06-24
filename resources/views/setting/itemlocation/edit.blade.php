@extends('layout.layout')
@section('title', 'Detail Item Location')
@section('content')

    <!-- Responsive Datatable -->
    <div class="card">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="dataTables_wrapper dt-bootstrap5 no-footer">
                <div class="card-datatable table-responsive">
                    <h5 class="card-header">Edit Location</h5>
                    <div class="mb-3 row">
                        <label for="locationCode" class="col-md-2 col-form-label">
                            Site
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $data->location_site }}" id="locationCode"
                                name="locationCode" disabled />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="locationDesc" class="col-md-2 col-form-label">
                            Location
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $data->location_desc }}" id="locationDesc"
                                name="locationDesc" disabled />
                        </div>
                    </div>
                    <table id="itemTable" class="dt-responsive table border-top">
                        <thead>
                            <tr>
                                <th>Lot Serial</th>
                                <th>Building</th>
                                <th>Level</th>
                                <th>Bin</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('setting.itemlocation.table-edit')
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Lot Serial</th>
                                <th>Building</th>
                                <th>Level</th>
                                <th>Bin</th>
                                <th>Action</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!--/ Responsive Datatable -->
@endsection

@section('scripts')
@endsection
