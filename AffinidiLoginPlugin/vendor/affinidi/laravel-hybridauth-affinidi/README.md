# Overview

**AUGMENT EXPERIENCES WITH A SAFER, SIMPLER AND MORE PRIVATE WAY TO LOGIN**

A paradigm shift in the registration and sign-in process, Affinidi Login is a game-changing solution for developers. With our revolutionary passwordless authentication solution your user's first sign-in doubles as their registration, and all the necessary data for onboarding can be requested during this streamlined sign-in/signup process. End users are in full control, ensuring that they consent to the information shared in a transparent and user-friendly manner. This streamlined approach empowers developers to create efficient user experiences with data integrity, enhanced security and privacy, and ensures compatibility with industry standards.

| Passwordless Authentication | Decentralised Identity Management | Uses Latest Standards |
|---|---|---|
| Offers a secure and user-friendly alternative to traditional password-based authentication by eliminating passwords and thus removing the vulnerability to password-related attacks such as phishing and credential stuffing. | Leverages OID4VP to enable users to control their data and digital identity, selectively share their credentials and authenticate themselves across multiple platforms and devices without relying on a centralised identity provider. | Utilises OID4VP to enhance security of the authentication process by verifying user authenticity without the need for direct communication with the provider, reducing risk of tampering and ensuring data integrity. |

## Introduction

This package extends HybridAuth to enable passwordless authentication with the Affinidi OIDC provider.

Learn more about Hybridauth [here](https://hybridauth.github.io/)

**Quick Links**
1. [Installation & Usage](#installation--basic-usage)
2. [Create Affinidi Login Configuration](#create-affinidi-login-configuration)
3. Run [Sample Playground Project](#setup--run-application-from-playground-folder)
4. Affinidi Login Integration in [Fresh Laravel Project](/docs/Fresh-Laravel-HybridAuth.md)

## Installation & Basic Usage

To get started with Affinidi hybridauth, follow these steps:

1. Install the Affinidi hybridauth package using Composer:

```
composer require affinidi/laravel-hybridauth-affinidi
```

2. Create a configuration file `hybridauth.php` with below content under `config` folder:

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

3. Create `LoginRegisterController.php` file under `app\Http\Controllers`, which has actions to perform normal login, logout, affinidi login and its callback, reference can be found [here](playground\example\app\Http\Controllers\LoginRegisterController.php)

```
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginRegisterController extends Controller
{
    private static $adapter;

    public function __construct() {
        $config = \Config::get('hybridauth.affinidi');
        self::$adapter = new \Affinidi\HybridauthProvider\AffinidiProvider($config);
    }

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
        self::$adapter->disconnect();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')
            ->withSuccess('You have logged out successfully!');
        ;
    }

    public function affinidiLogin(Request $request)
    {
        self::$adapter->authenticate();
    }

    public function affinidiCallback(Request $request)
    {
        try {

            self::$adapter->authenticate();

            $userProfile = self::$adapter->getUserProfile();

            session(['user' => $userProfile]);

            return redirect()->intended('home');
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withError($e->getMessage());
        }
    }
}

```

4. Open `routes\web.php` file and Add Web Routes which invokes the above login controller actions, reference can be found [here](playground\example\routes\web.php)

5. Create file `login.blade.php` under `resources\views` for adding Affinidi Login button, reference can be found [here](playground\example\resources\views\login.blade.php)

6. Create dashboard `dashboard.blade.php` under `resources\views` for displaying the logged in user info, reference can be found [here](playground\example\resources\views\dashboard.blade.php)

7. Open `.env` file and update value of `APP_URL` to `http://localhost:8000`
```
APP_URL=http://localhost:8000
```

## Create Affinidi Login Configuration

Create the Login Configuration using [Affinidi Dev Portal](https://portal.affinidi.com/) as illustrated [here](https://docs.affinidi.com/docs/affinidi-login/login-configuration/#using-affinidi-portal). You can given name as "hybridauth App" and Redirect URIs as per your application specific e.g. "https://<domain-name>/login/affinidi/callback"

**Important**: Safeguard the Client ID and Client Secret and Issuer; you'll need them for setting up your environment variables. Remember, the Client Secret will be provided only once.

**Note**: By default Login Configuration will requests only `Email VC`, if you want to request email and profile VC, you can refer PEX query under (docs\loginConfig.json)[playground\example\docs\loginConfig.json] and execute the below affinidi CLI command to update PEX
```
affinidi login update-config --id <CONFIGURATION_ID> -f docs\loginConfig.json
```

## Setup & Run application from playground folder

Open the directory `playground/example` in VS code or your favourite editor

 1. Install the dependencies by executing the below command in terminal
    ```
    composer install
    ```
 2. Create the `.env` file in the sample application by running the following command
    ```
    cp .env.example .env
    ```
 3. Create Affinidi Login Configuration as mentioned [here](#create-affinidi-login-configuration)
 
 4. Update below environment variables in `.env` based on the auth credentials received from the Login Configuration created earlier:
    ```
    PROVIDER_CLIENT_ID="<AUTH.CLIENT_ID>"
    PROVIDER_CLIENT_SECRET="<AUTH.CLIENT_SECRET>"
    PROVIDER_ISSUER="<AUTH.CLIENT_ISSUER>"
    ```
    Sample values looks like below
    ```
    PROVIDER_CLIENT_ID="xxxxx-xxxxx-xxxxx-xxxxx-xxxxx"
    PROVIDER_CLIENT_SECRET="xxxxxxxxxxxxxxx"
    PROVIDER_ISSUER="https://yyyy-yyy-yyy-yyyy.apse1.login.affinidi.io"
    ```
5. Run the application
    ```
    php artisan serve
    ```
6. Open the [http://localhost:8000/](http://localhost:8000/), which displays login page 
    **Important**: You might error on redirect URL mismatch if you are using `http://127.0.0.1:8000/` instead of `http://localhost:8000/`. 
7. Click on `Affinidi Login` button to initiate OIDC login flow with Affinidi Vault
