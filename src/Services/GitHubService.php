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
        
        // Try to get the secret from environment variable first
        $this->webhookSecret = getenv('GITHUB_WEBHOOK_SECRET');
        
        // If not found, try to load from a config file
        if (!$this->webhookSecret) {
            $configFile = __DIR__ . '/../../config/github.php';
            if (file_exists($configFile)) {
                $config = include $configFile;
                $this->webhookSecret = $config['webhook_secret'] ?? 'sephp_webhook_secret_2024';
            } else {
                $this->webhookSecret = 'sephp_webhook_secret_2024';
            }
        }
    }

    public function handleWebhook($payload, $signature, $bypassVerification = false) {
        // Verify signature unless bypassed for testing
        if (!$bypassVerification && !$this->verifySignature($payload, $signature)) {
            throw new \Exception('Invalid webhook signature. Please check your webhook secret configuration.');
        }

        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON payload: ' . json_last_error_msg());
        }

        $eventType = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? 'unknown';

        try {
            switch ($eventType) {
                case 'push':
                    return $this->handlePushEvent($data);
                case 'pull_request':
                    return $this->handlePullRequestEvent($data);
                case 'deployment':
                    return $this->handleDeploymentEvent($data);
                case 'deployment_status':
                    return $this->handleDeploymentStatusEvent($data);
                case 'ping':
                    return $this->handlePingEvent($data);
                default:
                    return ['status' => 'ignored', 'message' => "Event type '$eventType' not handled"];
            }
        } catch (\Exception $e) {
            error_log("Error processing GitHub webhook: " . $e->getMessage());
            throw $e;
        }
    }

    private function handlePushEvent($data) {
        if (!isset($data['repository']['name']) || !isset($data['ref'])) {
            return ['status' => 'error', 'message' => 'Missing required push event data'];
        }

        $repository = $data['repository']['name'];
        $branch = str_replace('refs/heads/', '', $data['ref']);
        $commits = $data['commits'] ?? [];
        $author = $data['pusher']['name'] ?? 'Unknown';

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
            'message' => 'Push event processed',
            'repository' => $repository,
            'branch' => $branch,
            'commits' => count($commits)
        ];
    }

    private function handlePullRequestEvent($data) {
        if (!isset($data['repository']['name']) || !isset($data['pull_request'])) {
            return ['status' => 'error', 'message' => 'Missing required pull request event data'];
        }

        $repository = $data['repository']['name'];
        $action = $data['action'] ?? 'unknown';
        $author = $data['pull_request']['user']['login'] ?? 'Unknown';
        $branch = $data['pull_request']['head']['ref'] ?? 'Unknown';

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
            'message' => 'Pull request event processed',
            'repository' => $repository,
            'branch' => $branch,
            'action' => $action
        ];
    }

    private function handleDeploymentEvent($data) {
        if (!isset($data['repository']['name']) || !isset($data['deployment'])) {
            return ['status' => 'error', 'message' => 'Missing required deployment event data'];
        }

        $repository = $data['repository']['name'];
        $environment = $data['deployment']['environment'] ?? 'production';
        $commitSha = $data['deployment']['sha'] ?? '';
        $description = $data['deployment']['description'] ?? '';

        $stmt = $this->db->prepare("
            INSERT INTO deployments (
                repository, environment, status, commit_sha, description
            ) VALUES (?, ?, 'pending', ?, ?)
        ");

        $stmt->execute([
            $repository,
            $environment,
            $commitSha,
            $description
        ]);

        if ($this->websocketServer) {
            $this->broadcastDeploymentActivity('pending', $repository, $environment, $commitSha);
        }

        return [
            'status' => 'success',
            'message' => 'Deployment event processed',
            'repository' => $repository,
            'environment' => $environment
        ];
    }

    private function handleDeploymentStatusEvent($data) {
        if (!isset($data['repository']['name']) || !isset($data['deployment']) || !isset($data['deployment_status'])) {
            return ['status' => 'error', 'message' => 'Missing required deployment status event data'];
        }

        $repository = $data['repository']['name'];
        $environment = $data['deployment']['environment'] ?? 'production';
        $commitSha = $data['deployment']['sha'] ?? '';
        $status = $data['deployment_status']['state'] ?? 'unknown';
        $description = $data['deployment_status']['description'] ?? '';

        $stmt = $this->db->prepare("
            UPDATE deployments 
            SET status = ?, description = ? 
            WHERE repository = ? AND environment = ? AND commit_sha = ?
        ");

        $stmt->execute([
            $status,
            $description,
            $repository,
            $environment,
            $commitSha
        ]);

        if ($this->websocketServer) {
            $this->broadcastDeploymentActivity($status, $repository, $environment, $commitSha);
        }

        return [
            'status' => 'success',
            'message' => 'Deployment status event processed',
            'repository' => $repository,
            'environment' => $environment,
            'status' => $status
        ];
    }

    private function handlePingEvent($data) {
        // GitHub sends a ping event when you first set up a webhook
        $repository = $data['repository']['name'] ?? 'unknown';
        $sender = $data['sender']['login'] ?? 'unknown';
        
        return [
            'status' => 'success',
            'message' => 'Ping event received',
            'repository' => $repository,
            'sender' => $sender,
            'hook_id' => $data['hook_id'] ?? null
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

    private function broadcastDeploymentActivity($status, $repository, $environment, $commitSha) {
        $data = json_encode([
            'type' => 'deployment',
            'status' => $status,
            'repository' => $repository,
            'environment' => $environment,
            'commit_sha' => $commitSha,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        if (method_exists($this->websocketServer, 'broadcast')) {
            $this->websocketServer->broadcast($data);
        }
    }

    private function verifySignature($payload, $signature) {
        if (empty($signature) || empty($this->webhookSecret)) {
            return false;
        }

        $calculated = 'sha1=' . hash_hmac('sha1', $payload, $this->webhookSecret);
        return hash_equals($signature, $calculated);
    }
}