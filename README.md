# PHP Telegram Bot API

This is a simple PHP package that provides an easy-to-use interface for the Telegram Bot API (https://core.telegram.org/bots). It is compliant with the March 31, 2024 Telegram Bot API update (version 7.2) and supports sending multiple requests at once.

## Requirements

To use PHP Telegram Bot API, you need the following:

- PHP 7.4 or higher
- MultiCurl and exec must be enabled in PHP
- Composer

## Installation

To install the package, use Composer:

```console
composer require alikm6/php-telegram-bot:v1.1.72
```

If you encounter error `it does not match your minimum-stability` during installation, run the following commands and try again:

```console
composer config minimum-stability dev
composer config prefer-stable true
```

## Usage

To use this package, you first need to create a Telegram object as follows:

```php
$tg = new TelegramBot\Telegram('your-bot-token');
```

### Parse Update
If you have set up a webhook for your bot, you can receive the information sent from Telegram to the server in the form of an array using the following code:

```php
$update = $tg->parseUpdate();
```

For more security, if you want to process only requests sent from Telegram servers and ignore other requests, you can use the following code at the beginning of your webhook file.

```php
TelegramBot\Telegram::limit_access_to_telegram_only();
```

In this case, if a request has not been sent from the Telegram servers, the php script will stop and the request will not be processed.

### Use of methods
You can use any of the Telegram Bot API methods, for example:

```php
$m = $tg->sendMessage([
      'chat_id' => 1233456,
      'text' => "Hello World"
]);
```

See https://core.telegram.org/bots/api for more information about available methods.

Each method can receive an optional array of options for API requests:

```php
$options = [
    'send_error' => true,  // If set to true, errors will be sent to the specified chat ID as a Telegram message.
    'run_in_background' => false,  // If set to true, requests will be processed in the background and responses will not be returned to the caller.
    'return' => 'result_array',  // The type of result to return from API requests.
];
```

The `return` option can have the following values:

- `result_array`: The 'result' key of the Telegram API response will be returned as an associative array.
- `result_object`: The 'result' key of the Telegram API response will be returned as an object.
- `response`: The raw response from the Telegram API will be returned as a string.
- `response_array`: The raw response from the Telegram API will be returned as an associative array.
- `response_object`: The raw response from the Telegram API will be returned as an object.

If `$options` is not set, then the default options will be applied.

### Sending Requests Simultaneously

To send several requests simultaneously, you can pass an array of requests to the method:

```php
$ms = $tg->sendMessage([
     [
          'chat_id' => 1233456,
          'text' => "Message 1"
     ],  [
          'chat_id' => 654321,
          'text' => "Message 2"
     ]
]);
```

## License

This package is licensed under the MIT License. See the LICENSE file for details.
