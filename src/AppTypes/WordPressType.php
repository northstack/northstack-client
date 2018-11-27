<?php

namespace NorthStack\NorthStackClient\AppTypes;

use NorthStack\NorthStackClient\AppTypes\BaseType;


class WordPressType extends BaseType
{
    protected $args = [
        'wpTitle' => [
            'prompt' => 'Enter the title of the site:',
            'default' => '$appName',
        ],
        'wpAdminUser' => [
            'prompt' => 'Enter the WP admin username:',
            'default' => '$accountUsername',
        ],
        'wpAdminPass' => [
            'prompt' => 'Enter the WP admin password:',
            'default' => 'randomly generated',
            'isRandom' => true,
            'randomLen' => 16,
            'passwordInput' => true
        ],
        'wpAdminEmail' => [
            'prompt' => 'Enter the WP admin email address:',
            'default' => '$accountEmail',
        ],
        'wpIsMultisite' => [
            'prompt' => 'Is this a multi-site WP app?',
            'type' => 'bool',
            'default' => false
        ],
        'wpMultisiteSubdomains' => [
            'prompt' => 'Multi-site mode: ',
            'choices' => ['subdomain', 'subfolder'],
            'depends' => 'wpIsMultisite',
            'default' => 'subdomain'
        ],
        'wpVersion' => [
            'prompt' => 'WordPress version: ',
            'default' => '4.8'
        ]
    ];

    protected function writePerEnvBuildConfigs()
    {
        if ($this->config['wpIsMultisite']) {
            $this->config['wpMultisiteSubdomains'] = ($this->config['wpMultisiteSubdomains'] === 'subdomain');
        } else {
            $this->config['wpMultisiteSubdomains'] = false;
        }

        foreach ($this->sapps as $sapp)
        {
            $this->writeConfigFile(
                "config/{$sapp->environment}/build.json",
                $this->buildWpInstallArgs($sapp)
            );
        }

        $this->writeConfigFile(
            "config/build.json",
            [
                'image' => [
                    'name' => 'wordpress-php',
                    'version' => $this->config['wpVersion']
                ],
                'build-type' => 'builder',
                'wordpress-version' => '^'.$this->config['wpVersion']
            ]
        );

        $this->writeConfigFile(
            "config/config.json",
            [
                'app-type' => 'wordpress',
                'layout' => 'standard',
                'shared-paths' => [
                    'wp-content/uploads',
                    'wp-content/cache'
                ]
            ]
        );
    }

    protected function buildWpInstallArgs($sapp)
    {
        return [
            'wordpress-install' => [
                'url'         => $this->domainForSapp($sapp),
                'title'       => $this->config['wpTitle'],
                'admin_user'  => $this->config['wpAdminUser'],
                'admin_pass'  => $this->config['wpAdminPass'],
                'admin_email' => $this->config['wpAdminEmail'],
                'multisite'   => $this->config['wpIsMultisite'],
                'subdomains'  => $this->config['wpMultisiteSubdomains'],
            ]
        ];
    }

}
