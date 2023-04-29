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

    public function call_api($client, $request_url, $request_header, $status_tracker, $save_filepath) {
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
            function ($reason) use ($status_tracker) {
                // Handle errors
                $status_tracker->num_other_errors += 1;
            }
        );

        return $promise;
    }
}

function api_endpoint_from_url($request_url) {
    preg_match('/^https:\/\/[^\/]+\/v\d+\/(.+)$/', $request_url, $matches);
    return $matches[1];
}

function append_to_jsonl($data, $filename) {
    $json_string = json_encode($data);
    file_put_contents($filename, $json_string . "\n", FILE_APPEND);
}

function num_tokens_consumed_from_request($request_json, $api_endpoint) {
    // Not implemented: Token counting in PHP requires a separate implementation or library.
}

function task_id_generator() {
    $task_id = 0;
    while (true) {
        yield $task_id;
        $task_id += 1;
    }
}

function process_api_requests_from_file($params) {
    $status_tracker = new StatusTracker();
    $client = new Client();
    $request_header = [
        'Authorization' => 'Bearer ' . $params['api_key'],
        'Content-Type' => 'application/json'
    ];

    // Read the requests from the file
    $requests = file($params['requests_filepath'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $promises = [];
foreach ($requests as $request) {
    $request_json = json_decode($request, true);
    $task_id = $status_tracker->num_tasks_started;
    $status_tracker->num_tasks_started += 1;
    $status_tracker->num_tasks_in_progress += 1;

    $api_request = new APIRequest($task_id, $request_json, 0, $params['max_attempts']);
    $promises[] = $api_request->call_api($client, $params['request_url'], $request_header, $status_tracker, $params['save_filepath']);
}

// Wait for all requests to complete
$results = Promise\Utils::settle($promises)->wait();

// ... (handle the results)
}

// ... (parse command line arguments or use the $params array)

// Call the main function.
process_api_requests_from_file($params);

?>
