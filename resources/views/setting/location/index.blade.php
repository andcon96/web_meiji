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

                    <div class="card-header-elements ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary">Actions</button>
                            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                                data-bs-toggle="dropdown"></button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="javascript:void(0)" id="buttonLoadLocation">
                                    <span id="loadLocation">
                                        <i class='bx bx-sync'></i>
                                        <span class="ml-2 d-none d-sm-inline-block">Load Data</span>
                                    </span>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('downloadTemplateLoadLocation') }}" target="_blank">
                                    <span id="downloadTemplateExcel">
                                        <i class='bx bxs-download'></i>
                                        <span class="ml-2 d-none d-sm-inline-block">Download Template</span>
                                    </span>
                                </a>
                                <a class="dropdown-item" href="{{ route('uploadLocationDetail') }}">
                                    <span id="downloadTemplateExcel">
                                        <i class='bx bx-upload'></i>
                                        <span class="ml-2 d-none d-sm-inline-block">Upload Location Detail</span>
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <form id="formLoadLocation" action="{{ route('loadLocation') }}" method="POST" style="display:none;"
                        enctype="multipart/form-data">
                        @method('POST')
                        @csrf
                        <button class="btn btn-secondary create-new btn-primary" tabindex="0" type="submit">
                            <span id="loadLocation">
                                <i class='bx bx-sync'></i>
                                <span class="d-none d-sm-inline-block">Load Data</span>
                            </span>
                            <span style="display: none;" class="spinner-border me-1" role="status"
                                aria-hidden="true"></span>
                            <span class="visually-hidden" id="menuLoader">Loading...</span>
                        </button>
                    </form>
                </div>
                <div class="card-datatable table-responsive">
                    <table id="itemTable" class="dt-responsive table border-top">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Site</th>
                                <th>Location</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('setting.location.table')
                        </tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th>Site</th>
                                <th>Location</th>
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
    <script type="text/javascript">
        $(document).ready(function() {
            $('#itemTable').dataTable();

            $(document).on('click', '.create-new', function(e) {
                // e.preventDefault();
                $('#loadLocation').css('display', 'none');
                $('.spinner-border').css('display', '');
                $('#menuLoader').removeClass();
            });

            $("#buttonLoadLocation").on('click', function() {
                $('#card-block').block({
                    message: '<div class="spinner-border text-primary" role="status"></div>',
                    css: {
                        backgroundColor: 'transparent',
                        border: '0'
                    },
                    overlayCSS: {
                        backgroundColor: '#fff',
                        opacity: 0.8
                    }
                });
                $('#formLoadLocation').submit();
            });

            $("#buttonDownloadTemplateExcel").on('click', function() {


            });
        });
    </script>
@endsection
