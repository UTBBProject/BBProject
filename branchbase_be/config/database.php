<?php

switch (env('APP_ENV')) {
    case 'prod' :
        $host = env('DB_HOST_PROD');
        $port = env('DB_PORT_PROD');
        $username = env('DB_USERNAME_PROD');
        $password = env('DB_PASSWORD_PROD');
        $db_utalk = env('DB_DATABASE_UTALK_PROD');
        $db_payroll = env('DB_DATABASE_PAYROLL_PROD');
        $db_recruitment = env('DB_DATABASE_RECRUITMENT_PROD');
        break;
    case 'test' :
        $host = env('DB_HOST_TEST');
        $port = env('DB_PORT_TEST');
        $username = env('DB_USERNAME_TEST');
        $password = env('DB_PASSWORD_TEST');
        $db_utalk = env('DB_DATABASE_UTALK_TEST');
        $db_payroll = env('DB_DATABASE_PAYROLL_TEST');
        $db_recruitment = env('DB_DATABASE_RECRUITMENT_TEST');
        break;
    default:
        $host = env('DB_HOST_DEV');
        $port = env('DB_PORT_DEV');
        $username = env('DB_USERNAME_DEV');
        $password = env('DB_PASSWORD_DEV');
        $db_utalk = env('DB_DATABASE_UTALK_DEV');
        $db_payroll = env('DB_DATABASE_PAYROLL_DEV');
        $db_recruitment = env('DB_DATABASE_RECRUITMENT_DEV');
}

return [
    'default' => 'db_edu',
    'connections' => [
        'db_recruitment' => [
            'driver' => env('DB_CONNECTION'),
            'host' => $host,
            'port' =>$port,
            'database' => $db_recruitment,
            'username' => $username,
            'password' => $password,
            'charset' => env('DB_CHARSET'),
            'collation' => env('DB_COLLATION'),
            'prefix' => '',
            'timezone' => env('DB_TIMEZONE'),
            'strict' => env('DB_STRICT_MODE')
        ],
        'db_payroll' => [
            'driver' => env('DB_CONNECTION'),
            'host' => $host,
            'port' =>$port,
            'database' => $db_payroll,
            'username' => $username,
            'password' => $password,
            'charset' => env('DB_CHARSET'),
            'collation' => env('DB_COLLATION'),
            'prefix' => '',
            'timezone' => env('DB_TIMEZONE'),
            'strict' => env('DB_STRICT_MODE')
        ],
        'db_edu' => [
            'driver' => env('DB_CONNECTION'),
            'host' => $host,
            'port' =>$port,
            'database' => $db_utalk,
            'username' => $username,
            'password' => $password,
            'charset' => env('DB_CHARSET'),
            'collation' => env('DB_COLLATION'),
            'prefix' => '',
            'timezone' => env('DB_TIMEZONE'),
            'strict' => env('DB_STRICT_MODE')
        ]
    ],
];
?>
