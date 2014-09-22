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
 * Abstract base class for the git tag synchronizers.
 */
abstract class AbstractGitTagSynchronizer extends AbstractGitVcsSynchronizer
{
    /**
     * The list of accepted tags.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Return the list of accepted tags.
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set list of accepted tags.
     *
     * @param array $tags The tag names or patterns.
     *
     * @return static
     */
    public function setTags(array $tags)
    {
        $this->tags = array_map('strval', $tags);
        return $this;
    }

    /**
     * Determine if the given tag is accepted.
     *
     * @param string $tag The tag name.
     *
     * @return bool
     */
    protected function determineTagIsAccepted($tag)
    {
        if (empty($this->tags) || in_array($tag, $this->tags)) {
            return true;
        }

        foreach ($this->tags as $pattern) {
            if (fnmatch($pattern, $tag)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a reference list of all tags.
     *
     * @return array
     */
    protected function buildTagsList()
    {
        $refsPerRemote = array();
        $tagsPerRemote = array();

        foreach ($this->remotes as $remote) {
            $refsPerRemote[$remote] = $this->repository
                ->lsRemote()
                ->tags()
                ->getRefs($remote);
        }

        foreach ($refsPerRemote as $remote => $refs) {
            $tagsPerRemote[$remote] = [];

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
}
