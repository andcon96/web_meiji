@extends('layout.layout')

@section('content')
<div class="container">
    <form action="{{ route('shipperPrefix.update', $prefix->id) }}" method="POST"
        enctype="multipart/form-data">
        @method('PUT')
        @csrf
        <input type="hidden" name="u_id" id="u_id" value="{{ $prefix->id }}">
        <div class="card mb-4">
            <h5 class="card-header">Edit Shipper Prefix</h5>
            <div class="card-body">
                <div class="mb-3 row">
                    <label for="shipperPrefix" class="col-md-2 col-form-label">
                        Shipper Prefix
                        <span id="alert1" style="color: red; font-weight: 200;">*</span>
                    </label>
                    <div class="col-md-10">
                        <input class="form-control" type="text" value="{{ $prefix->shipper_prefix }}"
                            id="shipperPrefix" name="shipperPrefix" required />
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="shipperNumber" class="col-md-2 col-form-label">
                        Shipper Sequence
                        <span id="alert1" style="color: red; font-weight: 200;">*</span>
                    </label>
                    <div class="col-md-10">
                        <input class="form-control" type="number" value="{{ $prefix->shipper_number }}"
                            id="shipperNumber" name="shipperNumber" required />
                    </div>
                </div>
                <div class="mt-1" style="float: inline-end;">
                    <a href="{{ route('shipperPrefix.index') }}"
                        class="btn btn-label-secondary cancel">Cancel</a>
                    <button type="submit" class="btn btn-warning me-sm-2 me-1 submitButton">Update</button>
                    <button style="display: none;" class="btn btn-secondary loading-btn btn-primary" type="button">
                        <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                        <span class="visually-hidden loadingText">Loading...</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
    <!-- Form Label Alignment -->

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
