<?php
abstract class StreamLoop_HTTP_Abstract extends StreamLoop_TCP_Abstract {

    abstract protected function _beforeConnect();
    abstract protected function _onReceive($tsSelect, $statusCode, $statusMessage, $headerArray, $body);
    abstract protected function _onError($tsSelect, $errorCode, $errorMessage);
    abstract protected function _onReady($tsSelect); // @todo переделать на FSM Events?

    public function write($method, $path, $body, $headerArray, $timeoutTo) {
        if ($this->_state == StreamLoop_HTTP_Const::STATE_READY) {
            $request = $method . ' ' . $path . " HTTP/1.1\r\nHost: ".$this->getDestinationHost()."\r\nConnection: keep-alive\r\n" . implode("\r\n", $headerArray)."\r\n";

            // @todo упростить
            if ($body != '') { // чаще body есть
                $request .= 'Content-Length: ' . strlen($body) . "\r\n\r\n" . $body;
            } else {
                $request .= "\r\n";
            }

            if (fwrite($this->stream, $request)) { // это не совсем верная проверка, но для коротких payload пойдет
                // timeout на запрос есть всегда, по дефолту это 10 сек (см код выше)
                $this->_state = StreamLoop_HTTP_Const::STATE_WAIT_FOR_RESPONSE_HEADERS; // new request sent

                $this->_loop->updateHandlerFlags($this, true, false); // request sent -> waiting for headers
                $this->_loop->updateStreamTimeout($this->streamID, $timeoutTo); // request sent -> waiting for headers
            } else {
                $this->throwError( // closed by server / reset by peer
                    microtime(true), // tsSelect
                    StreamLoop_HTTP_Const::ERROR_CLOSED_BY_SERVER, // http code 0
                    'Connection closed by server', // ясное сообщение
                );
            }
        } else {
            throw new StreamLoop_Exception(__CLASS__." already under active request");
        }
    }

    public function connect() {
        // перед connect надо вызвать setupConnection чтобы он поправил все параметры соединения
        $this->_beforeConnect();

        $this->_createAndConnectTCP();

        // state меняем ТОЛЬКО createAndConnect, потому что он может выкинуть exeption и мне нельзя остаться в state connecting
        $this->_state = StreamLoop_HTTP_Const::STATE_CONNECTING; // in 1st connect
    }

    private function _completeConnect($tsSelect) {
        // Переводим соединение в READY и снимаем служебные
        // флаги/таймаут подключения
        $this->_reset();

        $this->_onReady($tsSelect);
    }

    public function disconnect() {
        if ($this->streamID) { // этот if - защита от double disconnect: вдруг сработает read, а потом write и в нем я disconnected
            // reset сам сделает updateHandler в ноль
            $this->_reset(StreamLoop_HTTP_Const::STATE_DISCONNECTED); // reset in disconnect
            $this->_loop->unregisterHandler($this); // важно: disconnect снимает регистрацию handler'a
        }

        // бывают ситуации когда throwError два раза подряд и тогда disconnect два раза подряд
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->streamID = 0;
        $this->stream = null;
    }

