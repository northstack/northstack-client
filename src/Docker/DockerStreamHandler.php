<?php


namespace NorthStack\NorthStackClient\Docker;

use Docker\Stream\DockerRawStream;
use Symfony\Component\Console\Output\OutputInterface;

class DockerStreamHandler
{
    protected $stream;
    protected $output;
    protected $handleSigInt;

    public static $signaled = 3;

    public function __construct(
        DockerRawStream $stream,
        OutputInterface $output,
        $handleSigInt = false
    )
    {
        $this->stream = $stream;
        $this->output = $output;
        $this->handleSigInt = $handleSigInt;
    }

    public function watch()
    {
        if ($this->handleSigInt) {
            return $this->watchForked();
        }

        $this->watchInline();
        return null;
    }

    protected function watchForked()
    {
        $intHandler = pcntl_signal_get_handler(SIGINT);
        $termHandler = pcntl_signal_get_handler(SIGTERM);

        pcntl_signal(SIGTERM, SIG_IGN);
        pcntl_signal(SIGINT, SIG_IGN);

        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new \Exception("Couldn't fork() while watching output stream");
        }

        if ($pid) {
            // pass signals through to our child proc
            $killChild = function($sig) use ($pid) {
                posix_kill($pid, $sig);
            };
            pcntl_signal(SIGTERM, $killChild);
            pcntl_signal(SIGINT, $killChild);

            $status = 0;
            $exited = false;
            while (!$exited)
            {
                pcntl_signal_dispatch();
                $wait = pcntl_waitpid($pid, $status, WNOHANG);
                switch($wait) {
                    case -1:
                        throw new \Exception("Waiting on child process failed");
                        break;
                    case $pid:
                        $exited = true;
                        break;
                    case 0:
                        sleep(0.05);
                        break;
                    default:
                        throw new \Exception("Unknown exit status for pcntl_waitpid()");
                }
            }

            pcntl_signal(SIGTERM, $intHandler);
            pcntl_signal(SIGINT, $termHandler);

            if (pcntl_wifsignaled($status)) {
                return self::$signaled;
            }

        } else {
            pcntl_signal(SIGTERM, SIG_DFL);
            pcntl_signal(SIGINT, SIG_DFL);
            $this->watchInline();
        }

        return null;
    }

    protected function watchInline()
    {
        $handler = function($out) {
            $this->output->write($out);
        };

        $this->stream->onStdout($handler);
        $this->stream->onStderr($handler);
        $this->stream->wait();
    }
}
