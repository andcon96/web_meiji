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
                            <form id="formOSSDExport" action="{{ route('OSSDExport') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" id='rows' name="ossdrows"/>
                                <input type="hidden" id='nbr_mstr' name="nbr_mstr"/>
                               {{--  <button id="exportButton" class="btn btn-secondary create-new btn-primary" tabindex="0" type="submit">
                                    <span>
                                        <i class='fa-solid fa-file me-sm-2'></i>
                                        <span class="d-none d-sm-inline-block">Export To Excel</span>
                                    </span>
                                </button>
                                <button style="display: none;" class="btn loading-btn btn-primary" type="button">
                                    <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                                    <span class="visually-hidden loadingText">Loading...</span>
                                </button> --}}
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-datatable table-responsive">
                    <table id="othershipmentTable" class="dt-responsive table border-top">
                        <thead>
                            <tr>
                                <th>Number</th>
                                {{-- <th>Customer Code</th>
                                <th>Customer Description</th> --}}
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <body></body>
                        {{-- <tfoot>
                            <tr>
                                <th>Number</th>
                                <th>Customer Code</th>
                                <th>Customer Description</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </tfoot> --}}
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
            let table = $('#othershipmentTable').DataTable({
                ajax: '{{ url("/getAllOSSM") }}', 
                serverSide: true,
                columns: [
                    {data: 'ossm_number',    width: '32em'},
                    /* {data: 'ossm_cust_code', width: '12em'},
                    {data: 'ossm_cust_desc', width: '32em'}, */
                    {data: 'ossm_status',    width: '32em'},
                    {
                        data: null,
                        width: '12em',
                        sortable: false,
                        render: function(data, type, row, meta){
                            return `
                                <button data-detail='${JSON.stringify(row.get_other_shipment_schedule_detail)}' data-id='${row.id}' class="btn btn-secondary px-2 p-1 btnDetail" tabindex="0" type="button">Detail</button>
                                <button data-detail='${JSON.stringify(row.get_other_shipment_schedule_detail)}' data-nbr-mstr='${row.ossm_number}' class="btn btn-primary px-2 py-1 btnExport" tabindex="0" type="button">Export</button>
                            `
                        }
                    },
                ],
                scrollX: true
            });

            // //dijalankan setiap kali DataTables selesai melakukan request AJAX ke server
            // table.on('xhr.dt', function(e, settings, json){
            //     //simpan rows yg ditampilkan saat ini ke input value, untuk dikirim saat export excel
            //     $('#rows').val(JSON.stringify(json.data))
            // }) 

            //klik button export
            $(document).on('click', '.btnExport', function(){
                const $rows = $(this).data('detail');
                const $nbrMstr = $(this).data('nbrMstr');

                /* console.log($nbrMstr); */

                $('#rows').val(JSON.stringify($rows))
                $('#nbr_mstr').val($nbrMstr)
                $('#formOSSDExport').submit()

                /* $(this).hide()
                $('.loading-btn').css('display', '');
                $('.loadingText').removeClass('visually-hidden'); */

                const $nested = $(this).closest('tbody').children('tr.nested'); //ambil semua detail yg sedang tampil
                const $tr = $(this).closest('tr') //row master, wrapper button
                const $masterId = $(this).data('id');

                $.each($nested || [], function(index, tr){ //tutup detail lain yg sedang tampil
                    tr.remove();
                })
            })

            // klik button detail
            $(document).on('click', '.btnDetail', function(){
                const $nested = $(this).closest('tbody').children('tr.nested'); //ambil semua detail yg sedang tampil
                const $tr = $(this).closest('tr') //row master, wrapper button
                const $rows = $(this).data('detail'); //ambil data yg disimpan di attribute button, dengan prefix data
                const $masterId = $(this).data('id');

                if($tr.next().hasClass(`detail-master-${$masterId}`)){
                    $tr.next().remove(); //tututp detail, baris(tr)
                }else{
                    let html = buildDetailTable($rows || [], $masterId)
                    $tr.after(html) //simpan di baris(tr) baru setelah masternya, dengan penanda class
                    $.each($nested || [], function(index, tr){ //tutup detail lain yg sedang tampil
                        tr.remove();
                    })
                }
            })

            //function build table detail
            function buildDetailTable(rows, masterId){

                //buat baris(tr) baru setelah baris(tr) master
                //buat satu cell yg merge lima column
                //didalam cell tersebut buat table baru

                let detailTable = `
                <tr class='nested detail-master-${masterId}'>
                    <td colspan="5">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Item Number</th>
                                    <th>Item Description</th>
                                    <th>UM</th>
                                    <th>Qty Ordered</th>
                                    <th>No. Lot</th>
                                </tr>
                            </thead>
                            <tbody>`


                            $.each(rows, function (index, row) {
                                $.each(row.get_other_shipment_schedule_location, function (index, loc) {
                                    detailTable += `
                                    <tr>
                                        <td>${row.order}</td>
                                        <td>${row.ossd_part}</td>
                                        <td style="white-space: normal !important; word-wrap: break-word !important; max-width: 250px;">${row.ossd_desc}</td>
                                        <td>${row.ossd_uom}</td>
                                        <td>${row.ossd_qty_ord}</td>
                                        <td>${loc.ossl_lotserial}</td>
                                    </tr>`
                                })
                            })

                detailTable += `
                            </tbody>
                        </table>
                    </td>
                </tr>
                `

                return detailTable
            }
        })
    </script>
@endsection