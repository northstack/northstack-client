<?php
namespace Pagely\NorthstackClient\Client;

use Pagely\NorthstackClient\OauthToken;
use Psr\Http\Message\RequestInterface;

class BearerTokenAuthMiddleware
{
    protected $token;

    /**
     * @param OauthToken|string $token
     */
    public function __construct($token)
    {
        if (!is_object($token)) {
            $token = new OauthToken($token);
        }
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->token->token;
    }

    /**
     * Called when the middleware is handled.
     *
     * @param callable $handler
     *
     * @return \Closure
     */
    public function __invoke(callable $handler)
    {
        /**
         * @param RequestInterface $request
         * @param array $options
         * @return callable
         */
        return function ($request, array $options) use ($handler) {

            $request = $request->withHeader('Authorization', 'Bearer '.$this->token->token);

            return $handler($request, $options);
        };
    }
}
