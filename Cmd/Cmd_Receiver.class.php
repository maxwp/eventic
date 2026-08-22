<?php
final class Cmd_Receiver implements Connection_Socket_IReceiver {

    public function onReceive($tsReceived, $message, $fromAddress, $fromPort) {
        try {
            // распаковка и проверка команды
            # debug:start
            Cli::Print_n(__CLASS__ . ": cmd=" . $message . " len=" . strlen($message));
            # debug:end

            $json = simdjson_decode($message, true);

            # debug:start
            Cli::Print_r($json);
            # debug:end

            if (empty($json['cmd'])) {
                throw new Exception('Invalid cmd');
            } elseif (empty($json['data'])) {
                throw new Exception('Invalid cmd data');
            } elseif (!is_array($json['data'])) {
                throw new Exception('Invalid cmd data array');
            }

            $cmdClass = $json['cmd'];
            if (!class_exists($cmdClass)) {
                throw new Exception('Invalid cmd class');
            }

            $object = new $cmdClass();
            /**
             * @var Cmd_Interface $object
             */
            $object->process($json['data']);
        } catch (Exception $te) {
            # debug:start
            Cli::Print_n(__CLASS__ . ': ' . $te->getMessage());
            # debug:end
        }
    }

}