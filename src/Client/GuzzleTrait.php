<?php
namespace Pagely\NorthstackClient\Client;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Namshi\Cuzzle\Middleware\CurlFormatterMiddleware;
use Pagely\NorthstackClient\OauthToken;
use Pagely\NorthstackClient\RequestChain;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class GuzzleTrait
 * @package Pagely\NorthstackClient\Client
 * @property string apiName
 */
trait GuzzleTrait
{
    /**
     * @var RequestChain
     */
    protected $requestChain;
    protected $debug = false;
    protected $baseUrl;
    protected $guzzleClient;
    protected $allowExceptions = true;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var BearerTokenAuthMiddleware[]
     */
    protected $bearerTokenMiddleware = [];
    protected $maxRetries = 10;

    public function setExceptions($bool)
    {
        $this->allowExceptions = $bool;
    }

    /**
     * @param bool|callable $middleware
     * @param array $options
     * @return Client
     */
    public function guzzle($middleware = false, array $options = [])
    {
        if (empty($this->guzzleClient)) {

            $stack = $stack = HandlerStack::create();
            $stack->push(
                Middleware::retry(
                    function ($retries,
                        /** @noinspection PhpUnusedParameterInspection */
                        Request $request, Response $response = null, $exception = null) {
                        if ($exception) {
                            return false;
                        }
                        if ($response && $response instanceof Response && $response->getStatusCode() !== 502) {
                            return false;
                        }
                        return $retries < $this->maxRetries;
                    }
                )
            );
            if ($middleware) {
                $stack->push($middleware);
            }

            $this->setupGuzzleDebug($stack);

            $options = array_merge([
                'handler' => $stack,
                'base_uri' => $this->baseUrl,
            ], $options);

            if (!$this->allowExceptions) {
                $options[RequestOptions::HTTP_ERRORS] = false;
            }

            if (!isset($options['headers'])) {
                $options['headers'] = [];
            }

            if ($requestId = $this->requestChain->getRequestId()) {
                $options['headers']['X-Request-Id'] = $requestId[0];
            }
            if ($requestChain = $this->requestChain->getRequestChain()) {
                $options['headers']['X-Request-Chain'] = $requestChain[0];
            }

            $this->guzzleClient = new Client($options);
        }
        return $this->guzzleClient;
    }

    public function setDebug($enabled = true)
    {
        $this->debug = $enabled;
    }

    protected function setupGuzzleDebug(HandlerStack $stack)
    {
        if ($this->debug || getenv('GUZZLE_DEBUG'))
        {
            $logger = new Logger('guzzle');
            $handler = new StreamHandler('php://stderr');
            $formatter = new LineFormatter("%message%\n", null, true);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
            $stack->push(new CurlFormatterMiddleware($logger));
        }
        $env = getenv('LOG_API') ?: getenv('ENVIRONMENT') ?: 'development';
        if (($env === 'YES' || $env === 'development') && $this->logger)
        {
            $logger = $this->logger;
            $stack->push(
                function(callable $handler) use ($logger)
                {
                    return function (RequestInterface $request, array $options)
                    use ($logger, $handler)
                    {
                        $msg = 'API Request: ' .$request->getMethod(). ' ' .$request->getUri();
                        $logger->debug($msg, [
                            'api' => $this->apiName,
                            'request' => \GuzzleHttp\Psr7\str($request),
                            'uri' => $request->getUri(),
                            'method' => $request->getMethod()
                        ]);

                        /** @var PromiseInterface $promise */
                        $promise = $handler($request, $options);
                        return $promise->then(
                            function (ResponseInterface $response) use ($logger, $request)
                            {
                                $msg = 'API Response: ' .$request->getMethod(). ' ' .$request->getUri(). ' = ' .$response->getStatusCode();
                                $context = [
                                    'api' => $this->apiName,
                                    'code' => $response->getStatusCode(),
                                    'uri' => $request->getUri(),
                                    'method' => $request->getMethod()
                                ];
                                /** @noinspection PhpUndefinedFieldInspection */
                                if ($response->getStatusCode() >= 400) {
                                    $context = [
                                        'api' => $this->apiName,
                                        'response' => \GuzzleHttp\Psr7\str($response),
                                        'request' => \GuzzleHttp\Psr7\str($request),
                                        'code' => $response->getStatusCode(),
                                        'uri' => $request->getUri(),
                                        'method' => $request->getMethod()
                                    ];
                                    $response->getBody()->rewind();
                                }
                                $logger->debug($msg, $context);
                                return $response;
                            }
                        );
                    };
                }
            );
        }
    }

    protected function getBearerTokenMiddleware($accessToken)
    {
        if ($accessToken instanceof OauthToken) {
            $accessToken = $accessToken->token;
        }
        if (!isset($this->bearerTokenMiddleware[$accessToken])) {
            $this->bearerTokenMiddleware[$accessToken] = new BearerTokenAuthMiddleware($accessToken);
        }

        return $this->bearerTokenMiddleware[$accessToken];
    }
}
