<!doctype html>

<html lang="en" class="light-style layout-menu-fixed layout-compact" dir="ltr" data-theme="theme-default"
    data-assets-path="{{ url('sneat\assets\/') }}" data-template="horizontal-menu-template">

<head>
    <style>
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
            text-align: right;
        }
    </style>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>@yield('title')</title>

    <meta name="description" content="" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link rel="stylesheet" type="text/css" href="{{ url('sneat/assets/img/favicon/favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" type="text/css" href="{{ url('sneat/assets/vendor/fonts/boxicons.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('sneat/assets/vendor/fonts/fontawesome.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('sneat/assets/vendor/fonts/flag-icons.css') }}">

    <!-- Core CSS -->
    <link rel="stylesheet" type="text/css" href="{{ url('sneat/assets/vendor/css/rtl/core.css') }}"
        class="template-customizer-core-css">
    <link rel="stylesheet" type="text/css" href="{{ url('sneat/assets/vendor/css/rtl/theme-default.css') }}"
        class="template-customizer-theme-css">
    <link rel="stylesheet" type="text/css" href="{{ url('sneat/assets/css/demo.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('assets/css/so_mobile_sneat.css') }}">

    <!-- Vendors CSS -->
    <link rel="stylesheet" type="text/css"
        href="{{ url('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('sneat/assets/vendor/libs/typeahead-js/typeahead.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('sneat/assets/vendor/libs/apex-charts/apex-charts.css') }}">
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/libs/bootstrap-select/bootstrap-select.css') }}" />
    <link rel="stylesheet"
        href="{{ url('sneat/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/libs/pickr/pickr-themes.css') }}" />
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ url('sneat/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ url('sneat/assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ url('sneat/assets/vendor/libs/datatables-fixedcolumns-bs5/fixedcolumns.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ url('sneat/assets/vendor/libs/datatables-fixedheader-bs5/fixedheader.bootstrap5.css') }}" />
    <!-- Calendar -->
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/libs/fullcalendar/fullcalendar.css') }}" />
    <link rel="stylesheet" href="{{ url('sneat/assets/vendor/css/pages/app-calendar.css') }}" />
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.0/css/buttons.dataTables.css"> --}}
    <!-- End Calendar -->


    <!-- Helpers -->
    <script src="{{ url('sneat/assets/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    {{-- <script src="{{ url('sneat/assets/vendor/js/template-customizer.js') }}"></script> --}}
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ url('sneat/assets/js/config.js') }}"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu">
        <div class="layout-container">
            <!-- Navbar -->

            <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
                <div class="container-xxl">
                    <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
                        <a href="{{ route('home') }}" class="app-brand-link gap-2">
                            {{-- <span class="app-brand-logo demo">
                                <img src="{{ url('images/logo.png') }}" width="35" alt="">
                            </span> --}}
                            <span class="app-brand-text demo menu-text fw-bold">QAD Add-on</span>
                        </a>

                        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
                            <i class="bx bx-chevron-left bx-sm align-middle"></i>
                        </a>
                    </div>

                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="bx bx-menu bx-sm"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <!-- Search -->
                            {{-- <li class="nav-item navbar-search-wrapper me-2 me-xl-0">
                                <a class="nav-link search-toggler" href="javascript:void(0);">
                                    <i class="bx bx-search bx-sm"></i>
                                </a>
                            </li> --}}
                            <!-- /Search -->

                            <!-- Language -->
                            {{-- <li class="nav-item dropdown-language dropdown me-2 me-xl-0">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);"
                                    data-bs-toggle="dropdown">
                                    <i class="bx bx-globe bx-sm"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);" data-language="en"
                                            data-text-direction="ltr">
                                            <span class="align-middle">English</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);" data-language="fr"
                                            data-text-direction="ltr">
                                            <span class="align-middle">French</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);" data-language="ar"
                                            data-text-direction="rtl">
                                            <span class="align-middle">Arabic</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);" data-language="de"
                                            data-text-direction="ltr">
                                            <span class="align-middle">German</span>
                                        </a>
                                    </li>
                                </ul>
                            </li> --}}
                            <!-- /Language -->

                            <!-- Quick links  -->
                            <div>
                                Domain: {{Session::get('domain_name')}}
                            </div>
                            {{-- <li class="nav-item dropdown-shortcuts navbar-dropdown dropdown me-2 me-xl-0">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="bx bx-grid-alt bx-sm"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end py-0">
                                    <div class="dropdown-menu-header border-bottom">
                                        <div class="dropdown-header d-flex align-items-center py-3">
                                            <h5 class="text-body mb-0 me-auto">Shortcuts</h5>
                                            <a href="javascript:void(0)" class="dropdown-shortcuts-add text-body"
                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Add shortcuts"><i class="bx bx-sm bx-plus-circle"
                                                    data-bs-toggle="modal" data-bs-target="#shortcutModal"></i></a>
                                        </div>
                                    </div>
                                    <div class="dropdown-shortcuts-list scrollable-container">

                                    </div>
                                </div>
                            </li> --}}
                            <!-- Quick links -->
                            <!-- Notification -->
                            <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-1">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="bx bx-bell bx-sm"></i>
                                    <span class="badge bg-danger rounded-pill badge-notifications">{{ auth()->user()->unreadNotifications->count() }}</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end py-0">
                                    <li class="dropdown-menu-header border-bottom">
                                        <div class="dropdown-header d-flex align-items-center py-3">
                                            <h5 class="text-body mb-0 me-auto">Notification</h5>
                                            <a href="javascript:void(0)"
                                                class="dropdown-notifications-all text-body mark-as-read-all"
                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Mark all as read"><i class="bx fs-4 bx-envelope-open"></i>
                                            </a>
                                        </div>
                                    </li>
                                    <li class="dropdown-notifications-list scrollable-container">
                                        <ul class="list-group list-group-flush">
                                            @forelse(Auth::User()->unreadNotifications as $notif)
                                                <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                                    <a href="{{ $notif->data['url'] }}" class="d-flex mark-as-read" data-id="{{ $notif->id }}"
                                                        data-link="{{ $notif->data['url'] }}">
                                                        <div class="flex-shrink-0 me-3">
                                                            <div class="avatar">
                                                                <span class="avatar-initial rounded-circle bg-label-danger">
                                                                    {{ substr($notif->data['data'],0,2) }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <p class="mb-0">{{ $notif->data['data'] }}</p>
                                                            <small class="text-muted">{{ $notif->data['note'] }}</small>
                                                        </div>
                                                    </a>
                                                </li>
                                                @empty
                                                <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                                    <div class="d-flex">
                                                        {{-- <div class="flex-shrink-0 me-3">
                                                            <div class="avatar">
                                                                <span class="avatar-initial rounded-circle bg-label-danger">

                                                                </span>
                                                            </div>
                                                        </div> --}}
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1">No New Notifications</h6>
                                                            {{-- <p class="mb-0">Try Again Later</p> --}}
                                                            {{-- <small class="text-muted">{{ $notif->data['note'] }}</small> --}}
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforelse
                                        </ul>
                                    </li>
                                    {{-- <li class="dropdown-menu-footer border-top p-3">
                                <button class="btn btn-primary text-uppercase w-100">view all
                                    notifications</button>
                            </li> --}}
                                </ul>
                            </li>
                            <!--/ Notification -->
                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);"
                                    data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="{{ url('images/icon.jpg') }}" alt
                                            class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="pages-account-settings-account.html">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <img src="{{ url('images/icon.jpg') }}" alt
                                                            class="w-px-40 h-auto rounded-circle" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span class="fw-medium d-block">{{ Auth::user()->name }}</span>
                                                    <small
                                                        class="text-muted">{{ Auth::user()->getRole->role_code }}</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    {{-- <li>
                                        <div class="dropdown-divider"></div>
                                    </li> --}}
                                    {{-- <li>
                                        <a class="dropdown-item" href="pages-profile-user.html">
                                            <i class="bx bx-user me-2"></i>
                                            <span class="align-middle">My Profile</span>
                                        </a>
                                    </li> --}}
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                            <i class="bx bxs-key me-2"></i>
                                            <span class="align-middle">Change Password</span>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                            data-bs-target="#logoutModal">
                                            <i class="bx bx-power-off me-2"></i>
                                            <span class="align-middle">Log Out</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!--/ User -->
                        </ul>
                    </div>

                    <!-- Search Small Screens -->
                    <div class="navbar-search-wrapper search-input-wrapper container-xxl d-none">
                        <input type="text" class="form-control search-input border-0" placeholder="Search..."
                            aria-label="Search..." />
                        <i class="bx bx-x bx-sm search-toggler cursor-pointer"></i>
                    </div>
                </div>
            </nav>

            <!-- / Navbar -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Menu -->
                    <aside id="layout-menu"
                        class="layout-menu-horizontal menu-horizontal menu bg-menu-theme flex-grow-0">
                        <div class="container-xxl d-flex h-100">
                            <ul class="menu-inner">
                                @foreach ($menuTree as $menu)
                                    <li class="menu-item">
                                        @if ($menu->children->count() > 0)
                                            <a href="javascript:void(0)" class="menu-link menu-toggle">
                                                <i class="menu-icon tf-icons {{ $menu->getIcon->icon_value ?? '' }}"></i>
                                                <div data-i18n="{{ $menu->getMenu->menu_name }}">
                                                    {{ $menu->getMenu->menu_name }}</div>
                                            </a>
                                        @else
                                            <a href="/{{ $menu->getMenu->menu_route }}"
                                                class="menu-link">
                                                <div data-i18n="{{ $menu->getMenu->menu_name }}"
                                                    class="menu-text">{{ $menu->getMenu->menu_name }}
                                                </div>
                                            </a>
                                        @endif
                                        <ul class="menu-sub">
                                            @foreach ($menu->children as $menuChild)
                                                <li class="menu-item">
                                                    @if ($menuChild->children->count() > 0)
                                                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                                                            <i
                                                                class="menu-icon tf-icons bx bx-radio-circle-marked"></i>
                                                            <div data-i18n="{{ $menuChild->getMenu->menu_name }}"
                                                                class="menu-text">
                                                                {{ $menuChild->getMenu->menu_name }}</div>
                                                        </a>
                                                        <ul class="menu-sub">
                                                            @foreach ($menuChild->children as $menuDescendant)
                                                                <li class="menu-item">
                                                                    <a href="/{{ $menuDescendant->getMenu->menu_route }}"
                                                                        class="menu-link">
                                                                        <i
                                                                            class="menu-icon tf-icons {{ $menuDescendant->getIcon->icon_value ?? '' }}"></i>
                                                                        <div
                                                                            data-i18n="{{ $menuDescendant->getMenu->menu_name }}">
                                                                            {{ $menuDescendant->getMenu->menu_name }}
                                                                        </div>
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <a href="/{{ $menuChild->getMenu->menu_route }}"
                                                            class="menu-link">
                                                            <i
                                                                class="menu-icon tf-icons bx bx-radio-circle-marked"></i>
                                                            <div data-i18n="{{ $menuChild->getMenu->menu_name }}"
                                                                class="menu-text">{{ $menuChild->getMenu->menu_name }}
                                                            </div>
                                                        </a>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </aside>
                    <!-- / Menu -->

                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        @yield('content')
                    </div>
                    <!--/ Content -->

                    <!-- Footer -->
                    {{-- <footer class="content-footer footer bg-footer-theme">
                        <div
                            class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                            <div class="mb-2 mb-md-0">
                                ©
                                <script>
                                    document.write(new Date().getFullYear());
                                </script>
                                ,
                                <a href="https://ptimi.co.id" target="_blank" class="footer-link fw-medium">PT.
                                    Intelegensia Mustaka Indonesia</a>
                            </div>
                        </div>
                    </footer> --}}
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!--/ Content wrapper -->
            </div>

            <!--/ Layout container -->
        </div>
    </div>

    @include('sweetalert::alert')

    <!-- Change password Modal -->
    <form action="{{route('changePassword')}}" method="POST" enctype="multipart/form-data">
        {{ csrf_field() }}
        @method('POST')
        <input type="hidden" name="id" value="{{Auth::user()->id}}">
        <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel5">Change Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="col-md-6 form-password-toggle">
                            <div class="input-group input-group-merge">
                                <input type="password" name="password" id="multicol-password" class="form-control"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="multicol-password2" value="{{ old('password') }}" />
                                <span class="input-group-text cursor-pointer" id="multicol-password2"><i class="bx bx-hide"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit">Update</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel5">Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">Are you sure want to Logout now?</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="{{ route('logout') }}"
                        onclick="event.preventDefault();
                                  document.getElementById('logout-form').submit();">
                        {{ __('Logout') }} </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Shortcut Modal -->
    <div class="modal fade" id="shortcutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel5">Add Fav. Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- <form action="{{ route('saveFavMenu') }}" method="post">
                    @method('POST')
                    {{ csrf_field() }}
                    <div class="modal-body">
                        <div class="form-group row justify-content-center">
                            <label for="menu_id" class="col-md-3">Menu</label>
                            <div class="col-md-9">
                                <select name="menu_id" id="menu_id" class="select2 form-select form-select-lg">
                                    @foreach ($global_menuMaster as $menu)
                                        <option value="{{ $menu->id }}">{{ $menu->menu_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" id="btnsubmit" class="btn btn-success"
                            style="color: white !important;">Save</button>&nbsp;
                        <button type="button" class="btn btn-block btn-info" id="btnloading" style="display:none">
                            <i class="fas fa-spinner fa-spin"></i> &nbsp;Saving
                        </button>
                    </div>
                </form> --}}
            </div>
        </div>
    </div>
    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>

    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>

    <!--/ Layout wrapper -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->

    <!-- jQuery -->
    <script src="{{ url('sneat/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/js/menu.js') }}"></script>
    {{-- Date Picker --}}
    <script src="{{ url('assets/css/jquery-ui.js') }}"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ url('sneat/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/bootstrap-select/bootstrap-select.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/bloodhound/bloodhound.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/datatables-buttons-bs5/jszip.min.js') }}"></script>
    {{-- <script src="{{ url('sneat/assets/vendor/libs/datatables-buttons-bs5/buttons.dataTables.js') }}"></script> --}}
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.html5.min.js"></script> --}}

    <script src="{{ url('sneat/assets/vendor/libs/jquery-repeater/jquery-repeater.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/block-ui/block-ui.js') }}"></script>

    <!-- Main JS -->
    <script src="{{ url('sneat/assets/js/main.js') }}"></script>

    <!-- Page JS -->
    {{-- <script src="{{ url('sneat/assets/js/app-ecommerce-order-list.js') }}"></script> --}}
    <script src="{{ url('sneat/assets/js/dashboards-analytics.js') }}"></script>
    <script src="{{ url('sneat/assets/js/cards-actions.js') }}"></script>
    <script src="{{ url('sneat/assets/js/ui-modals.js') }}"></script>
    <script src="{{ url('sneat/assets/js/extended-ui-sweetalert2.js') }}"></script>
    <script src="{{ url('sneat/assets/js/forms-selects.js') }}"></script>
    <script src="{{ url('sneat/assets/js/forms-typeahead.js') }}"></script>
    <script src="{{ url('sneat/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
    <script src="{{ url('sneat/assets/js/forms-pickers.js') }}"></script>
    <script src="{{ url('sneat/assets/js/forms-extras.js') }}"></script>
    {{-- <script src="{{ url('sneat/assets/js/tables-datatables-advanced.js') }}"></script> --}}

    <!-- js calendar -->
    <script src="{{ url('sneat/assets/js/app-calendar-events.js') }}"></script>
    <script src="{{ url('sneat/assets/js/app-calendar.js') }}"></script>
    <!-- end js calendar -->

    <!-- chart yang lama dari adminLTE 3 -->
    <script src="{{ url('plugins/chart.js/Chart.bundle.min.js') }}"></script>
    <!-- End -->

    @yield('scripts')
    <script type="text/javascript">
        /** add active class and stay opened when selected */
        var url = window.location.href;
        var urlWithoutParam = url.split('?');

        // for sidebar menu entirely but not cover treeview
        $('ul.menu-inner li.menu-item a').filter(function() {
            return this.href == urlWithoutParam[0];
        }).closest('.menu-item').addClass('active').closest('.menu-sub').parent().addClass('active').closest(
            '.menu-sub').parent().addClass('active');

        // Notification

        function sendMarkRequest(id = null) {
            return $.ajax("{{ route('markAsRead') }}", {
                method: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "id": id
                }
            });
        }

        function sendMarkAllRequest(id = null) {
            return $.ajax("{{ route('markAllAsRead') }}", {
                method: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "id": id
                }
            });
        }

        $(function() {
            $('.mark-as-read').click(function() {
                let request = sendMarkRequest($(this).data('id'));
                request.done(() => {
                    $(this).parents('div.alert').remove();
                });
            });

            $('.mark-as-read-all').click(function() {
                // alert('hello');
                let request = sendMarkAllRequest($(this).data('id'));
                request.done(() => {
                    $(this).parents('div.alert').remove();
                    window.location.reload();
                });
            });
        });
    </script>
</body>

</html>
