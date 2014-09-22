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
 * Abstract base class for the git branch synchronizers.
 */
abstract class AbstractGitBranchSynchronizer extends AbstractGitVcsSynchronizer
{
    /**
     * The accepted branch names or patterns.
     *
     * @var array
     */
    protected $branches = [];

    /**
     * Get the accepted branch names.
     *
     * @return array
     */
    public function getBranches()
    {
        return $this->branches;
    }

    /**
     * Set the accepted branch names.
     *
     * @param array $branches The accepted branch names or patterns.
     *
     * @return static
     */
    public function setBranches(array $branches)
    {
        $this->branches = array_map('strval', $branches);
        return $this;
    }

    /**
     * Determine if the branch name is accepted.
     *
     * @param string $branch The branch name to check.
     *
     * @return bool
     */
    protected function determineBranchIsAccepted($branch)
    {
        if (empty($this->branches) || in_array($branch, $this->branches)) {
            return true;
        }

        foreach ($this->branches as $pattern) {
            if (fnmatch($pattern, $branch)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a reference list of all branches.
     *
     * @return array
     */
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
}
