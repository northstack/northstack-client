<?php


namespace NorthStack\NorthStackClient\API\Logs;


use NorthStack\NorthStackClient\API\BaseApiClient;
use Ratchet\Client;
use Symfony\Component\Console\Output\OutputInterface;

class LogsClient extends BaseApiClient
{
    /**
     * @var callable
     */
    protected $sender;
    protected $topic;
    protected $accessToken;

    public function streamTopic(string $accessToken, callable $sender, string $topic, OutputInterface $output = null)
    {
        $this->sender = $sender;
        $this->topic = $topic;
        $this->accessToken = $accessToken;
        $uri = str_replace('http', 'ws', $this->baseUrl);
        $uri = rtrim($uri, '/');
        $uri .= '/logs';

        if ($output) {
            $output->writeln('Connecting...');
        }
        Client\connect($uri)
            ->then(
                function (Client\WebSocket $conn) use ($output) {
                    $conn->on('message', function ($message) {
                        $sender = $this->sender;
                        $sender($message);
                    });

                    if ($output) {
                        $output->writeln('Authenticating...');
                    }
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

    public function getBuildLog(string $accessToken, string $sappId)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("sapps/{$sappId}/build-logs");
    }
}
