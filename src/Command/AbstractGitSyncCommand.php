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

use ContaoCommunityAlliance\BuildSystem\Repository\GitConfig;
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
 * Generic base command for the git sync commands.
 */
abstract class AbstractGitSyncCommand extends AbstractVcsSyncCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->addOption(
            'git',
            'g',
            InputOption::VALUE_OPTIONAL,
            'The git executable.',
            'git'
        );
        $this->addOption(
            'remote',
            'r',
            (InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY),
            'The remotes to sync.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException If no or too less remotes are defined to synchronize.
     */
    protected function synchronize($repositoryPath, InputInterface $input, OutputInterface $output)
    {
        $gitConfig = new GitConfig();
        $gitConfig->setLogger($this->createLogger($output));
        $gitConfig->setGitExecutablePath($input->getOption('git'));

        $repository = new GitRepository(
            $repositoryPath,
            $gitConfig
        );

        $remotes = $input->getOption('remote');

        if (empty($remotes)) {
            throw new \InvalidArgumentException('No remotes specified');
        }
        if (count($remotes) < 2) {
            throw new \InvalidArgumentException('You need to specify at least two remotes');
        }

        $this->doSynchronize($repository, $remotes, $input, $output);
    }

    /**
     * Do synchronisation on the given repository, between the given remotes.
     *
     * @param GitRepository   $repository The git repository.
     * @param array           $remotes    The remote names to synchronize.
     * @param InputInterface  $input      The console input.
     * @param OutputInterface $output     The console output.
     *
     * @return void
     */
    abstract protected function doSynchronize(
        GitRepository $repository,
        array $remotes,
        InputInterface $input,
        OutputInterface $output
    );
}
