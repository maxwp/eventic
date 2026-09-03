<?php
/**
 * Раз в минуту делаем пересортировку handler-ов
 */
class StreamLoop_Sort extends StreamLoop_Timer_Abstract {

    public function __construct(StreamLoop $loop) {
        parent::__construct(
            $loop,
            200, // special timer ID, реально будет -200
            60
        );
    }

    protected function _onTimer($tsSelect) {
        // обычно вызов сортировки стоит 5-8 us для ds (это с принтами, без принтов дешевле 2-4 us)
        $this->_loop->sortHandlerArray();
    }

}