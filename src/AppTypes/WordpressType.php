<?php

namespace NorthStack\AppTypes;

use NorthStack\AppTypes\BaseType;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class WordPressType extends BaseType
{
    private $args = [
        'wpTitle' => [
            'prompt' => "Enter the title of your new site",
            'default' => $this->config['appName']
        ],
        'wpAdminUser' => [
            'prompt' => 'The WP admin username',
            'default' => $this->config['accountUsername']
        ],
        'wpAdminPass' => [
            'prompt' => 'The WP admin password',
            'default' => 'randomly generated',
            'isRandom' => true,
            'randomLen' => 16,
            'passwordInput' => true
        ],
        'wpAdminEmail' => [
            'prompt' => 'The WP admin email address',
            'default' => $this->config['accountEmail']
        ]
    ];

    protected function promptForArgs($helper)
    {
        foreach ($this->args as $name => $arg)
        {
            $question = new Question($arg['prompt'], $arg['default']);
            if (array_key_exists('passwordInput', $arg) && ($arg['passwordInput'] === true))
            {
                $question->setHidden(true);
                $question->setHidddenFallback(true);
            }

            $answer = $this->askQuestion($question);

            if (
                array_key_exists('isRandom', $arg) &&
                ($arg['isRandom'] === true) &&
                ($answer === $arg['default'])
                )
            {
                $answer = bin2hex(random_bytes($arg['randomLen']));
            }

            $this->config[$name] = $answer;
        }

        $this->config['wpIsMultisite'] = false;
        $this->config['wpMultisiteSubdomains'] = false;

        $isMultiSite = new ChoiceQuestion(
            'Is this a WP multi-site?',
            [true, false],
            false
        );

        if ($this->askQuestion($isMultiSite))
        {
            $this->config['wpIsMultisite'] = true;
            $multiSiteType = new ChoiceQuestion(
                'What type of multi-site is this?',
                ['subdomain', 'subfolder'],
                'subdomain'
            );
            if ($this->askQuestion($multiSiteType) === 'subdomain')
            {
                $this->config['wpMultisiteSubdomains'] = true;
            }
        }

    }

    protected function writePerEnvBuildConfigs()
    {
        // Doesn't work yet!
        foreach ($this->sapps as $sapp) {

            switch($sapp->environment)
            {
            case 'prod':
                $build = ['wordpress-install' => $install];
                break;
            case 'test':
                $install['url'] = "http://$domain/";
                $build = ['wordpress-install' => $install];
                break;
            case 'dev':
                $install['url'] = "http://$domain/";
                $build = ['wordpress-install' => $install];
                break;
            }

            file_put_contents("{$appPath}/config/{$sapp->environment}/build.json", json_encode($build, JSON_PRETTY_PRINT));
        }


        $assetPath = dirname(__DIR__, 3).'/assets';
        copy("{$assetPath}/config.json", "{$appPath}/config/config.json");
        copy("{$assetPath}/build.json", "{$appPath}/config/build.json");
    }

    protected function buildWpInstallArgs($options, $args, OutputInterface $io)
    {
        if ($options['wpTitle'] === 'app-name') {
            $title = $args['name'];
        } else {
            $title = $args['wpTitle'];
        }


        if ($options['wpAdminUser'] === 'account-user')
        {
            // TODO grab the username of the currently logged in user
            $user = "ns-admin";
            $io->writeln("WordPress Admin User: $user\n");
        }
        else
        {
            $user = $options['wpAdminUser'];
        }

        if ($options['wpAdminPass'] === 'random-value')
        {
            $pass = bin2hex(random_bytes(16));
            $io->writeln("WordPress Admin Password: $pass\n");
        }
        else
        {
            $pass = $options['wpAdminPass'];
        }

        if ($options['wpAdminEmail'] === 'account-email')
        {
            [, $id] = explode(':',json_decode(base64_decode(explode('.', $this->token->token)[1]))->sub);
            $r = $this->orgs->getUser($this->token->token, $id);
            $currentUser = json_decode($r->getBody()->getContents());
            $email = $currentUser->email;
        }
        else
        {
            $email = $options['wpAdminEmail'];
        }

        $install = [
            'url' => $args['primaryDomain'],
            'title' => $title,
            'admin_user' => $user,
            'admin_pass' => $pass,
            'admin_email' => $email,
            'multisite' => $options['wpIsMultisite'],
            'subdomains' => $options['wpMultisiteSubdomains'],
        ];
        return $install;
    }

}
