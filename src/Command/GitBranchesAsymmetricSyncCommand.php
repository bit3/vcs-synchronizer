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
use ContaoCommunityAlliance\BuildSystem\VcsSync\Synchronizer\GitAsymmetricBranchSynchronizer;
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
 * Console command to run an asymmetric sync of git branches.
 */
class GitBranchesAsymmetricSyncCommand extends AbstractGitSyncCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->addOption(
            'primary',
            'p',
            InputOption::VALUE_REQUIRED,
            'The primary remote.'
        );
        $this->addOption(
            'branch',
            'b',
            (InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY),
            'The branches to sync, fnmatch patterns are allowed.'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doSynchronize(
        GitRepository $repository,
        array $remotes,
        InputInterface $input,
        OutputInterface $output
    ) {
        $primaryRemote = $input->getOption('primary');
        $branches      = $input->getOption('branch');

        $synchronizer = new GitAsymmetricBranchSynchronizer($repository, $remotes, $primaryRemote);
        $synchronizer->setBranches($branches);
        $synchronizer->setLogger($this->createLogger($output));
        $synchronizer->sync();
    }
}
