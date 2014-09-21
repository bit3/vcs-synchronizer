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

abstract class AbstractVcsSynchronizer
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct()
    {
        $this->logger = new NoOpLogger();
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return static
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
