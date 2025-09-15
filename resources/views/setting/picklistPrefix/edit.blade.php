@extends('layout.layout')

@section('content')
<div class="container">
    <form action="{{ route('PicklistPrefix.update', $prefix->id) }}" method="POST"
        enctype="multipart/form-data">
        @method('PUT')
        @csrf
        <input type="hidden" name="u_id" id="u_id" value="{{ $prefix->id }}">
        <div class="card mb-4">
            <h5 class="card-header">Edit Picklist Prefix</h5>
            <div class="card-body">
                <div class="mb-3 row">
                    <label for="PicklistPrefix" class="col-md-2 col-form-label">
                        Picklist Prefix
                        <span id="alert1" style="color: red; font-weight: 200;">*</span>
                    </label>
                    <div class="col-md-10">
                        <input class="form-control" type="text" value="{{ $prefix->prefix_wo }}"
                            id="PicklistPrefix" name="PicklistPrefix" required />
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="YearPrefix" class="col-md-2 col-form-label">
                        Year
                        <span id="alert1" style="color: red; font-weight: 200;">*</span>
                    </label>
                    <div class="col-md-10">
                        <input class="form-control" type="text" value="{{ $prefix->prefix_year_wo }}"
                            id="YearPrefix" name="YearPrefix" required />
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="MonthPrefix" class="col-md-2 col-form-label">
                        Month
                        <span id="alert1" style="color: red; font-weight: 200;">*</span>
                    </label>
                    <div class="col-md-10">
                        <input class="form-control" type="text" value="{{ $prefix->prefix_month_wo }}"
                            id="MonthPrefix" name="MonthPrefix" required />
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="PicklistNumber" class="col-md-2 col-form-label">
                        Running Number
                        <span id="alert1" style="color: red; font-weight: 200;">*</span>
                    </label>
                    <div class="col-md-10">
                        <input class="form-control" type="text" value="{{ $prefix->running_nbr_wo }}"
                            id="PicklistNumber" name="PicklistNumber" required />
                    </div>
                </div>
                <div class="mt-1" style="float: inline-end;">
                    <a href="{{ route('PicklistPrefix.index') }}"
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
