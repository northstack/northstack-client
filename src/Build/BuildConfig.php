<?php


namespace NorthStack\NorthStackClient\Build;


use Equip\Data\Traits\EntityTrait;

class BuildConfig
{
    use EntityTrait;

    public $build_type;
    public $framework_version;
    public $image;
    public $framework_config;
    public $define_constants;
}
