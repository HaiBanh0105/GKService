<?php
return [
    'routes' => [
        '/auth/login'         => ['service' => 'auth', 'path' => 'login.php', 'dir' => 'auth'],
        '/auth/get_user_info' => ['service' => 'auth', 'path' => 'get_user_info.php', 'dir' => 'auth'],
        '/auth/deduct_balance' => ['service' => 'auth', 'path' => 'deduct_balance.php', 'dir' => 'auth'],
        '/auth/add_transactions' => ['service' => 'auth', 'path' => 'add_transactions.php', 'dir' => 'auth'],
        '/payment/create_otp' => ['service' => 'payment', 'path' => 'create_otp.php', 'dir' => 'payment'],
        '/payment/resend_otp' => ['service' => 'payment', 'path' => 'resend_otp.php', 'dir' => 'payment'],
        '/payment/confirm_otp' => ['service' => 'payment', 'path' => 'confirm_otp.php', 'dir' => 'payment'],
        '/auth/transactions'  =>  ['service' => 'auth', 'path' => 'transactions.php', 'dir' => 'auth'],
        '/tuition/get' => ['service' => 'tuition', 'path' => 'get_tuition.php', 'dir' => 'tuition'],
        '/tuition/update_status' => ['service' => 'tuition', 'path' => 'update_status.php', 'dir' => 'tuition'],

    ],
    'ports' => [
        'auth'    => '8001',
        'payment' => '8002',
        'tuition' => '8003'
    ]
];
