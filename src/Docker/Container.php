<?php

namespace NorthStack\NorthStackClient\Docker;


use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\HostConfig;

class Container extends ContainersCreatePostBody
{

    public function setBindMounts(array $mounts): Container
    {
        $binds = [];
        foreach ($mounts as $mount)
        {
            $m = $mount['src'] . ':' . $mount['dest'];

            if (array_key_exists('mode', $mount))
            {
                $m .= ':' . $mount['mode'];
            }

            $binds[] = $m;
        }

        if (!isset($this->hostConfig)) {
            $this->hostConfig = new HostConfig();
        }

        $this->hostConfig->setBinds($binds);
        return $this;
    }

    public function update($config): Container
    {
        foreach ($config as $section => $value)
        {
            $method = "set{$section}";

            if (method_exists($this, $method))
            {
                $this->{$method}($value);
            } else {
                throw new \Exception("Unknown container creation config section: {$section}");
            }
        }

        return $this;
    }

    public static function allValues($obj)
    {
        $data = [];
        foreach (get_class_methods(get_class($obj)) as $m)
        {
            if (strpos($m, 'get') === 0)
            {
                $val = $obj->{$m}();
                if (is_object($val))
                {
                    $val = self::allValues($val);
                }

                $data[$m] = $val;
            }
        }

        return $data;
    }
}
