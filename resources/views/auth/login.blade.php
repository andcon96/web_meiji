<!doctype html>

<html lang="en" class="light-style layout-wide customizer-hide" dir="ltr" data-theme="theme-default"
    data-assets-path="{{ url('sneat\assets\/') }}" data-template="horizontal-menu-template">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Add On Module (Live)</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ url('sneat/images/logo.png') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/fonts/boxicons.css') }}" />
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/fonts/fontawesome.css') }}" />
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/fonts/flag-icons.css') }}" />

    <!-- Core CSS -->
    {{-- <link rel="stylesheet" href="{{url('sneat/assets/vendor/css/rtl/core.css')}}" class="template-customizer-core-css" /> --}}
    <link rel="stylesheet" type="text/css" href="{{ url('sneat/assets/vendor/css/rtl/core.css') }}"
        class="template-customizer-core-css">
    <link rel="stylesheet" type="text/css" href="{{ url('sneat/assets/vendor/css/rtl/theme-default.css') }}"
        class="template-customizer-theme-css" />
    <link rel="stylesheet" type="text/css" href="{{ url('sneat/assets/css/demo.css') }}" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/libs/typeahead-js/typeahead.css') }}" />
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/libs/select2/select2.css') }}" />
    <!-- Vendor -->
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/libs/@form-validation/form-validation.css') }}" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/css/pages/page-auth.css') }}" />

    <!-- Helpers -->
    <script src="{{ url('sneat/assets/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="{{ url('sneat/assets/vendor/js/template-customizer.js') }}"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ url('sneat/assets/js/config.js') }}"></script>
</head>

<body>
    <!-- Content -->

    <div class="authentication-wrapper authentication-cover">
        <div class="authentication-inner row m-0">
            <!-- /Left Text -->
            <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center p-5">
                <div class="w-100 d-flex justify-content-center">
                    <img src="../../assets/img/illustrations/boy-with-rocket-light.png" class="img-fluid"
                        alt="Login image" width="700" data-app-dark-img="illustrations/boy-with-rocket-dark.png"
                        data-app-light-img="illustrations/boy-with-rocket-light.png" />
                </div>
            </div>
            <!-- /Left Text -->

            <!-- Login -->
            <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg p-sm-5 p-4">
                <div class="w-px-400 mx-auto">
                    <!-- Logo -->
                    <div class="app-brand mb-5">
                        <span class="app-brand-logo demo">
                            <img src="{{ asset('images/logo.png') }}" width="70" height="70">
                        </span>
                        <span class="app-brand-text demo text-body fw-bold">Add-on Module</span>
                    </div>
                    <!-- /Logo -->

                    <form method="POST" action="{{ route('login') }}" name='loginform' id="formAuthentication"
                        class="form" autocomplete="off">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Username</label>
                            <input type="text" class="form-control" id='username' name='username'
                                placeholder="Enter your email or username" autofocus required>
                        </div>
                        <div class="mb-3 form-password-toggle">
                            <div class="d-flex justify-content-between">
                                <label class="form-label" for="password">Password</label>
                            </div>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password" class="form-control" name="password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="password" />
                                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                            </div>
                        </div>
                        <button class="btn btn-primary d-grid w-100">Sign in</button>
                    </form>
                    &nbsp;
                    &nbsp;
                    @if (session('error'))
                        <div class="alert alert-danger" id="getError"
                            style="background-color:#FF9E9E;color:#9D0000;font-weight:bold;padding: 10px 10px 10px 10px;">
                            {{ session()->get('error') }}
                        </div>
                    @endif
                </div>
            </div>
            <!-- /Login -->
        </div>
    </div>

    <!-- / Content -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->

    <script src="{{ url('sneat/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/js/menu.js') }}"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    {{-- <script src="{{ url('sneat/assets/vendor/libs/@form-validation/popular.js') }}"></script> --}}
    <script src="{{ url('sneat/assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/select2/select2.js') }}"></script>

    <!-- Main JS -->
    <script src="{{ url('sneat/assets/js/main.js') }}"></script>

    <!-- Page JS -->
    <script src="{{ url('sneat/assets/js/pages-auth.js') }}"></script>
</body>

</html>
