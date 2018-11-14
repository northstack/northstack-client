<?php


namespace NorthStack\NorthStackClient\Command\Sapp\Secrets;


use NorthStack\NorthStackClient\API\Sapp\SecretsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\Sapp\SappEnvironmentTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveCommand extends Command
{
    use OauthCommandTrait;
    use SappEnvironmentTrait;

    /**
     * @var SecretsClient
     */
    protected $setecAstronomy;

    public function __construct(SecretsClient $setecAstronomy, $name = 'secrets:remove')
    {
        parent::__construct($name);
        $this
            ->setDescription('Remove Sapp Secret')
            ->addArgument('sappName', InputArgument::REQUIRED)
            ->addArgument('key', InputArgument::REQUIRED)
        ;

        $this->setecAstronomy = $setecAstronomy;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        [$id] = $this->getSappIdAndFolderByOptions($input->getArgument('sappName'),
            $input->getArgument('environment'));

        $key = $input->getArgument('key');

        $this->setecAstronomy->removeSecret(
            $this->token->token,
            $id,
            $key
        );

        $output->writeln('Secret removed!');
    }
}
