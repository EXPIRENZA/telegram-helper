<?php

namespace Expirenza\src;

/**
 * Class TelegramHelper
 *
 * For init
 * $helper = new TelegramHelper('share_api_key');
 * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Send debug mode
 * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * helper = new TelegramHelper('share_api_key', true);
 * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Add message for send through all users in bot
 * $helper->addMessage('Broadcast message');
 * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Add message for send to user of bot
 * $helper->addMessage('Private message', 'phone_or_hash');
 * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Start send
 * $helper->send();
 * Returns if success: array(1) {
    ["success"]=>
        bool(true)
    }
 * Returns if false: array(1) {
    ["success"]=>
    bool(false)
   }
 * In debug mode returns logs data in success_send[], failed_send[], errors[]
 * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Get hash by phone
 * $helper->getHashByPhone('+380967611111');
 * Returns hash if exist, FALSE is hash not found
 * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * @package common\components\send
 */
class TelegramHelper
{
    const API_URL_SEND = 'http://telegram-hub.w3b.services/api/send';
    const API_URL_GET = 'http://telegram-hub.w3b.services/api/get/hash';

    protected $apiKey;
    protected $errors;

    protected $return;

    protected $receivers = [];
    protected $broadcast = [];

    public function __construct($apiKey = null, $debug = false)
    {

        if (empty($apiKey)) {
            throw new \InvalidArgumentException('api key is empty');
        }

        $this->apiKey = $apiKey;
        $this->debug = $debug;
    }

    public function send()
    {
        $data = [];

        $url = self::API_URL_SEND;

        if ($this->receivers) {

            $target = '/private';

            $data[] = [
                'url' => $url . $target,
                'request_body' => [
                    'apiKey' => $this->getApiKey(),
                    'receivers' => $this->receivers
                ]
            ];

        }

        if ($this->broadcast) {

            $target = '/public';

            foreach ($this->broadcast as $message) {
                $data[] = [
                    'url' => $url . $target,
                    'request_body' => [
                        'apiKey' => $this->getApiKey(),
                        'message' => $message
                    ]
                ];
            }
        }

        if ($data) {
            foreach ($data as $row) {
                /**
                 * Initialize CURL
                 */
                $ch = $this->initCurl($row['url'], $row['request_body']);

                $response = $this->makeRequest($ch);

                if ($this->parseServerResponse($response)) {

                    if ($this->debug) {

                        $return['success_send'][] =
                            [
                                'url' => $row['url'],
                                'request_body' => $row['request_body'],
                                'result' => $this->parseServerResponse($response)
                            ];
                    }
                } else {

                    if ($this->debug) {

                        $return['failed_send'][] = [
                            'url' => $row['url'],
                            'request_body' => $row['request_body']
                        ];
                    }
                }

                curl_close($ch);
            }
        }

        if (!$this->errors) {
            $return['success'] = true;
        } else {

            if($this->debug) {
                $return['errors'] = $this->errors;
            }

            $return['success'] = false;
        }

        return $return;
    }

    /**
     * Get current API key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    protected function initCurl($url, $body)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        return $ch;
    }

    protected function makeRequest($ch)
    {
        $server_output = curl_exec($ch);

        $this->_server_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return $server_output;
    }

    protected function parseServerResponse($serverOutput)
    {
        $json = json_decode($serverOutput);

        /**
         * Parse response from API
         */
        if (is_object($json)) {
            if ($json->success == true) {
                return $json;
            } else {

                $this->errors[] = [
                    'message' => $json->message
                ];

                return false;
            }
        } else {

            $this->errors[] = [
                'message' => 'Can not detect reason. Response is not an object'
            ];

            return false;
        }
    }

    public function getHashByPhone($phone)
    {
        $requestBody = [
            'apiKey' => $this->getApiKey(),
            'phone' => $phone
        ];

        $ch = $this->initCurl(self::API_URL_GET, $requestBody);

        $response = $this->makeRequest($ch);

        $result = $this->parseServerResponse($response);

        return $result ? $result->hash : $result;
    }

    /**
     * @param $receiver
     * @param $message
     * @return bool
     */
    public function addMessage($message, $receiver = false)
    {
        if ($receiver) {
            $this->receivers[] = [
                'receiver' => $receiver,
                'message' => $message
            ];
        } else {
            $this->broadcast[] = $message;
        }

        return true;
    }

    /**
     * Get errors if exist
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

}
