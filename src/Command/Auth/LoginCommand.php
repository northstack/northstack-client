<?php
namespace NorthStack\NorthStackClient\Command\Auth;

use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\API\AuthApi;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Http\Message\ResponseInterface;

use NorthStack\NorthStackClient\OauthToken;
use Symfony\Component\Console\Question\Question;

class LoginCommand extends Command
{
    protected $api;
    /**
     * @var OrgsClient
     */
    private $orgsClient;

    public function __construct(AuthApi $api, OrgsClient $orgsClient)
    {
        parent::__construct('auth:login');
        $this->api = $api;
        $this->orgsClient = $orgsClient;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('Login and get save access token')
            ->addArgument('username', InputArgument::OPTIONAL)
            ->addOption('scope', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Scope keys', [])
            ->addOption('show', 's', InputOption::VALUE_NONE, 'Just show the token, do not save it!')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->setDebug(true);
        }

        $username = $input->getArgument('username');
        if (empty($username))
        {
            $question = (new Question('Username: '))
                ->setValidator(function ($answer) {
                    if (empty($answer))
                    {
                        throw new \Exception("Username cannot be empty");
                    }
                    return $answer;
                })
                ->setMaxAttempts(3);
            $helper = $this->getHelper('question');
            $username = $helper->ask($input, $output, $question);
        }

        $question = (new Question('Password: '))
            ->setHidden(true)
            ->setValidator(function ($answer) {
                if (empty($answer))
                {
                    throw new \Exception("Password cannot be empty");
                }
                return $answer;
            })
            ->setMaxAttempts(3);

        $helper = $this->getHelper('question');
        $password = $helper->ask($input, $output, $question);

        $this->api->setResponseHandler(401,
            function (ResponseInterface $response) use ($output) {
                $info = json_decode($response->getBody()->getContents());
                $output->writeln("<error>{$info->error}</error>");
                $output->writeln($info->message);
                exit(1);
            }
        );

        $r = $this->api->login(
            $username,
            $password,
            null,
            $input->getOption('scope'),
            'org'
        );

        if ($input->getOption('show')) {
            $data = json_decode($r->getBody()->getContents());
            $output->writeln(json_encode($data, JSON_PRETTY_PRINT));
        } else {
            $token = new OauthToken();
            $token->saveRaw($r->getBody()->getContents());
            $output->writeln('Logged in');
        }

        $home = getenv('HOME');
        $accountsFile = "{$home}/.northstackaccount.json";
        if (file_exists($accountsFile)) {
            $orgs = json_decode(file_get_contents($accountsFile), true);
        } else {
            $orgs = [];
        }

        $token = new OauthToken();
        try {
            $data = $this->orgsClient->listOrgs($token->token)->getBody()->getContents();
        } catch (\Throwable $e) {
            $output->writeln('<error>Could not fetch the orgs you belong to</error>');
            return;
        }

        $data = json_decode($data)->data;

        foreach ($data as $org) {
            $orgs[$org->id] = $org;
        }

        file_put_contents($accountsFile, json_encode($orgs));
    }
}
