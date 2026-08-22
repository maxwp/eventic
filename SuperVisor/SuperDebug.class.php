<?php
class SuperDebug extends EE_AContentCli {

    public function process() {
        $this->print_r(SuperVisor::Get()->getConfigArray());
    }

}