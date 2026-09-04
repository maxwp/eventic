<?php
/**
 * Важное отличие StreamLoop_WebSocket от Connection_WebSocket:
 * SL_WS вызывает selectTimeout только ради websocket-layer frame-ping-pong, он не вызывает его
 * ради пустых callback.
 * Если нужны пустые callback - то надо добавлять timer внутрь StreamLoop.
 * Такой подход делает меньше вызовов selectTimeout и позволяет держать сильно больше WebSocket-handler-ов внутри одного StreamLoop,
 * но timer не будет синхронизирован с last event от websocket. Хотя он и в C_WS может быть не синхронизирован из-за app-layer & iframe-layer ping-pong.
 */
abstract class StreamLoop_WebSocket_Abstract extends StreamLoop_TCP_Abstract {

    // @todo как слепить в кучу websocket over https?
    // @todo сначала надо придумать как сделать StateMachine, чтобы я мог помещать команду с событиями onXXX,
    //       и затем handshake и switching protocol снанут этими командами

    abstract protected function _beforeConnect();
    abstract protected function _onReceive($tsSelect, $payload, $opcode);
    abstract protected function _onError($tsSelect, $errorCode, $errorMessage);
    abstract protected function _onReady($tsSelect);

    protected function _updateUpgradeParams($path, $headerArray = [], $writeArray = []) {
        $this->_path = $path;
        $this->_writeArray = $writeArray;
        $this->_headerArray = $headerArray;
    }

    public function connect() {
        // перед connect я вызываю _beforeConnect() чтобы он задал параметры соединения если надо
        $this->_beforeConnect();

        $this->_buffer = '';
        $this->_bufferLength = 0;
        $this->_bufferOffset = 0;

        $this->_createAndConnectTCP();

        // state меняем после createAndConnectTCP, потому он может кинуть exception и всему пизда, а state будет connecting
        $this->_state = StreamLoop_WebSocket_Const::STATE_CONNECTING;

        // на каждый connect новый период пинга
        $this->_pingPeriod = 10.01 + rand() % 5;
    }

    public function disconnect() {
        // дисконнект закрывает снимает регистрацию handler'a и закрывает stream.
        // это приводит к тому, что SL временно забывает про handler и не ебет его
        $this->_loop->unregisterHandler($this); // on disconnect

        $this->streamID = 0;
        $this->stream = null; // вместо fclose

        $this->_buffer = '';
        $this->_bufferLength = 0;
        $this->_bufferOffset = 0;
        $this->_fragmentOpcode = null;
        $this->_fragmentPayload = '';

        $this->_state = StreamLoop_WebSocket_Const::STATE_DISCONNECTED;
    }

