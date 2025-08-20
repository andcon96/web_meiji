@extends('layout.layout')

@section('content')
<div class="container">
    <div class="card mb-4">
        <h5 class="card-header">Create Approval Setup</h5>
        <div class="card-body">
            <form class="form-repeaterCustom" id="submitDetail" action="{{ route('approvalSetup.store') }}" method="POST">
                {{ csrf_field() }}
                @method('POST')
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="menu" class="form-label">Menu</label>
                        <select class="form-select" id="menu" name="menu">
                            <option selected disabled>Select Menu</option>
                            @foreach ($menus as $menu)
                            <option value="{{ $menu->id }}">{{ $menu->menu_name }}</option>
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
                                <select id="form-repeater-1-1" name="ar_user_approve" class="form-select" multiple>
                                    <option disabled>Select user</option>
                                    @foreach ($user as $users)
                                    <option value="{{ $users->id }}">{{ $users->username }} - {{ $users->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 col-lg-3 col-xl-2 col-12 mb-0">
                                <label class="form-label" for="form-repeater-1-2">Sequence</label>
                                <input type="text" id="form-repeater-1-2" name="ar_sequence" class="form-control"
                                    autocomplete="off" />
                                <input type="hidden" id="form-repeater-1-3" name="id" class="form-control"
                                    autocomplete="off" />
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
        $(document).on('click', '.submitButton', function() {
            $(this).hide();
            $('.loading-btn').css('display', '');
            $('.loadingText').removeClass('visually-hidden');
        });

        $(function() {
            $('.form-select').select2();
            var formRepeater = $('.form-repeaterCustom');
            // Form Repeater
            // ! Using jQuery each loop to add dynamic id and class for inputs. You may need to improve it based on form fields.
            // -----------------------------------------------------------------------------------------------------------------
            if (formRepeater.length) {
                var row = 2;
                var col = 1;
                formRepeater.on('submit', function(e) {
                    // e.preventDefault();
                });

                const repeater = formRepeater.repeater({
                    show: function() {
                        var fromControl = $(this).find('.form-control, .form-select');
                        var formLabel = $(this).find('.form-label');

                        fromControl.each(function(i) {
                            var id = 'form-repeater-' + row + '-' + col;
                            $(fromControl[i]).attr('id', id);
                            $(formLabel[i]).attr('for', id);
                            col++;
                        });

                        row++;

                        $(this).slideDown();
                    },
                    hide: function(e) {
                        Swal.fire({
                            title: "Confirm Delete Data ?",
                            text: "Data Will be Deleted.",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonText: "Confirm",
                        }).then((result) => {
                            /* Read more about isConfirmed, isDenied below */
                            if (result.isConfirmed) {
                                $(this).slideUp(e)
                            }
                        });
                        // confirm('Are you sure you want to delete this element?') && $(this)
                        //     .slideUp(e);
                    }
                });
            }
        });
    });
</script>
@endsection
