<?php
namespace NorthStack\NorthStackClient\Client;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Psr7;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Namshi\Cuzzle\Middleware\CurlFormatterMiddleware;
use NorthStack\NorthStackClient\OauthToken;
use NorthStack\NorthStackClient\RequestChain;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class GuzzleTrait
 * @package NorthStack\NorthStackClient\Client
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

    protected $responseHandlers = [];

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

            $stack->push(
                Middleware::mapResponse(
                    function (ResponseInterface $response) {
                        return $this->handleResponse($response);
                    }
                )
            );

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
                        $logRequest = $this->sanitizeRequest($request);
                        $logger->debug($msg, [
                            'api' => $this->apiName,
                            'request' => \GuzzleHttp\Psr7\str($logRequest),
                            'uri' => $logRequest->getUri(),
                            'method' => $logRequest->getMethod()
                        ]);

                        /** @var PromiseInterface $promise */
                        $promise = $handler($request, $options);
                        return $promise->then(
                            function (ResponseInterface $response) use ($logger, $logRequest)
                            {
                                $logResponse = $this->sanitizeResponse($response);
                                $msg = 'API Response: ' .$logRequest->getMethod(). ' ' .$logRequest->getUri(). ' = ' .$logResponse->getStatusCode();
                                $context = [
                                    'api' => $this->apiName,
                                    'code' => $logResponse->getStatusCode(),
                                    'uri' => $logRequest->getUri(),
                                    'method' => $logRequest->getMethod()
                                ];
                                /** @noinspection PhpUndefinedFieldInspection */
                                if ($response->getStatusCode() >= 400) {
                                    $context = [
                                        'api' => $this->apiName,
                                        'response' => \GuzzleHttp\Psr7\str($logResponse),
                                        'request' => \GuzzleHttp\Psr7\str($logRequest),
                                        'code' => $logResponse->getStatusCode(),
                                        'uri' => $logRequest->getUri(),
                                        'method' => $logRequest->getMethod()
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

    public function setResponseHandler(int $status, callable $function)
    {
        $this->responseHandlers[$status] = $function;
    }

    public function unsetResponseHandler(int $status)
    {
        unset($this->responseHandlers[$status]);
    }

    protected function handleResponse(ResponseInterface $response)
    {
        $code = $response->getStatusCode();
        if (!empty($this->responseHandlers[$code])) {
            $response = $this->responseHandlers[$code]($response);
        }

        return $response;
    }

    protected function sanitizeRequest(RequestInterface $request)
    {
        $placeholder = 'REDACTED';
        $newRequest = clone $request;

        if ($newRequest->hasHeader('Authorization')) {
            $newRequest = $newRequest->withHeader('Authorization', $placeholder);
        }

        $rewriteParams = [
            'password',
            'client_secret',
            'mfa',
        ];

        if (preg_match('@/auth/access_token@', $newRequest->getUri())
            && $newRequest->getMethod() === 'POST'
        ) {
            $body = $newRequest->getBody()->getContents();
            parse_str($body, $params);
            foreach ($params as $key => $value) {
                if (in_array($key, $rewriteParams)) {
                    $params[$key] = $placeholder;
                }
            }

            $newRequest = $newRequest->withBody(
                Psr7\stream_for(
                    http_build_query($params)
                )
            );
        }

        return $newRequest;

    }

    protected function sanitizeResponse(ResponseInterface $response)
    {
        return $response;
    }
}
