<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Database\DatabaseConnection;

try {
    $db = DatabaseConnection::getInstance()->getConnection();
    
    // Sample events data with real GitHub formatting
    $events = [
        [
            'event_type' => 'push',
            'repository' => 'microsoft/vscode',
            'branch' => 'main',
            'author' => 'joaomoreno',
            'commit_count' => 2,
            'details' => json_encode([
                'ref' => 'refs/heads/main',
                'commits' => [
                    [
                        'id' => 'a7d3fcb91a3cb89d214fe52b8c8af3f9a234bd98',
                        'message' => 'workbench: fix window reload action in web',
                        'timestamp' => '2025-03-24T10:30:00Z',
                        'url' => 'https://github.com/microsoft/vscode/commit/a7d3fcb91a3cb89d214fe52b8c8af3f9a234bd98'
                    ],
                    [
                        'id' => 'b8e5dc21a4c9a0b5d779319d0f4fe6f0d3ef5d5b',
                        'message' => 'extensions: improve error handling in extension host',
                        'timestamp' => '2025-03-24T10:25:00Z',
                        'url' => 'https://github.com/microsoft/vscode/commit/b8e5dc21a4c9a0b5d779319d0f4fe6f0d3ef5d5b'
                    ]
                ]
            ])
        ],
        [
            'event_type' => 'pull_request',
            'repository' => 'facebook/react',
            'branch' => 'feature/concurrent-mode',
            'author' => 'gaearon',
            'commit_count' => 1,
            'details' => json_encode([
                'pull_request' => [
                    'number' => 24601,
                    'title' => 'Improve Concurrent Mode scheduling algorithm',
                    'body' => 'This PR improves the scheduling algorithm in Concurrent Mode to better handle priority updates.',
                    'html_url' => 'https://github.com/facebook/react/pull/24601',
                    'state' => 'open',
                    'created_at' => '2025-03-24T09:15:00Z',
                    'updated_at' => '2025-03-24T10:30:00Z'
                ]
            ])
        ],
        [
            'event_type' => 'deployment',
            'repository' => 'vercel/next.js',
            'branch' => 'release/13.5.0',
            'author' => 'timneutkens',
            'commit_count' => 0,
            'details' => json_encode([
                'deployment' => [
                    'environment' => 'production',
                    'description' => 'Deploy Next.js 13.5.0',
                    'sha' => 'c7e91fe629b4a2deb8742991f2c6293bc9a85b22',
                    'creator' => [
                        'login' => 'timneutkens',
                        'avatar_url' => 'https://avatars.githubusercontent.com/u/12345678'
                    ],
                    'created_at' => '2025-03-24T08:00:00Z',
                    'updated_at' => '2025-03-24T08:05:00Z'
                ]
            ])
        ],
        [
            'event_type' => 'pull_request',
            'repository' => 'laravel/framework',
            'branch' => 'feature/eloquent-improvements',
            'author' => 'taylorotwell',
            'commit_count' => 3,
            'details' => json_encode([
                'pull_request' => [
                    'number' => 47892,
                    'title' => 'Add new Eloquent relationship types',
                    'body' => 'This PR introduces new relationship types to Eloquent ORM for better handling of polymorphic associations.',
                    'html_url' => 'https://github.com/laravel/framework/pull/47892',
                    'state' => 'open',
                    'created_at' => '2025-03-24T07:30:00Z',
                    'updated_at' => '2025-03-24T09:45:00Z'
                ]
            ])
        ]
    ];
    
    // Clear existing events first
    $db->exec("TRUNCATE TABLE github_events");
    
    // Insert sample events
    $stmt = $db->prepare("
        INSERT INTO github_events (
            event_type, repository, branch, author, commit_count, details, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    foreach ($events as $event) {
        $stmt->execute([
            $event['event_type'],
            $event['repository'],
            $event['branch'],
            $event['author'],
            $event['commit_count'],
            $event['details']
        ]);
    }
    
    echo "Sample GitHub events inserted successfully.\n";
    
} catch (Exception $e) {
    die("Error inserting sample events: " . $e->getMessage() . "\n");
}