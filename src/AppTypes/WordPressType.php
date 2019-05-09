<?php

namespace NorthStack\NorthStackClient\AppTypes;


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
        'frameworkVersion' => [
            'prompt' => 'WordPress version: ',
            'default' => '5.1'
        ]
    ];

    public function setArgsFromExistingApp($sapps)
    {
        $this->sapps = $sapps;
    }

    /**
     * @return array
     */
    public function getFrameworkConfig()
    {
        return [
                'title' => $this->config['wpTitle'],
                'admin_user' => $this->config['wpAdminUser'],
                'admin_email' => $this->config['wpAdminEmail'],
                'multisite' => $this->config['wpIsMultisite'],
                'subdomains' => $this->config['wpMultisiteSubdomains'],
        ];
    }

}
