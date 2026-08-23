<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2026 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

// кидать ошибку если не php8+, потому что работать не будет
if (PHP_MAJOR_VERSION < 8) {
    throw new Exception("Eventic needs PHP 8+");
}

// default locale
setlocale(LC_ALL, 'en_EN.utf8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding("UTF-8");
}

// fix for Mac OS X PHP 5.3 default
//@date_default_timezone_set(date_default_timezone_get());

//date_default_timezone_set('Europe/Kyiv');

//gc_disable();

// костыляка для Throwable и сборщика трасс (в php 8.3 отключат и так)
ini_set('zend.exception_ignore_args', 1);

// 1st
// NB! Супер важно именно Patten подключить прямым include
include_once(__DIR__.'/Pattern/Pattern_Exception.class.php');
include_once(__DIR__.'/Pattern/Pattern_ASingleton.class.php');
include_once(__DIR__.'/Pattern/Pattern_RegistryArray.class.php');
include_once(__DIR__.'/Pattern/Pattern_ARegistrySingleton.class.php');

// 2nd
// NB! Супер важно именно ClassLoader подключить прямым include
include_once(__DIR__.'/ClassLoader/ClassLoader.class.php');
include_once(__DIR__.'/ClassLoader/ClassLoader_Exception.class.php');

