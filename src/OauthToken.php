<?php
namespace Pagely\NorthstackClient;


class OauthToken
{
    public $token;

    public function __construct($token = null)
    {
        if (empty($token)) {
            $this->loadTokens();
        }
        else
        {
            $this->token = $token;
        }

    }

    protected function getLoginFile()
    {
        $home = getenv('HOME');
        if (getenv('STAGE') && !getenv('PROD_OVERRIDE')) {
            return "{$home}/.northstacklogin-staging";
        }
        return "{$home}/.northstacklogin";
    }

    protected function getClientLoginFile()
    {
        $home = getenv('HOME');
        if (getenv('STAGE') && !getenv('PROD_OVERRIDE')) {
            return "{$home}/.northstackclientlogin-staging";
        }
        return "{$home}/.northstackclientlogin";
    }

    public function loadTokens()
    {
        // check for saved tokens
        $tokenFiles = [
            $this->getLoginFile(),
            $this->getClientLoginFile()
        ];
        foreach ($tokenFiles as $file) {
            if (file_exists($file)) {
                $data = json_decode(file_get_contents($file));
                $this->token = $data->access_token ?? null;
                break;
            }
        }
    }

    public function updateFromResponse($data)
    {
        $data = (array)$data;
        $this->token = $data['access_token'] ?? null;
    }

    public function saveRaw($raw, $type='admin')
    {
        switch ($type) {
            case 'admin':
                $file = $this->getLoginFile();
                break;
            case 'client':
                $file = $this->getClientLoginFile();
                break;
            default:
                throw new \Exception('Bad login type: '.$type);
        }
        $f = fopen($file, 'w');
        fwrite($f, $raw);
        fclose($f);

        $this->updateFromResponse(json_decode($raw, true));
    }

    public function deleteSaved()
    {
        $tokenFiles = [
            $this->getLoginFile(),
            $this->getClientLoginFile()
        ];

        foreach ($tokenFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
