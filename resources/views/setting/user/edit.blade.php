@extends('layout.layout')

@section('content')
    <div class="container">
        <form action="{{ route('users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
            @method('PUT')
            @csrf
            <input type="hidden" name="u_id" value="{{ $user->id }}">
            <div class="card mb-4">
                <h5 class="card-header">Edit User</h5>
                <div class="card-body">
                    <div class="mb-3 row">
                        <label for="username" class="col-md-2 col-form-label">
                            Username
                            <span id="alert1" style="color: red; font-weight: 200;">*</span>
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $user->username }}" id="username"
                                name="username" disabled>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="name" class="col-md-2 col-form-label">
                            Name
                            <span id="alert1" style="color: red; font-weight: 200;">*</span>
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $user->name }}" id="name"
                                name="name" required />
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="email" class="col-md-2 col-form-label">
                            Email
                        </label>
                        <div class="col-md-10">
                            <input class="form-control" type="text" value="{{ $user->email }}" id="email"
                                name="email" />
                        </div>
                    </div>
                    {{-- <div class="mb-3 row">
                        <label for="domain" class="col-md-2 col-form-label">
                            Domain
                            <span id="alert1" style="color: red; font-weight: 200;">*</span>
                        </label>
                        <div class="col-md-10">
                            <select id="formtabs-domain" name="domain_id" class="select2 form-select" data-allow-clear="true">
                                <option value="">Select</option>
                                @foreach ($domains as $domain)
                                    <option value="{{ $domain->id }}" {{$user->domain_id == $domain->id ? 'selected' : ''}}>{{ $domain->domain }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div> --}}
                    <div class="mb-3 row">
                        <label for="role" class="col-md-2 col-form-label">
                            Role
                            <span id="alert1" style="color: red; font-weight: 200;">*</span>
                        </label>
                        <div class="col-md-10">
                            <select id="formtabs-role" name="role_id" class="select2 form-select" data-allow-clear="true">
                                <option value="">Select</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                        {{ $role->role_code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    {{-- <div class="mb-3 row">
                        <label for="department" class="col-md-2 col-form-label">
                            Department
                        </label>
                        <div class="col-md-10">
                            <select id="formtabs-department" name="department_id" class="select2 form-select" data-allow-clear="true">
                                <option value="">Select</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" {{$user->department_id == $department->id ? 'selected' : ''}}>{{ $department->department_code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div> --}}
                    {{-- <div class="mb-3 row">
                        <label for="workcenter" class="col-md-2 col-form-label">
                            Work Center
                        </label>
                        <div class="col-md-10">
                            @php
                                $userWorkCenters = $user->getWorkCenter->pluck('work_center_code')->toArray();
                                $preselectedDescriptions = [];
                                foreach ($workCenters as $workCenter) {
                                    if (in_array($workCenter->t_wc_code, $userWorkCenters)) {
                                        $preselectedDescriptions[] = [
                                            'value' => $workCenter->t_wc_code,
                                            'description' => $workCenter->t_wc_desc
                                        ];
                                    }
                                }
                            @endphp
                            <select id="formtabs-workcenter" name="workCenter[]" class="select2 form-select" data-allow-clear="true" multiple>
                                <option value="">Select</option>
                                @foreach ($workCenters as $workCenter)
                                <option value="{{ $workCenter->t_wc_code }}" {{ in_array($workCenter->t_wc_code, $userWorkCenters) ? 'selected' : '' }}
                                    data-description="{{$workCenter->t_wc_desc}}">
                                    {{ $workCenter->t_wc_code . ' - ' . $workCenter->t_wc_desc }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" class="hiddenWorkCenterDesc" name="workCenterDesc" value="">
                        </div>
                    </div> --}}
                    <div class="mb-3 row">
                        <label for="password" class="col-md-2 col-form-label">
                            Password
                            <span id="alert1" style="color: red; font-weight: 200;">*</span>
                        </label>
                        <div class="col-md-10 form-password-toggle">
                            <div class="input-group input-group-merge">
                                <input type="password" name="password" id="multicol-password" class="form-control"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="multicol-password2" value="" />
                                <span class="input-group-text cursor-pointer" id="multicol-password2"><i
                                        class="bx bx-hide"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <div class="row">
                            <label class="col-md-2 col-form-label">
                                Super User
                                <span id="alert1" style="color: red; font-weight: 200;">*</span>
                            </label>
                            <div class="col-sm-9">
                                <div class="form-check mb-2">
                                    <input name="isSuperUser" class="form-check-input" type="radio" value="Yes"
                                        id="collapsible-superUser-yes"
                                        {{ $user->is_super_user == 'Yes' ? 'checked' : '' }} />
                                    <label class="form-check-label" for="collapsible-superUser-yes">
                                        Yes
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input name="isSuperUser" class="form-check-input" type="radio" value="No"
                                        id="collapsible-superUser-no" {{ $user->is_super_user == 'No' ? 'checked' : '' }} />
                                    <label class="form-check-label" for="collapsible-superUser-no">
                                        No
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <div class="row">
                            <label class="col-md-2 col-form-label">
                                Is Active
                                <span id="alert1" style="color: red; font-weight: 200;">*</span>
                            </label>
                            <div class="col-sm-9">
                                <div class="form-check mb-2">
                                    <input name="isActive" class="form-check-input" type="radio" value="Active"
                                        id="collapsible-isActive-active"
                                        {{ $user->is_active == 'Active' ? 'checked' : '' }} />
                                    <label class="form-check-label" for="collapsible-isActive-active">
                                        Active
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input name="isActive" class="form-check-input" type="radio" value="Inactive"
                                        id="collapsible-isActive-inactive"
                                        {{ $user->is_active == 'Inactive' ? 'checked' : '' }} />
                                    <label class="form-check-label" for="collapsible-isActive-inactive">
                                        Inactive
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-1" style="float: inline-end;">
                        <a href="{{ route('users.index') }}" class="btn btn-label-secondary cancel">Cancel</a>
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
