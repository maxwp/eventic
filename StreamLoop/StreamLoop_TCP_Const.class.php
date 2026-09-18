<?php
final class StreamLoop_TCP_Const {

    // states
    public const STATE_DISCONNECTED = 0;
    public const STATE_CONNECTING = 1;
    public const STATE_HANDSHAKING = 2;
    public const STATE_READY = 200; // OK

    // errors
    public const ERROR_RESTART = -1;
    public const ERROR_CLOSED = 1;
    public const ERROR_HANDSHAKE = 4;
    public const ERROR_TIMEOUT = 408; // any timeout: including connecting, handshaking, upgrading, ...
    public const ERROR_USER = 600;

}