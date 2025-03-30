<?php

/**
 * GitHub configuration settings
 */
return [
    // Webhook secret for GitHub events (should match what's configured in GitHub)
    'webhook_secret' => getenv('GITHUB_WEBHOOK_SECRET') ?: 'sephp_webhook_secret_2024',
    
    // GitHub API configuration
    'api' => [
        'username' => getenv('GITHUB_USERNAME') ?: '',
        'token' => getenv('GITHUB_TOKEN') ?: '',
        'repo' => getenv('GITHUB_REPO') ?: '',
        'webhook_id' => getenv('GITHUB_WEBHOOK_ID') ?: '',
    ],
    
    // GitHub event types to process
    'events' => [
        'push',
        'pull_request',
        'deployment',
        'deployment_status'
    ],
    
    // Config for GitHub Actions
    'actions' => [
        'enabled' => true,
    ]
];