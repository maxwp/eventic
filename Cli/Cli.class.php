<?php
class Cli {

    // @todo надо делать не static, потому что terminal witdh один раз запрашивается

    public static function PrintSGRStart(...$args) {
        echo "\033[".implode(';', $args)."m";
    }

    public static function PrintSGREnd() {
        echo "\033[".self::RESET."m";
    }

    public static function Print($s) {
        echo (string) $s; // это нужно для типизации в string, потому что я могу передать объект типа DateTime_Object
    }

    public static function Print_n($s = '') {
        echo $s.PHP_EOL;
    }

    public static function Print_break($symbol = '-', $length = 120, $separator = "\n") {
        echo $separator;
        echo str_repeat($symbol, $length);
        echo $separator;
        echo "\n";
    }

    /**
     * Получить ширину терминала если это возможно
     *
     * @return int
     */
    public static function TerminalWidth() {
        if (self::IsTerminal()) {
            if (function_exists('shell_exec')) {
                $size = trim((string)shell_exec(
                    'stty size 2>/dev/null < /dev/tty'
                ));

                if (preg_match('/^\d+\s+(\d+)$/', $size, $matches)) {
                    $width = (int)$matches[1];

                    if ($width > 0) {
                        return $width;
                    }
                }
            }
        }

        return 120;
    }

    private static function _TerminalWidthLimited() {
        return max(80, (int) floor(self::TerminalWidth() * 0.8));
    }

    public static function IsTerminal() {
        return stream_isatty(STDOUT);

        /*$color = stream_isatty(STDOUT)
            && getenv('NO_COLOR') === false
            && getenv('TERM') !== 'dumb';*/
    }

    public static function Print_Title(...$args) {
        $width = self::_TerminalWidthLimited();
        $separator = str_repeat('═', $width);

        self::Print_n();
        self::Print_n();
        self::PrintSGRStart(self::BOLD_ON, self::FG_CYAN);
        self::Print_n($separator);

        foreach ($args as $arg) {
            foreach ((array) $arg as $text) {
                foreach (preg_split('/\R/u', (string) $text) as $line) {
                    self::Print_n('   ' . $line);
                }
            }
        }

        self::Print_n($separator);
        self::PrintSGREnd();
        self::Print_n();
    }

    public static function Print_Header1(...$args) {
        $width = self::_TerminalWidthLimited();

        self::Print_n();

        $first = true;

        self::PrintSGRStart(self::BOLD_ON);
        foreach ($args as $arg) {
            foreach ((array) $arg as $text) {
                foreach (preg_split('/\R/u', (string) $text) as $line) {
                    if ($first) {
                        // «── » + название + пробел + оставшаяся линия.
                        $remaining = max(
                            0,
                            $width - 4 - mb_strwidth($line, 'UTF-8')
                        );

                        self::Print_n(
                            '── '
                            . $line
                            . ($remaining > 0
                                ? ' ' . str_repeat('─', $remaining)
                                : '')
                        );

                        $first = false;
                    } else {
                        self::Print_n('   ' . $line);
                    }
                }
            }
        }
        self::PrintSGREnd();
        self::Print_n();
    }

    public static function Print_Header2(...$args) {
        self::Print_n();

        $first = true;

        foreach ($args as $arg) {
            foreach ((array) $arg as $text) {
                foreach (preg_split('/\R/u', (string) $text) as $line) {
                    self::Print_n(
                        ($first ? '-- ' : '   ')
                        . $line
                    );

                    $first = false;
                }
            }
        }

        self::Print_n();
    }

    public static function Print_t($s = '') {
        echo "$s\t";
    }

    public static function Print_r($a) {
        print_r($a);
    }

    public static function Print_f($s, $format, $eol = ' ', $color = false) {
        if ($color) {
            self::PrintSGRStart($color);
        }
        if (str_contains($format, '%')) {
            echo sprintf($format, $s) . $eol;
        } else {
            echo sprintf('%1$' . $format, $s) . $eol;
        }
        if ($color) {
            self::PrintSGREnd();
        }
    }

    // -------------------------------------------------
    // Сброс всех атрибутов к значениям по умолчанию
    // -------------------------------------------------
    public const RESET = "0";

