<?php


namespace NorthStack\NorthStackClient\Command\Stack\Permissions;


use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractPermissionsCmd
{
    protected $commandLabel = 'stack:permissions:list';

    public function configure()
    {
        parent::configure();
        $this->setDescription('Show Permissions for self');
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $perms = json_decode(
            $this->permissionsClient->permissionTypes()
                ->getBody()->getContents(),
            true
        );

        $bitToLabel = [];
        foreach ($perms as $perm) {
            $bitToLabel[$perm['bit']] = $perm['label'];
        }

        try {
            $result = $this->permissionsClient->getMyPermissions(
                $this->token->token
            );
            $result = json_decode($result->getBody()->getContents(), true);
            $result = array_map(
                function($row) use ($bitToLabel) {
                    $perms = [];
                    foreach ($bitToLabel as $bit => $label) {
                        if ($row['permissions'] & $bit) {
                            $perms[] = $label;
                        }
                    }
                    $row['permissions'] = implode(', ', $perms);
                    return $row;
                },
                $result
            );

            $this->displayTable($output, $result, [
                'Type' => 'target_type',
                'ID' => 'target_id',
                'Permissions' => 'permissions',
            ]);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 422) {
                $this->displayValidationErrors($e->getResponse(), $output);
            } else {
                throw $e;
            }
        }
    }
}
