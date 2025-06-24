@extends('layout.layout')

@section('content')
    <div class="container">
        <form action="{{ route('createOrUpdate') }}" method="POST" enctype="multipart/form-data">
            @method('POST')
            @csrf
            <input type="hidden" name="u_id" id="u_id" value="{{ $role->id }}">
            <div class="card mb-4">
                <h5 class="card-header">Edit Role</h5>
                <div class="card-body">
                    <div class="mb-3 row">
                        <label for="roleCode" class="col-md-2 col-form-label">
                            Role code
                            <span id="alert1" style="color: red; font-weight: 200;">*</span>
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $role->role_code }}" id="roleCode"
                                name="roleCode" disabled>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="roleDesc" class="col-md-2 col-form-label">
                            Role Desc
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $role->role_desc }}" id="roleDesc"
                                name="roleDesc" disabled />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <h4>
                            <center>
                                Menu Access
                            </center>
                        </h4>
                        @foreach ($menuTree as $menu)
                            <div class="form-group row">
                                <label for="level"
                                    class="col-6 col-form-label text-md-right full-txt py-1">{{ $menu->getMenu->menu_name }}</label>
                                <div class="col-6">
                                    <label class="switch switch-square">
                                        <input type="checkbox" class="switch-input" id="cb{{ $menu->getMenu->id }}"
                                            name="menus[]" value="{{ $menu->getMenu->id }}"
                                            @if (in_array($menu->getMenu->id, $menuAccess)) checked @endif />
                                        <span class="switch-toggle-slider">
                                            <span class="switch-on"></span>
                                            <span class="switch-off"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            @if (is_iterable($menu->children))
                                @foreach ($menu->children as $menuDescendant)
                                    <div class="form-group row">
                                        <label for="level"
                                            class="col-6 col-form-label text-md-right full-txt py-1">{{ $menuDescendant->getMenu->menu_name }}</label>
                                        <div class="col-6">
                                            <label class="switch switch-square">
                                                <input type="checkbox" class="switch-input"
                                                    id="cb{{ $menuDescendant->getMenu->id }}" name="menus[]"
                                                    value="{{ $menuDescendant->getMenu->id }}"
                                                    @if (in_array($menuDescendant->getMenu->id, $menuAccess)) checked @endif />
                                                <span class="switch-toggle-slider">
                                                    <span class="switch-on"></span>
                                                    <span class="switch-off"></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    @if (is_iterable($menuDescendant->children))
                                        @foreach ($menuDescendant->children as $lastDescendant)
                                            <div class="form-group row">
                                                <label for="level"
                                                    class="col-6 col-form-label text-md-right full-txt py-1">{{ $lastDescendant->getMenu->menu_name }}</label>
                                                <div class="col-6">
                                                    <label class="switch switch-square">
                                                        <input type="checkbox" class="switch-input"
                                                            id="cb{{ $lastDescendant->getMenu->id }}" name="menus[]"
                                                            value="{{ $lastDescendant->getMenu->id }}"
                                                            @if (in_array($lastDescendant->getMenu->id, $menuAccess)) checked @endif />
                                                        <span class="switch-toggle-slider">
                                                            <span class="switch-on"></span>
                                                            <span class="switch-off"></span>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                @endforeach
                            @endif
                            <hr>
                        @endforeach
                    </div>
                    <div class="row justify-content-end mt-3">
                        <div class="col-sm-9 d-flex justify-content-end">
                            <a href="{{ route('menuAccess.index') }}" class="btn btn-label-secondary cancel">Cancel</a>
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
