<?php


namespace NorthStack\NorthStackClient\API\Logs;


use NorthStack\NorthStackClient\API\BaseApiClient;
use Ratchet\Client;

class LogsClient extends BaseApiClient
{
    /**
     * @var callable
     */
    protected $sender;
    protected $topic;
    protected $accessToken;

    public function streamTopic(string $accessToken, callable $sender, string $topic)
    {
        $this->sender = $sender;
        $this->topic = $topic;
        $this->accessToken = $accessToken;
        $uri = str_replace('http', 'ws', $this->baseUrl);
        $uri = rtrim($uri, '/');
        $uri .= '/logs';

        Client\connect($uri)
            ->then(
                function (Client\WebSocket $conn) {
                    $conn->on('message', function ($message) {
                        $sender = $this->sender;
                        $sender($message);
                    });

                    $conn->send(json_encode([
                        'accessToken' => $this->accessToken,
                        'action' => 'subscribe',
                        'topic' => $this->topic,
                    ]));
                },
                function ($e) {
                    echo $e;
                    throw $e;
                }
            );
    }
}
