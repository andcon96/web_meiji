@extends('layout.layout')
@section('content')
    <!-- Responsive Datatable -->
    <div class="card">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="dataTables_wrapper dt-bootstrap5 no-footer">
                <div class="card-header flex-column flex-md-row py-1">
                    <div class="head-label text-center">
                        <h5 class="card-title mb-0">Role Android Menu</h5>
                    </div>
                </div>

                <div class="card-datatable table-responsive">
                    <table id="roleTable" class="dt-responsive table border-top">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Role</th>
                                <th>Menu Access</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('setting.roleAndroidMenu.table')
                        </tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th>Role</th>
                                <th>Menu Access</th>
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
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <form action="{{ route('updateRoleAccess') }}" id="updateForm" method="POST">
                @csrf
                @method('POST')

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel4">Role Access</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col mb-3">
                                <label for="roleName" class="form-label">Role</label>
                                <input type="text" id="roleName" class="form-control" readonly />
                                <input type="hidden" id="roleId" name="roleId">
                            </div>
                        </div>
                        <div class="row">
                            <h4>
                                <center><strong>Purchase Order</strong></center>
                            </h4>
                            <div class="form-check d-flex form-switch mt-3 mb-4">
                                <label for="level" class="form-check-label col-8">Purchase Order Maint</label>

                                <input type="checkbox" class="custom-control-input form-check-input" id="cbPurchaseOrder01"
                                    name="data[]" value="PO01" />
                            </div>
                            <div class="form-check d-flex form-switch mb-4">
                                <label for="level" class="form-check-label col-8">Purchase Order Approval</label>

                                <input type="checkbox" class="custom-control-input form-check-input" id="cbPurchaseOrder02"
                                    name="data[]" value="PO02" />
                            </div>
                            <div class="form-check d-flex form-switch mb-4">
                                <label for="level" class="form-check-label col-8">Quality Info</label>

                                <input type="checkbox" class="custom-control-input form-check-input" id="cbPurchaseOrder03"
                                    name="data[]" value="PO03" />
                            </div>
                            <div class="form-check d-flex form-switch mb-4">
                                <label for="level" class="form-check-label col-8">Print QR</label>

                                <input type="checkbox" class="custom-control-input form-check-input" id="cbPurchaseOrder04"
                                    name="data[]" value="PO04" />
                            </div>
                            <h4>
                                <center><strong>Transfer Item</strong></center>
                            </h4>
                            <div class="form-check d-flex form-switch mt-3 mb-4">
                                <label for="level" class="form-check-label col-8">Transfer Item Maint</label>

                                <input type="checkbox" class="custom-control-input form-check-input" id="cbTransferItem01"
                                    name="data[]" value="TS01" />
                            </div>
                            <h4>
                                <center><strong>Shipment</strong></center>
                            </h4>
                            <div class="form-check d-flex form-switch mt-3 mb-4">
                                <label for="level" class="form-check-label col-8">Shipment Menu</label>

                                <input type="checkbox" class="custom-control-input form-check-input" id="cbShipment"
                                    name="data[]" value="S001" />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button id="saveButton" type="submit" class="btn btn-primary">Save</button>
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

            $(document).on('click', '.editRoleAcc', function() {
                let roleName = $(this).attr('data-role');
                let roleId = $(this).attr('data-roleId');
                let roleAccess = $(this).attr('data-roleAccess');

                console.log(roleAccess);
                let parts = roleAccess.split(";").filter(Boolean);

                if (parts.includes("PO01")) {
                    $('#cbPurchaseOrder01').prop('checked', 'true');
                }
                if (parts.includes("PO02")) {
                    $('#cbPurchaseOrder02').prop('checked', 'true');
                }
                if (parts.includes("PO03")) {
                    $('#cbPurchaseOrder03').prop('checked', 'true');
                }
                if (parts.includes("PO04")) {
                    $('#cbPurchaseOrder04').prop('checked', 'true');
                }
                if (parts.includes("TS01")) {
                    $('#cbTransferItem01').prop('checked', 'true');
                }
                if (parts.includes("S001")) {
                    $('#cbShipment').prop('checked', 'true');
                }


                $('#roleId').val(roleId);
                $('#roleName').val(roleName);

                $('#editModal').modal('show');

                $('#saveButton').off('click').on('click', function() {
                    $('#updateForm').submit();
                });
            });
        });
    </script>
@endsection
