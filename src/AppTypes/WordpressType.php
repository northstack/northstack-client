<?php

namespace NorthStack\AppTypes;

use NorthStack\AppTypes\BaseType;

class WordPressType extends BaseType
{
    protected function promptForArgs($helper)
    {
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
