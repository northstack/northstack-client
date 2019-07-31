<?php


namespace NorthStack\NorthStackClient\Command\Stack;

use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\Infra\StackClient;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\Helpers\OutputFormatterTrait;
use NorthStack\NorthStackClient\Command\Helpers\ValidationErrors;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\OrgAccountHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StackCreateCommand extends Command
{
    use OauthCommandTrait;
    use OutputFormatterTrait;
    use ValidationErrors;

    /**
     * @var StackClient
     */
    private $stackClient;
    /**
     * @var OrgAccountHelper
     */
    private $orgAccountHelper;
    /**
     * @var OrgsClient
     */
    private $orgsClient;

    public function __construct(OrgsClient $orgsClient, StackClient $stackClient, OrgAccountHelper $orgAccountHelper)
    {
        parent::__construct("stack:create");
        $this->stackClient = $stackClient;
        $this->orgAccountHelper = $orgAccountHelper;
        $this->orgsClient = $orgsClient;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Create Stack')
            ->addArgument('type', InputArgument::REQUIRED, 'One of: "WEBSITE", "WORDPRESS", or "CUSTOM"')
            ->addArgument('label', InputArgument::REQUIRED, 'Short label, no whitespace, starting with a character, the rest can be combination of 0-9 and a-z.  The label must be globally unique!')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Only needed if you have access to multiple organizations');
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $orgId = $input->getOption('orgId') ?: $this->orgAccountHelper->getDefaultOrg()['id'];
        $label = $input->getArgument('label');
        $type = $input->getArgument('type');
        if (!in_array($type, ['WEBSITE', 'WORDPRESS', 'CUSTOM'])) {
            throw new \RuntimeException('Invalid type: '.$type);
        }

        if (!preg_match('/[a-z][0-9a-z]+/', $label)) {
            throw new \RuntimeException('Invalid label ('.$label.') - must start with a character (a-z), and contain no white space or special characters. Only lowercase letters and numbers.');
        }

        $this->requireLogin($this->orgsClient);

        try {
            $r = $this->stackClient->createStack(
                $this->token->token,
                $orgId,
                $type,
                $label
            );
            $body = json_decode($r->getBody()->getContents(), true);
            $this->displayRecord($output, $body);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 422) {
                $this->displayValidationErrors($e->getResponse(), $output);
            } else {
                throw $e;
            }
        }
    }
}
