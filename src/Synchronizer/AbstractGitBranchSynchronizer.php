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

abstract class AbstractGitBranchSynchronizer extends AbstractGitVcsSynchronizer
{
    /**
     * @var string[]
     */
    protected $branches = [];

    /**
     * @return \string[]
     */
    public function getBranches()
    {
        return $this->branches;
    }

    /**
     * @param \string[] $branches
     *
     * @return static
     */
    public function setBranches(array $branches)
    {
        $this->branches = array_map('strval', $branches);
        return $this;
    }

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
}
