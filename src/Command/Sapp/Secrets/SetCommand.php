<?php


namespace NorthStack\NorthStackClient\Command\Sapp\Secrets;


use NorthStack\NorthStackClient\API\Sapp\SecretsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\Sapp\SappEnvironmentTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetCommand extends Command
{
    use OauthCommandTrait;
    use SappEnvironmentTrait;

    /**
     * @var SecretsClient
     */
    protected $setecAstronomy;

    public function __construct(SecretsClient $setecAstronomy, $name = 'secrets:set')
    {
        parent::__construct($name);
        $this
            ->setDescription('Set Sapp Secret. You can optionally pipe a value to this command')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addArgument('key', InputArgument::REQUIRED)
            ->addArgument('value', InputArgument::REQUIRED)
            ->addArgument('environment', InputArgument::OPTIONAL, 'Environment', 'dev')
            ->addOauthOptions()
        ;

        $this->setecAstronomy = $setecAstronomy;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        ['id' => $id] = $this->getSapp($input->getArgument('name'), $input->getArgument('environment'));

        $key = $input->getArgument('key');
        $value = null;
        if (!$input->hasArgument('value') || !$input->getArgument('value')) {
            // check if we piped some value
            if (0 === ftell(STDIN)) {
                $value = stream_get_contents(STDIN);
            }

            if (null === $value || '' === $value) {
                $output->writeln('<error>You must provide a value</error>');
            }
        } else {
            $value = $input->getArgument('value');
        }

        $this->setecAstronomy->setSecret(
            $this->token->token,
            $id,
            $key,
            $value
        );

        $output->writeln('Secret saved!');
    }
}
