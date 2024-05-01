<?php

namespace TelegramBot;

use Curl\MultiCurl;
use ErrorException;
use InvalidArgumentException;
use CURLFile;


/**
 * Class representing a Telegram Bot API client.
 *
 * Class Version: 1.1.72
 * Telegram API Version: 7.2
 *
 * @package TelegramBot
 */
class Telegram
{
    private int $timeout = 60;

    private ?string $token;
    private ?string $ch_error_id;
    public ?string $update_from = null, $update_from_chat = null;

    private array $default_options = [
        'send_error' => true,
        'run_in_background' => false,
        'return' => 'result_array' //result_array, result_object, response, response_array, response_object
    ];

    private MultiCurl $MultiCurl;

    /**
     * Constructor method for the Telegram class.
     *
     * @param string|null $token The bot token to use for API requests.
     * @param mixed|null $ch_error_id The chat ID to send error messages to, or null to disable error reporting.
     * @param array $default_options An optional array of default options for API requests.
     *  $default_options can contain the following keys:
     *    - send_error (bool): If set to true, errors will be sent to the specified chat ID as a Telegram message.
     *    - run_in_background (bool): If set to true, requests will be processed in the background and responses will not be returned to the caller.
     *    - return (string): The type of result to return from API requests. Valid values are:
     *      - 'result_array': The 'result' key of the Telegram API response will be returned as an associative array.
     *      - 'result_object': The 'result' key of the Telegram API response will be returned as an object.
     *      - 'response': The raw response from the Telegram API will be returned as a string.
     *      - 'response_array': The raw response from the Telegram API will be returned as an associative array.
     *      - 'response_object': The raw response from the Telegram API will be returned as an object.
     */
    public function __construct(?string $token = null, $ch_error_id = null, array $default_options = [])
    {
        $this->token = $token;
        $this->ch_error_id = $ch_error_id;

        $this->validate_options($default_options);

        foreach ($default_options as $key => $val) {
            $this->default_options[$key] = $val;
        }

        $this->MultiCurl = new MultiCurl();
        $this->MultiCurl->setTimeout($this->timeout);
    }

    /**
     * @param array $options
     *
     * @return void
     */
    private function validate_options(array $options): void
    {
        foreach ($options as $key => $val) {
            if ($key == 'send_error') {
                if (!is_bool($val)) {
                    throw new InvalidArgumentException("Invalid send_error option.");
                }
            } elseif ($key == 'run_in_background') {
                if (!is_bool($val)) {
                    throw new InvalidArgumentException("Invalid run_in_background option.");
                }
            } elseif ($key == 'return') {
                if (!in_array($val, [
                    'result_array', 'result_array', 'result_object', 'response', 'response_array', 'response_object'
                ])) {
                    throw new InvalidArgumentException("Invalid return option.");
                }
            } else {
                throw new InvalidArgumentException("Invalid options.");
            }
        }

    }

    /**
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        $this->MultiCurl->setTimeout($timeout);

        return $this;
    }

    /**
     * @return false|string
     */
    public function getBotId()
    {
        if (empty($this->token)) {
            return false;
        }

        $tmp = explode(':', $this->token);
        return $tmp[0];
    }

    /**
     * @param array|null $update
     *
     * @return false|array
     */
    public function parseUpdate(?array $update = null)
    {
        if (empty($update)) {
            $update = json_decode(file_get_contents('php://input'), true);
        }

        if (empty($update)) {
            return false;
        }

        if (!empty($update['message'])) {
            $this->update_from_chat = $update['message']['chat']['id'];
            $this->update_from = $update['message']['from']['id'];
        } elseif (!empty($update['edited_message'])) {
            $this->update_from_chat = $update['edited_message']['chat']['id'];
            $this->update_from = $update['edited_message']['from']['id'];
        } elseif (!empty($update['channel_post'])) {
            $this->update_from_chat = $update['channel_post']['chat']['id'];
        } elseif (!empty($update['edited_channel_post'])) {
            $this->update_from_chat = $update['edited_channel_post']['chat']['id'];
        } elseif (!empty($update['inline_query'])) {
            $this->update_from = $update['inline_query']['from']['id'];
        } elseif (!empty($update['chosen_inline_result'])) {
            $this->update_from = $update['chosen_inline_result']['from']['id'];
        } elseif (!empty($update['callback_query'])) {
            if (!empty($update['callback_query']['message'])) {
                $this->update_from_chat = $update['callback_query']['message']['chat']['id'];
            }
            $this->update_from = $update['callback_query']['from']['id'];
        } elseif (!empty($update['shipping_query'])) {
            $this->update_from = $update['shipping_query']['from']['id'];
        } elseif (!empty($update['pre_checkout_query'])) {
            $this->update_from = $update['pre_checkout_query']['from']['id'];
        } elseif (!empty($update['poll_answer'])) {
            $this->update_from = $update['poll_answer']['user']['id'];
        } elseif (!empty($update['my_chat_member'])) {
            $this->update_from_chat = $update['my_chat_member']['chat']['id'];
            $this->update_from = $update['my_chat_member']['from']['id'];
        } elseif (!empty($update['chat_member'])) {
            $this->update_from_chat = $update['chat_member']['chat']['id'];
            $this->update_from = $update['chat_member']['from']['id'];
        } elseif (!empty($update['chat_join_request'])) {
            $this->update_from_chat = $update['chat_join_request']['chat']['id'];
            $this->update_from = $update['chat_join_request']['from']['id'];
        }

        return $update;
    }

