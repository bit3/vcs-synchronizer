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

class GitAsymmetricTagSynchronizer extends AbstractGitTagSynchronizer
{
    public function __construct(GitRepository $repository, array $remotes, $primaryRemote)
    {
        parent::__construct($repository, $remotes);
        $this->setPrimaryRemote($primaryRemote);
    }

    public function sync()
    {
        throw new \RuntimeException('NEED REWORK');

        $tagsPerRemote = $this->buildTagsList();

        $otherRemotes = $this->getRemotesWithoutPrimary();

        foreach ($otherRemotes as $remote) {
            foreach ($tagsPerRemote[$remote] as $tag => $hash) {
                if (!isset($existingTags[$tag]) || $hash !== $existingTags[$tag]) {
                    $this->logger->debug(
                        $this->repository
                            ->push()
                            ->enableDryRun()
                            ->execute(
                                $remote,
                                sprintf('+%s:%s', $hash, $tag)
                            )
                    );

                    $this->repository
                        ->push()
                        ->execute(
                            $remote,
                            sprintf('+%s:%s', $hash, $tag)
                        );
                }
            }
        }
    }
}
