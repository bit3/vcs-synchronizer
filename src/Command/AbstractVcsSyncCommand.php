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

use ContaoCommunityAlliance\BuildSystem\Repository\GitRepository;
use ContaoCommunityAlliance\BuildSystem\VcsSync\Synchronizer\GitVcsSynchronizer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generic base command for the vcs sync commands.
 */
abstract class AbstractVcsSyncCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument(
            'repository',
            InputArgument::REQUIRED,
            'The repository path.'
        );
    }

    /**
     * Create a console logger.
     *
     * @param OutputInterface $output The console output.
     *
     * @return Logger
     */
    protected function createLogger(OutputInterface $output)
    {
        $logger = new Logger('ccabs-vcs-sync');
        $logger->pushHandler(new ConsoleHandler($output));

        return $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException is thrown when the repository path does not exists.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repositoryPath = realpath($input->getArgument('repository'));

        if (!$repositoryPath) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The path %s does not exists',
                    $input->getArgument('repository')
                )
            );
        }

        $this->synchronize($repositoryPath, $input, $output);
    }

    /**
     * Start synchronize the repository in the given path.
     *
     * @param string          $repositoryPath The vcs repository path.
     * @param InputInterface  $input          The console input.
     * @param OutputInterface $output         The console output.
     *
     * @return void
     */
    abstract protected function synchronize($repositoryPath, InputInterface $input, OutputInterface $output);
}
