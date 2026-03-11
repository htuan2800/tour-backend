<?php

// config/momo.php
return [
    'endpoint'     => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
    'redirect_url' => env('MOMO_REDIRECT_URL'),
    'ipn_url'      => env('MOMO_IPN_URL'),
    'partner_code' => env('MOMO_PARTNER_CODE'),
    'access_key'   => env('MOMO_ACCESS_KEY'),
    'secret_key'   => env('MOMO_SECRET_KEY'),
    'request_type' => env('MOMO_REQUEST_TYPE', 'payWithMethod'),
];