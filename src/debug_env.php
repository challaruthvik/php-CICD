<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Environment Variables Debug\n";
echo "=========================\n";
echo "AWS_ACCESS_KEY_ID: " . (getenv('AWS_ACCESS_KEY_ID') ? "Set" : "Not set") . "\n";
echo "AWS_SECRET_ACCESS_KEY: " . (getenv('AWS_SECRET_ACCESS_KEY') ? "Set" : "Not set") . "\n";
echo "AWS_REGION: " . getenv('AWS_REGION') . "\n";
echo "AWS_EC2_INSTANCES: " . getenv('AWS_EC2_INSTANCES') . "\n";