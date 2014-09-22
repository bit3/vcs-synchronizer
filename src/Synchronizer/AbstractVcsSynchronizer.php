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
 * Abstract base class for vcs synchronizers.
 */
abstract class AbstractVcsSynchronizer
{
    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Create a new vcs logger.
     */
    public function __construct()
    {
        $this->logger = new NoOpLogger();
    }

    /**
     * Get the current logger.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Set the logger.
     *
     * @param LoggerInterface $logger The logger instance.
     *
     * @return static
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
