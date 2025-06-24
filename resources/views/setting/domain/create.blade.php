@extends('layout.layout')

@section('content')
    <div class="container">
        <form action="{{ route('domains.store') }}" method="POST" enctype="multipart/form-data">
            @method('POST')
            @csrf
            <div class="card mb-4">
                <h5 class="card-header">Create Domain</h5>
                <div class="card-body">
                    <div class="mb-3 row">
                        <label for="domainCode" class="col-md-2 col-form-label">
                            Domain
                            <span id="alert1" style="color: red; font-weight: 200;">*</span>
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{old('domainCode')}}" id="domainCode" name="domainCode" required>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="domainDesc" class="col-md-2 col-form-label">
                            Domain description
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{old('domainDesc')}}" id="domainDesc" name="domainDesc" />
                        </div>
                    </div>
                    <div class="row justify-content-end mt-3">
                        <div class="col-sm-9 d-flex justify-content-end">
                            <a href="{{route('domains.index')}}" class="btn btn-label-secondary cancel">Cancel</a>
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
