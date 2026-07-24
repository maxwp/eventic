<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Класс для работы с датами, форматирование, вычисления.
 *
 * Design-patter: Value Object
 */
class DateTime_Object {

    // @todo strtotime_ex

    public function __construct($timestamp) {
        $this->_timestamp = $timestamp;
        $this->_classFormat = new DateTime_ClassFormatDefault();
    }

    /**
     * @param string $format
     * @return DateTime_Object
     */
    public function setFormat($format) {
        $this->_format = $format;
        return $this;
    }

    /**
     * @param DateTime_IClassFormat $classFormat
     * @return DateTime_Object
     */
    public function setClassFormat(DateTime_IClassFormat $classFormat) {
        $this->_classFormat = $classFormat;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString() {
        $cf = $this->_classFormat;
        $cf->setDate($this->_timestamp);
        $cf->setFormat($this->_format);
        return $cf->__toString();
    }

    public function setDate($date) {
        $this->_timestamp = strtotime($date);
    }

    /**
     * Добавить месяц
     *
     * @param int $months
     * @return DateTime_Object
     */
    public function addMonth($months) {
        return $this->_addSomething('mon', $months);
    }

    /**
     * Добавить день
     *
     * @param int $days
     * @return DateTime_Object
     */
    public function addDay($days) {
        return $this->_addSomething('mday', $days);
    }

    /**
     * Добавить минут
     *
     * @param int $minutes
     * @return DateTime_Object
     */
    public function addMinute($minutes) {
        return $this->_addSomething('minutes', $minutes);
    }

    /**
     * Добавить часов
     *
     * @param int $hours
     * @return DateTime_Object
     */
    public function addHour($hours) {
        return $this->_addSomething('hours', $hours);
    }

    /**
     * Добавить секунд
     *
     * @param int $seconds
     * @return DateTime_Object
     */
    public function addSecond($seconds) {
        return $this->_addSomething('seconds', $seconds);
    }

    /**
     * Добавить год
     *
     * @param int $years
     * @return DateTime_Object
     */
    public function addYear($years) {
        return $this->_addSomething('year', $years);
    }

    /**
     * Дописать к текущей дате что-либо
     *
     * @param string $what "seconds" Секунды От 0 до 59
     *                      "minutes" Минуты От 0 до 59
     *                      "hours" Часы От 0 до 23
     *                      "mday" Порядковый номер дня месяца От 1 до 31
     *                      "wday" Порядковый номер дня  От 0 (воскресенье) до 6 (суббота)
     *                      "mon" Порядковый номер месяца От 1 до 12
     *                      "year" Порядковый номер года, 4 цифры Примеры: 1999, 2003
     *                      "yday" Порядковый номер дня в году (нумерация с 0) От 0 до 365
     *                      "weekday" Полное наименование дня недели От Sunday до Saturday
     *                      "month" Полное наименование месяца, например January или March от January до December
     * @param int $count
     * @return DateTime_Object
     */
    private function _addSomething($what, $count) {
        // @todo говнище ж лютое
        $array = getdate($this->_timestamp);
        $array[$what] += $count;
        $this->_timestamp = mktime($array['hours'],$array['minutes'],$array['seconds'], $array['mon'],$array['mday'],$array['year']);
        return $this;
    }

    /**
     * Привести дату в штамп времени
     *
     * @return int
     */
    public function getTimestamp() {
        return $this->_timestamp;
    }

    /**
     * @param mixed $datetime
     * @return DateTime_Object
     */
    public static function Create($datetime = false) {
        if (!$datetime) {
            return new DateTime_Object(time());
        } elseif (is_int($datetime)) {
            return new DateTime_Object($datetime);
        } elseif (is_float($datetime)) {
            return new DateTime_Object($datetime);
        } else {
            return new DateTime_Object(strtotime($datetime));
        }
    }

    /**
     * Создать объект на основе текущего времени
     *
     * @return DateTime_Object
     */
    public static function Now() {
        // @todo microtime(true)
        return new DateTime_Object(time());
    }

    /**
     * Создать объект на основе unix timestamp
     *
     * @return DateTime_Object
     */
    public static function FromTimeStamp($timestamp) {
        // @todo явно добавить типизацию float
        return new DateTime_Object($timestamp);
    }

    /**
     * Создать объект на основе времени, заданного строкой
     *
     * @return DateTime_Object
     */
    public static function FromString($strtime) {
        // @todo явно добавить типизацию string
        return new DateTime_Object(strtotime($strtime));
    }

    private $_timestamp; // float

    private $_format = 'Y-m-d H:i:s'; // @todo лучше все на class formatters

    /**
     * @var DateTime_IClassFormat
     */
    private $_classFormat;

}