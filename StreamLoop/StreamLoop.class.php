<?php
class StreamLoop {

    // NB: некоторые проверки делаются только в debug-mode, так сделано специально чтобы hot-path получится
    // extremely fucking fast

    public function __construct() {
        // сразу создаем внутренние handler-ы: GC & sorting
        // я могу лупить сколько угодно таймеров, потому что они меняют только timerArray и handlerArray,
        // а в hot path run нет никаких пересчетов, кроме foreach timeoutArray,
        // то есть два handler'a добавляют только 2 итерации и 2 if'a
        new StreamLoop_GC($this);
        new StreamLoop_Sort($this);
    }

    public function run() {
        // первый раз меряем tsSelect до круга
        $tsSelect = microtime(true);

        // фиксированно null всегда
        $e = null;

        // event loop из сами залуп
        do {
            if ($this->_rwFlag) {
                $r = $this->_selectReadArray;
                $w = $this->_selectWriteArray;

                $timeoutUS = ($this->_selectTimeoutToMin - $tsSelect) * 1_000_000;
                if ($timeoutUS < 0) { // эта проверка нужна только потому, что нельзя отправлять negative timeoutUS
                    $timeoutUS = 0; // если <= 0 - значит какой-то таймаут уже близко, но все равно будет быстрая проверка флагов rw
                }

                // stream_select() accepts values > 1000000 for microseconds and behaves correctly by normalizing internally.
                stream_select($r, $w, $e, 0, $timeoutUS);
            } else {
                // сюда пы попадаем если есть тайм-аут, но нет сокетов rw
                // это не редкая история: все сокеты могли отпасть, остался бы только race, который ждал бы когда включать

                // я специально обнуляю все до sleep:
                // sleep отдаст контекст и я не хочу тратиться на очистку переменных после пробуждения,
                // лучше сразу обрабатывать логику
                $r = []; // тут нужен array из-за foreach
                $w = false;

                time_sleep_until($this->_selectTimeoutToMin);
            }

            // меряем время select'a сразу же
            $tsSelect = microtime(true);

            // 1. в 99% случаев я имею один элемент в r/w, и нет смысла делать to locals,
            //    а остальные проценты распределы примерно также: и математически не выгодно делать to locals trick.
            //    @todo странно почему именно так?
            // 2. handlerArray to locals создает редкую проблему: если на readyRead я дропну handler, а потом на
            //    readyWrite попытаюсь шото сделать - нет элемента в массиве, а я не хочу обкладывать все isset-ами.
            //    поэтому отказавшись от to locals будет fatal - и это правильнее чем вызов из локального $handlerArray
            // 3. так как я не делаю проверку result === false - то всегда надо быть готовым что readyXXX может вызваться
            //    в случае result === false, и тогда будут холостые обработчики. Но это было 1 раз за год.
            //    и было выгодно закосить эту проверку на result.

            // тут if не нужен, потому что чаще всего есть именно r
            foreach ($r as $streamID => $stream) {
                $this->_handlerArray[$streamID]->readyRead($tsSelect);
            }

            // наличие if тут оправдано, потому что чаще массив пустой
            if ($w) {
                foreach ($w as $streamID => $stream) {
                    $this->_handlerArray[$streamID]->readyWrite($tsSelect);
                }
            }

            // этот if экономит 100 ns/call: если нет ближайшего таймаута - нахуй итерацию
            if ($tsSelect >= $this->_selectTimeoutToMin) {
                // массив _selectTimeoutToArray никогда не пустой и в нем всегда есть элемент 0 => бесконечность
                foreach ($this->_selectTimeoutToArray as $streamID => $timeoutTo) {
                    if ($tsSelect >= $timeoutTo) {
                        if (isset($this->_handlerArray[$streamID])) {
                            $this->_handlerArray[$streamID]->readyTimeout($tsSelect);
                        }
                    }
                }
            }

            // только в режиме debug проверяем что с handler-ами
            # debug:start
            if (!$this->_handlerArray) {
                throw new StreamLoop_Exception('no handlers');
            }
            # debug:end

        } while (true);
    }

    /**
     * Полное снятие handler'a
     *
     * @param StreamLoop_Handler_Abstract $handler
     * @return void
     * @throws StreamLoop_Exception
     */
    public function unregisterHandler(StreamLoop_Handler_Abstract $handler) {
        $streamID = $handler->streamID;

        # debug:start
        if (!$streamID) {
            throw new StreamLoop_Exception('Cannot unregister handler without streamID');
        }
        # debug:end

        unset(
            $this->_handlerArray[$streamID],
            $this->_selectReadArray[$streamID],
            $this->_selectWriteArray[$streamID],
            $this->_selectTimeoutToArray[$streamID],
            $this->_priorityArray[$streamID]
        );

        // так как я дропнул handler - то надо точно пересчитывать ближайший тайм-аут
        $this->_selectTimeoutToMin = min($this->_selectTimeoutToArray);

        if ($this->_selectReadArray) {
            $this->_rwFlag = true;
        } elseif ($this->_selectWriteArray) {
            $this->_rwFlag = true;
        } else {
            $this->_rwFlag = false;
        }
    }

    /**
     * Регистрация: обязательно должен быть timeoutTo > 0
     * Тяжелый метод, не использовать в hot path
     *
     * @param StreamLoop_Handler_Abstract $handler
     * @return void
     * @throws StreamLoop_Exception
     */
    public function registerHandler(StreamLoop_Handler_Abstract $handler) {
        // to locals
        $streamID = $handler->streamID;

        # debug:start
        if (!$streamID) {
            throw new StreamLoop_Exception('Cannot register handler without streamID');
        }
        # debug:end

        $this->_handlerArray[$streamID] = $handler;
        $this->_priorityArray[$streamID] = 0; // init
    }

