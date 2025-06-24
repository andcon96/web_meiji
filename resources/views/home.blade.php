@extends('layout.layout')
@section('title', 'Home - Meiji')

@section('content')
    <!-- Flash Menu -->
    @if (session()->has('updated'))
        <div class="alert alert-success  alert-dismissible fade show" role="alert">
            {{ session()->get('updated') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" id="getError" role="alert">
            {{ session()->get('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <head>
        <title>Homepage</title>
    </head>

    <body>
        {{-- Hello --}}
    </body>

@endsection
