<?php


namespace NorthStack\NorthStackClient\Command\Sapp\Secrets;


use NorthStack\NorthStackClient\API\Sapp\SecretsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\Sapp\SappEnvironmentTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListCommand extends Command
{
    use OauthCommandTrait;
    use SappEnvironmentTrait;

    /**
     * @var SecretsClient
     */
    protected $setecAstronomy;

    public function __construct(SecretsClient $setecAstronomy, $name = 'secrets:list')
    {
        parent::__construct($name);
        $this
            ->setDescription('List Sapp Secrets')
            ->addArgument('environment', InputArgument::OPTIONAL, 'Environment', 'dev')
            ->addOption('show', null, InputOption::VALUE_NONE, 'Show Secret Values')
            ->addOauthOptions()
        ;

        $this->setecAstronomy = $setecAstronomy;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        ['id' => $id] = $this->getSappFromWorkingDir($input->getArgument('environment'));
        $result = $this->setecAstronomy->listSecrets($this->token->token, $id);
        $secrets = json_decode($result->getBody()->getContents())->data;

        $io = new SymfonyStyle($input, $output);
        $show = $input->getOption('show');

        $headers = ['Key', 'Value', 'Created', 'Updated'];
        $rows = array_map(function ($secret) use ($show) {
            return [
                'Key' => $secret->secretKey,
                'Value' => $show ? $secret->secretValue : '***********',
                'Created' => $secret->created,
                'Updated' => $secret->updated,
            ];
        }, $secrets);

        $io->table($headers, $rows);
    }
}
