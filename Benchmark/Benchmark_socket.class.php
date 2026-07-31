<?php
class Benchmark_socket implements Benchmark_Interface {

    public function process() {
        $this->_socket->write('a', 1);
    }

    public function __construct() {
        $this->_socket = new Connection_SocketUDPConnected('127.0.0.1', 6666);
        $this->_socket->connect();
    }

    private Connection_SocketUDPConnected $_socket;

}