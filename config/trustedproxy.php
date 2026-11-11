<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Trust local loopback proxies by default so reverse proxies on the same
    | host forward the client IP and HTTPS scheme safely without trusting
    | arbitrary spoofed X-Forwarded-* headers from the public internet.
    |
    */

    'proxies' => env('TRUSTED_PROXIES', '127.0.0.1,::1'),

];

