<?php


namespace NorthStack\NorthStackClient\Command\Sapp\Stats;


use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\Sapp\SappEnvironmentTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AccessCommand extends Command
{
    use OauthCommandTrait;
    use SappEnvironmentTrait;


    /**
     * @var SappClient
     */
    private $sappClient;

    public function __construct(SappClient $sappClient, $name = 'app:stats:access')
    {
        parent::__construct($name);
        $this
            ->setDescription('Get HTTP access metrics')
            ->addArgument('environment', InputArgument::REQUIRED, 'Environment (prod, test, or dev)')
            ->addArgument('start', InputArgument::REQUIRED)
            ->addArgument('end', InputArgument::REQUIRED)
            ->addOauthOptions()
        ;

        $this->sappClient = $sappClient;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        ['id' => $id] = $this->getSappFromWorkingDir($input->getArgument('environment'));

        $start = new \DateTimeImmutable($input->getArgument('start'));
        $end = new \DateTimeImmutable($input->getArgument('end'));

        $r = $this->sappClient->accessStats($this->token->token, $id, $start, $end);

        $data = json_decode($r->getBody()->getContents());

        $output->writeln(json_encode($data, JSON_PRETTY_PRINT));
    }
}
