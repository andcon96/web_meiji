@extends('layout.layout')

@section('content')
    <div class="container">
        <form action="{{ route('icons.update', $icon->id) }}" method="POST" enctype="multipart/form-data">
            @method('PUT')
            @csrf
            <input type="hidden" name="u_id" id="u_id" value="{{$icon->id}}">
            <div class="card mb-4">
                <h5 class="card-header">Edit Icon</h5>
                <div class="card-body">
                    <div class="mb-3 row">
                        <label for="iconName" class="col-md-2 col-form-label">
                            Icon name
                            <span id="alert1" style="color: red; font-weight: 200;">*</span>
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{$icon->icon_name}}" id="iconName" name="iconName">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="iconDesc" class="col-md-2 col-form-label">
                            Icon description
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{$icon->icon_desc}}" id="iconDesc" name="iconDesc" />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="iconValue" class="col-md-2 col-form-label">
                            Icon value
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{$icon->icon_value}}" id="iconValue" name="iconValue" required />
                        </div>
                    </div>
                    <div class="row justify-content-end mt-3">
                        <div class="col-sm-9 d-flex justify-content-end">
                            <a href="{{route('icons.index')}}" class="btn btn-label-secondary cancel">Cancel</a>
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
