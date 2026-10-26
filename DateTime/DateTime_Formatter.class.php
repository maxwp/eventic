<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Утилита для быстрого форматирования дат в нужный формат
 * (формально - оболочка над DateTime_Object)
 *
 * Все что начинается на Date - только дата
 * Все что начинается на Time - только время
 * Все что начинается на DateTime - дата и время
 * Все что начинается на TimeDate - время и дата (обратный порядок)
 */
class DateTime_Formatter {

    /**
     * Формат даты в соответсвии с ГОСТ Р 6.30-2003 (п. 3.11).
     * 31.12.1986
     *
     * @param string $date
     *
     * @return string
     */
    public static function DateRussianGOST($date) {
        return DateTime_Object::FromString($date)->setFormat('d.m.Y')->__toString();
    }

    /**
     * Формат даты в соответсвии с ГОСТ Р 6.30-2003 (п. 3.11).
     * 31.12.1986 11:22:33
     *
     * @param string $date
     *
     * @return string
     */
    public static function DateTimeRussianGOST($date) {
        return DateTime_Object::FromString($date)->setFormat('d.m.Y H:i:s')->__toString();
    }

    /**
     * Формат даты в соответсвии с ISO 8601 (допускается в ГОСТ Р 6.30-2003 (п. 3.11))
     * 1986.12.31
     *
     * @param string $date
     *
     * @return string
     */
    public static function DateISO8601($date) {
        if ($date == '0000-00-00 00:00:00') {
            return false;
        }
        return DateTime_Object::FromString($date)->setFormat('Y.m.d')->__toString();
    }

    /**
     * Формат даты в соответсвии с ISO 9075 (SQL)
     * 1986-12-31
     *
     * @param string $date
     *
     * @return string
     */
    public static function DateISO9075($date) {
        return DateTime_Object::FromString($date)->setFormat('Y-m-d')->__toString();
    }

    /**
     * Формат даты и времени в соответсвии с ISO 9075 (SQL)
     * 1986-12-31 12:00:55
     *
     * @param string $date
     *
     * @return string
     */
    public static function DateTimeISO9075($date) {
        return DateTime_Object::FromString($date)->setFormat('Y-m-d H:i:s')->__toString();
    }
    
    /**
     * Фонетическое форматирование даты.
     * Пример: понедельник, 31.12.1986, 12:20
     * Пример: вчера, 31.12.1986, 12:20
     * Пример: 5 мин. назад, 31.12.1986, 12:20
     *
     * @param string $datetime
     *
     * @return string
     */
    public static function DateTimePhonetic($datetime) {
        return DateTime_Object::FromString($datetime)->setClassFormat(new DateTime_ClassFormatPhonetic())->__toString();
    }

    /**
     * Фонетическое форматирование даты.
     * Пример: понедельник, 31.12.1986
     * Пример: вчера, 31.12.1986
     * Пример: 5 мин. назад, 31.12.1986
     *
     * @param string $date
     *
     * @return string
     */
    public static function DatePhonetic($date) {
        $f = new DateTime_ClassFormatPhonetic();
        $f->setFormatMode('date');
        return DateTime_Object::FromString($date)->setClassFormat($f)->__toString();
    }

    /**
     * Фонетическое форматирование даты в будущем времени.
     * Пример: Январь, 31.12.2012, 12:20
     *
     * @param string $datetime
     *
     * @return string
     */
    public static function DateTimePhoneticFuture($datetime) {
        return DateTime_Object::FromString(
            $datetime
        )->setClassFormat(new DateTime_ClassFormatPhoneticFuture())->__toString();
    }
    
    /**
     * Формат времени без даты
     * 14:32:25
     *
     * @param string $datetime
     * 
     * @return string
     */
    public static function TimeISO8601($datetime) {
        return DateTime_Object::FromString($datetime)->setFormat('H:i')->__toString();
    }

