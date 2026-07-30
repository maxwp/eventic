<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */
/**
 * Abstract content
 */
abstract class EE_AContent implements EE_IContent {

    // @todo есть глобальное дерьмо - смешаны внутренние аргументы и внешние,
    //       более правильно передавать аргументы с наружи врунтрь и потом с ними работать,
    //       причем передавать при каждом создании контента (new), а как с get?

    /**
     * Получить входящий аргумент
     * Если аргумента нет - будет EE_Exception
     *
     * @param string $key
     * @param mixed $type
     *
     * @return mixed
     */
    public function getArgument($key, $type = false, $source = false) {
        if ($source && $source == EE_IRequest::ARG_SOURCE_INTERNAL) {
            // только внутренние аргументы
            $checkInternal = true;
            $checkExternal = false;
        } elseif ($source && $source != EE_IRequest::ARG_SOURCE_INTERNAL) {
            // только внешние
            // (ARG_SOURCE_EXTERNAL нет, внешними считаются GET/POST/... - все что не INTERNAL)
            $checkInternal = false;
            $checkExternal = true;
        } else {
            // все подряд
            $checkInternal = true;
            $checkExternal = true;
        }

        // сначала проверяем внутренние аргументы
        if ($checkInternal) {
            if (isset($this->_argumentArray[$key])) {
                $value = $this->_argumentArray[$key];

                // опциональная типизация
                if ($type) {
                    $value = EE_Typing::TypeString($value, $type);
                }

                return $value;
            }
        }

        // затем проверяю внешние аргументы
        if ($checkExternal) {
            $value = EE::Get()->getRequest()->getArgument($key, $source);

            // опциональная типизация
            if ($type) {
                $value = EE_Typing::TypeString($value, $type);
            }

            return $value;
        }

        throw new EE_Exception("Argument {$key} not found");
    }

    /**
     * Безопасно получить аргумент.
     * Если аргумента нет - будет false.
     *
     * @param string $key
     * @param mixed $type
     *
     * @return mixed
     * @see getArgument()
     *
     */
    public function getArgumentSecure($key, $type = false, $source = false) {
        try {
            return $this->getArgument($key, $type, $source);
        } catch (Exception) {
            if ($type) {
                return EE_Typing::TypeString(false, $type);
            } else {
                return false;
            }
        }
    }

    /**
     * Получить все внутренние аргументы
     *
     * @return array
     */
    public function getArgumentArray() {
        return $this->_argumentArray;
    }

    // @todo setArgumentArray

    /**
     * Задать внутренних аргумент контенту
     *
     * @param $key
     * @param $value
     * @return void
     * @throws EE_Exception
     */
    public function setArgument($key, $value) {
        if ($key) {
            $this->_argumentArray[$key] = $value;
        } else {
            throw new EE_Exception("Invalid argument key");
        }
    }

    public function unsetArgument($key) {
        unset($this->_argumentArray[$key]);
    }

    public function unsetArgumentArray() {
        $this->_argumentArray = [];
    }

    /**
     * Установить значение в контент.
     * Если secure - то автоматически делается htmlspecialchars
     *
     * @param string $key
     * @param mixed $value
     */
    public function setValue($key, $value) {
        // @todo скорее всего будет отрефакторено при разделении Smarty vs Content based on EE_DataBus
        if ($key) {
            $this->_valueArray[$key] = $value;
        } else {
            throw new EE_Exception("Empty key name. Nothing to set");
        }
    }

    /**
     * @param array $a
     * @return void
     */
    public function updateValueArray($a) {
        $this->_valueArray += $a;
    }

    public function setValueArray($a) {
        $this->_valueArray = $a;
    }

    public function unsetValue($key) {
        unset($this->_valueArray[$key]);
    }

    public function unsetValueArray() {
        $this->_valueArray = [];
    }

    /**
     * Получить значение контента
     *
     * @param string $key
     * @return mixed
     */
    public function getValue($key) {
        if (isset($this->_valueArray[$key])) {
            return $this->_valueArray[$key];
        }

        if (!$key) {
            throw new EE_Exception('Empty key name');
        }

        return false;
    }

    /**
     * Добавить значений в контент (массово)
     *
     * @param array $a
     */
    public function addValueArray($a) {
        if (!$this->_valueArray) {
            $this->_valueArray = $a;
        } else {
            $this->_valueArray = array_merge($this->_valueArray, $a);
        }
    }

    /**
     * Получить все установленные поля
     * 2D-array {key => value}
     *
     * @return array
     */
    public function getValueArray() {
        return $this->_valueArray;
    }

    // @todo возможно отказаться от требования к обязательному методу process(), потому что не удобно писать сервисы
    // @todo но с другой стороны без явного process не ясна точка старта для Web & Cli
    // @todo возможно заменить на main()
    abstract public function process();

    /**
     * Отрисовать контент (отрендерить в html-код).
     *
     * @return mixed
     */
    public function render() {
        EE_Events::Get()->notify('EE:content.process:before', $this);

        $this->process();

        EE_Events::Get()->notify('EE:content.process:after', $this);

        return $this->getValueArray();
    }

    public function reset() {
        $this->unsetArgumentArray();
        $this->unsetValueArray();
    }

    private array $_valueArray = [];

    /**
     * массив внутренних аргументов
     * @todo registry?
     */
    private array $_argumentArray = [];

}