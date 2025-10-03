<?php
return [
    'routes' => [
        '/auth/login'         => ['service' => 'auth', 'path' => 'login.php', 'dir' => 'auth'],
        '/payment/create_otp' => ['service' => 'payment', 'path' => 'create_otp.php', 'dir' => 'common'],
        '/payment/confirm_otp'=> ['service' => 'payment', 'path' => 'confirm_otp.php', 'dir' => 'common'],
        '/tuition/get'        => ['service' => 'tuition', 'path' => 'get_tuition.php', 'dir' => 'tuition']
    ],
    'ports' => [
        'auth'    => '8001',
        'payment' => '8002',
        'tuition' => '8003'
    ]
];