    /**
     * More information: https://core.telegram.org/bots/api#getupdates
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getUpdates(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getUpdates', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setwebhook
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setWebhook(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setWebhook', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#deletewebhook
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function deleteWebhook(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('deleteWebhook', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getwebhookinfo
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getWebhookInfo(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getWebhookInfo', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getme
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getMe(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getMe', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#logout
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function logOut(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('logOut', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#close
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function close(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('close', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendmessage
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendMessage(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendMessage', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#forwardmessage
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function forwardMessage(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('forwardMessage', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#copymessage
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function copyMessage(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('copyMessage', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendphoto
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendPhoto(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendPhoto', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendaudio
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendAudio(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendAudio', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#senddocument
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendDocument(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendDocument', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendvideo
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendVideo(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendVideo', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendanimation
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendAnimation(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendAnimation', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendvoice
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendVoice(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendVoice', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendvideonote
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendVideoNote(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendVideoNote', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendmediagroup
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendMediaGroup(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendMediaGroup', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendlocation
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendLocation(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendLocation', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#editmessagelivelocation
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function editMessageLiveLocation(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('editMessageLiveLocation', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#stopmessagelivelocation
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function stopMessageLiveLocation(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('stopMessageLiveLocation', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendvenue
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendVenue(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendVenue', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendcontact
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendContact(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendContact', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendpoll
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendPoll(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendPoll', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#senddice
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendDice(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendDice', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendchataction
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendChatAction(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendChatAction', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getuserprofilephotos
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getUserProfilePhotos(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getUserProfilePhotos', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getfile
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getFile(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getFile', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#banchatmember
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function banChatMember(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('banChatMember', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#unbanchatmember
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function unbanChatMember(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('unbanChatMember', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#restrictchatmember
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function restrictChatMember(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('restrictChatMember', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#promotechatmember
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function promoteChatMember(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('promoteChatMember', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setchatadministratorcustomtitle
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setChatAdministratorCustomTitle(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setChatAdministratorCustomTitle', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#banchatsenderchat
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function banChatSenderChat(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('banChatSenderChat', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#unbanchatsenderchat
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function unbanChatSenderChat(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('unbanChatSenderChat', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setchatpermissions
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setChatPermissions(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setChatPermissions', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#exportchatinvitelink
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function exportChatInviteLink(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('exportChatInviteLink', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#createchatinvitelink
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function createChatInviteLink(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('createChatInviteLink', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#editchatinvitelink
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function editChatInviteLink(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('editChatInviteLink', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#revokechatinvitelink
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function revokeChatInviteLink(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('revokeChatInviteLink', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#approvechatjoinrequest
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function approveChatJoinRequest(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('approveChatJoinRequest', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#declinechatjoinrequest
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function declineChatJoinRequest(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('declineChatJoinRequest', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setchatphoto
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setChatPhoto(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setChatPhoto', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#deletechatphoto
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function deleteChatPhoto(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('deleteChatPhoto', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setchattitle
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setChatTitle(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setChatTitle', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setchatdescription
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setChatDescription(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setChatDescription', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#pinchatmessage
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function pinChatMessage(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('pinChatMessage', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#unpinchatmessage
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function unpinChatMessage(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('unpinChatMessage', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#unpinallchatmessages
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function unpinAllChatMessages(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('unpinAllChatMessages', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#leavechat
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function leaveChat(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('leaveChat', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getchat
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getChat(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getChat', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getchatadministrators
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getChatAdministrators(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getChatAdministrators', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getchatmemberscount
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getChatMembersCount(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getChatMembersCount', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getchatmember
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getChatMember(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getChatMember', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setchatstickerset
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setChatStickerSet(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setChatStickerSet', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#deletechatstickerset
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function deleteChatStickerSet(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('deleteChatStickerSet', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getforumtopiciconstickers
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getForumTopicIconStickers(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getForumTopicIconStickers', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#createforumtopic
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function createForumTopic(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('createForumTopic', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#editforumtopic
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function editForumTopic(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('editForumTopic', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#closeforumtopic
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function closeForumTopic(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('closeForumTopic', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#reopenforumtopic
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function reopenForumTopic(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('reopenForumTopic', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#deleteforumtopic
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function deleteForumTopic(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('deleteForumTopic', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#unpinallforumtopicmessages
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function unpinAllForumTopicMessages(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('unpinAllForumTopicMessages', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#editgeneralforumtopic
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function editGeneralForumTopic(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('editGeneralForumTopic', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#closegeneralforumtopic
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function closeGeneralForumTopic(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('closeGeneralForumTopic', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#reopengeneralforumtopic
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function reopenGeneralForumTopic(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('reopenGeneralForumTopic', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#hidegeneralforumtopic
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function hideGeneralForumTopic(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('hideGeneralForumTopic', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#unhidegeneralforumtopic
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function unhideGeneralForumTopic(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('unhideGeneralForumTopic', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#answercallbackquery
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function answerCallbackQuery(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('answerCallbackQuery', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setmycommands
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setMyCommands(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setMyCommands', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#deletemycommands
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function deleteMyCommands(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('deleteMyCommands', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getmycommands
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getMyCommands(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getMyCommands', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setchatmenubutton
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setChatMenuButton(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setChatMenuButton', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getchatmenubutton
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getChatMenuButton(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getChatMenuButton', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setmydefaultadministratorrights
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setMyDefaultAdministratorRights(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setMyDefaultAdministratorRights', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getmydefaultadministratorrights
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getMyDefaultAdministratorRights(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getMyDefaultAdministratorRights', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#editmessagetext
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function editMessageText(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('editMessageText', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#editmessagecaption
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function editMessageCaption(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('editMessageCaption', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#editmessagemedia
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function editMessageMedia(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('editMessageMedia', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#editmessagereplymarkup
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function editMessageReplyMarkup(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('editMessageReplyMarkup', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#stoppoll
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function stopPoll(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('stopPoll', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#deletemessage
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function deleteMessage(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('deleteMessage', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendsticker
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendSticker(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendSticker', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getstickerset
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getStickerSet(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getStickerSet', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getcustomemojistickers
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getCustomEmojiStickers(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getCustomEmojiStickers', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#uploadstickerfile
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function uploadStickerFile(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('uploadStickerFile', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#createnewstickerset
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function createNewStickerSet(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('createNewStickerSet', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#addstickertoset
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function addStickerToSet(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('addStickerToSet', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setstickerpositioninset
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setStickerPositionInSet(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setStickerPositionInSet', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#deletestickerfromset
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function deleteStickerFromSet(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('deleteStickerFromSet', $parameters, $options);
    }

    /**
     * Renamed the method setStickerSetThumb to setStickerSetThumbnail since v6.6
     * More information: https://core.telegram.org/bots/api#setstickersetthumb
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setStickerSetThumb(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setStickerSetThumb', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setstickersetthumbnail
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setStickerSetThumbnail(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setStickerSetThumbnail', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#answerinlinequery
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function answerInlineQuery(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('answerInlineQuery', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#answerwebappquery
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function answerWebAppQuery(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('answerWebAppQuery', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendinvoice
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendInvoice(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendInvoice', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#createinvoicelink
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function createInvoiceLink(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('createInvoiceLink', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#answershippingquery
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function answerShippingQuery(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('answerShippingQuery', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#answerprecheckoutquery
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function answerPreCheckoutQuery(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('answerPreCheckoutQuery', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setpassportdataerrors
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setPassportDataErrors(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setPassportDataErrors', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#sendgame
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendGame(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('sendGame', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setgamescore
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setGameScore(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setGameScore', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getgamehighscores
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getGameHighScores(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getGameHighScores', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setmydescription
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setMyDescription(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setMyDescription', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getmydescription
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getMyDescription(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getMyDescription', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setmyshortdescription
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setMyShortDescription(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setMyShortDescription', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getmyshortdescription
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getMyShortDescription(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getMyShortDescription', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setcustomemojistickersetthumbnail
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setCustomEmojiStickerSetThumbnail(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setCustomEmojiStickerSetThumbnail', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setstickersettitle
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setStickerSetTitle(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setStickerSetTitle', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#deletestickerset
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function deleteStickerSet(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('deleteStickerSet', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setstickeremojilist
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setStickerEmojiList(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setStickerEmojiList', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setstickerkeywords
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setStickerKeywords(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setStickerKeywords', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setstickermaskposition
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setStickerMaskPosition(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setStickerMaskPosition', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setmyname
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setMyName(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setMyName', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getmyname
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getMyName(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getMyName', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#unpinallgeneralforumtopicmessages
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function unpinAllGeneralForumTopicMessages(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('unpinAllGeneralForumTopicMessages', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#setmessagereaction
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function setMessageReaction(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('setMessageReaction', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#deletemessages
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function deleteMessages(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('deleteMessages', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#forwardmessages
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function forwardMessages(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('forwardMessages', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getuserchatboosts
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getUserChatBoosts(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getUserChatBoosts', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#getbusinessconnection
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function getBusinessConnection(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('getBusinessConnection', $parameters, $options);
    }

    /**
     * More information: https://core.telegram.org/bots/api#replacestickerinset
     *
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function replaceStickerInSet(array $parameters = [], array $options = [])
    {
        return $this->sendMethod('replaceStickerInSet', $parameters, $options);
    }

    /**
     * @param string $method_name The API method to call.
     * @param array $parameters An associative array of parameters to pass to the API method.
     * @param array $options An array of options for this specific API request.
     *  $options can contain the same keys as $default_options.
     *
     * @return mixed|bool|null The result of the API request, as specified by the 'return' option.
     *
     * @throws ErrorException
     */
    public function sendMethod(string $method_name, array $parameters = [], array $options = [])
    {
        if (empty($this->token)) {
            throw new ErrorException("Token is empty.");
        }

        $this->validate_options($options);

        foreach ($this->default_options as $key => $option) {
            if (!isset($options[$key])) {
                $options[$key] = $option;
            }
        }

        $main_url = "https://api.telegram.org/bot{$this->token}/{$method_name}";

        if ($this->count_dim($parameters) == 1) {
            $is_multi = false;
        } elseif ($this->count_dim($parameters) == 2) {
            $is_multi = true;
        } else {
            throw new ErrorException("Invalid parameters.");
        }

        if (!$options['run_in_background']) {
            $requests = [];

            if (!$is_multi) {
                $requests[] = $this->MultiCurl->addPost($main_url, $parameters);
            } else {
                foreach ($parameters as $key => $p) {
                    $requests[$key] = $this->MultiCurl->addPost($main_url, $p);
                }
            }

            $this->MultiCurl->start();

            $responses = [];

            foreach ($requests as $request_key => $request) {
                if (empty($request->rawResponse)) {
                    $response = false;

                    if ($options['send_error']) {
                        $this->send_error("Response is empty!", $request->errorCode);
                    }
                } else {
                    $response = $request->rawResponse;

                    if ($options['send_error'] && $request->error) {
                        $response_array = json_decode($response, true);

                        if (!$response_array) {
                            $error_message = $response;
                        } else {
                            $error_message = print_r($response_array, true);
                        }

                        $this->send_error($error_message);
                    }

                    if ($options['return'] == 'response_array') {
                        $response = json_decode($response, true);
                    } elseif ($options['return'] == 'response_object') {
                        $response = json_decode($response);
                    } elseif ($options['return'] == 'result_object') {
                        $response_object = json_decode($response);

                        if (!$response_object || !$response_object->ok || empty($response_object->result)) {
                            $response = false;
                        } else {
                            $response = $response_object->result;
                        }
                    } elseif ($options['return'] == 'result_array') {
                        $response_array = json_decode($response, true);

                        if (!$response_array || !$response_array['ok'] || empty($response_array['result'])) {
                            $response = false;
                        } else {
                            $response = $response_array['result'];
                        }
                    }
                }

                $responses[$request_key] = $response;
            }

            if ($is_multi) {
                return $responses;
            } else {
                return $responses[array_key_first($responses)];
            }
        } else {
            if (!$is_multi) {
                return $this->run_url_in_background($main_url, $parameters, $this->timeout);
            } else {
                $response = [];
                foreach ($parameters as $key => $p) {
                    $response[$key] = $this->run_url_in_background($main_url, $p, $this->timeout);
                }
                return $response;
            }
        }
    }

