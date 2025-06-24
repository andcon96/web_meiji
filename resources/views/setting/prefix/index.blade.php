@extends('layout.layout')
@section('title', $menuMaster->menu_name)
@section('content')
    <!-- Responsive Datatable -->
    <div class="card">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="dataTables_wrapper dt-bootstrap5 no-footer">
                <div class="card-header flex-column flex-md-row py-1">
                    <div class="head-label text-center">
                        <h5 class="card-title mb-0">{{ $menuMaster->menu_name }}</h5>
                    </div>
                    <div class="dt-action-buttons text-end pt-3 pt-md-0">
                        <div class="dt-buttons btn-group flex-wrap">
                            <form action="{{ route('prefix.create') }}" method="GET" enctype="multipart/form-data">
                                <button class="btn btn-secondary create-new btn-primary" tabindex="0" type="submit">
                                    <span>
                                        <i class="bx bx-plus me-sm-1"></i>
                                        <span class="d-none d-sm-inline-block">Add New Record</span>
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-datatable table-responsive">
                    <table id="roleTable" class="dt-responsive table border-top">
                        <thead>
                            <tr>
                                <th>Receipt Number</th>
                                <th>Buku Penerimaan Number</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('setting.prefix.table')
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Receipt Number</th>
                                <th>Buku Penerimaan Number</th>
                                <th>Action</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!--/ Responsive Datatable -->

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form action="{{ route('deletePrefix') }}" id="deleteForm" method="POST" enctype="multipart/form-data">
                @method('POST')
                @csrf
                <input type="hidden" id="d_id" name="d_id" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="prefixTitle"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col mb-3">
                                <label for="nameAnimation" class="form-label">Are you sure you want to delete this data?
                                    this action cannot be reversed</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="deleteButton" class="btn btn-danger">Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- End delete modal -->
@endsection


@section('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#roleTable').dataTable();

            $(document).on('click', '.deletedata', function() {
                let prefixStr = $(this).attr('data-prefix');
                let prefix = JSON.parse(prefixStr);
                console.log(prefix);
                let prefixID = prefix.id;
                let prefixDomain = prefix.get_domain.domain;

                $('#deleteModal').modal('show');
                $('#prefixTitle').empty().html(prefixDomain);
                $('#d_id').val(prefixID);

                $('#deleteButton').off('click').on('click', function() {
                    $('#deleteForm').submit();
                });
            });
        });
    </script>
@endsection
