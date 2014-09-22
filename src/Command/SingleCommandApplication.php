<?php

/**
 * This file is part of the Contao Community Alliance Build System tools.
 *
 * @copyright 2014 Contao Community Alliance <https://c-c-a.org>
 * @author    Tristan Lins <t.lins@c-c-a.org>
 * @package   contao-community-alliance/build-system-repositories
 * @license   MIT
 * @link      https://c-c-a.org
 */

namespace ContaoCommunityAlliance\BuildSystem\VcsSync\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * A single command console application.
 */
class SingleCommandApplication extends Application
{
    /**
     * The command to run.
     *
     * @var Command
     */
    protected $command;

    /**
     * Create a new single command application.
     *
     * @param Command $command The applications command.
     */
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandName(InputInterface $input)
    {
        return $this->command->getName();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $defaultCommands   = parent::getDefaultCommands();
        $defaultCommands[] = $this->command;

        return $defaultCommands;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();
        // clear out the normal first argument, which is the command name
        $inputDefinition->setArguments();
        return $inputDefinition;
    }
}
