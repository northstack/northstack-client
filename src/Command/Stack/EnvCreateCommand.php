<?php


namespace NorthStack\NorthStackClient\Command\Stack;

use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\Infra\ResourceClient;
use NorthStack\NorthStackClient\API\Infra\StackClient;
use NorthStack\NorthStackClient\API\Infra\StackEnvClient;
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

class EnvCreateCommand extends Command
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

    public function __construct(
        OrgsClient $orgsClient,
        StackClient $stackClient,
        StackEnvClient $envClient,
        OrgAccountHelper $orgAccountHelper,
        ResourceClient $resourceClient
    )
    {
        parent::__construct("stack:env:add");
        $this->orgAccountHelper = $orgAccountHelper;
        $this->orgsClient = $orgsClient;
        $this->envClient = $envClient;
        $this->stackClient = $stackClient;
        $this->resourceClient = $resourceClient;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Create New Stack Environments')
            ->addArgument('stack', InputArgument::REQUIRED, 'Stack label')
            ->addArgument('label', InputArgument::REQUIRED, 'Environment label (no whitespace, only letters & numbers)')
            ->addArgument('region', InputArgument::OPTIONAL, 'AWS Region for environment (defaults to us-east-1)', 'us-east-1')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Only needed if you have access to multiple organizations')
            ->addOption('resource', 'r', InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'Resource secret values, in the format "LABEL=VALUE"')
        ;
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

        $resources = $this->resourceClient->listResources($this->token->token, $stackId);
        $resources = json_decode($resources->getBody()->getContents())->data;

        // currently only secret and domain values are needed
        $resources = array_filter($resources, function (\stdClass $resource) {
            return in_array($resource->type, ['SECRET', 'DOMAIN']);
        });
        $resourcesByLabel = [];
        foreach ($resources as $resource) {
            $resourcesByLabel[$resource->label] = $resource;
        }

        $resourceValues = [];
        $failed = false;
        foreach ($input->getOption('resource') as $resource) {
            [$label, $value] = explode('=', $resource);
            $label = trim($label);
            $value = trim($value);
            if (!$label || !$value) {
                $failed = true;
                $output->writeln("<error>Invalid resource string: {$resource} - must be formatted like 'LABEL=VALUE'</error>");
                continue;
            }

            if (!array_key_exists($label, $resourcesByLabel)) {
                $failed = true;
                $output->writeln("<error>Unknown resource label: {$label}</error>");
                continue;
            }

            $resourceValues[$label] = $value;
        }

        foreach ($resources as $resource) {
            if (!array_key_exists($resource->label, $resourceValues)) {
                $failed = true;
                $output->writeln("<error>Missing resource value for label: {$resource->label}</error>");
            }
        }

        if ($failed) {
            return;
        }

        try {
            $r = $this->envClient->createEnvironment(
                $this->token->token,
                $stackId,
                $input->getArgument('region'),
                $input->getArgument('label'),
                $resourceValues
            );
            $this->displayRecord($output, json_decode($r->getBody()->getContents(), true));
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 422) {
                $this->displayValidationErrors($e->getResponse(), $output);
            } else {
                throw $e;
            }
        }
    }
}
