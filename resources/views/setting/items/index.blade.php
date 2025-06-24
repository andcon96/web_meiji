@extends('layout.layout')
@section('title', $menuMaster->menu_name)
@section('content')

    <!-- Responsive Datatable -->
    <div class="card">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="dataTables_wrapper dt-bootstrap5 no-footer">
                <div class="card-header flex-column flex-md-row py-1">
                    <div class="head-label text-center">
                        {{-- <h5 class="card-title mb-0">Menu</h5> --}}
                        <h5 class="card-title mb-0">{{ $menuMaster->menu_name }}</h5>
                    </div>
                    <div class="dt-action-buttons text-end pt-3 pt-md-0">
                        <div class="dt-buttons btn-group flex-wrap">
                            <form action="{{ route('loadItem') }}" method="POST" enctype="multipart/form-data">
                                @method('POST')
                                @csrf
                                <button class="btn btn-secondary create-new btn-primary" tabindex="0" type="submit">
                                    <span id="loadItem">
                                        <i class='bx bx-sync'></i>
                                        <span class="d-none d-sm-inline-block">Load Item</span>
                                    </span>
                                    <span style="display: none;" class="spinner-border me-1" role="status"
                                        aria-hidden="true"></span>
                                    <span class="visually-hidden" id="menuLoader">Loading...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-datatable table-responsive">
                    <table id="itemTable" class="dt-responsive table border-top">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Item Code</th>
                                <th>Item Desc</th>
                                <th>Item UM</th>
                                <th>Prod Line</th>
                                <th>Group</th>
                                <th>Type</th>
                                <th>Price</th>
                                <th>Loaded by</th>
                                <th>Updated by</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('setting.items.table')
                        </tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th>Item Code</th>
                                <th>Item Desc</th>
                                <th>Item UM</th>
                                <th>Prod Line</th>
                                <th>Group</th>
                                <th>Type</th>
                                <th>Price</th>
                                <th>Loaded by</th>
                                <th>Updated by</th>
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
    <script type="text/javascript">
        $(document).ready(function() {
            $('#itemTable').dataTable();

            $(document).on('click', '.create-new', function(e) {
                // e.preventDefault();
                $('#loadItem').css('display', 'none');
                $('.spinner-border').css('display', '');
                $('#menuLoader').removeClass();
            });
        });
    </script>
@endsection
