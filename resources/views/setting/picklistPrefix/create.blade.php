@extends('layout.layout')

@section('content')
<div class="container">
    <form action="{{ route('PicklistPrefix.store') }}" method="POST" enctype="multipart/form-data">
        @method('POST')
        @csrf
        <div class="card mb-4">
            <h5 class="card-header">Create Picklist Prefix</h5>
            <div class="card-body">
                <div class="mb-3 row">
                    <label for="PicklistPrefix" class="col-md-2 col-form-label">
                        Picklist Prefix
                        <span id="alert1" style="color: red; font-weight: 200;">*</span>
                    </label>
                    <div class="col-md-10">
                        <input class="form-control" type="text" value="{{ old('PicklistPrefix') }}"
                            id="PicklistPrefix" name="PicklistPrefix" required />
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="YearPrefix" class="col-md-2 col-form-label">
                        Year
                        <span id="alert1" style="color: red; font-weight: 200;">*</span>
                    </label>
                    <div class="col-md-10">
                        <input class="form-control" type="text" value="{{ old('YearPrefix')}}"
                            id="YearPrefix" name="YearPrefix" required />
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="MonthPrefix" class="col-md-2 col-form-label">
                        Month
                        <span id="alert1" style="color: red; font-weight: 200;">*</span>
                    </label>
                    <div class="col-md-10">
                        <input class="form-control" type="text" value="{{ old('MonthPrefix') }}"
                            id="MonthPrefix" name="MonthPrefix" required />
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="PicklistNumber" class="col-md-2 col-form-label">
                        Running Number
                        <span id="alert1" style="color: red; font-weight: 200;">*</span>
                    </label>
                    <div class="col-md-10">
                        <input class="form-control" type="text" value="{{ old('PicklistNumber') }}"
                            id="PicklistNumber" name="PicklistNumber" required />
                    </div>
                </div>
                <div class="mt-1" style="float: inline-end;">
                    <a href="{{ route('PicklistPrefix.index') }}"
                        class="btn btn-label-secondary cancel">Cancel</a>
                    <button type="submit" class="btn btn-primary me-sm-2 me-1 submitButton">Save</button>
                    <button style="display: none;" class="btn btn-secondary loading-btn btn-primary" type="button">
                        <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                        <span class="visually-hidden loadingText">Loading...</span>
                    </button>
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
