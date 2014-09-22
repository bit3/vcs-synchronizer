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
 * Asymmetric git branch synchronizer.
 */
class GitAsymmetricBranchSynchronizer extends AbstractGitBranchSynchronizer
{
    /**
     * The primary remote name.
     *
     * @var string
     */
    protected $primaryRemote;

    /**
     * Create a new synchronizer.
     *
     * @param GitRepository $repository    The git repository.
     * @param array         $remotes       The remote names.
     * @param string        $primaryRemote The primary remote name.
     */
    public function __construct(GitRepository $repository, array $remotes, $primaryRemote)
    {
        parent::__construct($repository, $remotes);
        $this->setPrimaryRemote($primaryRemote);
    }

    /**
     * Get the primary remote name.
     *
     * @return string
     */
    public function getPrimaryRemote()
    {
        return $this->primaryRemote;
    }

    /**
     * Set the primary remote name.
     *
     * @param string $primaryRemote The primary remote name.
     *
     * @return static
     */
    public function setPrimaryRemote($primaryRemote)
    {
        $this->primaryRemote = empty($primaryRemote) ? null : (string)$primaryRemote;
        return $this;
    }

    /**
     * Return all remotes, expect the primary remote.
     *
     * @return array
     */
    protected function getRemotesWithoutPrimary()
    {
        $otherRemotes = array_merge($this->remotes);

        if ($this->primaryRemote) {
            $index = array_search($this->primaryRemote, $otherRemotes);
            unset($otherRemotes[$index]);
        }

        return $otherRemotes;
    }

    /**
     * Synchronize the branches of the repository.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If the primary remote does not exists.
     */
    public function sync()
    {
        $branchesPerRemote = $this->buildBranchesList();

        if ($this->primaryRemote && !in_array($this->primaryRemote, $this->remotes)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Primary remote %s is not part of the synchronized remotes list [%s]',
                    $this->primaryRemote,
                    implode(', ', $this->remotes)
                )
            );
        }

        $branches     = $branchesPerRemote[$this->primaryRemote];
        $otherRemotes = $this->getRemotesWithoutPrimary();

        foreach ($branches as $branch => $primaryHead) {
            foreach ($otherRemotes as $remote) {
                $this->syncHeads($branchesPerRemote, $branch, $primaryHead, $remote, $otherRemotes);
            }
        }

        $branchNames = array_keys($branches);
        foreach ($otherRemotes as $remote) {
            $remoteBranches   = array_keys($branchesPerRemote[$remote]);
            $obsoleteBranches = array_diff($remoteBranches, $branchNames);

            foreach ($obsoleteBranches as $branch) {
                $this->logger->info(
                    sprintf(
                        'Remove %s:refs/heads/%s',
                        $remote,
                        $branch
                    )
                );

                $this->repository
                    ->push()
                    ->execute(
                        $remote,
                        sprintf(':refs/heads/%s', $branch)
                    );
            }
        }
    }

    /**
     * Synchronize heads within a specific branch.
     *
     * @param array  $branchesPerRemote List of all branches in all remotes.
     * @param string $branch            The specific branch name.
     * @param string $primaryHead       The primary head.
     * @param string $remote            The current remote to synch against.
     * @param array  $otherRemotes      The list of all remotes, expect the primary remote name.
     *
     * @return void
     */
    protected function syncHeads($branchesPerRemote, $branch, $primaryHead, $remote, $otherRemotes)
    {
        $heads = $this->buildHeadsList($branchesPerRemote, $otherRemotes, $branch);

        if ($heads[$remote]) {
            $head   = $heads[$remote];
            $ahead  = $this->countCommits($primaryHead, $head);
            $behind = $this->countCommits($head, $primaryHead);

            if ($ahead && $behind) {
                $this->logger->emergency(
                    sprintf(
                        'Branch %s/%s %s is %d commits ahead and %d commits behind %s/%s %s',
                        $remote,
                        $branch,
                        $head,
                        $ahead,
                        $behind,
                        $this->primaryRemote,
                        $branch,
                        $primaryHead
                    )
                );
            } elseif ($ahead) {
                $this->logger->emergency(
                    sprintf(
                        'Branch %s/%s %s is %d commits ahead %s/%s %s',
                        $remote,
                        $branch,
                        $head,
                        $ahead,
                        $this->primaryRemote,
                        $branch,
                        $primaryHead
                    )
                );
            } elseif ($behind) {
                $this->logger->info(
                    sprintf(
                        'Update %s:refs/heads/%s => %s',
                        $remote,
                        $branch,
                        $primaryHead
                    )
                );

                $this->repository
                    ->push()
                    ->execute(
                        $remote,
                        sprintf('%s:refs/heads/%s', $primaryHead, $branch)
                    );
            }
        } else {
            $this->logger->info(
                sprintf(
                    'Create %s:refs/heads/%s => %s',
                    $remote,
                    $branch,
                    $primaryHead
                )
            );

            $this->repository
                ->push()
                ->execute(
                    $remote,
                    sprintf('%s:refs/heads/%s', $primaryHead, $branch)
                );
        }
    }

    /**
     * Generate a list of all heads for a specific branch, over all remotes expect the primary remote.
     *
     * @param array  $branchesPerRemote List of all branches in all remotes.
     * @param array  $otherRemotes      The list of all remotes, expect the primary remote name.
     * @param string $branch            The branch to build the heads for.
     *
     * @return array
     */
    protected function buildHeadsList(array $branchesPerRemote, array $otherRemotes, $branch)
    {
        $heads = [];

        foreach ($otherRemotes as $remote) {
            if (isset($branchesPerRemote[$remote]) && isset($branchesPerRemote[$remote][$branch])) {
                $heads[$remote] = $branchesPerRemote[$remote][$branch];
            } else {
                $heads[$remote] = false;
            }
        }

        return $heads;
    }
}
