@extends('layout.layout')
@section('title', $menuMaster->menu_name)
@section('content')
    <div class="card">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="dataTables_wrapper dt-bootstrap5 no-footer">
                <div class="card-header flex-column flex-md-row py-1">
                    <div class="head-label text-center">
                        {{-- <h5 class="card-title mb-0">Department</h5> --}}
                        <h5 class="card-title mb-0">{{ $menuMaster->menu_name }}</h5>
                    </div>
                    <div class="dt-action-buttons text-end pt-3 pt-md-0">
                        <div class="dt-buttons btn-group flex-wrap">
                            <form action="{{ route('appReceipts.create') }}" method="GET" enctype="multipart/form-data">
                                <button class="btn btn-secondary create-new btn-primary" tabindex="0" type="submit">
                                    <span>
                                        <span class="d-none d-sm-inline-block">Setup Approval</span>
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Responsive Datatable -->
                <div class="card-datatable table-responsive">
                    <table id="connectionTable" class="dt-responsive table border-top">
                        <thead>
                            <tr>
                                <th></th>
                                <th>User Approval</th>
                                <th>Alt User Approval</th>
                                <th>Sequence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('setting.approvalReceipt.table')
                        </tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th>User Approval</th>
                                <th>Alt User Approval</th>
                                <th>Sequence</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <!--/ Responsive Datatable -->
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="text/javascript">
        $('#connectionTable').dataTable();
    </script>
@endsection