    public function updateHandlerFlags(StreamLoop_Handler_Abstract $handler, $flagRead, $flagWrite) {
        // to locals
        $streamID = $handler->streamID;

        # debug:start
        if (!$streamID) {
            throw new StreamLoop_Exception('Cannot register handler without streamID');
        }
        # debug:end

        if ($flagRead) {
            $this->_selectReadArray[$streamID] = $handler->stream;
        } else {
            unset($this->_selectReadArray[$streamID]);
        }

        if ($flagWrite) {
            $this->_selectWriteArray[$streamID] = $handler->stream;
        } else {
            unset($this->_selectWriteArray[$streamID]);
        }

        // обновляем rw флаг
        if ($this->_selectReadArray) {
            $this->_rwFlag = true;
        } elseif ($this->_selectWriteArray) {
            $this->_rwFlag = true;
        } else {
            $this->_rwFlag = false;
        }
    }

    /**
     * Задать приоритет handler'a: по этому приоритету будут сортироваться массивы для select()
     * Чем больше число - тем приоритет первее.
     *
     * Приоритет ставится per streamID, и если в будущем streamID пропадет (через resetHandler или unregisterHandler)
     *
     * @param StreamLoop_Handler_Abstract $handler
     * @param $priority
     * @return void
     */
    public function updateHandlerPriority(StreamLoop_Handler_Abstract $handler, $priority) {
        $this->_priorityArray[$handler->streamID] = $priority;

        // сортировка запускается отдельно на отдельном handler
    }

    public function sortHandlerArray() {
        $priorityArray = $this->_priorityArray;

        // приоритет есть всегда для любого handler'a, поэтому никаких if-ов

        // больший priority должен находиться выше (раньше)
        // специальный хак с use priorityArray чтобы сделать to locals
        $compare = static function ($streamIDA, $streamIDB) use ($priorityArray) {
            return $priorityArray[$streamIDB] <=> $priorityArray[$streamIDA];
        };

        if (count($this->_selectReadArray) > 1) {
            uksort($this->_selectReadArray, $compare);
        }

        if (count($this->_selectWriteArray) > 1) {
            uksort($this->_selectWriteArray, $compare);
        }

        // массив timeout-ов тоже сортируем:
        // в случае если в один момент надо будет вызвать кучу таймеров, то чтобы всякие race/gc/sorts были в конце.
        uksort($this->_selectTimeoutToArray, $compare);
    }

    /**
     * Сбросить все флаги у handler'a и таймаут
     *
     * @param StreamLoop_Handler_Abstract $handler
     * @return void
     * @throws StreamLoop_Exception
     */
    public function resetHandler(StreamLoop_Handler_Abstract $handler) {
        // @todo это почти unregisterHandler - может заменить на него? это усложнит HTTP handler, ему тогда надо будет дергать regiser

        // to locals
        $streamID = $handler->streamID;

        # debug:start
        if (!$streamID) {
            throw new StreamLoop_Exception('Cannot register handler without streamID');
        }
        # debug:end

        unset(
            $this->_selectReadArray[$streamID],
            $this->_selectWriteArray[$streamID],
            $this->_selectTimeoutToArray[$streamID],
        );

        $this->_priorityArray[$streamID] = 0; // init
        $this->_selectTimeoutToMin = min($this->_selectTimeoutToArray);

        // обновляем rw флаг
        if ($this->_selectReadArray) {
            $this->_rwFlag = true;
        } elseif ($this->_selectWriteArray) {
            $this->_rwFlag = true;
        } else {
            $this->_rwFlag = false;
        }
    }

    /**
     * Специальный метод который только поменяет timeout и все;
     * предполагается что handler уже зарегистрирован и его состояния rw правильные.
     * Использовать очень аккуратно и только в hot path.
     *
     * @param $streamID
     * @param $timeoutTo
     * @return void
     * @throws StreamLoop_Exception
     */
    public function updateStreamTimeout($streamID, $timeoutTo) {
        # debug:start
        if (!$streamID) {
            throw new StreamLoop_Exception('Cannot update handler without streamID');
        }
        if (!isset($this->_handlerArray[$streamID])) {
            throw new StreamLoop_Exception('Cannot update timeout for unregistered handler');
        }
        # debug:end

        $this->_selectTimeoutToArray[$streamID] = $timeoutTo;

        // если timeoutto меньше - то используем его; иначе пересчитываем
        // if-tree-optimization: обычно timeout увеличивается, а не уменьшается, поэтому if (true) первое
        if ($timeoutTo > $this->_selectTimeoutToMin) {
            $this->_selectTimeoutToMin = min($this->_selectTimeoutToArray);
        } else {
            $this->_selectTimeoutToMin = $timeoutTo;
        }
    }

    /**
     * @var array<StreamLoop_Handler_Abstract>
     */
    private $_handlerArray = [];
    /**
     * @var array<float>
     */
    private $_priorityArray = [];
    private $_rwFlag = false; // bool
    private $_selectReadArray = [];
    private $_selectWriteArray = [];
    private $_selectTimeoutToArray = [
        0 => PHP_FLOAT_MAX, // специальный костыль, чтобы массив таймаутов всегда был не пустой; sentinel: keeps timeout array non-empty; streamID=0 is forbidden
    ];
    private $_selectTimeoutToMin = PHP_FLOAT_MAX; // float

}