    // -------------------------------------------------
    //  Атрибуты шрифта — включение
    // -------------------------------------------------
    public const BOLD_ON         = "1";  // Жирный
    public const FAINT_ON        = "2";  // Блеклый (уменьшенная яркость)
    public const ITALIC_ON       = "3";  // Курсив (не всегда поддерживается)
    public const UNDERLINE_ON    = "4";  // Подчёркнутый
    public const BLINK_SLOW_ON   = "5";  // Мигание (медленное)
    public const BLINK_RAPID_ON  = "6";  // Мигание (быстрое) — редко поддерживается
    public const REVERSE_ON      = "7";  // Инверсия (фон ↔ текст)
    public const CONCEAL_ON      = "8";  // Скрытый (виден только при копировании)
    public const CROSSED_OUT_ON  = "9";  // Зачёркнутый

    // -------------------------------------------------
    //  Сброс отдельных атрибутов (выключение)
    // -------------------------------------------------
    // 10m по умолчанию = основной шрифт
    public const BOLD_OFF         = "22"; // Сброс жирного и блеклого
    public const ITALIC_OFF       = "23"; // Сброс курсива
    public const UNDERLINE_OFF    = "24"; // Сброс подчёркивания
    public const BLINK_OFF        = "25"; // Сброс мигания
    // 26m зарезервирован
    public const REVERSE_OFF      = "27"; // Сброс инверсии
    public const CONCEAL_OFF      = "28"; // Сброс скрытого
    public const CROSSED_OUT_OFF  = "29"; // Сброс зачёркнутого

    // -------------------------------------------------
    //  Альтернативные шрифты
    // -------------------------------------------------
    public const FONT_PRIMARY     = "10"; // Основной шрифт (сброс альтернативного)
    public const FONT_ALTERNATE_1 = "11";
    public const FONT_ALTERNATE_2 = "12";
    public const FONT_ALTERNATE_3 = "13";
    public const FONT_ALTERNATE_4 = "14";
    public const FONT_ALTERNATE_5 = "15";
    public const FONT_ALTERNATE_6 = "16";
    public const FONT_ALTERNATE_7 = "17";
    public const FONT_ALTERNATE_8 = "18";
    public const FONT_ALTERNATE_9 = "19";

    // -------------------------------------------------
    //  Цвет текста (FG) — стандартные
    // -------------------------------------------------
    public const FG_BLACK   = "30";
    public const FG_RED     = "31";
    public const FG_GREEN   = "32";
    public const FG_YELLOW  = "33";
    public const FG_ORANGE = "38;5;208";
    public const FG_BLUE    = "34";
    public const FG_MAGENTA = "35";
    public const FG_CYAN    = "36";
    public const FG_WHITE   = "37";
    public const FG_DEFAULT = "39"; // цвет по умолчанию

    // -------------------------------------------------
    //  Цвет фона (BG) — стандартные
    // -------------------------------------------------
    public const BG_BLACK   = "40";
    public const BG_RED     = "41";
    public const BG_GREEN   = "42";
    public const BG_YELLOW  = "43";
    public const BG_ORANGE = "48;5;208";
    public const BG_BLUE    = "44";
    public const BG_MAGENTA = "45";
    public const BG_CYAN    = "46";
    public const BG_WHITE   = "47";
    public const BG_DEFAULT = "49"; // фон по умолчанию

    // -------------------------------------------------
    //  Яркие (bright) цвета текста
    // -------------------------------------------------
    public const FG_BLACK_BRIGHT   = "90";
    public const FG_RED_BRIGHT     = "91";
    public const FG_GREEN_BRIGHT   = "92";
    public const FG_YELLOW_BRIGHT  = "93";
    public const FG_BLUE_BRIGHT    = "94";
    public const FG_MAGENTA_BRIGHT = "95";
    public const FG_CYAN_BRIGHT    = "96";
    public const FG_WHITE_BRIGHT   = "97";

    // -------------------------------------------------
    //  Яркие (bright) цвета фона
    // -------------------------------------------------
    public const BG_BLACK_BRIGHT   = "100";
    public const BG_RED_BRIGHT     = "101";
    public const BG_GREEN_BRIGHT   = "102";
    public const BG_YELLOW_BRIGHT  = "103";
    public const BG_BLUE_BRIGHT    = "104";
    public const BG_MAGENTA_BRIGHT = "105";
    public const BG_CYAN_BRIGHT    = "106";
    public const BG_WHITE_BRIGHT   = "107";

}