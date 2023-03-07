# PHP Telegram Bot API

This is a simple PHP package that provides an easy-to-use interface for the Telegram Bot API (https://core.telegram.org/bots). It is compliant with the February 3, 2023 Telegram Bot API update (version 6.5) and supports sending multiple requests at once.

## Requirements

To use PHP Telegram Bot API, you need the following:

- PHP 7.4 or higher
- MultiCurl and exec must be enabled in PHP
- Composer

## Installation

To install the package, use Composer:

```consol
composer require alikm6/php-telegram-bot:v1.6.5
```

If you encounter error `it does not match your minimum-stability` during installation, run the following commands and try again:

```console
composer config minimum-stability dev
composer config prefer-stable true
```

## Usage

To use this package, you first need to create a Telegram object as follows:

```php
use TelegramBot/Telegram;

$tg = new Telegram('your-bot-token');
```

### Parse Update
If you have set up a webhook for your bot, you can receive the information sent from Telegram to the server in the form of an array using the following code:

```php
$update = $tg->parseUpdate();
```

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
