[![Version](http://img.shields.io/packagist/v/contao-community-alliance/vcs-synchronizer.svg?style=flat-square)](https://packagist.org/packages/contao-community-alliance/vcs-synchronizer)
[![Stable Build Status](http://img.shields.io/travis/contao-community-alliance/vcs-synchronizer/master.svg?style=flat-square&label=stable build)](https://travis-ci.org/contao-community-alliance/vcs-synchronizer)
[![Upstream Build Status](http://img.shields.io/travis/contao-community-alliance/vcs-synchronizer/develop.svg?style=flat-square&label=dev build)](https://travis-ci.org/contao-community-alliance/vcs-synchronizer)
[![License](http://img.shields.io/packagist/l/contao-community-alliance/vcs-synchronizer.svg?style=flat-square)](https://github.com/contao-community-alliance/vcs-synchronizer/blob/master/LICENSE)
[![Downloads](http://img.shields.io/packagist/dt/contao-community-alliance/vcs-synchronizer.svg?style=flat-square)](https://packagist.org/packages/contao-community-alliance/vcs-synchronizer)

VCS Synchronizers
=================

This repository contains multiple synchronizers to synchronize multiple VCS repositories (of the same type).

Symmetric vs. Asymmetric synchronisation
----------------------------------------

Symmetric synchronisation means that each repository is compared and synchronized against each other.
If a conflict is detected - two remotes have divergent branches - no repository of the concerned branch is synchronized.

Asymmetric synchronisation means that one - the primary - repository is compared and synchronized against all the others
repositories. If a conflict is detected - one remote branch is ahead of the primary - the branch in the concerned
repository is not synchronized.

Working repository
------------------

The synchronizers work on a local *working repository*. But they won't create them for you! This let you keep control
of what happened.

Here is an example, how you could create the local *working repository*.

```php
use ContaoCommunityAlliance\BuildSystem\Repository\GitRepository;

$path = tempnam(sys_get_temp_dir());
unlink($path);
mkdir($path);

$repository = new GitRepository($path);
$repository->init()->execute();
$repository->remote()->add('github', 'git@github.com:contao-community-alliance/vcs-synchronizer.git')->execute();
$repository->remote()->add('bitbucket', 'git@bitbucket.org:contao-community-alliance/vcs-synchronizer.git')->execute();
```

GIT Synchronizers
=================

Symmetric branch synchronizer
-----------------------------

**CLI usage**

```bash
./bin/git-branches-symmetric-sync -b github -b bitbucket /path/to/repository
```

**PHP usage**

```php
use ContaoCommunityAlliance\BuildSystem\VcsSync\Synchronizer\GitSymmetricBranchSynchronizer;

$synchronizer = new GitSymmetricBranchSynchronizer(
    // the working repository
    $repository,
    // the remotes to synchronize
    ['github', 'bitbucket']
);
$synchronizer->setLogger($logger);
$synchronizer->sync();
```

Asymmetric branch synchronizer
------------------------------

**CLI usage**

```bash
./bin/git-branches-asymmetric-sync -b github -b bitbucket -p github /path/to/repository
```

**PHP usage**

```php
use ContaoCommunityAlliance\BuildSystem\VcsSync\Synchronizer\GitAsymmetricBranchSynchronizer;

$synchronizer = new GitAsymmetricBranchSynchronizer(
    // the working repository
    $repository,
    // the remotes to synchronize
    ['github', 'bitbucket'],
    // the primary remote
    'github'
);
$synchronizer->setLogger($logger);
$synchronizer->sync();
```

Asymmetric tag synchronizer
---------------------------

**CLI usage**

```bash
./bin/git-tags-asymmetric-sync -b github -b bitbucket -p github /path/to/repository
```

**PHP usage**

```php
use ContaoCommunityAlliance\BuildSystem\VcsSync\Synchronizer\GitAsymmetricTagSynchronizer;

$synchronizer = new GitAsymmetricTagSynchronizer(
    // the working repository
    $repository,
    // the remotes to synchronize
    ['github', 'bitbucket'],
    // the primary remote
    'github'
);
$synchronizer->setLogger($logger);
$synchronizer->sync();
```
