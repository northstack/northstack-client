<?php

namespace NorthStack\NorthStackClient\LogFormat;

interface LogFormatInterface
{
    public function __construct($io);
    public function render($message);
}
