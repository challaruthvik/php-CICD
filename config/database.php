<?php

// Database configuration
return [
    'database' => [
        'host' => 'localhost',
        'dbname' => 'sephp_monitoring', // Changed from monitoring_system
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],
    'websocket' => [
        'host' => '127.0.0.1',  // Changed from 0.0.0.0 to be more specific
        'port' => 8081,  // Changed from 8080 to 8081
        'allowed_origins' => ['http://localhost', 'http://localhost:80', 'http://127.0.0.1']
    ],
    'aws' => [
        'key' => getenv('AWS_ACCESS_KEY_ID'),
        'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
        'region' => getenv('AWS_REGION') ?: 'us-east-1',
        'instances' => explode(',', getenv('AWS_EC2_INSTANCES') ?: '')
    ]
];