<?php
namespace NorthStack\NorthStackClient\Command\Auth;

use NorthStack\NorthStackClient\Command\Command;
use GuzzleHttp\Exception\BadResponseException;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

class WhoAmICommand extends Command
{
    use OauthCommandTrait;
    protected $api;

    public function __construct(OrgsClient $api)
    {
        parent::__construct('auth:whoami');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('Details about the currently logged in user')
        ;
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->setDebug(true);
        }

        $io = new SymfonyStyle($input, $output);
        if (!empty($this->token->token))
        {
            $parts = explode('.', $this->token->token);
            [$type, $id] = explode(':',json_decode(base64_decode($parts[1]))->sub);

            $io->writeLn("current logged in as $type:$id");

            if ($type == 'Pagely.Model.Orgs.OrgUser')
            {
                $r = $this->api->getUser($id);
                $user = json_decode($r->getBody()->getContents());
                print_r($user);
            }
        }



    }
}
