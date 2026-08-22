<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Движок Eventic Engine
 */
class EE extends Pattern_ASingleton {

    protected function __construct() {
        $this->_contentRegistryArray = new Pattern_RegistryArray();
    }

    /**
     * Вызвать движок
     * Передаем параметр $request, получаем $response
     */
    public function execute(EE_Request_Interface $request, EE_Response_Interface $response) {
        EE_Events::Get()->notify('EE:execute:before');

        // сохраняем request в себе
        // это нужно чтобы в процессе работы движка любой контент мог получить доступ к Request
        $this->_request = $request;

        // создаем чистый объект response
        // это нужно чтобы в процессе работы движка любой контент мог получить доступ к Response
        $this->_response = $response;

        // до того как сработал роутинг
        EE_Events::Get()->notify('EE:routing:before');

        // получаем систему роутинга
        // она должна быть инициирована заранее
        $routing = $this->getRouting();

        // по системе роутинга определяем что у нас за контент
        try {
            $className = $routing->matchContent($request);

            // в этой точке мы нашли класс который надо запустить,
            // причем роутинг сам должен вернуть класс или класс-404,
            // в противном случае он должен вернуть пустоту или бросить exception - и это будет считаться ошибкой 500

            // на всякий случай проверяем чтобы роутинг не вернул пустоту
            if (!$className) {
                throw new EE_Exception("Routing returned null");
            }

        } catch (Exception $routingException) {
            $this->getResponse()->setCode(500);
            $this->getResponse()->setData($routingException->getMessage());
            $className = 'ee500'; // штатный контент
        }

        // после того как сработал роутинг
        EE_Events::Get()->notify('EE:routing:after');

        // формируем ответ
        try {
            // запускаем рендеринг ответа
            $data = $this->_run($className);

            // пишем ответ
            $this->getResponse()->setData($data);
        } catch (Exception $ex500) {
            // что-то пошло не так
            $this->getResponse()->setCode(500);

            EE_Events::Get()->notify('EE:execute:exception', $ex500);

            throw $ex500;
        }

        EE_Events::Get()->notify('EE:execute:after');

        // очищаем все контенты,
        // это нужно следующего запуска движка в режиме non-stop
        foreach ($this->_contentRegistryArray->getArray() as $content) {
            $content->reset();
        }

        // очищаем объекты request/response
        // чтобы движок был готов к следующему запуску
        $this->_request = false;
        $this->_response = false;
    }

    /**
     * @template T of EE_Content_Interface
     * @param class-string<T> $className
     * @return string
     * @throws EE_Exception
     */
    private function _run(string $className) {
        // получаем объект
        $content = $this->getContent($className);

        // записываем ссылку на контент в Engine
        $this->setContentCurrent($content);

        $data = $this->renderTree($content);

        // Если в процессе обработки контента поменялся указатель на запускаемый контент,
        // значит повторяем вызов рекурсивно.
        // Это нужно чтобы контент мог в процессе сказать "нет, сейчас запускаем что-то другое"
        $newClassName = get_class($this->getContentCurrent());
        if ($newClassName != $className) {
            return $this->_run($newClassName);
        }

        return $data;
    }

    /**
     * @return EE_Request_Interface
     */
    public function getRequest() {
        if (!$this->_request) {
            throw new EE_Exception('Request object not set');
        }

        return $this->_request;
    }

    /**
     * @return EE_IRouting
     */
    public function getRouting() {
        // @todo эти все проверки надо делать init в самом начале и не задрачивать потом ifами код
        if (!$this->_routing) {
            throw new EE_Exception('Routing object not set');
        }

        return $this->_routing;
    }

    public function setRouting(EE_IRouting $routing) {
        $this->_routing = $routing;
    }

    /**
     * @return EE_Response_Interface
     */
    public function getResponse() {
        if (!$this->_response) {
            throw new EE_Exception('Response object not set');
        }

        return $this->_response;
    }

    public function setContentCurrent($content) {
        if ($content instanceof EE_Content_Interface) {
            $this->_contentCurrent = $content;
        } else {
            $content = $this->getContent($content);
            $this->_contentCurrent = $content;
        }
    }

    /**
     * @return EE_Content_Interface
     */
    public function getContentCurrent() {
        return $this->_contentCurrent;
    }

    /**
     * Вернуть готовое html-содержимое контента и всех его вложений
     * с учетом иерархии
     *
     * @param EE_Content_Interface $content
     * @return string
     */
    public function renderTree(EE_Content_Interface $content) {
        $data = $content->render();

        $moveTo = $content->getValue('moveto');
        $moveAs = $content->getValue('moveas');

        if ($moveTo) {
            $moveToContent = $this->getContent($moveTo);
            if ($moveAs) {
                $moveToContent->setValue($moveAs, $data);
            }
            return $this->renderTree($moveToContent);
        }

        return $data;
    }

    /**
     * Вернуть объект имени класса
     *
     * @template T of EE_Content_Interface
     * @param class-string<T> $className
     * @return T
     */
    public function getContent(string $className) {
        if (!$className) {
            throw new EE_Exception('Empty className');
        }

        if ($this->_contentRegistryArray->has($className)) {
            return $this->_contentRegistryArray->get($className);
        }

        $content = new $className();
        $this->_contentRegistryArray->set($className, $content);
        return $content;
    }

    /**
     * Принудительно задать контент для подмены на этот класс
     *
     * @param string $className
     * @param EE_Content_Interface $content
     * @return void
     */
    public function setContent(string $className, EE_Content_Interface $content) {
        $this->_contentRegistryArray->set($className, $content);
    }

    /**
     * Узнать, был ли загружен/вызван контент
     *
     * @template T of EE_Content_Interface
     * @param class-string<T> $className
     * @return bool
     */
    public function isContentLoaded(string $className) {
        return $this->_contentRegistryArray->has($className);
    }

    private $_request = null;

    private $_response = null;

    private $_routing = null;

    /**
     * Массив загруженных контентов
     *
     * @var Pattern_RegistryArray
     */
    private $_contentRegistryArray;

    private $_contentCurrent;

}