@extends('layout.layout')

@section('content')
    <div class="container">
        <form action="{{ route('menus.store') }}" method="POST" enctype="multipart/form-data">
            @method('POST')
            @csrf
            <div class="card mb-4">
                <h5 class="card-header">Create Menu</h5>
                <div class="card-body">
                    <div class="mb-3 row">
                        <label for="menuName" class="col-md-2 col-form-label">
                            Menu name
                            <span id="alert1" style="color: red; font-weight: 200;">*</span>
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ old('menuName') }}" id="menuName"
                                name="menuName" required>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="menuRoute" class="col-md-2 col-form-label">
                            Menu route
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ old('menuRoute') }}" id="menuRoute"
                                name="menuRoute" />
                        </div>
                    </div>
                    {{-- <div class="mb-3 row">
                        <div class="row">
                            <label class="col-md-2 col-form-label">
                                Has approval
                            </label>
                            <div class="col-sm-9">
                                <div class="form-check mb-2">
                                    <input name="hasApproval" class="form-check-input" type="radio"
                                        value="Yes" id="collapsible-hasApproval-yes" {{old('hasApproval') == 'Yes' ? 'checked' : ''}} />
                                    <label class="form-check-label" for="collapsible-hasApproval-yes">
                                        Yes
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input name="hasApproval" class="form-check-input" type="radio"
                                        value="No" id="collapsible-hasApproval-no" {{old('hasApproval') == 'No' ? 'checked' : ''}} />
                                    <label class="form-check-label" for="collapsible-hasApproval-no">
                                        No
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div> --}}
                    <div class="row justify-content-end mt-3">
                        <div class="col-sm-9 d-flex justify-content-end">
                            <a href="{{ route('menus.index') }}" class="btn btn-label-secondary cancel">Cancel</a>
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
