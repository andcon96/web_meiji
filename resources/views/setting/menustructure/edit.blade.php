@extends('layout.layout')

@section('content')
    <div class="container">
        <form action="{{ route('menuStructure.update', $menuStructure->id) }}" method="POST" enctype="multipart/form-data">
            @method('PUT')
            @csrf
            <input type="hidden" name="u_id" id="u_id" value="{{$menuStructure->id}}">
            <div class="card mb-4">
                <h5 class="card-header">Edit Menu Structure</h5>
                <div class="card-body">
                    <div class="mb-3 row">
                        <label for="icon" class="col-md-2 col-form-label">
                            Icon
                        </label>
                        <div class="col-md-10">
                            <select id="select2Icons" name="icon_id" class="select2-icons form-select" data-allow-clear="true">
                                <option value="">Select value</option>
                                @foreach ($icons as $icon)
                                    <option value="{{ $icon->id }}" data-icon="{{$icon->icon_value}}"
                                        {{$menuStructure->menu_icon_id == $icon->id ? 'selected' : ''}}>{{$icon->icon_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="menuName" class="col-md-2 col-form-label">
                            Menu name
                            <span id="alert1" style="color: red; font-weight: 200;">*</span>
                        </label>
                        <div class="col-md-10">
                            <select id="formtabs-menu" name="menu_id" class="select2 form-select" data-allow-clear="true">
                                <option value="">Select</option>
                                @foreach ($menus as $menu)
                                    <option value="{{ $menu->id }}" {{$menuStructure->menu_id == $menu->id ? 'selected' : ''}}>{{ $menu->menu_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="menuParent" class="col-md-2 col-form-label">
                            Menu parent
                        </label>
                        <div class="col-md-10">
                            <select id="formtabs-menuParent" name="menu_parent_id" class="select2 form-select" data-allow-clear="true">
                                <option value="">Select</option>
                                @foreach ($menus as $menu)
                                    <option value="{{ $menu->id }}" {{$menuStructure->menu_parent_id == $menu->id ? 'selected' : ''}}>{{ $menu->menu_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row justify-content-end mt-3">
                        <div class="col-sm-9 d-flex justify-content-end">
                            <a href="{{route('menuStructure.index')}}" class="btn btn-label-secondary cancel">Cancel</a>
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
