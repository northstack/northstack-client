<?php
namespace NorthStack\NorthStackClient;

use Auryn\Injector;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use NorthStack\NorthStackClient\API\AuthApi;


class Helper
{
    public function apiClient($className)
    {
        $injector = new Injector();
        $handlers = [
            $injector->make(\Monolog\Handler\NullHandler::class)
        ];
        $injector
            ->alias(\Psr\Log\LoggerInterface::class, \Monolog\Logger::class)
            ->define(\Monolog\Logger::class, [':name' => 'CLI', ':handlers' => $handlers])
            ->share(\Psr\Log\LoggerInterface::class)
            ->define($className, [
                ':baseUrl' => getenv('MGMT_API_URL') ?: 'https://mgmt.pagely.com/api/'
            ]);

        return $injector->make($className);
    }

    public function cachedClientAuth(
        $reauth = false,
        $config = '/srv/pagely/conf/mgmt-client.conf',
        $cache = null
    )
    {
        if ($cache === null) {
            $cachefile = getenv('STAGE') && !getenv('PROD_OVERRIDE') ? '.northstack-staging.auth' : '.northstack.auth';
            $cache = getenv('HOME')."/{$cachefile}";
        }

        if (file_exists($config))
        {
            $dotenv = new Dotenv(dirname($config), basename($config));
            $dotenv->load();
        }

        if (!$reauth && file_exists($cache) && is_readable($cache) && filemtime($cache) > time()-(12*60*60))
        {
            return json_decode(file_get_contents($cache));
        }

        if (file_exists('/srv/pagely/conf/mgmt-client.conf'))
        {
            $dotenv = new Dotenv('/srv/pagely/conf', 'mgmt-client.conf');
            $dotenv->load();
        }
        $clientId = getenv('PAGELY_CLIENT_ID');
        $clientSecret = getenv('PAGELY_CLIENT_SECRET');

        $auth = $this->ApiClient(AuthApi::class);
        $token = json_decode($auth->clientLogin($clientId, $clientSecret)->getBody()->getContents());
        file_put_contents($cache, json_encode($token));

        return $token;
    }

    public function configureInjector(Injector $injector)
    {

        $injector
            ->share(\Psr\Log\LoggerInterface::class)
            ->delegate(\Psr\Log\LoggerInterface::class, function()
            {
                $logger = new Logger('CLI');
                if (getenv('DEBUG') != 1)
                {
                    $handler = new NullHandler();
                    $logger->pushHandler($handler);
                }

                return $logger;
            });
    }

    public function loadConfig($baseDir)
    {
        $staging = getenv('STAGE') && !getenv('PROD_OVERRIDE');
        try
        {
            $home = getenv('HOME');
            $dotenv = new Dotenv($home, $staging ? '/.northstack-staging' : '/.northstack');
            $dotenv->load();
        }
        catch (InvalidPathException $e)
        {
            // just ignore this we don't care if you don't have a .env file
        }

        try
        {
            $dotenv = new Dotenv($baseDir, $staging ? '/.env-staging' : '/.env');
            $dotenv->load();
        }
        catch (InvalidPathException $e)
        {
            // just ignore this we don't care if you don't have a .env file
        }

        // doesn't overwrite so defaults get loaded last
        $dotenv = new Dotenv($baseDir, $staging ? '/.env.staging' : '/.env.defaults');
        $dotenv->load();
    }
}