    public function readyRead($tsSelect) {
        // if-tree optimization
        if ($this->_state == StreamLoop_HTTP_Const::STATE_WAIT_FOR_RESPONSE_HEADERS) {
            // drain read headers
            $readIndex = 0;
            do {
                $readIndex++;
                $line = fgets($this->stream, 4096); // я читаю через fgetS и врядли будет строка больше 4Kb

                $this->_buffer .= $line;

                // такая строка = конец блока заголовков
                if ($line == "\r\n" || $line == "\n") {
                    // разбираем заголовки в assoc массив
                    // @todo возможно можно сразу накапливать headerArray вместо buffer;
                    //       то есть если я жду заголовки - то сразу писать их в header и так пока не встречу rn
                    $lineArray = explode("\r\n", $this->_buffer);

                    // @todo можно ускорить за счет [...]
                    // Формат статус-строки: HTTP/1.1 200 OK
                    $statusParts = explode(' ', $lineArray[0], 3);
                    // $statusParts[0] = "HTTP/1.1"
                    // $statusParts[1] = "200"
                    // $statusParts[2] = "OK"

                    $this->_statusCode = (int) ($statusParts[1] ?? 0);
                    $this->_statusMessage = $statusParts[2] ?? '';

                    // @todo сохранить content-length отдельно если он есть
                    $this->_headerArray = [];
                    foreach ($lineArray as $line) {
                        // разделяем заголовок на имя и значение
                        $x = explode(':', $line, 2);
                        if (isset($x[1])) {
                            // уже lowercase key
                            $this->_headerArray[strtolower(trim($x[0]))] = trim($x[1]);
                        }
                    }

                    $this->_state = StreamLoop_HTTP_Const::STATE_WAIT_FOR_RESPONSE_BODY; // headers readed -> waiting for body

                    $this->_buffer = '';

                    return;
                } elseif (!$line) {
                    // Если до этого уже прочитали строки headers,
                    // значит просто закончили drain.
                    if ($readIndex == 1) {
                        $this->_checkEOF($tsSelect);
                    }

                    break;
                }
            } while (true);
        } elseif ($this->_state == StreamLoop_HTTP_Const::STATE_WAIT_FOR_RESPONSE_BODY) {
            // @todo как смержить wait for headers & body в кучу? Все равно у меня http 1.1
            $headerArray = $this->_headerArray; // @todo отдельная переменная вместо массива

            if (isset($headerArray['content-length'])) {
                $bodyLength = (int) $headerArray['content-length'];

                // Работаем с локальным буфером весь readyRead.
                $buffer = $this->_buffer;
                $this->_buffer = '';

                $bufferLength = strlen($buffer);

                for ($drainIndex = 1; $drainIndex <= 10; $drainIndex++) {
                    // Вдруг тело уже было полностью накоплено.
                    // Также обрабатывает Content-Length: 0.
                    if ($bufferLength >= $bodyLength) {
                        break;
                    }

                    $chunk = fread($this->stream, 16384);

                    if ($chunk === false) {
                        $this->throwError(
                            $tsSelect,
                            StreamLoop_HTTP_Const::ERROR_CLOSED_BY_SERVER,
                            'fread failed',
                        );
                        return;
                    }

                    $chunkLength = strlen($chunk);

                    if ($chunkLength == 0) {
                        // EOF проверяем только на первом fread после stream_select
                        if ($drainIndex == 1) {
                            if ($this->_checkEOF($tsSelect)) {
                                return;
                            }
                        }

                        // На втором+ чтении это обычный конец drain.
                        break;
                    }

                    $buffer .= $chunk;
                    $bufferLength += $chunkLength;

                    // Ответ собран — больше fread не нужен.
                    if ($bufferLength >= $bodyLength) {
                        break;
                    }

                    // Для обычного TCP short read обычно означает,
                    // что receive queue сейчас опустела.
                    if ($chunkLength < 16384) {
                        if (!$this->_crypto) {
                            break;
                        }
                    }

                    // Для SSL после short read продолжаем:
                    // следующая TLS record уже может быть готова.
                }

                // Тело пока пришло не полностью.
                if ($bufferLength < $bodyLength) {
                    $this->_buffer = $buffer;
                    return;
                }

                // В обычном случае копии здесь не будет.
                if ($bufferLength == $bodyLength) {
                    $body = $buffer;
                } else {
                    $body = substr($buffer, 0, $bodyLength);
                }

                $statusCode = $this->_statusCode;
                $statusMessage = $this->_statusMessage;

                // Сначала READY, затем пользовательский callback.
                $this->_reset();

                $this->_onReceive(
                    $tsSelect,
                    $statusCode,
                    $statusMessage,
                    $headerArray,
                    $body
                );

            } elseif (isset($headerArray['transfer-encoding']) && strpos($headerArray['transfer-encoding'], 'chunked') !== false) {
                // ---- chunked ----
                // тут не настолько high-performance как в non-chunked

                // докачиваем сырой поток chunked-данных в _buffer
                $drainIndex = 10;
                do {
                    $chunk = fread($this->stream, 16384);
                    if ($chunk === '') {
                        break;
                    } elseif ($chunk === false) {
                        $this->_checkEOF($tsSelect); // read body - false chunk
                        break;
                    }
                    $this->_buffer .= $chunk;
                } while (--$drainIndex);

                // пытаемся распарсить то, что уже есть в _buffer
                do {
                    // 1) если не знаем размер текущего чанка — читаем строку размера
                    if ($this->_chunkExpected === null) {
                        $pos = strpos($this->_buffer, "\r\n");
                        if ($pos === false) {
                            // нет целой строки размера
                            break;
                        }

                        $line = substr($this->_buffer, 0, $pos);
                        $this->_buffer = (string) substr($this->_buffer, $pos + 2);

                        // отрезаем chunk-ext после ';'
                        $sc = strpos($line, ';');
                        if ($sc !== false) {
                            $line = substr($line, 0, $sc);
                        }

                        $line = trim($line);
                        if ($line === '') {
                            // иногда бывает лишний CRLF — просто пропускаем
                            continue;
                        }

                        // hex -> int
                        $size = hexdec($line);
                        $this->_chunkExpected = $size;

                        // нулевой чанк = конец. дальше могут быть трейлеры + пустая строка
                        if ($size === 0) {
                            // ждём конец трейлеров: \r\n\r\n (или просто \r\n если трейлеров нет)
                            $end = strpos($this->_buffer, "\r\n\r\n");
                            if ($end !== false) {
                                $this->_buffer = (string) substr($this->_buffer, $end + 4);
                            } else {
                                // самый частый кейс: сразу \r\n
                                if (substr($this->_buffer, 0, 2) === "\r\n") {
                                    $this->_buffer = (string) substr($this->_buffer, 2);
                                } else {
                                    // трейлеры ещё не пришли полностью
                                    break;
                                }
                            }

                            // готово — отдаём
                            $statusCode = $this->_statusCode;
                            $statusMessage = $this->_statusMessage;
                            $body = $this->_bodyDecoded;

                            $this->_reset(); // chunked parser

                            $this->_onReceive(
                                $tsSelect,
                                $statusCode,
                                $statusMessage,
                                $headerArray,
                                $body
                            );

                            break; // всё, запрос завершён
                        }
                    }

                    // 2) у нас есть ожидаемый размер чанка > 0: ждём данные + \r\n
                    $need = $this->_chunkExpected + 2; // data + CRLF
                    if (strlen($this->_buffer) < $need) {
                        break; // не хватает данных
                    }

                    $data = substr($this->_buffer, 0, $this->_chunkExpected);
                    $crlf = substr($this->_buffer, $this->_chunkExpected, 2);

                    // “по тупому”: если не CRLF — можно либо падать, либо пытаться жить
                    if ($crlf !== "\r\n") {
                        throw new StreamLoop_Exception("Bad chunked encoding (missing CRLF after chunk data)");
                    }

                    $this->_bodyDecoded .= $data;
                    $this->_buffer = substr($this->_buffer, $need);

                    // ждём следующий chunk-size
                    $this->_chunkExpected = null;
                } while (true);
            } else {
                throw new StreamLoop_Exception('Unsupported encoding');
            }
        } elseif ($this->_state == StreamLoop_HTTP_Const::STATE_HANDSHAKING) {
            $this->_processHandshake($tsSelect);
        }
    }

