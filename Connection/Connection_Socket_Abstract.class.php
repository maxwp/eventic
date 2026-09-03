<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Обертка над socket resource
 */
abstract class Connection_Socket_Abstract implements Connection_IConnection {

    public function __construct($socket) {
        $this->_socket = $socket;
    }

    abstract public function connect();

    public function disconnect() {
        if ($this->_socket) {
            socket_close($this->_socket);
        }
    }

    public function getLink() {
        return $this->_socket;
    }

    public function setNonBlocking() {
        socket_set_nonblock($this->_socket);
    }

    public function setBlocking() {
        socket_set_block($this->_socket);
    }

    public function setSocketOption($type, $value) {
        if (!socket_set_option($this->_socket, SOL_SOCKET, $type, $value)) {
            throw new Connection_Exception("Socket option error type=$type");
        }
    }

    public function getSocketOption($type) {
        return socket_get_option($this->_socket, SOL_SOCKET, $type);
    }

    public function setTimeoutRead($timeoutSec, $timeoutUsec) {
        // @todo стоит ли переделывать на один параметр?
        // @todo а нафига сделали два?
        $timeoutSec = ['sec' => $timeoutSec, 'usec' => $timeoutUsec];
        $this->setSocketOption(SO_RCVTIMEO, $timeoutSec);
    }

    public function setBufferSizeRead($size) {
        $this->setSocketOption(SO_RCVBUF, $size);
        $this->_checkBufferSize(SO_RCVBUF, $size);
    }

    public function setBufferSizeWrite($size) {
        $this->setSocketOption(SO_SNDBUF, $size);
        $this->_checkBufferSize(SO_SNDBUF, $size);
    }

    public function setReuseAddr($mode = 0) {
        $this->setSocketOption(SO_REUSEADDR, $mode);
    }

    public function setQuickACK($value = 1) {
        socket_set_option($this->_socket, SOL_TCP, 12, $value); // TCP_QUICKACK as 12 defined in php 8.3+ only
    }

    /**
     * Linux-only TCP_CORK.
     *
     * При включении ядро может задерживать последний неполный TCP-сегмент,
     * пытаясь дополнить его следующими записями. Полные сегменты отправляются.
     *
     * Накопленные данные отправляются при отключении TCP_CORK либо
     * принудительно ядром примерно через 200 мс.
     *
     * Сам socket option TCP_CORK по умолчанию выключен.
     * Отдельно Linux может автоматически объединять мелкие записи
     * через механизм tcp_autocorking.
     */
    public function setCORK($value = 0) {
        socket_set_option($this->_socket, SOL_TCP, 3, $value); // TCP_CORK as 3 defined
    }

    public function setKeepAlive($value = 1) {
        socket_set_option($this->_socket, SOL_SOCKET, SO_KEEPALIVE, $value);
    }

    private function _checkBufferSize($side, $size) {
        // При установке опции SO_RCVBUF/SO_SNDBUF в Linux значение, которое вы указываете, автоматически удваивается для учёта
        // накладных расходов ядра (служебных структур, буферов для управления данными и т.п.)
        // Это стандартное поведение, задокументированное в описании сокетов в Linux,
        // и оно работает как для TCP, так и для UDP.
        if (socket_get_option($this->_socket, SOL_SOCKET, $side) < $size * 2) {
            throw new Connection_Exception("$side size error");
        }
    }

    public function getSocketError() {
        return socket_strerror(socket_last_error($this->_socket));
    }

    protected $_socket;

}