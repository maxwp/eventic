<?php
class SuperDebug extends EE_Content_Abstract_Cli {

    public function process() {
        $this->print_r(SuperVisor::Get()->getConfigArray());
    }

}