<?php


namespace NorthStack\NorthStackClient\Build;


use NorthStack\NorthStackClient\Enumeration\BuildScriptType;

class ScriptConfig
{
    /**
     * @var BuildScriptType
     */
    private $type;
    /**
     * @var null
     */
    private $version;
    /**
     * @var array
     */
    private $config;
    /**
     * @var array
     */
    private $env;

    public function __construct(
        BuildScriptType $type,
        $version = null,
        array $config = [],
        array $env = []
    ) {
        $this->type = $type;
        $this->version = $version;
        $this->config = $config;
        $this->env = $env;
    }

    /**
     * @return BuildScriptType
     */
    public function getType(): BuildScriptType
    {
        return $this->type;
    }

    /**
     * @return null
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return array
     */
    public function getEnv(): array
    {
        return $this->env;
    }
}
