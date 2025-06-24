@extends('layout.layout')

@section('content')
    <div class="container">
        <form action="{{ route('connections.update', $connection->id) }}" method="POST" enctype="multipart/form-data">
            @method('PUT')
            @csrf
            <input type="hidden" value="{{ $connection->id }}" id="u_id" name="u_id">
            <div class="card mb-4">
                <h5 class="card-header">Edit Connection</h5>
                <div class="card-body">
                    <div class="mb-3 row">
                        <label for="wsaURL" class="col-md-2 col-form-label">
                            WSA URL
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $connection->wsa_url }}" id="wsaURL"
                                name="wsaURL" placeholder="http://qad2021ee.server:22079/wsa/wsaweb/" />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="wsaPath" class="col-md-2 col-form-label">
                            WSA Path
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $connection->wsa_path }}" id="wsaPath"
                                name="wsaPath" placeholder="urn:imi.co.id:wsaweb" />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="qxURL" class="col-md-2 col-form-label">
                            QX URL
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $connection->qx_url }}" id="qxURL"
                                name="qxURL" placeholder="http://qad2021ee.server:22079/qxi/services/QdocWebService" />
                        </div>
                    </div>
                    {{-- <div class="mb-3 row">
                        <label for="qxPath" class="col-md-2 col-form-label">
                            QX Path
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{$connection->qx_path}}" id="qxPath" name="qxPath" />
                        </div>
                    </div> --}}
                    <div class="row justify-content-end mt-3">
                        <div class="col-sm-9 d-flex justify-content-end">
                            <a href="{{ route('connections.index') }}" class="btn btn-label-secondary cancel">Cancel</a>
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
