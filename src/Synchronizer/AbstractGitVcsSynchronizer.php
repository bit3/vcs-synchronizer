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

abstract class AbstractGitVcsSynchronizer extends AbstractVcsSynchronizer
{
    /**
     * @var GitRepository
     */
    protected $repository;

    /**
     * @var string[]
     */
    protected $remotes = [];

    /**
     * @var string
     */
    protected $primaryRemote;

    public function __construct(GitRepository $repository, array $remotes)
    {
        parent::__construct();
        $this->setRepository($repository);
        $this->setRemotes($remotes);
    }

    /**
     * @return GitRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param GitRepository $repository
     *
     * @return static
     */
    public function setRepository(GitRepository $repository)
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * @return \string[]
     */
    public function getRemotes()
    {
        return $this->remotes;
    }

    /**
     * @param \string[] $remotes
     *
     * @return static
     */
    public function setRemotes($remotes)
    {
        $this->remotes = array_map('strval', $remotes);
        return $this;
    }

    /**
     * @return string
     */
    public function getPrimaryRemote()
    {
        return $this->primaryRemote;
    }

    /**
     * @param string $primaryRemote
     *
     * @return static
     */
    public function setPrimaryRemote($primaryRemote)
    {
        $this->primaryRemote = empty($primaryRemote) ? null : (string) $primaryRemote;
        return $this;
    }

    protected function buildBranchesList()
    {
        $refsPerRemote     = array();
        $branchesPerRemote = array();

        foreach ($this->remotes as $remote) {
            $refsPerRemote[$remote] = $this->repository
                ->lsRemote()
                ->heads()
                ->getRefs($remote);
        }

        foreach ($refsPerRemote as $remote => $refs) {
            $branchesPerRemote[$remote] = [];

            $this->fetchRefs($remote, $refs);

            foreach ($refs as $ref => $hash) {
                if (preg_match('~^refs/heads/(.*)$~', $ref, $matches)) {
                    $name = $matches[1];

                    $branchesPerRemote[$remote][$name] = $hash;
                }
            }
        }

        return $branchesPerRemote;
    }

    protected function buildTagsList()
    {
        $refsPerRemote = array();
        $tagsPerRemote = array();

        foreach ($this->remotes as $remote) {
            $refsPerRemote[$remote] = $this->repository
                ->lsRemote()
                ->heads()
                ->getRefs($remote);
        }

        foreach ($refsPerRemote as $remote => $refs) {
            $tagsPerRemote[$remote]     = [];

            $this->fetchRefs($remote, $refs);

            foreach ($refs as $ref => $hash) {
                if (preg_match('~^refs/tags/(.*)$~', $ref, $matches)) {
                    $name = $matches[1];

                    $tagsPerRemote[$remote][$name] = $hash;
                }
            }
        }

        return $tagsPerRemote;
    }

    protected function getRemotesWithoutPrimary()
    {
        $otherRemotes = array_merge($this->remotes);

        if ($this->primaryRemote) {
            $index = array_search($this->primaryRemote, $otherRemotes);
            unset($otherRemotes[$index]);
        }

        return $otherRemotes;
    }

    protected function fetchRefs($remote, $refs)
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
