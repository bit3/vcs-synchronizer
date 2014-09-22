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

use ContaoCommunityAlliance\BuildSystem\VcsSync\Synchronizer\GitAsymmetricBranchSynchronizer;

class GitAsymmetricBranchSynchronizerTest extends AbstractGitVcsSynchronizerTest
{
    public function testSynchronizeWithFirstAsPrimary()
    {
        $synchronizer = new GitAsymmetricBranchSynchronizer($this->repository, ['first', 'second', 'third'], 'first');
        $synchronizer->setLogger($this->logger);
        $synchronizer->sync();

        $log = file_get_contents($this->logPath);
        $log = explode("\n", $log);
        $log = array_filter($log);

        $patterns = [
            // differences second against X
            '~EMERGENCY: Branch second/develop [a-f0-9]+ is 2 commits ahead '
            . 'and 1 commits behind first/develop [a-f0-9]+~',
            '~EMERGENCY: Branch second/master [a-f0-9]+ is 1 commits ahead '
            . 'first/master [a-f0-9]+~',
            '~EMERGENCY: Branch second/stable [a-f0-9]+ is 1 commits ahead '
            . 'first/stable [a-f0-9]+~',
            // differences third against X
            '~EMERGENCY: Branch third/develop [a-f0-9]+ is 2 commits ahead '
            . 'and 1 commits behind first/develop [a-f0-9]+~',
            '~EMERGENCY: Branch third/master [a-f0-9]+ is 1 commits ahead '
            . 'first/master [a-f0-9]+~',
            '~EMERGENCY: Branch third/stable [a-f0-9]+ is 2 commits ahead '
            . 'first/stable [a-f0-9]+~',
        ];

        foreach ($log as $rowIndex => $row) {
            if (strpos($row, 'phpunit.INFO:') !== false) {
                unset($log[$rowIndex]);
                continue;
            }

            foreach ($patterns as $patternIndex => $pattern) {
                if (preg_match($pattern, $row)) {
                    unset($log[$rowIndex]);
                    unset($patterns[$patternIndex]);
                }
            }
        }

        if (count($log)) {
            $this->fail(
                'Unexpected log entries:' . PHP_EOL . implode(PHP_EOL, $log)
            );
        }

        if (count($patterns)) {
            $this->fail(
                'Missing log entries:' . PHP_EOL . implode(PHP_EOL, $patterns)
            );
        }

        $repository = $synchronizer->getRepository();

        $firstRepositoriesRefs  = $repository->lsRemote()->heads()->getRefs('first');
        $secondRepositoriesRefs = $repository->lsRemote()->heads()->getRefs('second');
        $thirdRepositoriesRefs  = $repository->lsRemote()->heads()->getRefs('third');

        $this->assertEquals(
            [
                'refs/heads/develop',
                'refs/heads/foo',
                'refs/heads/master',
                'refs/heads/stable',
            ],
            array_keys($firstRepositoriesRefs),
            'Branches of the first repository mismatch'
        );

        $this->assertEquals(
            [
                'refs/heads/develop',
                'refs/heads/foo',
                'refs/heads/master',
                'refs/heads/stable',
            ],
            array_keys($secondRepositoriesRefs),
            'Branches of the second repository mismatch'
        );

        $this->assertEquals(
            [
                'refs/heads/develop',
                'refs/heads/foo',
                'refs/heads/master',
                'refs/heads/stable',
            ],
            array_keys($thirdRepositoriesRefs),
            'Branches of the third repository mismatch'
        );

        foreach (['refs/heads/foo'] as $branch) {
            $this->assertEquals(
                $firstRepositoriesRefs[$branch],
                $secondRepositoriesRefs[$branch],
                'First and second repositories branch ' . $branch . ' is out of sync'
            );
            $this->assertEquals(
                $firstRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'First and third repositories branch ' . $branch . ' is out of sync'
            );
            $this->assertEquals(
                $secondRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'Second and third repositories branch ' . $branch . ' is out of sync'
            );
        }

        foreach (['refs/heads/develop', 'refs/heads/master', 'refs/heads/stable'] as $branch) {
            $this->assertNotEquals(
                $firstRepositoriesRefs[$branch],
                $secondRepositoriesRefs[$branch],
                'First and second repositories branch ' . $branch . ' is in sync'
            );
            $this->assertNotEquals(
                $firstRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'First and third repositories branch ' . $branch . ' is in sync'
            );
            $this->assertNotEquals(
                $secondRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'Second and third repositories branch ' . $branch . ' is in sync'
            );
        }
    }

