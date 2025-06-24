@extends('layout.layout')

@section('content')
    <div class="container">
        <form action="{{ route('itemlocation.store') }}" method="POST" enctype="multipart/form-data">
            @method('POST')
            @csrf
            <div class="card mb-4">
                <h5 class="card-header">Create Item Location</h5>
                <div class="card-body">
                    <div class="mb-3 row">
                        <label for="locationSite" class="col-md-2 col-form-label">
                            Site
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $data->getMaster->location_site }}"
                                id="locationSite" name="locationSite" disabled />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="locationDesc" class="col-md-2 col-form-label">
                            Location
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $data->getMaster->location_desc }}"
                                id="locationDesc" name="locationDesc" disabled />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="lotSerial" class="col-md-2 col-form-label">
                            Lot Serial
                        </label>
                        <div class="col-md-4">
                            <input class="form-control" type="text" value="{{ $data->ld_lot_serial }}" id="lotSerial"
                                name="lotSerial" disabled />
                        </div>
                        <label for="building" class="col-md-2 col-form-label">
                            Building
                        </label>
                        <div class="col-md-4">
                            <input class="form-control" type="text" value="{{ $data->ld_building }}" id="building"
                                name="building" disabled />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="level" class="col-md-2 col-form-label">
                            Level
                        </label>
                        <div class="col-md-4">
                            <input class="form-control" type="text" value="{{ $data->ld_rak }}" id="level"
                                name="level" disabled />
                        </div>
                        <label for="bin" class="col-md-2 col-form-label">
                            Bin
                        </label>
                        <div class="col-md-4">
                            <input class="form-control" type="text" value="{{ $data->ld_bin }}" id="bin"
                                name="bin" disabled />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="item" class="col-md-2 col-form-label">
                            Item Part
                            <span id="alert1" style="color: red; font-weight: 200;">*</span>
                        </label>
                        <div class="col-md-10">
                            <select id="item" name="item" class="select2-icons form-select" data-allow-clear="true">
                                <option value="">Select value</option>
                                @foreach ($item as $items)
                                    <option value="{{ $items->id }}">{{ $items->im_item_part }} -
                                        {{ $items->im_item_desc }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="idLocation" value="{{ $data->id }}">
                        </div>
                    </div>
                    <div class="row justify-content-end mt-3">
                        <div class="col-sm-9 d-flex justify-content-end">
                            <a href="{{ route('itemlocation.index') }}" class="btn btn-label-secondary cancel">Cancel</a>
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
