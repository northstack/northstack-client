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
            if (array_key_exists('default', $data))
            {
                return $data[$data['default']];
            }
            throw new \Exception('More than one org accessible, and no default is set. Please provide org ID');
        }

        return array_values($data)[0];
    }
}
