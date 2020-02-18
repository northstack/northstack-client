<?php

namespace NorthStack\NorthStackClient\AppTypes;

use NorthStack\NorthStackClient\UserInput\BasicInput;
use NorthStack\NorthStackClient\UserInput\ChoiceInput;
use NorthStack\NorthStackClient\UserInput\PasswordInput;
use NorthStack\NorthStackClient\UserInput\BoolInput;


class WordPressType extends BaseType
{
    public function setArgsFromExistingApp($sapps)
    {
        $this->sapps = $sapps;
    }

    public function getFrameworkVersion()
    {
        return $this->config['frameworkVersion'];
    }

    /**
     * @return array
     */
    public function getFrameworkConfig()
    {
        $config = [
            'title' => $this->config['wpTitle'],
            'admin_user' => $this->config['wpAdminUser'],
            'admin_email' => $this->config['wpAdminEmail'],
            'multisite' => $this->config['wpIsMultisite'],
        ];

        if ($config['multisite']) {
            $config['subdomains'] = !empty($this->config['wpMultisiteSubdomains']) ? $this->config['wpMultisiteSubdomains'] : 'subdomain';
        }

        return $config;
    }

    public static function getArgs()
    {
        return [
            new BasicInput(
                'wpTitle',
                'The title for this WordPress app',
                '$appName'
            ),
            new BasicInput(
                'wpAdminUser',
                'The WordPress admin username',
                '$accountUsername'
            ),
            new PasswordInput(
                'wpAdminPass',
                'The WordPress admin password',
                16
            ),
            new BasicInput(
                'wpAdminEmail',
                'The WordPress admin email address',
                '$accountEmail'
            ),
            new BoolInput(
                'wpIsMultisite',
                'Is this a multi-site WordPress app?',
                false
            ),
            (new ChoiceInput(
                'wpMultisiteSubdomains',
                'The WordPress multi-site mode',
                'subdomain'
            ))->setChoices(['subdomain', 'subfolder']),
            new BasicInput(
                'frameworkVersion',
                'The WordPress version',
                '5.1'
            )
        ];

    }

}
