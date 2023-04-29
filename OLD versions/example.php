<?PHP
/*
This is a placeholder, example file for starting a new job.


this needs to be worked on and combined with the new version
  this is how to inititate a new job
  
 */

// ... (Include previous classes and functions here)

$params = [
    'api_key' => 'your_api_key_here',
    'requests_filepath' => 'requests.txt',
    'request_url' => 'https://api.example.com/v1/process_text',
    'save_filepath' => 'results.jsonl',
    'max_attempts' => 3,
    'rate_limit_tokens' => 1,
    'rate_limit_refill_rate' => 0.5
];

function process_api_requests_from_file($params) {
    $status_tracker = new StatusTracker();
    $client = new Client();
    $request_header = [
        'Authorization' => 'Bearer ' . $params['api_key'],
        'Content-Type' => 'application/json'
    ];
    $rate_limiter = new RateLimiter($params['rate_limit_tokens'], $params['rate_limit_refill_rate']);

    // Read the requests from the file
    $requests = file($params['requests_filepath'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $promises = [];
    foreach ($requests as $request) {
        // ... (existing code)

        $promises[] = $api_request->call_api($client, $params['request_url'], $request_header, $status_tracker, $params['save_filepath'], $rate_limiter);
    }

    // Wait for all requests to complete
    $results = Promise\Utils::settle($promises)->wait();

    // Handle the results
    handle_api_results($results, $status_tracker);
}

function handle_api_results($results, $status_tracker) {
    // Print a summary of the API calls
    echo "Summary of API calls:\n";
    print_r($status_tracker->get_summary());

    // You can also process the results further, e.g., aggregate data, generate reports, etc.
}

// Call the main function
process_api_requests_from_file($params);

?>
