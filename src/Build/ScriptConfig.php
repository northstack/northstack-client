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
    private $script;

    public static function fromConfig(BuildScriptType $type, \stdClass $config): ScriptConfig
    {
        if (!isset($config->script)) {
            throw new \RuntimeException('Invalid build script config (missing script)');
        }
        $script = $config->script;
        $version = $config->version ?? null;
        $configValue = $config->config ?? [];
        $env = $config->env ?? [];

        return new self($type, $script, $version, $configValue, $env);
    }

    protected function __construct(
        BuildScriptType $type,
        string $script,
        $version = null,
        array $config = [],
        array $env = []
    ) {
        $this->type = $type;
        $this->version = $version;
        $this->config = $config;
        $this->env = $env;
        $this->script = $script;
    }

    /**
     * @return string
     */
    public function getScript(): string
    {
        return $this->script;
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
