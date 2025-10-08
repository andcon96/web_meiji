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
                            <form id="formSSDExport" action="{{ route('SSDExport') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" id='rows' name="ssdrows"/>
                                <button id="exportButton" class="btn btn-secondary create-new btn-primary" tabindex="0" type="submit">
                                    <span>
                                        <i class='fa-solid fa-file me-sm-2'></i>
                                        <span class="d-none d-sm-inline-block">Export To Excel</span>
                                    </span>
                                </button>
                                {{-- <button style="display: none;" class="btn loading-btn btn-primary" type="button">
                                    <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                                    <span class="visually-hidden loadingText">Loading...</span>
                                </button> --}}
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-datatable table-responsive">
                    <table id="shipmentTable" class="dt-responsive table border-top">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Sold-To</th>
                                <th>Item Number</th>
                                <th>UM</th>
                                <th>Qty Ordered</th>
                                <th>No. Lot</th>
                            </tr>
                        </thead>
                        <body></body>
                        <tfoot>
                            <tr>
                                <th>Order</th>
                                <th>Sold-To</th>
                                <th>Item Number</th>
                                <th>UM</th>
                                <th>Qty Ordered</th>
                                <th>No. Lot</th>
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
        $(document).ready(function () {
            //datatable
            let table = $('#shipmentTable').DataTable({
                ajax: '{{ url("/getAllSSD") }}', 
                serverSide: true,
                columns: [
                    {data: 'ssd_sod_nbr',     width: '12em'},
                    {data: 'sold_to',         width: '12em'},
                    {data: 'ssd_sod_part',    width: '12em'},
                    {data: 'ssd_uom',         width: '12em'},
                    {data: 'ssd_sod_qty_ord', width: '12em'},
                    {data: 'ssd_sod_lot',     width: '12em'},
                ],
            });

            //dijalankan setiap kali DataTables selesai melakukan request AJAX ke server
            table.on('xhr.dt', function(e, settings, json){
                //simpan rows yg ditampilkan saat ini ke input value, untuk dikirim saat export excel
                $('#rows').val(JSON.stringify(json.data))
            }) 

            $('#exportButton').on('click', function(){
                /* $(this).hide()
                $('.loading-btn').css('display', '');
                $('.loadingText').removeClass('visually-hidden'); */
            })

        })
    </script>
@endsection