// 3rd: others
ClassLoader::Get()->registerClassArray([
    __DIR__.'/File/File.class.php',
    __DIR__.'/File/File_Exception.class.php',

    __DIR__.'/Validator/Validator.class.php',

    __DIR__.'/Connection/Connection.class.php',
    __DIR__.'/Connection/Connection_IConnection.class.php',
    __DIR__.'/Connection/Connection_IDatabaseAdapter.class.php',
    __DIR__.'/Connection/Connection_Exception.class.php',
    __DIR__.'/Connection/Connection_MySQLi.class.php',
    __DIR__.'/Connection/Connection_RDS.class.php',
    __DIR__.'/Connection/Connection_PDO.class.php',
    __DIR__.'/Connection/Connection_Redis.class.php',
    __DIR__.'/Connection/Connection_Memcached.class.php',
    __DIR__.'/Connection/Connection_Socket_IReceiver.interface.php',
    __DIR__.'/Connection/Connection_Socket_Abstract.class.php',
    __DIR__.'/Connection/Connection_SocketStream.class.php',
    __DIR__.'/Connection/Connection_SocketUDP.class.php',
    __DIR__.'/Connection/Connection_SocketUDPConnected.class.php',
    __DIR__.'/Connection/Connection_SocketUDS.class.php',

    __DIR__.'/DateTime/DateTime_IClassFormat.class.php',
    __DIR__.'/DateTime/DateTime_ClassFormatDefault.class.php',
    __DIR__.'/DateTime/DateTime_ClassFormatPhonetic.class.php',
    __DIR__.'/DateTime/DateTime_ClassFormatPhoneticFuture.class.php',
    __DIR__.'/DateTime/DateTime_Object.class.php',
    __DIR__.'/DateTime/DateTime_Differ.class.php',
    __DIR__.'/DateTime/DateTime_Formatter.class.php',
    __DIR__.'/DateTime/DateTime_Translate.class.php',

    __DIR__.'/Events/Events_Abstract.class.php',
    __DIR__.'/Events/Events_Exception.class.php',

    __DIR__.'/EE/EE_Exception.class.php',
    __DIR__.'/EE/EE_Typing.class.php',
    __DIR__.'/EE/EE.class.php',
    __DIR__.'/EE/EE_Call.class.php',
    __DIR__.'/EE/EE_Request_Interface.interface.php',
    __DIR__.'/EE/EE_Content_Interface.interface.php',
    __DIR__.'/EE/EE_Routing_Interface.interface.php',
    __DIR__.'/EE/EE_Request_Cli.class.php',
    __DIR__.'/EE/EE_Request_Array.class.php',
    __DIR__.'/EE/EE_Routing_Cli.class.php',
    __DIR__.'/EE/EE_Content_Abstract.class.php',
    __DIR__.'/EE/EE_Content_Abstract_Cli.class.php',

    __DIR__.'/Cli/Cli.class.php',

    __DIR__.'/TextProcessor.class.php',
    __DIR__.'/TextProcessor/TextProcessor_Exception.class.php',
    __DIR__.'/TextProcessor/TextProcessor_IAction.class.php',
    __DIR__.'/TextProcessor/TextProcessor_ActionContentFromURL.class.php',
    __DIR__.'/TextProcessor/TextProcessor_ActionTidy.class.php',
    __DIR__.'/TextProcessor/TextProcessor_ActionPregMatch.class.php',
    __DIR__.'/TextProcessor/TextProcessor_ActionPregReplace.class.php',
    __DIR__.'/TextProcessor/TextProcessor_ActionIconv.class.php',
    __DIR__.'/TextProcessor/TextProcessor_ActionCSSClear.class.php',
    __DIR__.'/TextProcessor/TextProcessor_ActionHTMLClear.class.php',
    __DIR__.'/TextProcessor/TextProcessor_ActionHTMLTagsClear.class.php',
    __DIR__.'/TextProcessor/TextProcessor_ActionHTMLTagsRemove.class.php',
    __DIR__.'/TextProcessor/TextProcessor_ActionCSSCompress.class.php',
    __DIR__.'/TextProcessor/TextProcessor_ActionTextToHTML.class.php',

    __DIR__.'/StringUtils/StringUtils_Converter.class.php',
    __DIR__.'/StringUtils/StringUtils_Transliterate.class.php',
    __DIR__.'/StringUtils/StringUtils_SimilarText.class.php',
    __DIR__.'/StringUtils/StringUtils_Orthographic.class.php',
    __DIR__.'/StringUtils/StringUtils_BadLanguageDetector.class.php',
    __DIR__.'/StringUtils/StringUtils_Limiter.class.php',
    __DIR__.'/StringUtils/StringUtils_Punycode.class.php',
    __DIR__.'/StringUtils/StringUtils_AFormatter.class.php',
    __DIR__.'/StringUtils/StringUtils_FormatterPhoneClear.class.php',
    __DIR__.'/StringUtils/StringUtils_FormatterPhoneDefault.class.php',
    __DIR__.'/StringUtils/StringUtils_FormatterPhoneUACN.class.php',
    __DIR__.'/StringUtils/StringUtils_FormatterAddressUACN.class.php',
    __DIR__.'/StringUtils/StringUtils_FormatterURL.class.php',
    __DIR__.'/StringUtils/StringUtils_MD5.class.php',
    __DIR__.'/StringUtils/StringUtils_Exception.class.php',

    __DIR__.'/Array/Array_Object.class.php',
    __DIR__.'/Array/Array_Static.class.php',

    __DIR__.'/MA/MA_Interface.class.php',
    __DIR__.'/MA/MA_PrevValue.class.php',
    __DIR__.'/MA/MA_ContinuousEMA.class.php',

    __DIR__.'/Cron/Cron.class.php',
    __DIR__.'/Cron/Cron_Clear.class.php',

    __DIR__.'/IPC/IPC.class.php',
    __DIR__.'/IPC/IPC_Addressing.class.php',
    __DIR__.'/IPC/IPC_Semaphore.class.php',
    __DIR__.'/IPC/IPC_Memory.class.php',

    __DIR__.'/Storage/Storage.class.php',
    __DIR__.'/Storage/Storage_Exception.class.php',
    __DIR__.'/Storage/Storage_IHandler.class.php',
    __DIR__.'/Storage/Storage_Array.class.php',
    __DIR__.'/Storage/Storage_Memcached.class.php',
    //__DIR__.'/Storage/Storage_MemSock.class.php', // @todo
    __DIR__.'/Storage/Storage_Redis.class.php',
    __DIR__.'/Storage/Storage_Shmop.class.php',

    __DIR__.'/StreamLoop/StreamLoop.class.php',
    __DIR__.'/StreamLoop/StreamLoop_Exception.class.php',
    __DIR__.'/StreamLoop/StreamLoop_Handler_Abstract.class.php',
    __DIR__.'/StreamLoop/StreamLoop_TCP_Abstract.class.php',
    __DIR__.'/StreamLoop/StreamLoop_HTTP_Abstract.class.php',
    __DIR__.'/StreamLoop/StreamLoop_UDP_Abstract.class.php',
    __DIR__.'/StreamLoop/StreamLoop_UDP_Drain_Abstract.class.php',
    __DIR__.'/StreamLoop/StreamLoop_UDP_DrainForward_Abstract.class.php',
    __DIR__.'/StreamLoop/StreamLoop_UDP_DrainBackward_Abstract.class.php',
    __DIR__.'/StreamLoop/StreamLoop_WebSocket_Abstract.class.php',
    __DIR__.'/StreamLoop/StreamLoop_Timer_Abstract.class.php',
    __DIR__.'/StreamLoop/StreamLoop_GC.class.php',

    __DIR__.'/Benchmark/Benchmark_Interface.interface.php',
    __DIR__.'/Benchmark/Benchmark_Stub.class.php',
    __DIR__.'/Benchmark/Benchmark.class.php',
    __DIR__.'/Benchmark/Benchmark_call.class.php',
    __DIR__.'/Benchmark/Benchmark_json.class.php',
    __DIR__.'/Benchmark/Benchmark_math.class.php',
    __DIR__.'/Benchmark/Benchmark_microtime.class.php',
    __DIR__.'/Benchmark/Benchmark_rand.class.php',
    __DIR__.'/Benchmark/Benchmark_socket.class.php',
    __DIR__.'/Benchmark/Benchmark_socket_reader.class.php',
    __DIR__.'/Benchmark/Benchmark_All.class.php',

    __DIR__.'/ImageProcessor/ImageProcessor.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_Action.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionToPNG.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionToJPEG.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionResizeCrop.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionResizeProportional.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionBlurGaussian.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionNegate.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionGrayscale.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionBrightness.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionContrast.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionColorize.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionEdgeDetect.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionEmboss.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionSmooth.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionPixelate.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionSharpen.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionRoundCorners.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionGammaCorrect.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionCut.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_Thumber.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ThumberStorage.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_Exception.class.php',
    __DIR__.'/ImageProcessor/ImageProcessor_ActionWatermarkPNG.class.php',

    __DIR__.'/TelegramAPI/TelegramAPI.class.php',

    __DIR__.'/SuperVisor/SuperVisor.class.php',
    __DIR__.'/SuperVisor/SuperRun.class.php',
    __DIR__.'/SuperVisor/SuperDebug.class.php',

    __DIR__.'/Cmd/Cmd.class.php',
    __DIR__.'/Cmd/Cmd_Sender.class.php',
    __DIR__.'/Cmd/Cmd_Receiver.class.php',
]);

/*ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_Letter.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_Smarty.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_ISender.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_SenderMail.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_SenderSMTP.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_SenderQueDB.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_SMTP.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/MailQue/MailQue_Exception.class.php');*/

//ClassLoader::Get()->registerClass(__DIR__.'/SQLBuilder/SQLBuilder.class.php');
//ClassLoader::Get()->registerClass(__DIR__.'/SQLBuilder/SQLBuilder_Exception.class.php');
//ClassLoader::Get()->registerClass(__DIR__.'/SQLBuilder/SQLBuilder_String.class.php');
//ClassLoader::Get()->registerClass(__DIR__.'/SQLBuilder/SQLBuilder_AQuery.class.php');
//ClassLoader::Get()->registerClass(__DIR__.'/SQLBuilder/SQLBuilder_Select.class.php');

include __DIR__.'/StreamLoop/StreamLoop_HTTP_Const.class.php';
include __DIR__.'/StreamLoop/StreamLoop_WebSocket_Const.class.php';

include_once __DIR__.'/StringUtils/StringUtils_FormatterPrice.class.php'; // no autoload for static classes, performance
include_once __DIR__.'/StringUtils/StringUtils_FormatterTimestamp.class.php'; // no autoload for static classes, performance
