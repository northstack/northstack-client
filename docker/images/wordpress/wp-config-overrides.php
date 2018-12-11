<?php

if (getenv('EXPOSE_HTTP_PORT'))
{
    $scheme = $_SERVER['HTTPS_ON'] ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?: 'localhost';
    [$host, $_] = explode(':', $host, 2);
    $port = getenv('EXPOSE_HTTP_PORT');

    $url = "{$scheme}://${host}:{$port}";

    define('WP_SITEURL', $url);
    define('WP_HOME', $url);
}
