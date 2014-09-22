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

namespace ContaoCommunityAlliance\BuildSystem\VcsSync\Synchronizer;

use ContaoCommunityAlliance\BuildSystem\NoOpLogger;
use ContaoCommunityAlliance\BuildSystem\Repository\GitRepository;
use Psr\Log\LoggerInterface;

/**
 * Abstract base class for git synchronizers.
 */
abstract class AbstractGitVcsSynchronizer extends AbstractVcsSynchronizer
{
    /**
     * The git repository.
     *
     * @var GitRepository
     */
    protected $repository;

    /**
     * The remote names to synchronize.
     *
     * @var array
     */
    protected $remotes = [];

    /**
     * Create a new git synchronizer.
     *
     * @param GitRepository $repository The git repository.
     * @param array         $remotes    The remote names to synchronize.
     */
    public function __construct(GitRepository $repository, array $remotes)
    {
        parent::__construct();
        $this->setRepository($repository);
        $this->setRemotes($remotes);
    }

    /**
     * Get the git repository.
     *
     * @return GitRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Set the git repository.
     *
     * @param GitRepository $repository The git repository.
     *
     * @return static
     */
    public function setRepository(GitRepository $repository)
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * Get the remote names.
     *
     * @return array
     */
    public function getRemotes()
    {
        return $this->remotes;
    }

    /**
     * Set the remote names.
     *
     * @param array $remotes The remote names.
     *
     * @return static
     */
    public function setRemotes($remotes)
    {
        $this->remotes = array_map('strval', $remotes);
        return $this;
    }

    /**
     * Fetch given references from a remote.
     *
     * @param string $remote The remote name.
     * @param array  $refs   The list of references.
     *
     * @return void
     */
    protected function fetchRefs($remote, array $refs)
    {
        // use the remote ref names
        $refs = array_keys($refs);

        // start the fetch command
        $fetchCommand = $this->repository->fetch();

        // build the FetchCommandBuilder::execute() arguments list
        $arguments = array_merge([$remote], $refs);

        // fetch all refs with one single request
        call_user_func_array(
            [$fetchCommand, 'execute'],
            $arguments
        );
    }

    /**
     * Count the commits between two commits/refs.
     *
     * @param string $left  The left commit/ref.
     * @param string $right The right commit/ref.
     *
     * @return int
     */
    protected function countCommits($left, $right)
    {
        $log   = $this->repository
            ->log()
            ->oneline()
            ->revisionRange($left . '..' . $right)
            ->execute();
        $lines = explode("\n", $log);
        $lines = array_filter($lines);
        return count($lines);
    }
}