    /**
     * More information: https://core.telegram.org/bots/api#replykeyboardmarkup
     *
     * @param array $parameters
     *
     * @return string
     */
    public function replyKeyboardMarkup(array $parameters): string
    {
        return json_encode($parameters);
    }

    /**
     * More information https://core.telegram.org/bots/api#replykeyboardremove
     *
     * @param array $parameters
     *
     * @return string
     */
    public function replyKeyboardRemove(array $parameters = []): string
    {
        return json_encode(array_merge(['remove_keyboard' => true, 'selective' => false], $parameters));
    }

    /**
     * More information: https://core.telegram.org/bots/api#forcereply
     *
     * @param array $parameters
     *
     * @return string
     */
    public function forceReply(array $parameters = []): string
    {
        return json_encode(array_merge(['force_reply' => true, 'selective' => false], $parameters));
    }


    /**
     * @param string $error_message
     * @param int|null $error_code
     *
     * @return bool
     *
     * @throws ErrorException
     */
    public function send_error(string $error_message, ?int $error_code = null): bool
    {
        if ($error_code != null) {
            $error_message .= "\nCode: {$error_code}";
        }

        if ($this->update_from_chat != null) {
            $this->sendMessage(array("chat_id" => $this->update_from_chat, 'text' => $error_message), ['send_error' => false]);
        } elseif ($this->update_from != null) {
            $this->sendMessage(array("chat_id" => $this->update_from, 'text' => $error_message), ['send_error' => false]);
        }

        if ($this->ch_error_id != null && $this->ch_error_id != $this->update_from_chat && $this->ch_error_id != $this->update_from) {
            $this->sendMessage(array("chat_id" => $this->ch_error_id, 'text' => $error_message), ['send_error' => false]);
        }

        return true;
    }

