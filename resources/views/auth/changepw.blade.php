@extends('layout.reworklayout')
@section('content-header')
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6 mt-2">
            <h1 class="m-0 text-dark">Change Password</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{url('/home')}}">Home</a></li>
              <li class="breadcrumb-item active">Change Password</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
@endsection
@section('content')
<!-- Flash Menu -->

<div class="panel-body">
   <hr>
    <form class="form-horizontal" method="POST" action="/userchange/changepass">
        {{ csrf_field() }}

        
        <div>
            <input id="id" type="hidden" class="form-control" name="id" value='{{ $users->id }}' required>
        </div>

        <div class="form-group{{ $errors->has('uname') ? ' has-error' : '' }} row">
            <label for="uname" class="col-md-3 ml-4 control-label">User ID</label>
            <div class="col-md-4 col-sm-12">
                <input id="uname" type="text" class="form-control" name="uname" value='{{ $users->username }}' required disabled>
            </div>
        </div>
        
        <div class="form-group{{ $errors->has('oldpass') ? ' has-error' : '' }} row">
            <label for="oldpass" class="col-md-3 ml-4 control-label">Old Password</label>

            <div class="col-md-4">
                <input id="oldpass" type="password" class="form-control" name="oldpass" autocomplete="new-password" required>

                @if ($errors->has('oldpass'))
                    <span class="help-block">
                        <strong>{{ $errors->first('oldpass') }}</strong>
                    </span>
                @endif
            </div>
        </div>
        
        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }} row">
            <label for="password" class="col-md-3 ml-4 control-label">New Password</label>
            <div class="col-md-4">
                <input id="password" type="password" class="form-control" name="password" autocomplete="new-password" required>
                @if ($errors->has('password'))
                    <span class="help-block">
                        <strong>{{ $errors->first('password') }}</strong>
                    </span>
                @endif
            </div>
        </div>
               
        <div class="form-group{{ $errors->has('confpass') ? ' has-error' : '' }} row">
            <label for="confpass" class="col-md-3 ml-4 control-label">Confirm New Password</label>
            <div class="col-md-4">
                <input id="confpass" type="password" class="form-control" name="confpass" autocomplete="new-password" required>
                @if ($errors->has('password'))
                    <span class="help-block">
                        <strong>{{ $errors->first('password') }}</strong>
                    </span>
                @endif
            </div>
        </div>

        <br>
        <div class="form-group row">
            <div class="col-md-6 col-md-offset-4 ml-4">
                <button type="submit" class="btn btn-success bt-action">
                    Save
                </button>
            </div>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
  
</script>
@endsection