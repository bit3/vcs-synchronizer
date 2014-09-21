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

namespace ContaoCommunityAlliance\BuildSystem\VcsSync\Test\Synchronizer;

use ContaoCommunityAlliance\BuildSystem\NoOpLogger;
use ContaoCommunityAlliance\BuildSystem\Repository\GitRepository;
use ContaoCommunityAlliance\BuildSystem\VcsSync\Synchronizer\GitAsymmetricBranchSynchronizer;
use ContaoCommunityAlliance\BuildSystem\VcsSync\Synchronizer\GitAsymmetricTagSynchronizer;
use ContaoCommunityAlliance\BuildSystem\VcsSync\Synchronizer\GitSymmetricBranchSynchronizer;
use ContaoCommunityAlliance\BuildSystem\VcsSync\Synchronizer\GitVcsSynchronizer;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ProcessBuilder;

abstract class AbstractGitVcsSynchronizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $sourceFirstRepositoriesPath;

    /**
     * @var string
     */
    protected $sourceSecondRepositoriesPath;

    /**
     * @var string
     */
    protected $sourceThirdRepositoriesPath;

    /**
     * @var string
     */
    protected $sourceSynchronizedPath;

    /**
     * @var GitRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $logPath;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var GitAsymmetricBranchSynchronizer|GitSymmetricBranchSynchronizer|GitAsymmetricTagSynchronizer
     */
    protected $synchronizer;


    public function setUp()
    {
        $this->filesystem = new Filesystem();

        $this->sourceFirstRepositoriesPath = tempnam(sys_get_temp_dir(), 'vcs_sync_test_first');
        $this->filesystem->remove($this->sourceFirstRepositoriesPath);
        $this->filesystem->mkdir($this->sourceFirstRepositoriesPath);

        $this->sourceSecondRepositoriesPath = tempnam(sys_get_temp_dir(), 'vcs_sync_test_second');
        $this->filesystem->remove($this->sourceSecondRepositoriesPath);
        $this->filesystem->mkdir($this->sourceSecondRepositoriesPath);

        $this->sourceThirdRepositoriesPath = tempnam(sys_get_temp_dir(), 'vcs_sync_test_third');
        $this->filesystem->remove($this->sourceThirdRepositoriesPath);
        $this->filesystem->mkdir($this->sourceThirdRepositoriesPath);

        $this->sourceSynchronizedPath = tempnam(sys_get_temp_dir(), 'vcs_sync_test_synchronized');
        $this->filesystem->remove($this->sourceSynchronizedPath);
        $this->filesystem->mkdir($this->sourceSynchronizedPath);

        $this->logPath = tempnam(sys_get_temp_dir(), 'vcs_sync_test_log');

        $this->initializeFirstRepository();
        $this->initializeSecondRepository();
        $this->initializeThirdRepository();
        $this->initializeSynchronizedRepository();

        $this->repository = new GitRepository($this->sourceSynchronizedPath);

        $this->logger = new Logger('phpunit');
        $this->logger->pushHandler(new StreamHandler($this->logPath));
    }

    protected function initializeFirstRepository()
    {
        $path = tempnam(sys_get_temp_dir(), 'vcs_sync_test_repository');
        $this->filesystem->remove($path);
        $this->filesystem->mkdir($path);

        // initialize the bare repository
        $this->execute($this->sourceFirstRepositoriesPath, 'git', 'init', '--bare');

        // initialize the first repository
        $this->execute($path, 'git', 'init');
        $this->execute($path, 'git', 'remote', 'add', 'origin', 'file://' . $this->sourceFirstRepositoriesPath);

        // start with master branch
        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'I\'m the first repositories master branch'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Initial commit.');
        $this->execute($path, 'git', 'push', 'origin', 'master');

        // fork a develop branch
        $this->execute($path, 'git', 'checkout', '-b', 'develop', 'master');

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'I\'m the first repository develop branch'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Go ahead.');
        $this->execute($path, 'git', 'push', 'origin', 'develop');

        // fork a stable branch
        $this->execute($path, 'git', 'checkout', '-b', 'stable', 'master');
        $this->execute($path, 'git', 'push', 'origin', 'stable');

        // create a foo branch
        $this->execute($path, 'git', 'checkout', '--orphan', 'foo');

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'I\'m the first repository foo branch'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Foo commit.');
        $this->execute($path, 'git', 'push', 'origin', 'foo');

        // remote the first repository, stay with the bare repository
        $this->filesystem->remove($path);
    }

    protected function initializeSecondRepository()
    {
        $path = tempnam(sys_get_temp_dir(), 'vcs_sync_test_repository');
        $this->filesystem->remove($path);
        $this->filesystem->mkdir($path);

        // initialize the bare repository
        $this->execute($this->sourceSecondRepositoriesPath, 'git', 'init', '--bare');

        // initialize the first repository
        $this->execute($path, 'git', 'init');
        $this->execute($path, 'git', 'remote', 'add', 'origin', 'file://' . $this->sourceSecondRepositoriesPath);

        // fetch first repositories master branch
        $this->execute($path, 'git', 'fetch', 'file://' . $this->sourceFirstRepositoriesPath, 'refs/heads/master');
        $this->execute($path, 'git', 'checkout', '-b', 'master', 'FETCH_HEAD');

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'I\'m the second repositories master branch'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Go ahead.');
        $this->execute($path, 'git', 'push', 'origin', 'master');

        // create a develop branch
        $this->execute($path, 'git', 'fetch', 'file://' . $this->sourceFirstRepositoriesPath, 'refs/heads/master');
        $this->execute($path, 'git', 'checkout', '-b', 'develop', 'FETCH_HEAD');

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'I\'m the second repositories develop branch'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Go ahead.');

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'Finally I\'m the develop branch of the second repository'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Go ahead further.');
        $this->execute($path, 'git', 'push', 'origin', 'develop');

        // create a stable branch
        $this->execute($path, 'git', 'fetch', 'file://' . $this->sourceFirstRepositoriesPath, 'refs/heads/stable');
        $this->execute($path, 'git', 'checkout', '-b', 'stable', 'FETCH_HEAD');

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'I\'m the second repositories stable branch'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Go ahead.');
        $this->execute($path, 'git', 'push', 'origin', 'stable');

        // create a release branch
        $this->execute($path, 'git', 'checkout', '--orphan', 'release');

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'I\'m the second repository release branch'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Release commit.');
        $this->execute($path, 'git', 'push', 'origin', 'release');

        // create a bar branch
        $this->execute($path, 'git', 'checkout', '--orphan', 'bar');

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'I\'m the second repository bar branch'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Bar commit.');
        $this->execute($path, 'git', 'push', 'origin', 'bar');

        // remote the second repository, stay with the bare repository
        $this->filesystem->remove($path);
    }

    protected function initializeThirdRepository()
    {
        $path = tempnam(sys_get_temp_dir(), 'vcs_sync_test_repository');
        $this->filesystem->remove($path);
        $this->filesystem->mkdir($path);

        // initialize the bare repository
        $this->execute($this->sourceThirdRepositoriesPath, 'git', 'init', '--bare');

        // initialize the first repository
        $this->execute($path, 'git', 'init');
        $this->execute($path, 'git', 'remote', 'add', 'origin', 'file://' . $this->sourceThirdRepositoriesPath);

        // fetch first repositories master branch
        $this->execute($path, 'git', 'fetch', 'file://' . $this->sourceFirstRepositoriesPath, 'refs/heads/master');
        $this->execute($path, 'git', 'checkout', '-b', 'master', 'FETCH_HEAD');

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'I\'m the third repositories master branch'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Go ahead.');
        $this->execute($path, 'git', 'push', 'origin', 'master');

        // create a develop branch
        $this->execute($path, 'git', 'fetch', 'file://' . $this->sourceSecondRepositoriesPath, 'refs/heads/master');
        $this->execute($path, 'git', 'checkout', '-b', 'develop', 'FETCH_HEAD');

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'I\'m the third repositories develop branch'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Go more ahead.');
        $this->execute($path, 'git', 'push', 'origin', 'develop');

        // create a stable branch
        $this->execute($path, 'git', 'fetch', 'file://' . $this->sourceSecondRepositoriesPath, 'refs/heads/stable');
        $this->execute($path, 'git', 'checkout', '-b', 'stable', 'FETCH_HEAD');

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'I\'m the third repositories stable branch'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Go ahead further.');
        $this->execute($path, 'git', 'push', 'origin', 'stable');

        // create a release branch
        $this->execute($path, 'git', 'checkout', '--orphan', 'release');

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'I\'m the third repository release branch'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Release commit.');
        $this->execute($path, 'git', 'push', 'origin', 'release');

        // create a bar branch
        $this->execute($path, 'git', 'checkout', '--orphan', 'zap');

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . 'README',
            'I\'m the third repository zap branch'
        );

        $this->execute($path, 'git', 'add', 'README');
        $this->execute($path, 'git', 'commit', '-m', 'Zap commit.');
        $this->execute($path, 'git', 'push', 'origin', 'zap');

        // remote the third repository, stay with the bare repository
        $this->filesystem->remove($path);
    }

    protected function initializeSynchronizedRepository()
    {
        $path = $this->sourceSynchronizedPath;

        // initialize the first repository
        $this->execute($path, 'git', 'init');
        $this->execute($path, 'git', 'remote', 'add', 'first', 'file://' . $this->sourceFirstRepositoriesPath);
        $this->execute($path, 'git', 'remote', 'add', 'second', 'file://' . $this->sourceSecondRepositoriesPath);
        $this->execute($path, 'git', 'remote', 'add', 'third', 'file://' . $this->sourceThirdRepositoriesPath);
    }

    protected function execute($workingDirectory, $arguments, $_ = null)
    {
        $arguments = func_get_args();
        array_shift($arguments);

        ProcessBuilder::create($arguments)
            ->setWorkingDirectory($workingDirectory)
            ->getProcess()
            ->mustRun();
    }

    public function tearDown()
    {
        return;
        $this->filesystem->remove($this->sourceFirstPath);
        $this->filesystem->remove($this->sourceSecondPath);
        $this->filesystem->remove($this->sourceThirdPath);
        $this->filesystem->remove($this->sourceSynchronizedPath);
        $this->filesystem->remove($this->logPath);

        unset($this->filesystem);
        unset($this->sourceFirstPath);
        unset($this->sourceSecondPath);
        unset($this->sourceThirdPath);
        unset($this->sourceSynchronizedPath);
        unset($this->repository);
        unset($this->logPath);
        unset($this->logger);
    }
}