    public function readyRead($tsSelect) {
        // if-tree optimization
        if ($this->_state == StreamLoop_WebSocket_Const::STATE_READY) {
            // счетчик чтений
            $drainCounter = $this->_readFrameDrain;

            // to locals (оправдано)
            $buffer = $this->_buffer;
            $bufLen = $this->_bufferLength;
            $offset = $this->_bufferOffset;
            $readFrameLength = $this->_readFrameLength;

            // один общий try-catch экономит до 11% cpu time если вызовов onReceive несколько
            try {
                // dynamic drain: если после вычитки большого пакета fread() он считался ровно впритык - то вызываем
                // чтение еще раз и так до drainLimit.
                // Надо стараться делать меньше fread (syscall overhead), но если все-таки данных много - то лучше читать
                // еще раз, чтобы не ждать нового круга stream_select(). Но опять-таки, это сильно зависит от количество
                // потоков внутри всего StreamLoop и насколько я могу затупить на одном handler'e.
                do {
                    // я не использую stream to locals, потому что в 95% случаев чтение одно
                    // и у меня есть проверка $length < $readFrameLength - то есть я выйду сразу же и не буду
                    // пытаться сделать второй fread
                    $data = fread($this->stream, $readFrameLength);
                    $length = strlen($data);

                    # debug:start
                    if ($length > 1500) {
                        Cli::Print_n(__CLASS__ . ": received $length bytes");
                    }
                    # debug:end

                    // чаще всего будет срабатывать length > 0
                    if ($length > 0) {
                        $buffer .= $data;
                        $bufLen += $length;

                        // минимальный заголовок - 2 байта
                        while ($bufLen - $offset >= 2) {
                            $secondByte = ord($buffer[$offset + 1]);
                            $lenFlag = $secondByte & 0x7F;

                            if ($lenFlag == 126) { // чаще всего срабатывает 126
                                $maskOffset = 4; // 2 + 2 bytes ext len
                                if ($bufLen - $offset < $maskOffset) {
                                    break;
                                }
                                $payloadLength = (ord($buffer[$offset + 2]) << 8) | ord($buffer[$offset + 3]);
                            } elseif ($lenFlag == 127) { // 127 почти никогда не срабатывает
                                $maskOffset = 10; // 2 + 8 bytes ext len
                                if ($bufLen - $offset < $maskOffset) {
                                    break;
                                }
                                // я проверял, тут unpack(J) это правильно и это быстрее чем ord
                                $payloadLength = unpack('J', $buffer, $offset + 2)[1];
                            } else {
                                $maskOffset = 2;
                                $payloadLength = $lenFlag; // 0..125
                            }

                            // разные ветки на if masked or not, причем обычно NOT masked:
                            if ($secondByte < 128) {
                                // not masked
                                $frameLength = $maskOffset + $payloadLength;
                                if ($bufLen - $offset < $frameLength) {
                                    break;
                                }

                                $payload = substr(
                                    $buffer,
                                    $offset + $maskOffset,
                                    $payloadLength
                                );
                            } else {
                                // masked
                                $frameLength = $maskOffset + $payloadLength + 4;
                                if ($bufLen - $offset < $frameLength) {
                                    break;
                                }

                                $payload = substr(
                                    $buffer,
                                    $offset + $maskOffset + 4,
                                    $payloadLength
                                );

                                $maskKey = substr($buffer, $offset + $maskOffset, 4);

                                // повторяем маску до длины payload и XOR'им строкой
                                //$mask = str_repeat($maskKey, ($payloadLength + 3) >> 2);
                                //$payload ^= substr($mask, 0, $payloadLength);
                                // XOR возьмет только strlen($payload) байт из правого операнда автоматически
                                $payload ^= str_repeat($maskKey, ($payloadLength >> 2) + 1);
                            }

                            // обработка опкодов
                            $firstByte = ord($buffer[$offset]);
                            $fin = ($firstByte & 0x80) !== 0;
                            $opcode = $firstByte & 0x0F;

                            if ($opcode == 0x1) {
                                # debug:start
                                Cli::Print_n(__CLASS__.': received opcode='.$opcode.' '.$payload);
                                # debug:end

                                if ($fin) {
                                    $this->_active = true; // спец-костыль: если данные идут - похер на iframe ping-pong
                                    $this->_onReceive($tsSelect, $payload, $opcode);
                                } else {
                                    $this->_fragmentOpcode = $opcode;
                                    $this->_fragmentPayload = $payload;
                                }
                            } elseif ($opcode == 0x2) {
                                # debug:start
                                Cli::Print_n(__CLASS__.': received opcode='.$opcode.' '.$payload);
                                # debug:end

                                if ($fin) {
                                    $this->_active = true; // спец-костыль: если данные идут - похер на iframe ping-pong
                                    $this->_onReceive($tsSelect, $payload, $opcode);
                                } else {
                                    $this->_fragmentOpcode = $opcode;
                                    $this->_fragmentPayload = $payload;
                                }
                            } elseif ($opcode == 0x0) { // continuation
                                # debug:start
                                Cli::Print_n(__CLASS__.': received opcode='.$opcode.' '.$payload);
                                # debug:end

                                if ($this->_fragmentOpcode === null) {
                                    throw new StreamLoop_Exception(StreamLoop_WebSocket_Const::ERROR_UNKNOWN_OPCODE);
                                }

                                $this->_fragmentPayload .= $payload;

                                if ($fin) {
                                    $this->_active = true; // спец-костыль: если данные идут - похер на iframe ping-pong
                                    $this->_onReceive($tsSelect, $this->_fragmentPayload, $this->_fragmentOpcode);
                                    $this->_fragmentOpcode = null;
                                    $this->_fragmentPayload = '';
                                }
                            } elseif ($opcode == 0xA) { // FRAME PONG
                                # debug:start
                                Cli::Print_n(__CLASS__ . ": received frame-pong $payload");
                                # debug:end

                                // считаем что соединение активно и с ним все ок
                                $this->_active = true;
                            } elseif ($opcode == 0x9) { // FRAME PING
                                # debug:start
                                Cli::Print_n(__CLASS__ . ": received frame-ping $payload");
                                # debug:end

                                $this->write($payload, 0xA); // pong

                                # debug:start
                                Cli::Print_n(__CLASS__ . ": sent frame-pong $payload");
                                # debug:end
                            } elseif ($opcode == 0x8) { // FRAME CLOSED
                                throw new StreamLoop_Exception(StreamLoop_WebSocket_Const::ERROR_FRAME_CLOSED);
                            } else {
                                throw new StreamLoop_Exception(StreamLoop_WebSocket_Const::ERROR_UNKNOWN_OPCODE);
                            }

                            // Сдвигаем указатель на следующий фрейм
                            $offset += $frameLength;
                        }

                        // если всё съели - сбрасываем буфер полностью (самый дешевый случай)
                        if ($offset == $bufLen) {
                            $buffer = '';
                            $bufLen = 0;
                            $offset = 0;
                        } elseif ($offset > 65536) {
                            // редкое "сжатие" буфера: чаще всего фреймы летят целые, поэтому обрабатывается предыдущее условие
                            $buffer = substr($buffer, $offset);
                            $bufLen -= $offset;
                            $offset = 0;
                        }

                        if ($length < $readFrameLength) {
                            // Если fread вернул меньше, чем запрошено - дальше не дренируем
                            break;
                        }
                    } elseif ($data === '') {
                        // на втором месте по частоте срабатывания - пустая строка, я упрусь в drain limit
                        // Если fread вернул пустую строку, проверяем, достигнут ли EOF
                        // upd: она запускается только если drain вернул пустоту, что бывает очень редко, так как есть проверка на length
                        if ($this->_checkEOF($tsSelect)) { // in drain read
                            // EOF: connection closed by remote host
                            return; // на выход, чтобы дальше ничего не проверять, ошибка уже выкинута
                        }
                        break;
                    } elseif ($data === false) {
                        // и в редких случаях ошибка drain
                        // в неблокирующем режиме если данных нет - то будет string ''
                        // а если false - то это ошибка чтения
                        // например, PHP Warning: fread(): SSL: Connection reset by peer
                        //$errorString = error_get_last()['message'];
                        throw new StreamLoop_Exception(StreamLoop_WebSocket_Const::ERROR_RESET_BY_PEER);
                    }
                } while (--$drainCounter);

                $this->_buffer = $buffer;
                $this->_bufferLength = $bufLen;
                $this->_bufferOffset = $offset;

            } catch (StreamLoop_Exception $se) {
                $this->throwError($tsSelect, $se->getMessage());
                return;
            } catch (Exception $ue) {
                // тут вылетаем, но надо сделать disconnect
                $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_USER, $ue->getMessage());
                return;
            } catch (Throwable $te) {
                // более жесткая ошибка
                $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_USER, $te->getMessage());
                return;
            }

        } elseif ($this->_state == StreamLoop_WebSocket_Const::STATE_HANDSHAKING) {
            $this->_processHandshake($tsSelect);
        } elseif ($this->_state == StreamLoop_WebSocket_Const::STATE_UPGRADING) {
            $this->_checkUpgrade($tsSelect);
        }
    }

    public function readyWrite($tsSelect) {
        switch ($this->_state) {
            case StreamLoop_WebSocket_Const::STATE_CONNECTING:
                if ($this->_crypto) {
                    // коннект установился, я готов к записи
                    $host = $this->getDestinationHost(); // to locals: 2+
                    stream_context_set_option($this->stream, [
                        'ssl' => [
                            'SNI_enabled' => true,
                            'SNI_server_name' => $host,
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'peer_name' => $host, // так надо делать если я перебираю IPшники хоста
                            'allow_self_signed' => true,
                        ],
                    ]);

                    $this->_state = StreamLoop_WebSocket_Const::STATE_HANDSHAKING;

                    // NB! НЕ ставим write, потому что во время handshaking всегда идет write и просто зайобка CPU, я проверял
                    $this->_loop->updateHandlerFlags($this, true, false); // connecting done -> handshaking

                    $this->_processHandshake($tsSelect);
                } else {
                    // Обычный ws:// — TLS-handshake не нужен
                    $this->_startUpgrade($tsSelect);
                }
                return;
            case StreamLoop_WebSocket_Const::STATE_HANDSHAKING:
                $this->_processHandshake($tsSelect);
                return;
            case StreamLoop_WebSocket_Const::STATE_UPGRADING:
                $this->_checkUpgrade($tsSelect);
                return;
        }
    }

    public function readyTimeout($tsSelect) {
        /*
         * idle ping logic: @todo какая-то мутная логика ping-pong
         * - изначально после подключения ставится первый интервал в 10-15 sec (rand) updateHandlerTimeout(),
         *   а флаг active = 1 (bool)
         * - при каждом pong ставится active =1 и продлевается updateHandlerTimeout() на 10-15 sec rand
         * - когда запускается readyTimeout():
         *   если флаг active = 1, то я ставлю active = 0 и вбрасываю ping и продлеваю updateHandlerTimeout на 10-15 sec rand.
         *   если флаг active = 0 - я выхожу по ошибке что мол нет iframe pong'a
         *
         * Таким образом интервалы длинные, нет бесконечных проверок в конце каждого read.
         * плюс я отказываюсь от переменных ping intrval чтобы их не запрашивать все время.
         */

        if ($this->_state == StreamLoop_WebSocket_Const::STATE_READY) {
            // в состоянии READY может прилететь timeout только ради ping
            if ($this->_active) {
                $this->_active = false;
                $this->write('', 9); // ping

                # debug:start
                Cli::Print_n(__CLASS__.": sent frame-ping");
                # debug:end
            } else {
                $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_NO_PONG);
                return;
            }

            $this->_loop->updateStreamTimeout($this->streamID, $tsSelect + $this->_pingPeriod);
        } else {
            // во всех остальных случаях я нарвался на проблему что за timeout я не смог установить соединение и сделать handshake/upgrade
            // (то есть не успел аж до ready)
            $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_TIMEOUT);
        }
    }

    private function _checkUpgrade($tsSelect) {
        // @todo тут построчный fgets, нужен drain если я хочу быстрый upgrade
        $line = fgets($this->stream, 4096);

        if ($line) {
            $this->_buffer .= $line; // to locals не нужен, меньше 4х использований
            // пустая строка - конец блока заголовков
            if ($line == "\r\n" || $line == "\n") {
                if (explode(' ', $this->_buffer, 3)[1] == '101') {
                    // вот тут опционально ебашим writeArray если он передан, за один fwrite syscall
                    // @todo write можно вызвать в onReady и тогда его не передавать в SL_WS вообще
                    if ($this->_writeArray) {
                        $this->writeMulti($this->_writeArray);
                    }

                    $this->_state = StreamLoop_WebSocket_Const::STATE_READY;

                    // таймер двигаем вперед на 10-15 сек
                    $this->_loop->updateStreamTimeout($this->streamID, $tsSelect + $this->_pingPeriod); // upgrading done -> ready with iframe-layer ping-pong

                    $this->_buffer = '';
                    $this->_bufferLength = 0;
                    $this->_bufferOffset = 0;

                    // считаем соединение активно и с ни все ок
                    $this->_active = true;

                    $this->_onReady($tsSelect);
                } else {
                    # debug:start
                    Cli::Print_n(__CLASS__.': invalid upgrade response: '.$this->_buffer);
                    # debug:end

                    $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_UPGRADE);
                    return;
                }
            }
        } elseif ($line === false) {
            $this->_checkEOF($tsSelect); // in upgrade
        }
    }

    private function _checkEOF($tsSelect) {
        if (feof($this->stream)) {
            $this->throwError(
                $tsSelect,
                StreamLoop_WebSocket_Const::ERROR_EOF,
                json_encode(stream_get_meta_data($this->stream)),
            );
            return true;
        } else {
            return false;
        }
    }

    /**
     * Disconnect + onError
     *
     * @param $tsSelect
     * @param $message
     * @param $errorMessage
     * @return void
     */
    public function throwError($tsSelect, $errorCode, $errorMessage = false) {
        $this->disconnect();
        $this->_onError($tsSelect, $errorCode, $errorMessage);
    }

    private function _processHandshake($tsSelect) {
        if ($this->_checkEOF($tsSelect)) { // in handshake
            return; // на выход потому что ошибка уже выкинута
        }

        $return = stream_socket_enable_crypto(
            $this->stream,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        // тут нужны ===, потому что если вернется int 0 - то надо пробовать еще раз
        if ($return === true) {
            $this->_startUpgrade($tsSelect);
        } elseif ($return === false) {
            $this->throwError($tsSelect, StreamLoop_WebSocket_Const::ERROR_HANDSHAKE);
        }
    }

    private function _startUpgrade($tsSelect) {
        fwrite(
            $this->stream,
            "GET {$this->_path} HTTP/1.1\r\nHost: ".$this->getDestinationHost()."\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: ".base64_encode(random_bytes(16))."\r\nSec-WebSocket-Version: 13\r\n"
            . ($this->_headerArray ? implode("\r\n", $this->_headerArray)."\r\n" : '')
            . "\r\n"
        );

        $this->_state = StreamLoop_WebSocket_Const::STATE_UPGRADING;
        $this->_loop->updateHandlerFlags($this, true, false); // handshaking done -> upgrading

        $this->_checkUpgrade($tsSelect);
    }

    public function write($data, $opcode = 1) {
        try {
            // @todo не хватает проверки на результат записи
            fwrite(
                $this->stream,
                $this->_encodeMessage($data, $opcode)
            );
        } catch (Throwable $te) {
            $this->throwError(
                microtime(true),
                StreamLoop_WebSocket_Const::ERROR_EOF,
                $te->getMessage()
            );
        }
    }

    public function writeMulti($dataArray, $opcode = 1) {
        // важно: метод не проверяет массив на пустоту, это надо контроллировать снаружи

        $s = '';
        foreach ($dataArray as $data) {
            $s .= $this->_encodeMessage($data, $opcode);
        }

        try {
            // @todo не хватает проверки на результат записи
            fwrite(
                $this->stream,
                $s
            );
        } catch (Throwable $te) {
            $this->throwError(
                microtime(true),
                StreamLoop_WebSocket_Const::ERROR_EOF,
                $te->getMessage()
            );
        }
    }

    private function _encodeMessage($data, $opcode = 1) {
        $length = strlen($data);

        if ($length <= 125) {
            return chr(0x80 | $opcode) . chr(0x80 | $length)."\x00\x00\x00\x00".$data;
        } elseif ($length <= 0xFFFF) {
            return chr(0x80 | $opcode) . $this->_chr126 . chr($length >> 8) . chr($length)."\x00\x00\x00\x00".$data;
        } else {
            // 64-bit length (network order)
            return chr(0x80 | $opcode). $this->_chr127 . pack('J', $length)."\x00\x00\x00\x00".$data;
        }
    }

    public function setReadFrame($length, $drain) {
        $length = (int) $length;
        $drain = (int) $drain;

        if ($length <= 0) {
            throw new StreamLoop_Exception("Length must be a positive integer");
        }
        if ($drain <= 0) {
            throw new StreamLoop_Exception("Drain must be a positive integer");
        }
        $this->_readFrameLength = $length;
        $this->_readFrameDrain = $drain;
    }

    public function getState() {
        return $this->_state;
    }

    public function __construct(StreamLoop $loop) {
        parent::__construct($loop);

        $this->_chr126 = chr(0x80 | 126);
        $this->_chr127 = chr(0x80 | 127);
    }

    private $_writeArray = [];
    /**
     * @var array<string>
     */
    private $_headerArray = [];
    private $_path = ''; // string
    private $_buffer = ''; // string
    private $_bufferLength = 0; // int
    private $_bufferOffset = 0; // cursor: сколько байт уже "съели" из _buffer
    private $_state = 0; // 0 is a stop, by default
    private $_active = false; // bool, см логику idle ping @todo rf naming
    private $_readFrameLength = 16384; // default
    private $_readFrameDrain = 1;
    private $_chr126, $_chr127;
    private $_pingPeriod = 0.0; // float
    private $_fragmentOpcode = null; // @todo -1
    private $_fragmentPayload = '';

}