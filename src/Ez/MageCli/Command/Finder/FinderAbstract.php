<?php

namespace Ez\MageCli\Command\Finder;

use Ez\MageBridge\MageBridge;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

abstract class FinderAbstract implements FinderInterface
{
    /**
     * Command classes to include.
     *
     * @var array
     */
    protected $commandClasses = array();

    /**
     * Commands found.
     *
     * @var array
     */
    protected $commands = null;

    /**
     * Get all the command classes.
     *
     * @return array
     */
    public function getCommandClasses()
    {
        return $this->commandClasses;
    }

    /**
     * Get all the commands.
     *
     * @return array
     */
    public function getCommands()
    {
        if (!isset($this->commands)) {
            $this->scan();
            foreach ($this->getCommandClasses() as $commandClass) {
                $this->commands[] = new $commandClass();
            }
        }
        return $this->commands;
    }

    /**
     * Scan the directories to find the command classes.
     *
     * @return $this
     */
    abstract public function scan();
}