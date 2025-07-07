<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default session "driver" that will be used on
    | requests. By default, we use the lightweight file driver but you may
    | specify any of the other wonderful drivers provided by Laravel.
    |
    */

    'driver' => env('SESSION_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that the session should be
    | allowed to remain idle before it expires. If you typically allow users
    | to remain authenticated for long periods of time, you may want to
    | increase this configuration value.
    |
    */

    'lifetime' => env('SESSION_LIFETIME', 120),

    'expire_on_close' => false,

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify that all of your session data
    | should be encrypted before it is stored. All encryption is performed
    | using the AES-256 algorithm and a random key that is generated for you.
    |
    */

    'encrypt' => env('APP_ENCRYPT_SESSION', false),

    /*
    |--------------------------------------------------------------------------
    | Session File Location
    |--------------------------------------------------------------------------
    |
    | When using the "file" session driver, we need a location where the session
    | files may be stored. A default has been provided for you. You are free
    | to use a different location but it should be writable by the web server.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the connection
    | that should be used to store your sessions. Of course, a default has
    | been set for you but you are free to change this value if needed.
    |
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Table
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the table and
    | column names that should be used to store your sessions. Of course,
    | a default has been set for you but you are free to change this value.
    |
    */

    'table' => 'sessions',

    /*
    |--------------------------------------------------------------------------
    | Session Cache Store
    |--------------------------------------------------------------------------
    |
    | When using the "cache" session driver, you may specify the cache store
    | that should be used to store your sessions. Of course, a default has
    | been set for you but you are free to change this value if needed.
    |
    */

    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Session Sweeping Lottery
    |--------------------------------------------------------------------------
    |
    | Some session drivers must manually sweep their expired sessions. Here,
    | you may specify the percentage of requests that should trigger the
    | sweeping to occur. By default, the lottery is set to 2 out of 100.
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify the name of the cookie that will be used to store
    | your session ID when using the "cookie" session driver. The name must
    | be unique within your application to avoid conflicts with other apps.
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    |
    | The session cookie path determines the path for which the cookie will
    | be regarded as valid. Typically, this will be the root path of your
    | application but you are free to change this value if needed.
    |
    */

    'path' => '/',

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    |
    | Here you may specify the domain of the cookie that should be used to
    | store your session ID. If this is left null, the cookie will be set
    | to the domain of the current request.
    |
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies
    |--------------------------------------------------------------------------
    |
    | By default, cookies will only be sent over HTTPS connections if you have
    | specified a secure URL in your "APP_URL" environment variable. However,
    | you may force all cookies to be secure by setting this option to true.
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Only Cookies
    |--------------------------------------------------------------------------
    |
    | By default, Laravel will set the "httpOnly" flag on your session
    | cookies. This flag will prevent JavaScript from accessing the value of
    | the cookie and will reduce the risk of cross-site scripting attacks.
    |
    */

    'http_only' => true,

    /*
    |--------------------------------------------------------------------------
    | Same-Site None Cookies
    |--------------------------------------------------------------------------
    |
    | This option determines if your session cookie will be set with the
    | "SameSite" attribute. By default, this is set to "lax" which works for
    | most use cases. You may set this to "strict" or "none" if needed.
    |
    | If your application is served via HTTPS and you wish to utilize the
    | "SameSite=None" attribute, you MUST also set the "secure" session
    | option to `true`. Otherwise, the cookie will not be set.
    |
    */

    'same_site' => 'lax',

    /*
    |--------------------------------------------------------------------------
    | Session Partitioned Cookies
    |--------------------------------------------------------------------------
    |
    | This option determines if your session cookie will be set with the
    | "Partitioned" attribute. By default, this is set to `false`. If you
    | want to experiment with the feature you may set this option to `true`.
    |
    */

    'partitioned' => false,

];
