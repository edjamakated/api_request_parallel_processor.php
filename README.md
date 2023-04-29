# Asynchronous API Requests in PHP

This script is an adaptation of a Python version provided by OpenAI that allows you to asynchronously send requests to their API without exceeding rate limits or token limits.

The script reads requests from a file and sends them asynchronously to the specified API endpoint. It handles rate limiting and retries failed requests up to a specified number of attempts.

## Table of Contents

- [Usage](#usage)
- [Installation](#installation)
- [Configuration](#configuration)
- [Customization](#customization)
- [Contributing](#contributing)
- [License](#license)

## Usage

1. Replace `'your_api_key_here'` with your actual API key in the `$params` array.
2. Modify the other parameters in the `$params` array as needed, such as the file paths and rate limiting settings.
3. Run the script.

## Installation

1. Clone the repository:
https://github.com/edjamakated/api_request_parallel_processor.php/

2. Install dependencies using [Composer](https://getcomposer.org/):
composer install guzzle

## Configuration

Modify the parameters in the `$params` array in the main script:

- `'api_key'`: Your API key
- `'requests_filepath'`: File path for the requests input file
- `'request_url'`: API endpoint URL
- `'save_filepath'`: File path for saving the results
- `'max_attempts'`: Maximum number of attempts to retry failed requests
- `'rate_limit_tokens'`: Number of tokens for rate limiting
- `'rate_limit_refill_rate'`: Rate at which the rate limiting tokens refill

## Customization

You can customize the script to suit your needs, for example:

- Change the API endpoint
- Modify the rate limiting settings
- Implement different error handling strategies
- Extend the script to handle more complex requests and responses

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change. Please make sure to update tests as appropriate.

## License

[MIT](https://choosealicense.com/licenses/mit/)



# api_request_parallel_processor.php

## converted OpenAI cookbook Python example to PHP 
