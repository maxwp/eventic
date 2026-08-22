<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Routing
 */
class EE_Routing implements EE_Routing_Interface {

    /**
     * На основе запроса выдать имя класса, который надо запустить
     * или бросить Exception
     *
     * @param EE_Request_Interface $request
     * @return string
     */
    public function matchContent(EE_Request_Interface $request) {
        // $url, $args = array(), $return = false
        $url = $request->getURL();
        $args = $request->getArgumentArray();
        $return = false;

        // перебираем все данные из источника
        foreach ($this->_routeArray as $murl => $className) {
            // так как preg-выражения долгие, то используем их только если на то есть повод
            $found = false;
            if (str_contains($murl, '{')) {
                $this->_callbackArray = array();

                // убираем из URL'a все необязательные параметры вида [*]
                $this->_callbackReturn = "(.*?)";

                // заменяем в URL'е все {*} конструкции на (.*?) и запоминаем порядок их следования
                $this->_callbackReturn = "([^/]+?)";
                $murl = preg_replace_callback("/\{(.*?)\}/is", array($this, '_callbackPregMatchURL'), $murl);
                $murl = str_replace('/', '\/', $murl);
                $murl = "/^{$murl}$/u";

                if (preg_match($murl, $url, $r)) {
                    $found = true;

                    // url подошел - необходимо найти и создать все обязательные аргументы
                    $urlParams = $this->_callbackArray;
                    foreach ($urlParams as $index => $name) {
                        if (isset($r[$index+1])) {
                            $value = $r[$index+1];

                            // var_dump($name); // - это может быть регулярное выражение
                            // var_dump($value); // - значение

                            if (strcasecmp($name, '{')) {
                                $expression = "{$name}";
                                // заменяем в URL'е все {*} конструкции на (.*?) и запоминаем порядок их следования
                                $this->_callbackArray = array();
                                $this->_callbackReturn = "([^/]+?)";
                                $expression = preg_replace_callback(
                                    "/\{(.*?)\}/is",
                                    array($this, '_callbackPregMatchURL'),
                                    $expression
                                );
                                $expression = str_replace('/', '\/', $expression);
                                $urlParams2 = $this->_callbackArray;

                                if (preg_match("/{$expression}/", $value, $rx)) {
                                    foreach ($urlParams2 as $index2 => $name2) {
                                        if (!isset($rx[$index2+1])) {
                                            continue;
                                        }

                                        // print_r($name2);
                                        // var_dump($rx[$index2+1]);
                                        // добавляем агрумент в URLParser
                                        $request->setArgument($name2, $rx[$index2+1]);
                                        // добавляем агрумент в $args
                                        // хотя скорее всего это и не нужно при правильном проектировании
                                        $args[$name2] = $rx[$index2+1];
                                    }
                                }
                            } else {
                                // добавляем агрумент в URLParser
                                $request->setArgument($name, $value);
                                // добавляем агрумент в $args
                                $args[$name] = $value;
                            }
                        }
                    }
                }
            } else {
                // обычное сравнение
                if ($murl == $url) {
                    $found = true;
                }
            }

            if (!$found) continue;

            // проверяем агрументы
            /*$params_ok = true;
            $argumentsArray = $a['arguments'];
            foreach ($argumentsArray as $argname) {
                if (!isset($args[$argname])) {
                    $params_ok = false;
                }
            }

            if (!$params_ok) continue;*/

            /*if ($return) {
                return $className;
            }*/
            return $className;
        }

        //if ($return) {
            // @todo: exception?
            //return false;
        //}

        //return false;
        throw new EE_Exception('matchClassName failed for url='.$url);
    }

    private function _callbackPregMatchURL($paramArray) {
        $param = trim($paramArray[1]);
        if (!$param) {
            throw new EE_Exception("Empty param in match URL!");
        }
        $this->_callbackArray[] = $param;

        return $this->_callbackReturn;
    }

    /**
     * Register or override URL route to class
     *
     * @param string $url
     * @param string $className
     */
    public function registerRoute($url, $className) {
        $this->_routeArray[$url] = $className;
    }

    private $_callbackArray = array();

    private $_callbackReturn;

    private $_routeArray;

    /**
     * @return EE_Routing
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    private static $_Instance = false;

}