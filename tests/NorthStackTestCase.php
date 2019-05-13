<?php

namespace Test;

use Auryn\Injector;
use duncan3dc\Console\Application;
use NorthStack\NorthStackClient\Command\Loader;
use NorthStack\NorthStackClient\Helper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

abstract class NorthStackTestCase extends TestCase
{
    /**
     * @var MockObject[]
     */
    public $diCallables = [];
    public $injector;
    public $application;
    /**
     * @var MockObject[]
     */
    protected $mocks = [];

    function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->injector = new Injector();
        $helper = new Helper();

        $helper->configureInjector($this->injector);
    }

    function setUp(): void
    {
        $this->application = new Application();
        $loader = new Loader($this->injector, $this->application);
        $loader->loadCommands(dirname(__DIR__, 1) . '/src/Command', 'NorthStack\\NorthStackClient\\Command');
    }

    public function generateMocks($classes, $injectIntoDi = false)
    {
        foreach ($classes as $class) {
            if (!array_key_exists($class, $this->mocks)) {
                $this->mocks[$class] = $this->getMockBuilder($class)
                    ->disableOriginalConstructor()->getMock();
            }
        }
        if ($injectIntoDi) {
            foreach ($classes as $class) {
                $this->injector->share($class);
                $this->injector->delegate($class, function () use ($class) {
                    return $this->getMockService($class);
                });
            }
        }
    }

    /**
     * @param $class
     *
     * @return MockObject|bool|$class
     */
    public function getMockService($class)
    {
        return $this->mocks[$class] ?? false;
    }

    /**
     * Inject pre-created mocks
     *
     * @param $mocks
     */
    public function injectMocksIntoDi($mocks)
    {
        $this->addDiCallback(function (Injector $injector) use ($mocks) {
            foreach ($mocks as $class => $mock) {
                $this->injector->delegate($class, function () use ($mock) {
                    return $mock;
                });
            }
        });
    }

    public function addDiCallback(callable $callable)
    {
        $this->diCallables[] = $callable;
    }
}
