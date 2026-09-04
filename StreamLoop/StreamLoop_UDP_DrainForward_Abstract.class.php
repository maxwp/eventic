<?php
abstract class StreamLoop_UDP_DrainForward_Abstract extends StreamLoop_UDP_Abstract {

    public function readyRead($tsSelect) {
        $buffer = '';
        $fromAddress = '';
        $fromPort = null;

        // to locals
        $socket = $this->_socketResource;

        for ($drainIndex = 1; $drainIndex <= 10; $drainIndex++) {
            $bytes = socket_recvfrom(
                $socket,
                $buffer,
                1500, // UDP limit MTU
                MSG_DONTWAIT,
                $fromAddress,
                $fromPort
            );

            // if-tree optimization
            if ($bytes > 0) {
                $this->_onReceive($tsSelect, $buffer, $bytes);
            } else {
                // внимание! я не делаю тут проверки на ошибки, потому что эта штука занимает 0..1.1 us
                // stop drain
                return;
            }
        }
    }

}