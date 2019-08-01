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
    protected $timeout;
    protected $startDelta;

    /**
     * @deprecated see streamLog
     */
    public function streamTopic(string $accessToken, callable $sender, array $topic, OutputInterface $output = null)
    {
        $this->streamLog($accessToken, $sender, $topic, null, 0, $output);
    }

    public function getBuildLog(string $accessToken, string $sappId)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("sapps/{$sappId}/build-logs");
    }

    /**
     * @var $timeout in seconds, 0 means no timeout
     */
    public function streamLog(string $accessToken, callable $sender, array $topic, string $startDelta = null, int $timeout = 0, OutputInterface $output = null)
    {
        $this->topic = $topic;
        $this->sender = $sender;
        $this->accessToken = $accessToken;
        $this->timeout = $timeout;
        $this->startDelta = $startDelta;
        $output = function($msg) use($output) {
            if ($output !== null) {
                $output->writeln($msg);
            }
        };

        $uri = str_replace('http', 'ws', $this->baseUrl);
        $uri = rtrim($uri, '/');
        $uri .= '/logs';

        $loop = \React\EventLoop\Factory::create();
        $reactConnector = new \React\Socket\Connector($loop);
        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        $connector($uri)
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($loop, $output) {

                if ($this->timeout > 0) {
                    $loop->addTimer($this->timeout, function() use($conn, $output) {
                        $output("Reached timeout");
                        $conn->close();
                    });
                }

                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $msg) {
                    $sender = $this->sender;
                    $sender($msg);
                });

                $conn->on('close', function($code = null, $reason = null) use ($output) {
                    $output("Connection closed ({$code} - {$reason})");
                });

                $conn->send(json_encode([
                    'accessToken' => $this->accessToken,
                    'action' => 'subscribe',
                    'startDelta' => $this->startDelta,
                    'topic' => $this->topic,
                ]));
            }, function(\Exception $e) use ($loop) {
                $loop->stop();
                throw new \Exception("Failed to connect: {$e->getMessage()}");
            });

        $loop->run();
    }
}
