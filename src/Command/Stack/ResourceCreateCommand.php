<?php


namespace NorthStack\NorthStackClient\Command\Stack;

use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\Infra\ResourceClient;
use NorthStack\NorthStackClient\API\Infra\StackClient;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\Helpers\OutputFormatterTrait;
use NorthStack\NorthStackClient\Command\Helpers\ValidationErrors;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\StackCommandTrait;
use NorthStack\NorthStackClient\OrgAccountHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResourceCreateCommand extends Command
{
    use OauthCommandTrait;
    use StackCommandTrait;
    use OutputFormatterTrait;
    use ValidationErrors;

    /**
     * @var OrgAccountHelper
     */
    private $orgAccountHelper;
    /**
     * @var OrgsClient
     */
    private $orgsClient;
    /**
     * @var ResourceClient
     */
    private $resourceClient;

    public function __construct(OrgsClient $orgsClient, StackClient $stackClient, ResourceClient $resourceClient, OrgAccountHelper $orgAccountHelper)
    {
        parent::__construct("stack:resource:add");
        $this->orgAccountHelper = $orgAccountHelper;
        $this->orgsClient = $orgsClient;
        $this->stackClient = $stackClient;
        $this->resourceClient = $resourceClient;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Add Resource to Stack')
            ->addArgument('stack', InputArgument::REQUIRED, 'Stack label')
            ->addArgument('type', InputArgument::REQUIRED, 'One of "RDS", "REDIS", or "SECRET"')
            ->addArgument('label', InputArgument::REQUIRED, 'Simple label for this resource (no whitespace, letters and numbers only)')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Only needed if you have access to multiple organizations');
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $orgId = $input->getOption('orgId') ?: $this->orgAccountHelper->getDefaultOrg()['id'];
        $this->requireLogin($this->orgsClient);

        $stackId = $this->getStackIdForLabel(
            $this->token->token,
            $input->getArgument('stack'),
            $orgId
        );

        try {
            $r = $this->resourceClient->createResource(
                $this->token->token,
                $stackId,
                $input->getArgument('type'),
                $input->getArgument('label')
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
