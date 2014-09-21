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

class VcsSyncCommand extends Command
{
    protected function configure()
    {
        $this->setName('ccabs:vcs-sync:execute');
        $this->addOption(
            'remote',
            'R',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'The remotes to sync (GIT only).'
        );
        $this->addOption(
            'primary',
            'P',
            InputOption::VALUE_REQUIRED,
            'The primary remote.'
        );
        $this->addOption(
            'branches',
            'b',
            InputOption::VALUE_NONE,
            'Synchronize branches.'
        );
        $this->addOption(
            'branch',
            'B',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'The branches to sync, fnmatch patterns are allowed (imply --branches).'
        );
        $this->addOption(
            'tags',
            't',
            InputOption::VALUE_NONE,
            'Synchronize tags.'
        );
        $this->addOption(
            'tag',
            'T',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'The tags to sync, fnmatch patterns are allowed (imply --tags).'
        );
        $this->addArgument(
            'repository',
            InputArgument::REQUIRED,
            'The repository path.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = realpath($input->getArgument('repository'));

        if (!$path) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The path %s does not exists',
                    $input->getArgument('repository')
                )
            );
        }

        if (file_exists($path . DIRECTORY_SEPARATOR . '.git')) {
            $this->executeGitSync($path, $input, $output);
            return;
        }

        throw new \RuntimeException('Does not find a supported VCS system in ' . $path);
    }

    protected function executeGitSync($path, InputInterface $input, OutputInterface $output)
    {
        $remotes  = $input->getOption('remote');
        $primary = $input->getOption('primary');
        $synchronizeBranches = $input->getOption('branches');
        $branches = $input->getOption('branch');
        $synchronizeTags = $input->getOption('tags');
        $tags = $input->getOption('tag');

        if (empty($remotes)) {
            throw new \InvalidArgumentException('No remotes specified');
        }
        if (count($remotes) < 2) {
            throw new \InvalidArgumentException('You need to specify at least two remotes');
        }

        $repository = new GitRepository($path);

        $synchronizer = new GitVcsSynchronizer($repository, $remotes);
        $synchronizer->setPrimaryRemote($primary);
        $synchronizer->setSynchronizeBranches($synchronizeBranches || count($branches));
        $synchronizer->setBranches($branches);
        $synchronizer->setSynchronizeTags($synchronizeTags || count($tags));
        $synchronizer->setTags($tags);
        $synchronizer->setLogger($this->createLogger($output));
        $synchronizer->sync();
    }

    protected function createLogger(OutputInterface $output)
    {
        $logger = new Logger('ccabs-vcs-sync');
        $logger->pushHandler(new ConsoleHandler($output));

        return $logger;
    }
}
