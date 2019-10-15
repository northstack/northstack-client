<?php


namespace NorthStack\NorthStackClient\Command\Stack\Permissions;


use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetCommand extends AbstractPermissionsCmd
{
    protected $commandLabel = 'stack:permissions:set';

    public function configure()
    {
        parent::configure();
        $this->setDescription('Set Permissions')
            ->addArgument('orgUserId', InputArgument::REQUIRED, 'User ID')
            ->addArgument('targetType', InputArgument::REQUIRED, 'Target Type (stack, environment, app, deployment)')
            ->addArgument('label', InputArgument::REQUIRED, 'Label for target or ID for deployment')
            ->addArgument('permissions', InputArgument::IS_ARRAY + InputArgument::REQUIRED, 'Permission labels')
            ->addOption('stackId', null, InputOption::VALUE_REQUIRED, 'Stack ID (required for any target type except stack)')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Only needed if you have access to multiple organizations')
        ;
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($input->getArgument('targetType')) {
            case 'stack':
                $orgId = $input->getOption('orgId') ?: $this->orgAccountHelper->getDefaultOrg()['id'];
                $targetId = $this->getStackIdForLabel(
                    $this->token->token,
                    $input->getArgument('label'),
                    $orgId
                );
                break;
            case 'environment':
                $stackId = $input->getArgument('stackId');
                if (!$stackId) {
                    $output->writeln('<error>Stack ID Required</error>');
                    return;
                }
                $targetId = $this->getEnvIdForLabel($this->token->token, $input->getArgument('label'), $stackId);
                break;
            case 'app':
                $stackId = $input->getArgument('stackId');
                if (!$stackId) {
                    $output->writeln('<error>Stack ID Required</error>');
                    return;
                }
                $targetId = $this->getAppIdForLabel(
                    $this->token->token,
                    $input->getArgument('label'),
                    $stackId
                );
                break;
            case 'deployment':
                $targetId = $input->getArgument('label');
                break;
            default:
                throw new \RuntimeException('Invalid target type: '.$input->getArgument('targetType'));
        }

        $perms = json_decode(
            $this->permissionsClient->permissionTypes()
                ->getBody()->getContents(),
            true
        );

        $labelToBit = [];
        foreach ($perms as $perm) {
            $labelToBit[$perm['label']] = $perm['bit'];
        }
        $inputPerms = $input->getArgument('permissions');
        $permissions = 0;
        $errors = false;
        foreach ($inputPerms as $perm) {
            if (!array_key_exists($perm, $labelToBit)) {
                $output->writeln('<error>Unknown permission: '.$perm.'</error>');
                $errors = true;
            } else {
                $permissions += $labelToBit[$perm];
            }
        }

        if ($errors) {
            exit(1);
        }
        try {
            $this->permissionsClient->setPermissions(
                $this->token->token,
                $input->getArgument('targetType'),
                $targetId,
                $input->getArgument('orgUserId'),
                $permissions
            );
            $output->writeln('Success');
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 422) {
                $this->displayValidationErrors($e->getResponse(), $output);
            } else {
                throw $e;
            }
        }
    }
}
