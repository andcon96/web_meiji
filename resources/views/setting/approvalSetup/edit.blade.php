@extends('layout.layout')

@section('content')
<div class="container">
    <div class="card mb-4">
        <h5 class="card-header">Edit Approval Setup</h5>
        <div class="card-body">
            <form class="form-repeaterCustom" id="submitDetail" action="{{ route('approvalSetup.update', $data->id) }}"
                method="POST">
                {{ csrf_field() }}
                @method('PUT')
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="menu" class="form-label">Menu</label>
                        <select class="form-select menuSelection" id="menu" name="menu">
                            <option selected disabled>Select Menu</option>
                            @foreach ($menus as $menu)
                            <option value="{{ $menu->id }}" {{$data->menu_id == $menu->id ? 'selected' : ''}}>{{ $menu->menu_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <h6 class="mt-4">Detail Approval</h6>
                <hr />
                <div data-repeater-list="userApprover">
                    <div data-repeater-item>
                        <div class="row">
                            <div class="mb-3 col-lg-4 col-xl-4 col-12 mb-0">
                                <label class="form-label" for="form-repeater-1-1">User Approval</label>
                                <select id="form-repeater-1-1" name="asd_approval_user" class="form-select" multiple>
                                    <option disabled>Select user</option>
                                    @foreach ($user as $users)
                                    <option value="{{ $users->id }}">{{ $users->username }} - {{ $users->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 col-lg-3 col-xl-2 col-12 mb-0">
                                <label class="form-label" for="form-repeater-1-2">Sequence</label>
                                <input type="text" id="form-repeater-1-2" name="asd_approval_sequence" class="form-control"
                                    autocomplete="off" required />
                                <input type="hidden" id="form-repeater-1-3" name="id" class="form-control"
                                    autocomplete="off" required />
                            </div>
                            <div class="mb-3 col-lg-12 col-xl-2 col-12 d-flex align-items-center mb-0">
                                <button type="button" class="btn btn-label-danger mt-4" data-repeater-delete>
                                    <i class='bx bx-trash'></i>
                                </button>
                            </div>
                        </div>
                        <hr />
                    </div>
                </div>
                <div class="mb-0">
                    <button type="button" class="btn btn-primary" data-repeater-create>
                        <i class="bx bx-plus me-1"></i>
                        <span class="align-middle">Add</span>
                    </button>
                </div>
                <div class="row justify-content-end mt-3">
                    <div class="col-sm-9 d-flex justify-content-end">
                        <a href="{{ route('approvalSetup.index') }}" class="btn btn-label-secondary cancel">Cancel</a>
                        &nbsp;
                        &nbsp;
                        <button type="submit" class="btn btn-primary me-sm-2 me-1 submitButton">Save</button>
                        <button style="display: none;" class="btn btn-secondary loading-btn btn-primary" type="button">
                            <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                            <span class="visually-hidden loadingText">Loading...</span>
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        $('.menuSelection').select2();
        $(document).on('click', '.submitButton', function() {
            $(this).hide();
            $('.loading-btn').css('display', '');
            $('.loadingText').removeClass('visually-hidden');
        });

        $(function() {
            var formRepeater = $('.form-repeaterCustom');

            if (formRepeater.length) {
                var row = 2;
                var col = 1;

                const repeater = formRepeater.repeater({
                    show: function () {
                        var fromControl = $(this).find('.form-control, .form-select');
                        var formLabel = $(this).find('.form-label');

                        fromControl.each(function(i) {
                            var id = 'form-repeater-' + row + '-' + col;
                            $(fromControl[i]).attr('id', id);
                            $(formLabel[i]).attr('for', id);
                            col++;
                        });
                        row++;

                        // Only init select2 here for new row
                        $(this).find('.form-select').select2();

                        $(this).slideDown();
                    },
                    hide: function (e) {
                        Swal.fire({
                            title: "Confirm Delete Data ?",
                            text: "Data Will be Deleted.",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonText: "Confirm",
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $(this).slideUp(e)
                            }
                        });
                    }
                });

                // convert "11;12" -> ["11","12"]
                let dataDefault = @json($dataDetail);
                dataDefault = dataDefault.map(item => ({
                    ...item,
                    asd_approval_user: item.asd_approval_user
                        ? item.asd_approval_user.split(';')
                        : []
                }));

                repeater.setList(dataDefault);

                // now init select2 for existing rows created by setList
                formRepeater.find('[data-repeater-item]').each(function() {
                    $(this).find('.form-select').select2();
                });
            }
        });

    });
</script>
@endsection
