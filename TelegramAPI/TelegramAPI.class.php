<?php
class TelegramAPI {

    public function deleteMessage($chatID, $messageID) {
        return $this->_request(
            'deleteMessage',
            [
                'chat_id'    => $chatID,
                'message_id' => $messageID,
            ],
        );
    }

    public function getUpdates($offset = 0) {
        return $this->_request(
            'getUpdates',
            [
                'offset' => $offset,
            ],
        );
    }

    public function sendMessage($chatID, $text, $parseMode = false) {
        $a = [
            'chat_id' => $chatID,
            'text' => $text,
        ];
        if ($parseMode) {
            $a['parse_mode'] = $parseMode;
        }

        return $this->_request(
            'sendMessage',
            $a
        );
    }

    private function _request($method, $paramArray = []) {
        curl_setopt($this->_ch, CURLOPT_URL, 'https://api.telegram.org/bot'.$this->_token.'/'.$method);
        curl_setopt($this->_ch, CURLOPT_POST, true);
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $paramArray);

        $response = curl_exec($this->_ch);

        return json_decode($response, true);
    }

    public function __construct($token) {
        $this->_token = $token;

        $this->_ch = curl_init();
        curl_setopt($this->_ch, CURLOPT_HEADER, false);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_ch, CURLOPT_POST, 1);
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
    }

    private $_token; // string
    private $_ch; // curl resource

}