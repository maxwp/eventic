<?php
abstract class StreamLoop_TCP_Abstract extends StreamLoop_Handler_Abstract {

    // @todo теоретически можно перейти на trait, но что это даст?

    protected function _createAndConnectTCP() {
        # debug:start
        Cli::Print_n(__CLASS__." connecting to {$this->_destinationHost} ip={$this->_destinationIP} port={$this->_destinationPort} bind={$this->_sourceIP}:{$this->_sourcePort} crypto={$this->_crypto}");
        # debug:end

        $stream = stream_socket_client(
            'tcp://'.($this->_destinationIP ?: $this->_destinationHost).':'.$this->_destinationPort,
            $errno,
            $errstr,
            0, // timeout = 0, чтобы мгновенно вернулось
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT,
            stream_context_create([
                'socket' => [ // супер важно: надо создавать контекст без ssl-опций!
                    'tcp_nodelay' => true,  // no Nagle algorithm
                    'bindto' => $this->_sourceIP.':'.$this->_sourcePort,
                ],
            ]),
        );

        if ($stream) {
            $this->stream = $stream;
            $this->streamID = (int) $stream;

            // сразу 10 sec на connect .. ready
            $this->_loop->registerHandler($this); // 1st register (for connecting)
            $this->_loop->updateHandlerFlags($this, false, true);
            $this->_loop->updateStreamTimeout($this->streamID, microtime(true) + 10);

            // Устанавливаем буфер до начала SSL
            $socket = new Connection_SocketStream($stream);
            $socket->setBufferSizeRead(2 * 1024 * 1024);
            $socket->setBufferSizeWrite(2 * 1024 * 1024);
            $socket->setKeepAlive();
            $socket->setQuickACK();
            $socket->setCORK();

            stream_set_blocking($stream, false);

            // отключаем буферизацию php
            stream_set_read_buffer($stream, 0);
            stream_set_write_buffer($stream, 0);
        } else {
            // критическая ошибка — завершаем
            throw new StreamLoop_Exception("TCP connect failed immediately: $errstr ($errno)");
        }
    }

    protected function _updateDestination($host, $port, $ip = false, $crypto = true) {
        if (Validator::CheckHost($host)) {
            if (Validator::CheckPort($port)) {
                $this->_destinationHost = $host;
                $this->_destinationPort = $port;
                $this->_destinationIP = $ip; // @todo ip пока не проверяется
                $this->_crypto = $crypto;

                return;
            }
        }

        throw new StreamLoop_Exception("Invalid destination $host:$port");
    }

    protected function _updateDestinationHost($host) {
        if (Validator::CheckHost($host)) {
            $this->_destinationHost = $host;
        } else {
            throw new StreamLoop_Exception("Invalid hostname $host");
        }
    }

    protected function _updateDestinationIP($ip) {
        if ($ip) {
            if (Validator::CheckIP($ip)) {
                $this->_destinationIP = $ip;
            } else {
                throw new StreamLoop_Exception("Invalid destination IP $ip");
            }
        } else {
            $this->_destinationIP = false;
        }
    }

    protected function _updateDestinationPort($port) {
        if ($port) {
            if (Validator::CheckPort($port)) {
                $this->_destinationPort = $port;
            } else {
                throw new StreamLoop_Exception("Invalid destination port $port");
            }
        } else {
            $this->_destinationPort = 0;
        }
    }

    protected function _updateSourceIP($ip) {
        if ($ip) {
            if (Validator::CheckIP($ip)) {
                $this->_sourceIP = $ip;
            } else {
                throw new StreamLoop_Exception("Invalid source IP $ip");
            }
        } else {
            $this->_sourceIP = '0.0.0.0';
        }
    }

    protected function _updateSourcePort($port = 0) {
        if ($port) {
            if (Validator::CheckPort($port)) {
                $this->_sourcePort = $port;
            } else {
                throw new StreamLoop_Exception("Invalid source port $port");
            }
        } else {
            $this->_sourcePort = 0;
        }
    }

    public function getDestinationHost() {
        return $this->_destinationHost;
    }

    public function getDestinationIP() {
        return $this->_destinationIP;
    }

    public function getDestinationPort() {
        return $this->_destinationPort;
    }

    public function getSourceIP() {
        return $this->_sourceIP;

    }

    public function getSourcePort() {
        return $this->_sourcePort;
    }

    private $_destinationHost; // string
    private $_destinationPort; // int
    private $_destinationIP = false; // string
    private $_sourceIP = '0.0.0.0'; // string, any ip by default
    private $_sourcePort = 0; // int, any port by default
    protected $_crypto = true; // bool

}