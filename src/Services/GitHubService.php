<?php

namespace App\Services;

use App\Database\DatabaseConnection;

class GitHubService {
    private $db;
    private $websocketServer;
    private $webhookSecret; // GitHub webhook secret for verification

    public function __construct($websocketServer = null) {
        $this->db = DatabaseConnection::getInstance()->getConnection();
        $this->websocketServer = $websocketServer;
        $this->webhookSecret = getenv('GITHUB_WEBHOOK_SECRET') ?: 'your-webhook-secret';
    }

    public function handleWebhook($payload, $signature) {
        if (!$this->verifySignature($payload, $signature)) {
            throw new \Exception('Invalid webhook signature');
        }

        $data = json_decode($payload, true);
        $eventType = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';

        switch ($eventType) {
            case 'push':
                return $this->handlePushEvent($data);
            case 'pull_request':
                return $this->handlePullRequestEvent($data);
            default:
                return ['status' => 'ignored', 'message' => 'Event type not handled'];
        }
    }

    private function handlePushEvent($data) {
        $repository = $data['repository']['name'];
        $branch = str_replace('refs/heads/', '', $data['ref']);
        $commits = $data['commits'];
        $author = $data['pusher']['name'];

        // Store in database
        $stmt = $this->db->prepare("
            INSERT INTO github_events (
                event_type, repository, branch, author, commit_count, details, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            'push',
            $repository,
            $branch,
            $author,
            count($commits),
            json_encode($data)
        ]);

        // Broadcast to WebSocket clients
        if ($this->websocketServer) {
            $this->broadcastGitHubActivity('push', $repository, $branch, $author);
        }

        return [
            'status' => 'success',
            'message' => 'Push event processed'
        ];
    }

    private function handlePullRequestEvent($data) {
        $repository = $data['repository']['name'];
        $action = $data['action'];
        $author = $data['pull_request']['user']['login'];
        $branch = $data['pull_request']['head']['ref'];

        // Store in database
        $stmt = $this->db->prepare("
            INSERT INTO github_events (
                event_type, repository, branch, author, details, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            'pull_request',
            $repository,
            $branch,
            $author,
            json_encode($data)
        ]);

        // Broadcast to WebSocket clients
        if ($this->websocketServer) {
            $this->broadcastGitHubActivity('pull_request', $repository, $branch, $author);
        }

        return [
            'status' => 'success',
            'message' => 'Pull request event processed'
        ];
    }

    private function broadcastGitHubActivity($event, $repository, $branch, $author) {
        $data = json_encode([
            'type' => 'github',
            'event' => $event,
            'repository' => $repository,
            'branch' => $branch,
            'author' => $author,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        if (method_exists($this->websocketServer, 'broadcast')) {
            $this->websocketServer->broadcast($data);
        }
    }

    private function verifySignature($payload, $signature) {
        if (empty($signature)) {
            return false;
        }

        $calculated = 'sha1=' . hash_hmac('sha1', $payload, $this->webhookSecret);
        return hash_equals($signature, $calculated);
    }
}