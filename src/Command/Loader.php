<?php
namespace NorthStack\NorthStackClient\Command;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Application;
use Auryn\Injector;
use Symfony\Component\Finder\SplFileInfo;


class Loader
{
    private $injector;
    private $application;

    public function __construct(Injector $injector, Application $application)
    {
        $this->injector = $injector;
        $this->application = $application;
    }

    public function loadCommands($path, $namespace, $filter = null)
    {
        $commands = [];

        # Get the realpath so we can strip it from the start of the filename
        $realpath = realpath($path);

        $finder = (new Finder)->files()->in($path)->name('/[A-Z].*Command.php/');
        /** @var SplFileInfo $file */
        foreach ($finder as $file)
        {
            # Get the realpath of the file and ensure the class is loaded
            $filename = $file->getRealPath();

            # Convert the filename to a class
            $class = $filename;
            $class = str_replace([$realpath, '.php', '/'], ['', '', "\\"], $class);

            if ($filter && is_callable($filter) && $filter($class)) {
                continue;
            }

            # Create an instance of the command class
            $class = $namespace . $class;
            $commands[] = $this->injector->make($class);
        }

        if (count($commands) < 1)
        {
            throw new \InvalidArgumentException("No commands were found in the path ($path)");
        }

        $this->application->addCommands($commands);
    }
}