    /**
     * @param array $array
     *
     * @return int
     */
    private function count_dim(array $array): int
    {
        if (is_array(reset($array))) {
            return $this->count_dim(reset($array)) + 1;
        }

        return 1;
    }

    /**
     * @param string $url
     * @param array $parameters An associative array of parameters to pass to the url.
     * @param int $timeout
     *
     * @return bool
     */
    private function run_url_in_background(string $url, array $parameters = [], int $timeout = 60): bool
    {
        $cmd = "curl";
        $cmd .= " ";
        $cmd .= "--max-time {$timeout}";
        if (!empty($parameters)) {
            foreach ($parameters as $key => $p) {
                if (empty($p)) {
                    continue;
                } elseif (is_bool($p) || is_string($p) || is_numeric($p)) {
                    $cmd .= " --data \"" . str_replace('"', "\\\"", $key . "=" . $p) . "\"";
                } elseif ($p instanceof CURLFile) {
                    $cmd .= " --form \"" . str_replace('"', "\\\"", $key . "=@" . $p->name) . "\"";
                } else {
                    return false;
                }
            }
        }

        $cmd .= " ";
        $cmd .= "\"" . str_replace('"', "\\\"", $url) . "\"";
        $cmd .= " ";
        $cmd .= "> /dev/null 2>&1 &";
        exec($cmd);

        return true;
    }

    /**
     * Using this function, you can check whether the request sent to the server was from Telegram or not.
     * If the request sent is not from the Telegram side, the script will stop.
     *
     * @return void
     */
    public static function limit_access_to_telegram_only()
    {
        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ipAddress = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ipAddress = $forward;
        } else {
            $ipAddress = $remote;
        }

        if (!isset($ipAddress)) {
            die('Error. No ip detected!');
        }

        $ip = sprintf('%u', ip2long($ipAddress));

        // 149.154.160.0/20	    =	149.154.160.0 ~ 149.154.175.255
        // 91.108.4.0/22	    =	91.108.4.0 ~ 91.108.7.255
        // The source of the above IPs: https://core.telegram.org/bots/webhooks#the-short-version
        // Convert IP to number: https://ipconvertertools.com/ip-2-bin-hex-dec-converter

        if (($ip >= 2509938688) && ($ip <= 2509942783)) { // ok
            return;
        }

        if (($ip >= 1533805568) && ($ip <= 1533806591)) { // ok
            return;
        }

        die('Error. You have not Telegram valid IP.');
    }
}
