<?php
class Cmd extends EE_AContentCli {

    public function process() {
        $socket = new Connection_SocketUDP();
        $socket->setBufferSizeRead(5 * 1024 * 1024); // 5 mb буфер чтобы не потерять ничего
        $socket->setTimeoutRead(30*60, 0); // выходить через 30 минут не было никаких данных вообще

        try {
            $socket->read(
                self::CMD_PORT,
                new Cmd_Receiver(),
                self::CMD_MAX_LENGTH // max UDP MTU
            );

            $socket->disconnect();
        } catch (Exception $e) {
            $this->print_n_exception($e);
        }
    }

    public const CMD_PORT = 5000;
    public const CMD_MAX_LENGTH = 1500; // max UDP MTU

}