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
 * Symmetric git branch synchronizer.
 */
class GitSymmetricBranchSynchronizer extends AbstractGitBranchSynchronizer
{
    /**
     * Synchronize the branches of the repository.
     *
     * @return void
     */
    public function sync()
    {
        $branchesPerRemote = $this->buildBranchesList();

        $branches = array_map('array_keys', $branchesPerRemote);
        $branches = call_user_func_array('array_merge', $branches);
        $branches = array_unique($branches);

        foreach ($branches as $branch) {
            $heads = $this->buildHeadsList($branchesPerRemote, $branch);

            $distances = [];

            $conflict = $this->calculateDistances($branch, $heads, $distances);

            // if there is a conflict
            if ($conflict) {
                // continue with next branch
                continue;
            }

            // filter remotes without distances / differences
            $distances = array_filter(
                $distances,
                function ($distances) {
                    return count($distances);
                }
            );

            // if no distances / differences
            if (empty($distances)) {
                // continue with next branch
                continue;
            }

            foreach ($distances as $outdatedRemote => $newerRemotes) {
                $newerRemotes = array_keys($newerRemotes);
                $newestRemote = array_pop($newerRemotes);
                $newestHash   = $heads[$newestRemote];

                $this->logger->info(
                    sprintf(
                        'Update %s:refs/heads/%s => %s:%s',
                        $outdatedRemote,
                        $branch,
                        $newestRemote,
                        $newestHash
                    )
                );

                $this->repository
                    ->push()
                    ->execute(
                        $outdatedRemote,
                        sprintf('%s:refs/heads/%s', $newestHash, $branch)
                    );
            }
        }
    }

    /**
     * Generate a list of all heads for a specific branch, over all remotes.
     *
     * @param array  $branchesPerRemote List of all branches in all remotes.
     * @param string $branch            The branch to build the heads for.
     *
     * @return array
     */
    protected function buildHeadsList($branchesPerRemote, $branch)
    {
        $heads = [];

        foreach ($this->remotes as $remote) {
            if (isset($branchesPerRemote[$remote]) && isset($branchesPerRemote[$remote][$branch])) {
                $heads[$remote] = $branchesPerRemote[$remote][$branch];
            } else {
                $heads[$remote] = false;
            }
        }

        return $heads;
    }

    /**
     * Callculate all distances for a specific branch over all remotes.
     *
     * @param string $branch    The branch name.
     * @param array  $heads     The heads of this branch in all remotes.
     * @param array  $distances The distances between the remotes.
     *
     * @return bool Return false if a conflict was detected.
     */
    protected function calculateDistances($branch, $heads, array &$distances)
    {
        $remotes  = array_keys($heads);
        $count    = count($remotes);
        $conflict = false;

        foreach ($remotes as $remote) {
            $distances[$remote] = [];
        }

        for ($i = 0; $i < $count; $i++) {
            $leftRemote = $remotes[$i];
            $leftHead   = $heads[$leftRemote];

            if (!$leftHead) {
                continue;
            }

            for ($j = 0; $j < $count; $j++) {
                if ($i == $j) {
                    continue;
                }

                $rightRemote = $remotes[$j];
                $rightHead   = $heads[$rightRemote];

                $this->calculateDistance(
                    $branch,
                    $leftRemote,
                    $leftHead,
                    $rightRemote,
                    $rightHead,
                    $distances,
                    $conflict
                );
            }

            asort($distances[$leftRemote]);
        }

        return $conflict;
    }

    /**
     * Calculate the distance for a specific branch between two remotes.
     *
     * @param string $branch      The branch name.
     * @param string $leftRemote  The left remote name.
     * @param string $leftHead    The left remotes branch head.
     * @param string $rightRemote The right remote name.
     * @param string $rightHead   The right remotes branch head.
     * @param array  $distances   The distances between the remotes.
     * @param bool   $conflict    The conflict status.
     *
     * @return void
     */
    protected function calculateDistance(
        $branch,
        $leftRemote,
        $leftHead,
        $rightRemote,
        $rightHead,
        array &$distances,
        &$conflict
    ) {
        if (
            isset($distances[$rightRemote][$leftRemote])
            || isset($distances[$leftRemote][$rightRemote])
        ) {
            return;
        }

        if (!$rightHead) {
            $ahead  = PHP_INT_MAX;
            $behind = 0;
        } else {
            $ahead  = $this->countCommits($rightHead, $leftHead);
            $behind = $this->countCommits($leftHead, $rightHead);
        }

        if ($ahead && $behind) {
            $this->logger->emergency(
                sprintf(
                    'Branch %s/%s %s is %d commits ahead and %d commits behind %s/%s %s',
                    $leftRemote,
                    $branch,
                    $leftHead,
                    $ahead,
                    $behind,
                    $rightRemote,
                    $branch,
                    $rightHead
                )
            );
            $conflict = true;
        } elseif ($ahead) {
            $distances[$rightRemote][$leftRemote] = $ahead;
        } elseif ($behind) {
            $distances[$leftRemote][$rightRemote] = $behind;
        }
    }
}
