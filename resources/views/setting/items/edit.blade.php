@extends('layout.layout')

@section('content')
    <div class="container">
        <form action="{{ route('items.update', $item->id) }}" method="POST" enctype="multipart/form-data">
            @method('PUT')
            @csrf
            <input type="hidden" name="u_id" id="u_id" value="{{ $item->id }}">
            <div class="card mb-4">
                <h5 class="card-header">Edit Item</h5>
                <div class="card-body">
                    <div class="mb-3 row">
                        <label for="itemPart" class="col-md-2 col-form-label">
                            Item part
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $item->im_item_part }}" id="itemPart"
                                name="itemPart" disabled />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="itemDesc" class="col-md-2 col-form-label">
                            Item Description
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $item->im_item_desc }}" id="itemDesc"
                                name="itemDesc" disabled />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="itemHyperlink" class="col-md-2 col-form-label">
                            Item Hyperlink
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $item->im_item_hyperlink }}"
                                id="itemHyperlink" name="itemHyperlink" />
                        </div>
                    </div>
                    <div class="row justify-content-end mt-3">
                        <div class="col-sm-9 d-flex justify-content-end">
                            <a href="{{ route('items.index') }}" class="btn btn-label-secondary cancel">Cancel</a>
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
