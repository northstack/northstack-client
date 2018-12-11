<?php

if (getenv('EXPOSE_HTTP_PORT'))
{
    error_log("Overriding home/siteurl");

    $scheme = $_SERVER['HTTPS_ON'] ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?: 'localhost';
    $port = getenv('EXPOSE_HTTP_PORT');

    $url = "{$scheme}://${host}:{$port}";

    define('WP_SITEURL', $url);
    define('WP_HOME', $url);
}
