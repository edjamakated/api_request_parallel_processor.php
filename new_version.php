<?PHP
/* a work in progress */
<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class StatusTracker {
    public $num_tasks_started = 0;
    public $num_tasks_in_progress = 0;
    public $num_tasks_succeeded = 0;
    public $num_tasks_failed = 0;
    public $num_rate_limit_errors = 0;
    public $num_api_errors = 0;
    public $num_other_errors = 0;
    public $time_of_last_rate_limit_error = 0;

    public function get_summary() {
        return [
            'tasks_started' => $this->num_tasks_started,
            'tasks_in_progress' => $this->num_tasks_in_progress,
            'tasks_succeeded' => $this->num_tasks_succeeded,
            'tasks_failed' => $this->num_tasks_failed,
            'rate_limit_errors' => $this->num_rate_limit_errors,
            'api_errors' => $this->num_api_errors,
            'other_errors' => $this->num_other_errors,
            'time_of_last_rate_limit_error' => $this->time_of_last_rate_limit_error
        ];
    }
}

class RateLimiter {
    private $tokens;
    private $refill_rate;
    private $last_refill_time;

    public function __construct($tokens, $refill_rate) {
        $this->tokens = $tokens;
        $this->refill_rate = $refill_rate;
        $this->last_refill_time = time();
    }

    public function acquire($num_tokens) {
        $this->refill_tokens();
        if ($this->tokens >= $num_tokens) {
            $this->tokens -= $num_tokens;
            return true;
        }
        return false;
    }

    private function refill_tokens() {
        $current_time = time();
        $time_elapsed = $current_time - $this->last_refill_time;
        $tokens_to_add = $time_elapsed * $this->refill_rate;
        $this->tokens = min($this->tokens + $tokens_to_add, 1);
        $this->last_refill_time = $current_time;
    }
}

class APIRequest {
    public $task_id;
    public $request_json;
    public $token_consumption;
    public $attempts_left;
    public $result = [];

    public function __construct($task_id, $request_json, $token_consumption, $attempts_left) {
        $this->task_id = $task_id;
        $this->request_json = $request_json;
        $this->token_consumption = $token_consumption;
        $this->attempts_left = $attempts_left;
    }

    public function handle_error($reason, $status_tracker, $rate_limiter) {
        $error_message = $reason->getMessage();
        $response = $reason->getResponse();
        if ($response && $response->getStatusCode() === 429) {
            $status_tracker->num_rate_limit_errors += 1;
            $status_tracker->time_of_last_rate_limit_error = time();
            $retry_after = $response->getHeader('Retry-After')[0] ?? 1;
            sleep($retry_after);
            $rate_limiter->acquire($this->token_consumption);
        } elseif ($response) {
            $status_tracker->num_api_errors += 1;
        } else {
            $status_tracker->num_other_errors += 1;
        }
    }
public function call_api($client, $request_url, $request_header, $status_tracker, $save_filepath, $rate_limiter) {
    // Start the asynchronous request
    $promise = $client->postAsync($request_url, [
        'headers' => $request_header,
        'json' => $this->request_json
    ]);

    // Handle the result
    $promise->then(
        function ($response) use ($save_filepath, $status_tracker) {
            $result = json_decode($response->getBody(), true);
            append_to_jsonl([$this->request_json, $result], $save_filepath);
            $status_tracker->num_tasks_in_progress -= 1;
            $status_tracker->num_tasks_succeeded += 1;
        },
        function ($reason) use ($status_tracker, $rate_limiter) {
            // Handle errors
            $this->handle_error($reason, $status_tracker, $rate_limiter);
            if ($this->attempts_left > 0) {
                $this->attempts_left -= 1;
                $this->call_api($client, $request_url, $request_header, $status_tracker, $save_filepath, $rate_limiter);
            } else {
                $status_tracker->num_tasks_in_progress -= 1;
                $status_tracker->num_tasks_failed += 1;
            }
        }
    );

    return $promise;
}
