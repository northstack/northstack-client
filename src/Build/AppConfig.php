<?php


namespace NorthStack\NorthStackClient\Build;


use Equip\Data\Traits\EntityTrait;

class AppConfig
{
    use EntityTrait;
    public $app_type;
    public $framework_version;
    public $layout;
    public $shared_paths = [];
    public $environment;
    public $auth_type;
}