    public function testSynchronizeWithSecondAsPrimary()
    {
        $synchronizer = new GitAsymmetricBranchSynchronizer($this->repository, ['first', 'second', 'third'], 'second');
        $synchronizer->setLogger($this->logger);
        $synchronizer->sync();

        $log = file_get_contents($this->logPath);
        $log = explode("\n", $log);
        $log = array_filter($log);

        $patterns = [
            // differences first against X
            '~EMERGENCY: Branch first/develop [a-f0-9]+ is 1 commits ahead '
            . 'and 2 commits behind second/develop [a-f0-9]+~',
            // differences third against X
            '~EMERGENCY: Branch third/develop [a-f0-9]+ is 2 commits ahead '
            . 'and 2 commits behind second/develop [a-f0-9]+~',
            '~EMERGENCY: Branch third/master [a-f0-9]+ is 1 commits ahead '
            . 'and 1 commits behind second/master [a-f0-9]+~',
            '~EMERGENCY: Branch third/release [a-f0-9]+ is 1 commits ahead '
            . 'and 1 commits behind second/release [a-f0-9]+~',
            '~EMERGENCY: Branch third/stable [a-f0-9]+ is 1 commits ahead '
            . 'second/stable [a-f0-9]+~',
        ];

        foreach ($log as $rowIndex => $row) {
            if (strpos($row, 'phpunit.INFO:') !== false) {
                unset($log[$rowIndex]);
                continue;
            }

            foreach ($patterns as $patternIndex => $pattern) {
                if (preg_match($pattern, $row)) {
                    unset($log[$rowIndex]);
                    unset($patterns[$patternIndex]);
                }
            }
        }

        if (count($log)) {
            $this->fail(
                'Unexpected log entries:' . PHP_EOL . implode(PHP_EOL, $log)
            );
        }

        if (count($patterns)) {
            $this->fail(
                'Missing log entries:' . PHP_EOL . implode(PHP_EOL, $patterns)
            );
        }

        $repository = $synchronizer->getRepository();

        $firstRepositoriesRefs  = $repository->lsRemote()->heads()->getRefs('first');
        $secondRepositoriesRefs = $repository->lsRemote()->heads()->getRefs('second');
        $thirdRepositoriesRefs  = $repository->lsRemote()->heads()->getRefs('third');

        $this->assertEquals(
            [
                'refs/heads/bar',
                'refs/heads/develop',
                'refs/heads/master',
                'refs/heads/release',
                'refs/heads/stable',
            ],
            array_keys($firstRepositoriesRefs),
            'Branches of the first repository mismatch'
        );

        $this->assertEquals(
            [
                'refs/heads/bar',
                'refs/heads/develop',
                'refs/heads/master',
                'refs/heads/release',
                'refs/heads/stable',
            ],
            array_keys($secondRepositoriesRefs),
            'Branches of the second repository mismatch'
        );

        $this->assertEquals(
            [
                'refs/heads/bar',
                'refs/heads/develop',
                'refs/heads/master',
                'refs/heads/release',
                'refs/heads/stable',
            ],
            array_keys($thirdRepositoriesRefs),
            'Branches of the third repository mismatch'
        );

        // These branches are in sync
        foreach (['refs/heads/bar'] as $branch) {
            $this->assertEquals(
                $firstRepositoriesRefs[$branch],
                $secondRepositoriesRefs[$branch],
                'First and second repositories branch ' . $branch . ' is out of sync'
            );
            $this->assertEquals(
                $firstRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'First and third repositories branch ' . $branch . ' is out of sync'
            );
            $this->assertEquals(
                $secondRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'Second and third repositories branch ' . $branch . ' is out of sync'
            );
        }

        // These branches are out of sync
        foreach (['refs/heads/develop'] as $branch) {
            $this->assertNotEquals(
                $firstRepositoriesRefs[$branch],
                $secondRepositoriesRefs[$branch],
                'First and second repositories branch ' . $branch . ' is in sync'
            );
            $this->assertNotEquals(
                $firstRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'First and third repositories branch ' . $branch . ' is in sync'
            );
            $this->assertNotEquals(
                $secondRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'Second and third repositories branch ' . $branch . ' is in sync'
            );
        }

        // These branches are only in sync between first and second repository
        foreach (['refs/heads/master', 'refs/heads/release', 'refs/heads/stable'] as $branch) {
            $this->assertEquals(
                $firstRepositoriesRefs[$branch],
                $secondRepositoriesRefs[$branch],
                'First and second repositories branch ' . $branch . ' is out of sync'
            );
        }

        // These branches are not in sync between first/second and third repository
        foreach (['refs/heads/master', 'refs/heads/release', 'refs/heads/stable'] as $branch) {
            $this->assertNotEquals(
                $firstRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'First and third repositories branch ' . $branch . ' is in sync'
            );
            $this->assertNotEquals(
                $secondRepositoriesRefs[$branch],
                $thirdRepositoriesRefs[$branch],
                'Second and third repositories branch ' . $branch . ' is in sync'
            );
        }
    }
}
