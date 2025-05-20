<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="assets/css/loginstyle.css">

    <!-- <script src="https://kit.fontawesome.com/a076d05399.js"></script> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js" crossorigin="anonymous"></script>

    <title>Login</title>
</head>

<body>
    <div class="wrapper">

        <div class="login-wrapper">

            <form method="POST" action="{{ route('login') }}" name='loginform' class="form" autocomplete="off">
                @csrf
                <div class="input-group img-container">
                    <img src="{{ asset('images/logo.png') }}" width="150" height="150">
                </div>
                <div style="margin-top: 1em;">
                    <label>
                        <h2>Purchasing module</h2>
                    </label>
                </div>
                <div class="input-field">
                    <select class="form-control" name="domain" id="domain"
                        style="width:100%;height:50px;font-size:16px;padding-left:10px;" required>
                        @foreach ($domains as $domain)
                            <option value="{{ $domain->id }}">{{ $domain->domain }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="input-field">
                    <input type="text" id='username' name='username' required>
                    <label>User ID</label>
                </div>
                <div class="input-field">
                    <input class="pswrd" type="password" id='password' name='password' required>
                    <span class="show">SHOW</span>
                    <label>Password</label>
                </div>
                <div class="button">
                    <div class="inner">
                    </div>
                    <button type="submit" onclick="loginform.submit()" style="font-weight: bold;">LOGIN</button>
                </div>
                @if (session('error'))
                    <div class="alert alert-danger" id="getError"
                        style="background-color:#FF9E9E;color:#9D0000;font-weight:bold;padding: 10px 10px 10px 10px;">
                        {{ session()->get('error') }}
                    </div>
                @endif
            </form>
        </div>
    </div>

    <script>
        var input = document.querySelector('.pswrd');
        var show = document.querySelector('.show');
        show.addEventListener('click', active);

        function active() {
            console.log("Function active() is running");
            if (input.type === "password") {
                input.type = "text";
                show.style.color = "#1DA1F2";
                show.textContent = "HIDE";
            } else {
                input.type = "password";
                show.textContent = "SHOW";
                show.style.color = "#111";
            }
        }
    </script>

</body>

</html>
