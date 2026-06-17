<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * UDPConnected на отправку быстрее на 16% - я проверял это на hot-cold benchmark
 */
class Connection_SocketUDPConnected extends Connection_SocketUDP {

    // @todo override read() to socket_recv()

    public function __construct($host, $port) {
        parent::__construct();

        // @todo verify host & port
        $this->_host = $host;
        $this->_port = $port;
    }

    public function connect() {
        if (socket_connect($this->_socket, $this->_host, $this->_port) == false) {
            throw new Connection_Exception("Cannot UDP connect to $this->_host:$this->_port");
        }
    }

    public function write($message, $messageSize) {
        if (socket_write(
            $this->_socket,
            $message,
            $messageSize,
        ) != $messageSize) {
            # debug:start
            Cli::Print(__CLASS__.": {$this->_host}:{$this->_port} write error: ".socket_strerror(socket_last_error($this->_socket)));
            # debug:end

            // reconnect
            $this->connect();

            // повторная отправка
            return socket_write(
                $this->_socket,
                $message,
                $messageSize,
            ) == $messageSize;
        }

        // я отправил успешно
        return true;
    }

    private $_host;
    private $_port;

}