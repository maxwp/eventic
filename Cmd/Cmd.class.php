<?php
class Cmd extends Pattern_ASingleton {

    // @todo сократить cmd & data до 0/1

    public static function InitWorkers() {
        Cron::Get()->add(
            Cmd_Worker::class,
        );
    }

    public function sendCommand($serverIP, $cmdClass, $argumentArray = []) {
        $message = json_encode([
            'cmd' => $cmdClass,
            'data' => $argumentArray,
        ]);

        $length = strlen($message);
        if ($length > Cmd::CMD_MAX_LENGTH) {
            throw new Exception('UDP-message too long');
        }

        $result = $this->_socketUDP->writeTo(
            $message,
            $length,
            $serverIP,
            Cmd::CMD_PORT
        );

        # debug:start
        Cli::Print_n("cmd $cmdClass to $serverIP result=$result");
        # debug:end
    }

    public function sendCommandSupervisor($serverIP, $superID, $superClass, $argumentArray = [], $configArray = [], $ttl = 300) {
        $this->sendCommand( // supervisor
            $serverIP,
            Cmd_SuperVisor::class,
            [
                'id' => $superID,
                'cn' => $superClass,
                'aa' => $argumentArray,
                'ca' => $configArray,
                'ttl' => $ttl,
            ],
        );
    }

    public function __construct() {
        $this->_socketUDP = new Connection_SocketUDP();
        $this->_socketUDP->setBufferSizeWrite(5 * 1024 * 1024);
        $this->_socketUDP->setNonBlocking();
    }

    private Connection_SocketUDP $_socketUDP;
    public const CMD_PORT = 5000;
    public const CMD_MAX_LENGTH = 1500; // max UDP MTU

}