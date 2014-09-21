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

abstract class AbstractGitTagSynchronizer extends AbstractGitVcsSynchronizer
{
    /**
     * @var string[]
     */
    protected $tags = [];

    /**
     * @return \string[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param \string[] $tags
     *
     * @return static
     */
    public function setTags(array $tags)
    {
        $this->tags = array_map('strval', $tags);
        return $this;
    }

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
}
