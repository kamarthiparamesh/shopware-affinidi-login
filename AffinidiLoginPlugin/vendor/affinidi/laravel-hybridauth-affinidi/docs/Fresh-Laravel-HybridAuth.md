## Introduction

This package extends HybridAuth to enable passwordless authentication with the Affinidi OIDC provider.

Learn more about Hybridauth [here](https://hybridauth.github.io/)

### Create Basic Laravel Project

Before creating your first Laravel project, you should ensure that your local machine has `PHP` and `Composer` installed.

1. You may create a new Laravel project via the Composer `create-project` command
```
composer create-project laravel/laravel example-app
```

**Note**: If you encounter any issue on creating project like `fileInfo`, then you may have enable the fileInfo extension in your `php.ini` file like below
```
extension=fileinfo
```
2. After the project has been created, start Laravel's local development server using the Laravel's Artisan CLI `serve` command
```
cd example-app
 
php artisan serve
```
3. Once you have started the Artisan development server, your application will be accessible in your web browser at [http://localhost:8000](http://localhost:8000)

**Note**: If you encounter an error on generating Key, then execute the below command which updates `APP_KEY` in your .env file and then run the app
```
php artisan key:generate
```

### Install HybridAuth
To get started with Socialite, use the Composer package manager to add the package to your project's dependencies
```
composer require affinidi/laravel-hybridauth-affinidi
```
### Add Login Functionality

1. Create a configuration file `hybridauth.php` with below content under `config` folder:
```
<?php
return [
    'affinidi' => [
        'callback' => env('APP_URL') . '/login/affinidi/callback',
        'keys' => [
            'id' => env('PROVIDER_CLIENT_ID'),
            'secret' => env('PROVIDER_CLIENT_SECRET')
        ],
        'endpoints' => [
            'api_base_url' => env('PROVIDER_ISSUER'),
            'authorize_url' => env('PROVIDER_ISSUER') . '/oauth2/auth',
            'access_token_url' => env('PROVIDER_ISSUER') . '/oauth2/token',
        ]
    ]
]
    ?>
```

2. Create `LoginRegisterController.php` file under `app\Http\Controllers`, which has actions to perform normal login, logout, affinidi login and its callback.
```
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class LoginRegisterController extends Controller
{

    public function login()
    {
        return view('login');
    }

    public function home()
    {
        if (session("user")) {
            return view('dashboard');
        }

        return redirect()->route('login')
            ->withErrors([
                'email' => 'Please login to access the home.',
            ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')
            ->withSuccess('You have logged out successfully!');
        ;
    }

    public function affinidiLogin(Request $request)
    {
        return Socialite::driver('affinidi')->redirect();
    }

    public function affinidiCallback(Request $request)
    {
        try {
            $user = Socialite::driver('affinidi')->user();

            session(['user' => $user]);

            return redirect()->intended('home');
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withError($e->getMessage());
        }
    }
}

```

3. Open `routes\web.php` file and Add Web Routes which invokes the above login controller actions. File looks like below
```
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
});

Route::controller(App\Http\Controllers\LoginRegisterController::class)->group(function() {
    Route::get('/login', 'login')->name('login');
    Route::get('/home', 'home')->name('home');
    Route::get('/logout', 'logout')->name('logout');
    Route::get('/login/affinidi', 'affinidiLogin')->name('affinidi-login');
    Route::get('/login/affinidi/callback', 'affinidiCallback')->name('affinidi-callback');
});
```

4. Create file `login.blade.php` under `resources\views` for adding Affinidi Login button
```
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Login</title>
    <style>
        .body {
            padding: 1rem;
        }

        .affinidi-login-div {
            width: 300px;
        }

        .affinidi-login-button {
            border: 0;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 15rem;
            height: 2rem;
            cursor: pointer;
            background: #1d58fc;
            color: #fff;
            box-shadow: 0 4px 16px 0 rgba(55, 62, 151, 0.32);
        }

        .affinidi-login-img {
            margin-right: 1rem;
            width: 24px;
            height: 24px;
        }
        .alert {
            padding: 1rem;
        }

        .alert-success {
            color: #3dc58b;
            background-color: #f0fcf7;
            border-color: #d2f5e6;
        }
        .alert-danger {
            color: red;
            background-color: #f0fcf7;
            border-color: #d2f5e6;
        }
    </style>
</head>

<body>
    <div class="card-body">
        <h2 class="h4 mb-1">Sign in</h2>
        <hr class="mt-2">
        @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif

        <div class="affinidi-login-div">
            <a class="btn affinidi-login-button" href="/login/affinidi">
                <img alt="logo affinidi" class="affinidi-login-img"
                    src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAExUlEQVRoge2ZT2iURxjGf+/styZmq1WjiSQbkSYYCwVrRNCDUJUehEqhQrSHilpq1UsvxYMglUov0ksp2CoE+8eDUPDQQy/9Z/9SWoyeihW1rckGjTVWaVw3uztPD18SNcluvk2yyR7ywMB++80z87zzzbzvvDMwi1lMCjZF7cSgJQH354SPNQNwpR/IR2+iPgEkwBSdE+QnaEBDDbi1YBswrQGWA7VA1WCFDNAH/IXsPOgHyP4GN/sLt9l4CGeHgPsRRcQQV4MShbfi3B7ENqAZVGzAmoBVwIvhY/waljyL9x3Qc2lUbWfzEAlQIpIUA7C7LprwZCOu8TjmLiAOYmrGiop/pCMNlaeQ3sTcBVzyBDQmR1T02KCwKCVEPoIBja9gdILtxzS3pCk6yhgDUzXSXsw6oWnXxBsLUcSANfFw1PkEUx1MQvgwBtswwLQE06nwawAoN4E+rMAaaJmP9Z5GtvXRfssCsRe3bCH+eg/UgKsFRe+wgBdqrAV7BpSZIpnjIGULNv+0Pt395e+ZP47chuUxyI5HMiA9DeKioe5lHavfoY5SeRG9UPlhyuWk/J4l23O7S+FVjAFeeCQMHWvYoaaovIoxICbnAQxbnPP5w1F5g16oqRnT+0CM8vqcsaHufPqfX1ur69aB8oB2xlccXp69fDSPJQs4GjmM7tAAx6ugLdOneKQW8Nl7w4/mgqq5S597Pnv56KCfLDCmsi4H9Qmkl6ZF6Jgihn6EIV4SSMxZtEo2b4Pwd4qxMw6CdcCKMsuMDDMDPLGqhRZPbhH0U2zX73BuU9EaMwBJYI6q2tXj1g2QNoasmbJhaA6NFhAkmkIvKc8ohxnWnhsgTgGfMxPe56GSgXhi2QtIm4b/kydINPZS3fYeDy7moOFxA4WB+ipm6izdoSPe594yLPwWEmbu76f3uZZzGy1XiFcxgSzvc/HhpTi8G1XNpeMsLMarGAMmiooxIOaC7MOEZ2hm2/2VBygaCAJIvkZ4ojBDixige2Dg30sb4k+2An5o/uOz96rPbdl8ENzoRRyu9L4AYzewfgZUD0IgyPZfJ75g5cNhNEeuP1XPg853sIbRNANkPQFm3yKtn1SyPhk8vpV4bJRz/V1hADBHgQmSdnj/TaG3MwWzMA5kbl8Yt66D3C/A5fLLigZJgCOfuaNs1xcGT1BsfB3c7Mfs7LQpHInhSRNuJcwMzBjou2j670fDLSjGrgrzAU8HZs8yUwkN5F18fivQDCCfy6RvfPc9kA+3DGNtGMKEZlpVFsPS9vzbde1Z1W/Pqa49dzIqr2ICWd68AxC6FbjY0ai8Ek+nywdnOIUbuYM9Z6wrKq9iDJAFgUFH7xn7qBReAQOm/2gx3fvzjXTqq6+BtaUcLRbIB1rmY5nToK1TK7SQFPdZeLibeAO3qJTD3asFFvGVe6huG2Yfhh2oPAXAOIm/3o5L3g19vi+JX8QLnc/iu/Yj7UTWOzV5/yNtyG4h7cJ3vz74LphAH4rgRlOfItpAHyBLTy75F8jS4E4gtUHq40k0BkSOA90pfOoA8qsxjiG7iizaScZQPdk1sHeRb8N37YPUiCgqhyByCREr9zXrn8g6y3nNOoUX3YtrYM6gAdN30V2KyFnMYgz8D4yD44VCwb0lAAAAAElFTkSuQmCC"
                    crossorigin="anonymous" class="sc-dmyDmy fxYwlQ">
                Affinidi Login
            </a>
        </div>
    </div>
</body>

</html>
```

5. Create dashboard `dashboard.blade.php` under `resources\views` for displaying the logged-in user info
```
<!DOCTYPE html>
<html>

<head>
    <title>Laravel</title>
</head>
<style>
    body {
        padding: 1rem;
    }
    .alert-success {
        color: #3dc58b;
        background-color: #f0fcf7;
        border-color: #d2f5e6;
    }
</style>

<body>
    <h1 class="alert-success">Congratulations, your login was successful!</h1>
    <h3>Welcome <i>{{ session("user")['email'] }}</i></h3>
    <a class="btn btn-outline-primary" href="/logout" style="width: 100%; text-decoration: none; font-weight: bold;">
        Logout
    </a>
    <p>{{ dd(session("user")) }}</p>
</body>

</html>
```

### Create Affinidi Login Configuration

Create [Affinidi Login Configuration](https://docs.affinidi.com/docs/affinidi-login/login-configuration/#create-login-configuration) by giving name as `Laravel App` and `Redirect URIs` as `http://localhost:8000/login/affinidi/callback`. Sample response is given below
```
{
    ...
    "auth": {
        "clientId": "<AUTH.CLIENT_ID>",
        "clientSecret": "<AUTH.CLIENT_SECRET>",
        "issuer": "https://<PROJECT_ID>.apse1.login.affinidi.io"
    }
    ...
}
```
**Important**: Safeguard the Client ID and Client Secret and Issuer; you'll need them for setting up your environment variables. Remember, the Client Secret will be provided only once.

**Note**: By default Login Configuration will requests only `Email VC`, if you want to request email and profile VC, you can refer PEX query under `docs\loginConfig.json` and execute the below affinidi CLI command to update PEX
```
affinidi login update-config --id <CONFIGURATION_ID> -f docs\loginConfig.json
```

### Setup & Run application

1. Install the dependencies by executing the below command in terminal
```
composer install
```
2. Open `.env` file and update the `` variable value with app URL contains port
```
APP_URL=http://localhost:8000
```
3. Add the below environment variables in `.env` at the end with the auth credentials received from the Login Configuration created earlier:
```
PROVIDER_CLIENT_ID="<AUTH.CLIENT_ID>"
PROVIDER_CLIENT_SECRET="<AUTH.CLIENT_SECRET>"
PROVIDER_ISSUER="https://<PROJECT_ID>.apse1.login.affinidi.io"
```
4. Run the application
    ```
    php artisan serve
    ```
5. Open the [http://localhost:8000/](http://localhost:8000/), which displays login page 
    **Important**: You might error on redirect URL mismatch if you are using `http://127.0.0.1:8000/` instead of `http://localhost:8000/`. 
6. Click on `Affinidi Login` button to initiate OIDC login flow with Affinidi Vault
