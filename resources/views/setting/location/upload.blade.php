@extends('layout.layout')

@section('content')
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            display: table;
            width: 100%;
            /* Ensures full width */
            table-layout: fixed;
            /* Ensures columns are aligned */
        }

        tbody {
            display: block;
            /* Allows scrolling */
            height: 200px;
            /* Fixed height */
            overflow-y: auto;
            /* Vertical scroll */
            width: 100%;
            /* Ensures full width */
        }

        tbody tr {
            display: table;
            width: 100%;
            /* Ensures rows fill tbody width */
            table-layout: fixed;
            /* Ensures columns align */
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
            /* Header styling */
        }
    </style>
    <!-- Content wrapper -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <!-- Basic  -->
            <div class="col-12">
                <div class="card mb-4">
                    <h5 class="card-header">Upload Location</h5>
                    <div class="card-body">
                        <form action="{{ route('checkFileUploadLocation') }}" method="POST" class="dropzone needsclick">
                            @csrf
                            <div class="dz-message needsclick">
                                Drop files here or click to upload
                            </div>
                        </form>
                        <form action="{{ route('confirmFileUploadLocation') }}" method="POST">
                            @csrf
                            @method('POST')
                            <div class="col-12 mt-4">
                                <h6>Preview Data</h6>
                                <table class="table table-bordered table-hover">
                                    <tbody id="previewData">

                                    </tbody>
                                </table>
                                <div class="d-flex mt-3">
                                    <input type="hidden" name="tempFileName" id="tempFileName">
                                    <a href="{{ route('locations.index') }}" class="btn btn-label-secondary mr-3">Cancel</a>
                                    &nbsp;
                                    <button type="submit" class="btn btn-info pl-3" id="btnSubmit" style="display: none;">
                                        <span class="d-none d-sm-inline-block submitBtn">Submit</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- /Basic  -->
        </div>
    </div>
    <!-- Content wrapper -->
@endsection


@section('scripts')
    <script type="text/javascript">
        let urlTempFile = '';
        const myDropzone = new Dropzone('.dropzone', {
            parallelUploads: 1,
            maxFilesize: 2,
            addRemoveLinks: true,
            maxFiles: 1,
            // acceptedFiles: '.xls, .xlsx',
            init: function() {
                this.on("success", function(file, response) {
                    urlTempFile = response['imageName'];
                    const data = response['data'];
                    const tbody = document.getElementById('previewData');

                    document.getElementById('tempFileName').value = urlTempFile;

                    tbody.innerHTML = '';
                    data.forEach(element => {
                        const row = document.createElement('tr');
                        element.forEach(isiData => {
                            const cell = document.createElement('td');
                            cell.textContent = isiData !== null ? isiData : 'N/A';
                            row.appendChild(cell);
                        });
                        tbody.appendChild(row);
                    });

                    const button = document.getElementById('btnSubmit');
                    button.style.display = 'block';
                });
                this.on("error", function(file, response) {
                    const tbody = document.getElementById('previewData');
                    tbody.innerHTML = '';
                });
            }
        });

        $('#btnSubmit').on('click', function(e) {
            $.blockUI({
                message: '<div class="spinner-border text-white" role="status"></div>',
                css: {
                    backgroundColor: 'transparent',
                    border: '0'
                },
                overlayCSS: {
                    opacity: 0.5
                }
            });
        });
    </script>
@endsection
