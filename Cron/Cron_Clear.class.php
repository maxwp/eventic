<?php
class Cron_Clear extends EE_Content_Abstract_Cli {

    public function process() {
        Cron::Get()->clear();
        exit;
    }

}