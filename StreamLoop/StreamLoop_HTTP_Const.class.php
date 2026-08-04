<?php
final class StreamLoop_HTTP_Const {

    // states
    public const STATE_DISCONNECTED = 0;
    public const STATE_CONNECTING = 1;
    public const STATE_HANDSHAKING = 2;
    public const STATE_WAIT_FOR_RESPONSE_HEADERS = 3;
    public const STATE_WAIT_FOR_RESPONSE_BODY = 4;
    public const STATE_READY = 5;

    // errors
    public const ERROR_RESTART = -1;
    public const ERROR_EOF = 1;
    public const ERROR_HANDSHAKE = 4;
    public const ERROR_CLOSED_BY_SERVER = 7; // similar to reset by peer
    public const ERROR_TIMEOUT = 408;

}