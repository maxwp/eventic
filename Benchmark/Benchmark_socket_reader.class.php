<?php
class Benchmark_socket_reader extends EE_Content_Abstract_Cli {

    public function process() {
        $socket = new Connection_SocketUDP();
        $socket->read(
            6666,
            new class implements Connection_Socket_IReceiver {

                public function onReceive($tsReceived, $message, $fromAddress, $fromPort) {

                }
            }
        );
    }

}