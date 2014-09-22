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

namespace ContaoCommunityAlliance\BuildSystem\VcsSync\Test\Synchronizer;

use ContaoCommunityAlliance\BuildSystem\VcsSync\Synchronizer\GitAsymmetricTagSynchronizer;

class GitAsymmetricTagSynchronizerTest extends AbstractGitVcsSynchronizerTest
{
    public function testSyncWithFirstAsPrimary()
    {
        $synchronizer = new GitAsymmetricTagSynchronizer($this->repository, ['first', 'second', 'third'], 'first');
        $synchronizer->setLogger($this->logger);
        $synchronizer->sync();

        $repository = $synchronizer->getRepository();

        $firstRepositoriesRefs  = $repository->lsRemote()->tags()->getRefs('first');
        $secondRepositoriesRefs = $repository->lsRemote()->tags()->getRefs('second');
        $thirdRepositoriesRefs  = $repository->lsRemote()->tags()->getRefs('third');

        $this->assertEquals(
            [
                'refs/tags/1.0',
                'refs/tags/1.1',
            ],
            array_keys($firstRepositoriesRefs),
            'Tags of the first repository mismatch'
        );

        $this->assertEquals(
            [
                'refs/tags/1.0',
                'refs/tags/1.1',
            ],
            array_keys($secondRepositoriesRefs),
            'Tags of the second repository mismatch'
        );

        $this->assertEquals(
            [
                'refs/tags/1.0',
                'refs/tags/1.1',
            ],
            array_keys($thirdRepositoriesRefs),
            'Tags of the third repository mismatch'
        );

        foreach (['refs/tags/1.0', 'refs/tags/1.1'] as $branch) {
            $this->assertEquals(
                $firstRepositoriesRefs[$branch],
                $secondRepositoriesRefs[$branch],
                'First and second repositories tag ' . $branch . ' is out of sync'
            );
            $this->assertEquals(
                $firstRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'First and third repositories tag ' . $branch . ' is out of sync'
            );
            $this->assertEquals(
                $secondRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'Second and third repositories tag ' . $branch . ' is out of sync'
            );
        }
    }

    public function testSyncWithSecondAsPrimary()
    {
        $synchronizer = new GitAsymmetricTagSynchronizer($this->repository, ['first', 'second', 'third'], 'second');
        $synchronizer->setLogger($this->logger);
        $synchronizer->sync();

        $repository = $synchronizer->getRepository();

        $firstRepositoriesRefs  = $repository->lsRemote()->tags()->getRefs('first');
        $secondRepositoriesRefs = $repository->lsRemote()->tags()->getRefs('second');
        $thirdRepositoriesRefs  = $repository->lsRemote()->tags()->getRefs('third');

        $this->assertEquals(
            [
                'refs/tags/1.0',
                'refs/tags/1.1',
                'refs/tags/1.2',
            ],
            array_keys($firstRepositoriesRefs),
            'Tags of the first repository mismatch'
        );

        $this->assertEquals(
            [
                'refs/tags/1.0',
                'refs/tags/1.1',
                'refs/tags/1.2',
            ],
            array_keys($secondRepositoriesRefs),
            'Tags of the second repository mismatch'
        );

        $this->assertEquals(
            [
                'refs/tags/1.0',
                'refs/tags/1.1',
                'refs/tags/1.2',
            ],
            array_keys($thirdRepositoriesRefs),
            'Tags of the third repository mismatch'
        );

        foreach (['refs/tags/1.0', 'refs/tags/1.1', 'refs/tags/1.2'] as $branch) {
            $this->assertEquals(
                $firstRepositoriesRefs[$branch],
                $secondRepositoriesRefs[$branch],
                'First and second repositories tag ' . $branch . ' is out of sync'
            );
            $this->assertEquals(
                $firstRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'First and third repositories tag ' . $branch . ' is out of sync'
            );
            $this->assertEquals(
                $secondRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'Second and third repositories tag ' . $branch . ' is out of sync'
            );
        }
    }

    public function testSyncWithThirdAsPrimary()
    {
        $synchronizer = new GitAsymmetricTagSynchronizer($this->repository, ['first', 'second', 'third'], 'third');
        $synchronizer->setLogger($this->logger);
        $synchronizer->sync();

        $repository = $synchronizer->getRepository();

        $firstRepositoriesRefs  = $repository->lsRemote()->tags()->getRefs('first');
        $secondRepositoriesRefs = $repository->lsRemote()->tags()->getRefs('second');
        $thirdRepositoriesRefs  = $repository->lsRemote()->tags()->getRefs('third');

        $this->assertEquals(
            [
                'refs/tags/1.2',
            ],
            array_keys($firstRepositoriesRefs),
            'Tags of the first repository mismatch'
        );

        $this->assertEquals(
            [
                'refs/tags/1.2',
            ],
            array_keys($secondRepositoriesRefs),
            'Tags of the second repository mismatch'
        );

        $this->assertEquals(
            [
                'refs/tags/1.2',
            ],
            array_keys($thirdRepositoriesRefs),
            'Tags of the third repository mismatch'
        );

        foreach (['refs/tags/1.2'] as $branch) {
            $this->assertEquals(
                $firstRepositoriesRefs[$branch],
                $secondRepositoriesRefs[$branch],
                'First and second repositories tag ' . $branch . ' is out of sync'
            );
            $this->assertEquals(
                $firstRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'First and third repositories tag ' . $branch . ' is out of sync'
            );
            $this->assertEquals(
                $secondRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'Second and third repositories tag ' . $branch . ' is out of sync'
            );
        }
    }
}
