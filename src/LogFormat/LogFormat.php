<?php

namespace NorthStack\NorthStackClient\LogFormat;

use NorthStack\NorthStackClient\LogFormat\BaseLogFormat;
use NorthStack\NorthStackClient\LogFormat\PHPErrorLogFormat;
use NorthStack\NorthStackClient\LogFormat\AccessLogFormat;

class LogFormat
{
    private static $formats = [
        'json' => BaseLogFormat::class,
        'error' => PHPErrorLogFormat::class,
        'traffic' => AccessLogFormat::class,
        'build' => PHPErrorLogFormat::class,
        'stats' => BaseLogFormat::class,
    ];

    public static function getFormat($format)
    {
        $fmt = self::$formats[$format];
        return $fmt;
    }
}
