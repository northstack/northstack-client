<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use NorthStack\NorthStackClient\Command\UserSettingsCommandTrait;
use NorthStack\NorthStackClient\UserSettingsHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\HelpCommand;

trait CommandFetchAppTrait
{
    use UserSettingsCommandTrait;
    protected static $directories = [
        'config',
        'scripts',
        'app',
        'app/public'
    ];
    protected static $perEnvDirectories = [
        'config/{{env}}'
    ];
    /**
     * Supported environment files. All of these with the exception of `domains` can override
     * the related "shared" App-level field.
     *
     * @var array An array of the Sapp field matched to its config filename
     */
    protected static $envFiles = [
        'configBuild' => 'build.json',
        'config' => 'config.json',
        'domains' => 'domains.json',
        'configGatewayCust' => 'gateway.json',
    ];
    public $app;
    public $appPath;
    protected $input;
    protected $output;

    protected function getLocalAppSlug($name)
    {
        return preg_replace('/[^A-Za-z0-9\-\_]+/', '', $name);
    }

    protected function getLocalAppDir(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper, $appSlug)
    {
        $nsdir = $this->findDefaultAppsDir($input, $output, $questionHelper);

        $this->appPath = "{$nsdir}/{$appSlug}";

        if (file_exists($this->appPath)) {
            $output->writeln("Folder for app {$appSlug} already exists at {$this->appPath}");
            return false;
        }

        $localApps = UserSettingsHelper::get(UserSettingsHelper::KEY_LOCAL_APPS_MAP);
        if (!empty($localApps[$appSlug])) {
            $askOverwrite = new Question(
                'An app by that slug was found in your local user settings map. Do you want to overwrite that app? (Y/n): ',
                'Y');
            $overwriteMap = strtolower($questionHelper->ask($input, $output, $askOverwrite));

            if ('n' === $overwriteMap || 'no' === $overwriteMap) {
                return false;
            }
        }

        return $this->appPath;
    }

    protected function setupLocalApp(InputInterface $input, OutputInterface $output, $app, $appSlug, $appPath)
    {
        $this->app = $app;
        $this->appPath = $appPath;
        $this->input = $input;
        $this->output = $output;

        $localApps = UserSettingsHelper::get(UserSettingsHelper::KEY_LOCAL_APPS_MAP) ?: [];
        $localApps[$appSlug] = ['id' => $app->id, 'orgId' => $app->orgId, 'stack' => $app->appType, 'localPath' => $this->appPath];
        UserSettingsHelper::updateSetting(UserSettingsHelper::KEY_LOCAL_APPS_MAP, $localApps);
        try {
            $this->createSkeleton();
            $this->writeEnvironmentFile();
            $this->writePerEnvConfigs();
            $this->writeSharedConfigFiles();
        } catch (\Throwable $e) {
            // TODO: clean up any directories or files that were created

            // remove the app from the local apps map
            unset($localApps[$appSlug]);
            UserSettingsHelper::updateSetting(UserSettingsHelper::KEY_LOCAL_APPS_MAP, $localApps);

            // TODO: show user --help for app:fetch since there was an error just in creating local files
            $output->writeln($e->getMessage());
            throw $e;
        }
    }

    protected function createSkeleton()
    {
        $this->mkdirRecursive(self::$directories);
        $paths = [];
        foreach (self::$perEnvDirectories as $dir) {
            foreach ($this->app->sapps as $sapp) {
                $paths[] = str_replace('{{env}}', $sapp->environment, $dir);
            }
            $paths[] = str_replace('{{env}}', 'local', $dir);
        }
        $this->mkdirRecursive($paths);
    }

    protected function mkdirRecursive(array $paths)
    {
        foreach ($paths as $path) {
            $mkPath = $this->appPath . '/' . $path;
            if (!file_exists($mkPath)) {
                $this->output->writeln("Creating directory {$mkPath}");
                if (!mkdir($mkPath, 0775, true) && !is_dir($mkPath)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $mkPath));
                }
            }
        }
    }

    protected function writeEnvironmentFile()
    {
        $env = [];
        foreach ($this->app->sapps as $sapp) {
            $env[$sapp->environment] = $sapp->id;
        }
        $this->writeConfigFile('config/environment.json', $env);
    }

    /**
     * @param string $path
     * @param object|array $data
     */
    protected function writeConfigFile(string $path, $data)
    {
        $path = $this->appPath . '/' . $path;
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    protected function writePerEnvConfigs()
    {
        foreach ($this->app->sapps as $sapp) {
            foreach (self::$envFiles as $sappKey => $filename) {
                $this->writeConfigFile(
                    "config/{$sapp->environment}/{$filename}",
                    $sapp->{$sappKey}
                );
            }
        }
    }

    protected function writeSharedConfigFiles()
    {
        foreach ([
                     'shared-build.json' => $this->app->sharedConfigBuild,
                     'shared-config.json' => $this->app->sharedConfig,
                     'shared-gateway.json' => $this->app->sharedConfigGatewaySys,
                 ] as $filename => $data) {
            if (!$data) {
                continue;
            }

            $this->writeConfigFile('config/' . $filename, $data);
        }
    }

    function printSuccess(InputInterface $input, OutputInterface $output, $app, string $appSlug, string $appPath, $includeDeployHelp = false)
    {
        $io = new SymfonyStyle($input, $output);

        $io->table([
            [new TableCell($app->appName, ['colspan' => 2])],
        ], [
            ['App ID', $app->id],
            ['Local slug', $appSlug],
            ['Local directory', $appPath],
        ]);
        foreach ($app->sapps as $sapp) {
            $headers = [
                [new TableCell($sapp->name . ' (' . $sapp->environment . ')', ['colspan' => 2])],
            ];

            $rows = [
                ['ID', $sapp->id],
                ['Environment', $sapp->environment],
                ['Internal URL', $sapp->internalUrl],
                ['Primary Domain', $sapp->primaryDomain],
            ];

            $io->table($headers, $rows);
        }

        $io->writeln("Paths:");
        $io->table(
            ['location', 'path'],
            [
                ['root', $appPath],
                ['code', "{$appPath}/app"],
                ['webroot', "{$appPath}/app/public"],
                ['configuration', "{$appPath}/config"]
            ]
        );

        if ($includeDeployHelp) {
            $io->newLine();
            $io->note("Your app isn't live until you create and deploy your first release! Use the `app:deploy` command for that:");
            $io->newLine();
            $io->writeln("$ northstack app:deploy --help\n");

            $help = new HelpCommand();
            $deploy = $this->getApplication()->find('app:deploy');
            $help->setCommand($deploy);
            $help->run($input, $output);
        }
    }
}
