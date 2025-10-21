<?php
return [
    'routes' => [
        '/auth/login'         => ['service' => 'auth', 'path' => 'login.php', 'dir' => 'auth'],
        '/user/get_user_info' => ['service' => 'user', 'path' => 'get_user_info.php', 'dir' => 'user'],
        '/user/deduct_balance' => ['service' => 'user', 'path' => 'deduct_balance.php', 'dir' => 'user'],
        '/user/add_transactions' => ['service' => 'user', 'path' => 'add_transactions.php', 'dir' => 'user'],
        '/payment/create_otp' => ['service' => 'payment', 'path' => 'create_otp.php', 'dir' => 'payment'],
        '/payment/resend_otp' => ['service' => 'payment', 'path' => 'resend_otp.php', 'dir' => 'payment'],
        '/payment/confirm_otp' => ['service' => 'payment', 'path' => 'confirm_otp.php', 'dir' => 'payment'],
        '/user/transactions'  =>  ['service' => 'user', 'path' => 'transactions.php', 'dir' => 'user'],
        '/tuition/get' => ['service' => 'tuition', 'path' => 'get_tuition.php', 'dir' => 'tuition'],
        '/tuition/update_status' => ['service' => 'tuition', 'path' => 'update_status.php', 'dir' => 'tuition'],

    ],
    'ports' => [
        'auth'    => '8001',
        'user'    => '8001',
        'payment' => '8002',
        'tuition' => '8003'
    ]
];
