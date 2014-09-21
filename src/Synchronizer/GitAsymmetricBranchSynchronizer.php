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

class GitAsymmetricBranchSynchronizer extends AbstractGitBranchSynchronizer
{
    public function __construct(GitRepository $repository, array $remotes, $primaryRemote)
    {
        parent::__construct($repository, $remotes);
        $this->setPrimaryRemote($primaryRemote);
    }

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

    protected function buildHeadsList($branchesPerRemote, $otherRemotes, $branch)
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
