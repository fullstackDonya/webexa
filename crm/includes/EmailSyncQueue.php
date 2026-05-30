<?php
/**
 * EmailSyncQueue - Redis-based job queue for email synchronization
 * 
 * Manages background jobs for email sync to avoid blocking web requests
 * Supports priority levels, retries, and rate limiting
 * 
 * @package CRM
 * @version 1.0.0
 */

class EmailSyncQueue {
    
    private Redis $redis;
    private string $queueKey = 'email:sync:queue';
    private string $processingKey = 'email:sync:processing';
    private string $deadLetterKey = 'email:sync:failed';
    
    public function __construct() {
        $this->redis = new Redis();
        
        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = (int)(getenv('REDIS_PORT') ?: 6379);
        $password = getenv('REDIS_PASSWORD') ?: null;
        $db = (int)(getenv('REDIS_DB') ?: 0);
        
        if (!$this->redis->connect($host, $port, 2.5)) {
            throw new Exception('Failed to connect to Redis');
        }
        
        if ($password) {
            $this->redis->auth($password);
        }
        
        $this->redis->select($db);
    }
    
    /**
     * Add sync job to queue
     * 
     * @param int $configId Email configuration ID
     * @param array $options Additional options (priority, retry_count, etc.)
     * @return bool Success
     */
    public function enqueue(int $configId, array $options = []): bool {
        $job = [
            'config_id' => $configId,
            'priority' => $options['priority'] ?? 'normal',
            'retry_count' => $options['retry_count'] ?? 0,
            'max_retries' => $options['max_retries'] ?? 3,
            'created_at' => time(),
            'scheduled_at' => $options['scheduled_at'] ?? time(),
        ];
        
        $priority = $this->getPriorityScore($job['priority']);
        
        // Use sorted set for priority queue
        $this->redis->zAdd(
            $this->queueKey, 
            $priority * 1000000 + $job['scheduled_at'], 
            json_encode($job)
        );
        
        return true;
    }
    
    /**
     * Get next job from queue
     * 
     * @return array|null Job data or null if queue empty
     */
    public function dequeue(): ?array {
        // Get highest priority job (lowest score)
        $jobs = $this->redis->zRange($this->queueKey, 0, 0);
        
        if (empty($jobs)) {
            return null;
        }
        
        $jobJson = $jobs[0];
        $job = json_decode($jobJson, true);
        
        // Check if scheduled time has arrived
        if ($job['scheduled_at'] > time()) {
            return null; // Not yet time
        }
        
        // Move to processing set
        $this->redis->zRem($this->queueKey, $jobJson);
        $this->redis->setex(
            $this->processingKey . ':' . $job['config_id'], 
            3600, // 1 hour timeout
            $jobJson
        );
        
        return $job;
    }
    
    /**
     * Mark job as completed
     * 
     * @param int $configId Configuration ID
     * @return bool Success
     */
    public function complete(int $configId): bool {
        return $this->redis->del($this->processingKey . ':' . $configId) > 0;
    }
    
    /**
     * Mark job as failed and optionally retry
     * 
     * @param int $configId Configuration ID
     * @param string $error Error message
     * @return bool Success
     */
    public function fail(int $configId, string $error): bool {
        $jobJson = $this->redis->get($this->processingKey . ':' . $configId);
        
        if (!$jobJson) {
            return false;
        }
        
        $job = json_decode($jobJson, true);
        $job['retry_count']++;
        $job['last_error'] = $error;
        $job['failed_at'] = time();
        
        // Remove from processing
        $this->redis->del($this->processingKey . ':' . $configId);
        
        // Retry if under max retries
        if ($job['retry_count'] < $job['max_retries']) {
            // Exponential backoff: 1min, 5min, 15min
            $backoffSeconds = min(900, 60 * pow(2, $job['retry_count'] - 1));
            $job['scheduled_at'] = time() + $backoffSeconds;
            
            return $this->enqueue($configId, $job);
        } else {
            // Move to dead letter queue
            $this->redis->zAdd(
                $this->deadLetterKey,
                time(),
                json_encode($job)
            );
            
            return false;
        }
    }
    
    /**
     * Get queue statistics
     * 
     * @return array Stats
     */
    public function getStats(): array {
        return [
            'pending' => $this->redis->zCard($this->queueKey),
            'processing' => count($this->redis->keys($this->processingKey . ':*')),
            'failed' => $this->redis->zCard($this->deadLetterKey),
        ];
    }
    
    /**
     * Check if a config is already queued or processing
     * 
     * @param int $configId Configuration ID
     * @return bool True if already in queue
     */
    public function isQueued(int $configId): bool {
        // Check processing
        if ($this->redis->exists($this->processingKey . ':' . $configId)) {
            return true;
        }
        
        // Check pending queue
        $jobs = $this->redis->zRange($this->queueKey, 0, -1);
        foreach ($jobs as $jobJson) {
            $job = json_decode($jobJson, true);
            if ($job['config_id'] === $configId) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Clear all queues (use with caution)
     * 
     * @return bool Success
     */
    public function clear(): bool {
        $this->redis->del($this->queueKey);
        $this->redis->del($this->deadLetterKey);
        
        $processingKeys = $this->redis->keys($this->processingKey . ':*');
        if ($processingKeys) {
            $this->redis->del($processingKeys);
        }
        
        return true;
    }
    
    /**
     * Get priority score for sorting
     * 
     * @param string $priority Priority level
     * @return int Score (lower = higher priority)
     */
    private function getPriorityScore(string $priority): int {
        return match($priority) {
            'high' => 1,
            'normal' => 5,
            'low' => 10,
            default => 5
        };
    }
}