    public static function DatePhoneticMonthRus($date, $language = false) {
        $date = DateTime_Object::FromString($date)->setFormat('d-m-Y');
        $date = explode('-', $date);
        $str = @$date[0];
        $month = @$date[1];
        if ($language) {
            DateTime_Translate::Get()->setLanguage($language);
        }
        if ($month == 1) {
            $str.= ' '.DateTime_Translate::Get()->getTranslateSecure('january');
        } elseif ($month == 2) {
            $str.= ' '.DateTime_Translate::Get()->getTranslateSecure('february');
        } elseif ($month == 3) {
            $str.= ' '.DateTime_Translate::Get()->getTranslateSecure('march');
        } elseif ($month == 4) {
            $str.= ' '.DateTime_Translate::Get()->getTranslateSecure('april');
        } elseif ($month == 5) {
            $str.= ' '.DateTime_Translate::Get()->getTranslateSecure('may');
        } elseif ($month == 6) {
            $str.= ' '.DateTime_Translate::Get()->getTranslateSecure('june');
        } elseif ($month == 7) {
            $str.= ' '.DateTime_Translate::Get()->getTranslateSecure('july');
        } elseif ($month == 8) {
            $str.= ' '.DateTime_Translate::Get()->getTranslateSecure('august');
        } elseif ($month == 9) {
            $str.= ' '.DateTime_Translate::Get()->getTranslateSecure('september');
        } elseif ($month == 10) {
            $str.= ' '.DateTime_Translate::Get()->getTranslateSecure('october');
        } elseif ($month == 11) {
            $str.= ' '.DateTime_Translate::Get()->getTranslateSecure('november');
        } elseif ($month == 12) {
            $str.= ' '.DateTime_Translate::Get()->getTranslateSecure('december');
        }
        $str .= ' '.@$date[2];
        return $str;
    }

    /**
     * Форматирование интервала
     *
     * @param $dateFrom
     * @param $dateTo
     * @return array
     */
    public static function IntervalDateFromDateTo($dateFrom, $dateTo) {
        if (preg_match("/^(\d+)m$/ius", $dateTo, $r)) {
            $dateTo = DateTime_Object::Create($dateFrom)->addMinute(+$r[1])->__toString();
        }

        if (preg_match("/^(\d+)h$/ius", $dateTo, $r)) {
            $dateTo = DateTime_Object::Create($dateFrom)->addHour(+$r[1])->__toString();
        }

        if (preg_match("/^(\d+)d$/ius", $dateTo, $r)) {
            $dateTo = DateTime_Object::Create($dateFrom)->addDay(+$r[1])->__toString();
        }

        // @todo use consts from separate class (see Differ)
        if ($dateFrom == 'today') {
            $dateFrom = date('Y-m-d');
            $dateTo = DateTime_Object::FromString($dateFrom)->addDay(+1)->__toString();
        } elseif ($dateFrom == 'day') {
            $dateFrom = date('Y-m-d');
            $dateTo = DateTime_Object::FromString($dateFrom)->addDay(+1)->__toString();
        } elseif ($dateFrom == 'prevday') {
            $dateFrom = DateTime_Object::Create()->addDay(-1)->setFormat('Y-m-d')->__toString();
            $dateTo = DateTime_Object::FromString($dateFrom)->addDay(+1)->__toString();
        } elseif ($dateFrom == 'week') {
            $dateFrom = DateTime_Object::Create()->addDay(-6)->__toString();
            $dateTo = DateTime_Object::Create()->__toString();
        } elseif ($dateFrom == 'month') {
            $dateFrom = date('Y-m-01');
            $dateTo = DateTime_Object::FromString($dateFrom)->addMonth(+1)->__toString();
        } elseif ($dateFrom == 'month-1') {
            $dateFrom = date('Y-m-01');
            $dateTo = DateTime_Object::Create()->addDay(-1)->setFormat('Y-m-d')->__toString();
        } elseif ($dateFrom == 'year') {
            $dateFrom = date('Y-01-01');
            $dateTo = DateTime_Object::FromString($dateFrom)->addMonth(+12)->__toString();
        } elseif (preg_match("/^(\d+)m$/ius", $dateFrom, $r)) {
            $dateFrom = DateTime_Object::Create()->addMinute(-$r[1])->__toString();
            $dateTo = DateTime_Object::Create()->addDay(+1)->__toString();
        } elseif (preg_match("/^(\d+)h$/ius", $dateFrom, $r)) {
            $dateFrom = DateTime_Object::Create()->addHour(-$r[1])->__toString();
            $dateTo = DateTime_Object::Create()->addDay(+1)->__toString();
        } elseif (preg_match("/^(\d+)d$/ius", $dateFrom, $r)) {
            $dateFrom = DateTime_Object::Create()->addDay(-$r[1])->setFormat('Y-m-d')->__toString();
            $dateTo = DateTime_Object::Create()->addDay(+1)->__toString();
        }

        if (!$dateFrom) {
            $dateFrom = DateTime_Object::Create()->addDay(-1)->__toString();
        }

        if (!$dateTo) {
            $dateTo = DateTime_Object::Create($dateFrom)->addDay(+1)->__toString();
        }

        $now = date('Y-m-d H:i:s');
        if ($dateTo >= $now) {
            $dateTo = $now;
        }

        return array($dateFrom, $dateTo);
    }

}