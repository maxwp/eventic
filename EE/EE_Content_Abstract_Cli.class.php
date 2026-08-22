<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Content for CLI
 */
abstract class EE_Content_Abstract_Cli extends EE_Content_Abstract implements EE_Content_Interface {

    public function printSGRStart(...$args) {
        Cli::PrintSGRStart(...$args);
    }

    public function printSGREnd() {
        Cli::PrintSGREnd();
    }

    public function print($s) {
        Cli::Print($s);
    }

    public function print_n($s = '') {
        Cli::Print_n($s);
    }

    public function print_break($symbol = '-', $length = 120, $separator = "\n") {
        Cli::Print_break($symbol, $length, $separator);
    }

    public function print_title($title, $width = 120) {
        Cli::Print_title($title, $width);
    }

    public function print_t($s = '') {
        Cli::Print_t($s);
    }

    public function print_r($a) {
        Cli::Print_r($a);
    }

    public function print_f($s, $format, $eol = ' ', $color = false) {
        Cli::Print_f($s, $format, $eol, $color);
    }

    public function print_n_error(Throwable $e) {
        $this->print_n_failure($e->getMessage());
    }

    public function print_n_exception(Exception $e) {
        $this->print_n_failure(get_class($e).': '.$e->getMessage());
    }

    public function print_n_success($s) {
        $this->printSGRStart(Cli::FG_GREEN_BRIGHT);
        $this->print_n($s);
        $this->printSGREnd();
    }

    public function print_n_warning($s) {
        $this->printSGRStart(Cli::FG_YELLOW_BRIGHT);
        $this->print_n($s);
        $this->printSGREnd();
    }

    public function print_n_failure($s) {
        $this->printSGRStart(Cli::FG_RED_BRIGHT);
        $this->print_n($s);
        $this->printSGREnd();
    }

    // @todo experimental cache, maybe move to other class or inherit in other layer
    protected function _cacheLoad($cacheFile) {
        // cache - это попытка считать из кеша и сформировать его если его нет
        $cache = $this->getArgumentSecure('cache', EE_Typing::TYPE_STRING);

        // если сказано строит новый cache - то не используем старый
        if ($cache == 'new') {
            return false;
        }

        if ($cache) {
            try {
                if (file_exists($cacheFile)) {
                    return simdjson_decode(
                        file_get_contents('compress.zlib://'.$cacheFile),
                        true
                    );
                }
            } catch (Exception) {

            }
        }

        return false;
    }

    protected function _cacheSave($cacheFile, $data) {
        // cache - это попытка считать из кеша и сформировать его если его нет
        $cache = $this->getArgumentSecure('cache', EE_Typing::TYPE_STRING);

        if ($cache) {
            file_put_contents(
                $cacheFile,
                gzencode(json_encode($data)),
                LOCK_EX
            );
        }
    }

}