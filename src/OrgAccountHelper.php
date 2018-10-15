<?php


namespace NorthStack\NorthStackClient;


class OrgAccountHelper
{
    protected $file;

    public function __construct()
    {
        $home = getenv('HOME');
        $this->file = "{$home}/.northstackaccount.json";
    }

    public function getDefaultOrg()
    {
        if (!file_exists($this->file)) {
            throw new \Exception('Account file not found - please log in again.');
        }

        $data = json_decode(file_get_contents($this->file), true);

        if (count($data) > 1) {
            throw new \Exception('More than one org accessible. Please provide org ID');
        }

        return array_values($data)[0];
    }
}
