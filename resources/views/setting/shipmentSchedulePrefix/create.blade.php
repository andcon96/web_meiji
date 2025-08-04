@extends('layout.layout')

@section('content')
<div class="container">
    <form action="{{ route('shipmentSchedulePrefix.store') }}" method="POST" enctype="multipart/form-data">
        @method('POST')
        @csrf
        <div class="card mb-4">
            <h5 class="card-header">Create Shipment Schedule Prefix</h5>
            <div class="card-body">
                <div class="mb-3 row">
                    <label for="shipmentSchedulePrefix" class="col-md-2 col-form-label">
                        Shipment Schedule Prefix
                        <span id="alert1" style="color: red; font-weight: 200;">*</span>
                    </label>
                    <div class="col-md-10">
                        <input class="form-control" type="text" value="{{ old('shipmentSchedulePrefix') }}" id="shipmentSchedulePrefix"
                            name="shipmentSchedulePrefix" required />
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="runningNbrShipmentSchedule" class="col-md-2 col-form-label">
                        Shipment Schedule Sequence
                        <span id="alert1" style="color: red; font-weight: 200;">*</span>
                    </label>
                    <div class="col-md-10">
                        <input class="form-control" type="number" value="{{ old('runningNbrShipmentSchedule') }}"
                            id="runningNbrShipmentSchedule" name="runningNbrShipmentSchedule" required />
                    </div>
                </div>
                <div class="mt-1" style="float: inline-end;">
                    <a href="{{ route('shipmentSchedulePrefix.index') }}" class="btn btn-label-secondary cancel">Cancel</a>
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
