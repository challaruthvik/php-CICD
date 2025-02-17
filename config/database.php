<?php

// Database configuration
return [
    'database' => [
        'host' => 'localhost',
        'dbname' => 'monitoring_system',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],
    'websocket' => [
        'host' => '0.0.0.0',
        'port' => 8080,
        'allowed_origins' => ['http://localhost']
    ],
    'aws' => [
        'key' => getenv('AWS_ACCESS_KEY_ID'),
        'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
        'region' => getenv('AWS_REGION') ?: 'us-east-1',
        'instances' => explode(',', getenv('AWS_EC2_INSTANCES') ?: '')
    ]
];