    public function readyWrite($tsSelect) {
        // if-tree optimization
        if ($this->_state == StreamLoop_HTTP_Const::STATE_CONNECTING) {
            // TCP-соединение установлено
            // коннект установился, я готов к записи
            if ($this->_crypto) {
                $host = $this->getDestinationHost(); // to locals: 2+
                stream_context_set_option($this->stream, [
                    'ssl' => [
                        'SNI_enabled' => true,
                        'SNI_server_name' => $host,
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'peer_name' => $host,
                        'allow_self_signed' => true,
                    ],
                ]);

                $this->_state = StreamLoop_HTTP_Const::STATE_HANDSHAKING; // handshake starting

                // NB! НЕ ставим write, потому что во время handshaking всегда идет write и просто зайобка
                $this->_loop->updateHandlerFlags($this, true, false); // connected done -> waiting for SSL handshake

                // и сразу же проверяем его, вдруг подключился
                $this->_processHandshake($tsSelect);
            } else {
                // HTTP без TLS: после TCP-connect сразу готовы
                $this->_completeConnect($tsSelect);
            }
        } elseif ($this->_state == StreamLoop_HTTP_Const::STATE_HANDSHAKING) {
            $this->_processHandshake($tsSelect);
        }
    }

    public function readyTimeout($tsSelect) {
        // если прошел timeout - кидаем ошибку и отключаемся;
        // это касается любого типа timeout - request, connecting, handshaking.
        // потому что все равно соединению пизда

        // важно: readySelectTimeout не может вызваться если timeout не настал, поэтому никаких проверок на timeout'ы тут просто делать не надо.

        $this->throwError( // timeout 408
            $tsSelect,
            StreamLoop_HTTP_Const::ERROR_TIMEOUT,
        );
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

    private function _checkEOF($tsSelect) { // @todo protected
        if (feof($this->stream)) {
            // затем кидаем ошибку
            $this->throwError( // EOF
                $tsSelect,
                StreamLoop_HTTP_Const::ERROR_EOF, // http code 0
                'Connection closed by server', // ясное сообщение
            );

            return true;
        } else {
            return false;
        }
    }

    private function _processHandshake($tsSelect) {
        $return = stream_socket_enable_crypto(
            $this->stream,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if ($return === true) {
            $this->_completeConnect($tsSelect);

            return;
        } elseif ($return === false) {
            $this->throwError( // handshake
                $tsSelect,
                StreamLoop_HTTP_Const::ERROR_HANDSHAKE,
                'Failed to setup SSL'
            );

            return; // чтобы не лупиться в eof
        }

        // $return === 0:
        // TLS-handshake пока не завершён
        $this->_checkEOF($tsSelect); // in _processHandshake
    }

    private function _reset($state = StreamLoop_HTTP_Const::STATE_READY) {
        // чистка всего перед новым запросом или отключением
        $this->_buffer = '';
        $this->_statusCode = 0;
        $this->_statusMessage = '';
        $this->_headerArray = [];

        // reset chunked state
        $this->_chunkExpected = null;
        $this->_bodyDecoded = '';

        // обнуляем состояние в ready и стираем все таймеры
        $this->_state = $state; // in reset

        // важно: reset снимает флаги чтобы получить тишину, в тч таймаут
        // для http после reset ничего не жду, поэтому нужно уведомить StreamLoop что ничего не надо делать,
        // @todo это почти unregisterHandler - может заменить на него?
        $this->_loop->resetHandler($this);
    }

    public function getState() {
        return $this->_state;
    }

    private $_buffer = ''; // string
    private $_headerArray = []; // array
    private $_statusCode = 0; // int
    private $_statusMessage = ''; // string
    private $_state = 0; // int, 0 is STATE_DISCONNECTED, by default disconnected
    private $_chunkExpected = null; // int|null, сколько байт данных ждем в текущем чанке
    private $_bodyDecoded = ''; // сюда складываем уже декодированное тело (без chunk-обвязки)

}