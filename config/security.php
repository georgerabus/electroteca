<?php

return [
    'csp' => [
        'allow_dev' => env('CSP_ALLOW_DEV', env('APP_ENV', 'production') === 'local'),
        'allow_inline_style' => env('CSP_ALLOW_INLINE_STYLE', false),
        'connect_src' => env('CSP_CONNECT_SRC', ''),
        'img_src' => env('CSP_IMG_SRC', ''),
    ],
];
