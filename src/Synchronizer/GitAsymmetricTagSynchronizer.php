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
 * This synchronizer synchronize tags in a git repository.
 */
class GitAsymmetricTagSynchronizer extends AbstractGitTagSynchronizer
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
     * @param array         $remotes       The list of remote names to synchronize.
     * @param string        $primaryRemote The primary remote name.
     */
    public function __construct(GitRepository $repository, array $remotes, $primaryRemote)
    {
        parent::__construct($repository, $remotes);
        $this->setPrimaryRemote($primaryRemote);
    }

    /**
     * Return the primary remote.
     *
     * @return string
     */
    public function getPrimaryRemote()
    {
        return $this->primaryRemote;
    }

    /**
     * Set the primary remote.
     *
     * @param string $primaryRemote The primary remote name.
     *
     * @return static
     */
    public function setPrimaryRemote($primaryRemote)
    {
        $this->primaryRemote = (string)$primaryRemote;
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
     * Synchronize the tags of the repository.
     *
     * @return void
     */
    public function sync()
    {
        $tagsPerRemote = $this->buildTagsList();

        $otherRemotes = $this->getRemotesWithoutPrimary();
        $tagHashes    = $tagsPerRemote[$this->primaryRemote];
        $tagNames     = array_keys($tagHashes);

        foreach ($tagHashes as $tag => $primaryHash) {
            foreach ($otherRemotes as $remote) {
                $remoteTags = $tagsPerRemote[$remote];
                if (!isset($remoteTags[$tag]) || $primaryHash !== $remoteTags[$tag]) {
                    $this->logger->info(
                        sprintf(
                            'Update %s:refs/tags/%s => %s',
                            $remote,
                            $tag,
                            $primaryHash
                        )
                    );

                    $this->repository
                        ->push()
                        ->execute(
                            $remote,
                            sprintf('+%s:refs/tags/%s', $primaryHash, $tag)
                        );
                }
            }
        }

        foreach ($otherRemotes as $remote) {
            $remoteTags   = $tagsPerRemote[$remote];
            $obsoleteTags = array_diff(array_keys($remoteTags), $tagNames);

            foreach ($obsoleteTags as $obsoleteTag) {
                $this->logger->info(
                    sprintf(
                        'Remote %s:refs/tags/%s',
                        $remote,
                        $obsoleteTag
                    )
                );

                $this->repository
                    ->push()
                    ->execute(
                        $remote,
                        sprintf(':refs/tags/%s', $obsoleteTag)
                    );
            }
        }
    }
}
