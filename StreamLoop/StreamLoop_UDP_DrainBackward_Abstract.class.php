<?php
abstract class StreamLoop_UDP_DrainBackward_Abstract extends StreamLoop_UDP_Abstract {

    public function readyRead($tsSelect) {
        // тут всегда будет как минимум две попытки чтения, поэтому to locals оправдан для всего
        $socket = $this->_socketResource;

        $buffer1 = '';
        $fromAddress = '';
        $fromPort = null; // он не будет нужен

        // --- recv #1 (must exist if select says readable) ---
        $bytes1 = socket_recvfrom(
            $socket,
            $buffer1,
            1500, // UDP limit MTU
            MSG_DONTWAIT,
            $fromAddress,
            $fromPort
        );

        if ($bytes1 <= 0) {
            // rare: select said readable but nothing read
            // редкая ситуация select сказал что данные есть, но ничего не прочиталось
            return;
        }

        // stash #1 (because next recv overwrites vars)
        $buffer2 = '';
        $fromAddress2 = '';

        // --- recv #2 (single extra recv to detect batching) ---
        $bytes2 = socket_recvfrom(
            $socket,
            $buffer2,
            1500, // UDP limit MTU
            MSG_DONTWAIT,
            $fromAddress2,
            $fromPort
        );

        if ($bytes2 <= 0) {
            // common case: only one datagram available -> no arrays/loops
            $this->_onReceive($tsSelect, $buffer1, $bytes1);
            return;
        }

        // --- batching case: buffer ALL and emit latest-first ---
        // push #1
        // push #2 (currently in $buffer/$fromAddress/$fromPort)
        $bufferArray = [$buffer1, $buffer2];

        // drain up to limit:
        // start from 3 because we already have 2
        $drainLimit = 8; // 10-2 fixed

        do {
            $bytes1 = socket_recvfrom(
                $socket,
                $buffer1,
                1500, // UDP limit MTU
                MSG_DONTWAIT,
                $fromAddress1,
                $fromPort
            );

            if ($bytes1 > 0) {
                $bufferArray[] = $buffer1;
            } else {
                // end of drain
                break;
            }
        } while (--$drainLimit);

        // emit latest-first: newest datagram first
        // тут нельзя foreach, потому что бегу по элементам в обратном порядке
        $found = count($bufferArray);
        do {
            $buffer1 = $bufferArray[--$found];

            $this->_onReceive(
                $tsSelect,
                $buffer1,
                strlen($buffer1),
            );
        } while ($found);
    }

}