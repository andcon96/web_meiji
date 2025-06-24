@extends('layout.layout')

@section('content')
    <div class="container">
        <form action="{{ route('roles.update', $dataRole->id) }}" method="POST" enctype="multipart/form-data">
            @method('PUT')
            @csrf
            <input type="hidden" name="u_id" id="u_id" value="{{ $dataRole->id }}">
            <div class="card mb-4">
                <h5 class="card-header">Edit Role</h5>
                <div class="card-body">
                    <div class="mb-3 row">
                        <label for="roleCode" class="col-md-2 col-form-label">
                            Role code
                            <span id="alert1" style="color: red; font-weight: 200;">*</span>
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $dataRole->role_code }}" id="roleCode"
                                name="roleCode" disabled>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="roleDesc" class="col-md-2 col-form-label">
                            Role Desc
                            <span id="alert1" style="color: red; font-weight: 200;">*</span>
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $dataRole->role_desc }}" id="roleDesc"
                                name="roleDesc" required />
                        </div>
                    </div>
                    <div class="row justify-content-end mt-3">
                        <div class="col-sm-9 d-flex justify-content-end">
                            <a href="{{ route('roles.index') }}" class="btn btn-label-secondary cancel">Cancel</a>
                            &nbsp;
                            &nbsp;
                            <button type="submit" class="btn btn-primary me-sm-2 me-1 submitButton">Save</button>
                            <button style="display: none;" class="btn btn-secondary loading-btn btn-primary" type="button">
                                <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                                <span class="visually-hidden loadingText">Loading...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
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
        });
    </script>
@endsection
